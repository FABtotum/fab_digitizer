#!/bin/env python
# -*- coding: utf-8; -*-
#
# (c) 2016 FABtotum, http://www.fabtotum.com
#
# This file is part of FABUI.
#
# FABUI is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# FABUI is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with FABUI.  If not, see <http://www.gnu.org/licenses/>.

# Import standard python module
import argparse
import time
from datetime import datetime
import gettext
import os
import errno
from fractions import Fraction
from threading import Event, Thread

# Import external modules

# Import internal modules
from fabtotum.utils.translation import _, setLanguage
from fabtotum.fabui.config  import ConfigService
from fabtotum.fabui.gpusher import GCodePusher
from fabtotum.totumduino.format import parseG30 as parseG38
################################################################################

class Application(GCodePusher):
    """
    Digitizer scan application.
    """
    
    MINIMAL_SAFE_Z  = 36.0
    SAFE_Z_OFFSET   = 2.0
    XY_FEEDRATE     = 4000
    Z_FEEDRATE      = 1500
    E_FEEDRATE      = 800
    
    def __init__(self, standalone = False, lang = 'en_US.UTF-8', send_email=False):
        super(Application, self).__init__(use_stdout=standalone, lang=lang, send_email=send_email)
        
        self.standalone = standalone
        self.progress = 0.0
        
        self.scan_stats = {
            'type'          : 'digitizer',
            'projection'    : 'planar',
            'scan_total'    : 0,
            'scan_current'  : 0,
            'point_count'   : 0,
            'cloud_size'    : 0.0,
            'file_id'       : 0,
            'object_id'     : 0
        }
        
        self.add_monitor_group('scan', self.scan_stats)
        self.ev_resume = Event()
    
    def get_progress(self):
        """ Custom progress implementation """
        return self.progress
            
    def probe(self, x, y):
        """ 
        Probe Z at specific (X,Y). Returns Z or ``None`` on failure.
        
        :param x: X position
        :param y: Y position
        :rtype: float
        """
        self.move_to_xy(x, y)
        time.sleep(1)
        
        reply = self.send('G38', timeout = 200)
        result = parseG38(reply)
        if result:
            x = result['x']
            y = result['y']
            z = result['z']
            return [x,y,z,1]
            
        return None
    
    def move_to_xy(self, x, y):
        """
        Move head to X,Y position
        
        :param x: X position
        :param y: Y position
        """
        self.send('G90')
        self.send('G0 X{0} Y{1} F{2}'.format(x, y, self.XY_FEEDRATE) )
        self.send('M400')
    
    def move_up(self, up=SAFE_Z_OFFSET):
        """
        Move away from bed
        
        :param up: Amount to move away from the bed
        :type up: float
        """
        self.send('G91')
        self.send('G0 Z{0} F{1}'.format(up, self.Z_FEEDRATE) )
        self.send('M400')
        self.send('G90')
        
    def save_as_cloud(self, points, cloud_file):
        """
        Save `points` to a file in asc format.
        
        :param points: Array of [x,y,z] points
        :param cloud_file: Cloud file filename
        :type points: list
        :type cloud_file: string
        """
        with open(cloud_file,"w")  as cloud_file:
            if len(points)>0:
                for row in xrange(0, len(points)):
                    cloud_file.write( '{0}, {1}, {2}\n'.format( points[row][0], points[row][1], points[row][2]))
        
    def store_object(self, task_id, object_id, object_name, cloud_file, file_name):
        """
        Store object and file to database. If `object_id` is not zero the new file
        is added to that object. Otherwise a new object is created with name `object_name`.
        If `object_name` is empty an object name is automatically generated. Same goes for
        `file_name`.
        
        :param task_id:     Task ID used to read User ID from the task
        :param object_id:   Object ID used to add file to an object
        :param object_name: Object name used to name the new object
        :param cloud_file:  Full file path and filename to the cloud file to be stored
        :param file_name:   User file name for the cloud file
        :type task_id: int
        :type object_id: int
        :type object_name: string
        :type cloud_file: string
        :type file_name: string
        """
        obj = self.get_object(object_id)
        task = self.get_task(task_id)
        
        print "Object_name:", object_name
        print "File_name:", file_name
        print "Cloud_name:", cloud_file
        print "Task_ID:", task_id
        
        ts = time.time()
        dt = datetime.fromtimestamp(ts)
        datestr = dt.strftime('%Y-%m-%d %H:%M:%S')
        datestr_fs_friendly = 'cloud_'+dt.strftime('%Y%m%d_%H%M%S')
        
        if not object_name:
            object_name = "Scan object ({0})".format(datestr)
        
        client_name = file_name
        
        if not file_name:
            client_name = datestr_fs_friendly
        
        if not obj:
            # File should not be part of an existing object so create a new one
            user_id = 0
            if task:
                user_id = task['user']
            
            obj = self.add_object(object_name, "", user_id)
        
        f = obj.add_file(cloud_file, client_name=client_name)
        if task:
            os.remove(cloud_file)
        
        self.scan_stats['file_id']   = f['id']
        self.scan_stats['object_id'] = obj['id']
        # Update task content
        if task:
            task['id_object'] = obj['id']
            task['id_file'] = f['id']
            task.write()
        
    def state_change_callback(self, state):
        if state == 'resumed' or state == 'aborted':
            self.ev_resume.set()
        
    def run(self, task_id, object_id, object_name, file_name, x1, y1, x2, y2, probe_density, orig_safe_z, threshold, max_skip, cloud_file):
        """
        Run the print.
        
        :param gcode_file: GCode file containing print commands.
        :param task_id: Task ID
        :type gcode_file: string
        :type task_id: int
        """

        self.prepare_task(task_id, 'scan')
        self.set_task_status(GCodePusher.TASK_RUNNING)
        
        if self.standalone:
            self.exec_macro("start_probe_scan")
            
        if self.is_aborted():
            self.set_task_status(GCodePusher.TASK_ABORTING)
        else:
            self.set_task_status(GCodePusher.TASK_COMPLETING)
            
        self.exec_macro("end_scan")
    
        if self.is_aborted():
            self.trace( _("Physical Probing aborted.") )
            self.set_task_status(GCodePusher.TASK_ABORTED)
        else:
            self.trace( _("Physical Probing completed.") )
            self.set_task_status(GCodePusher.TASK_COMPLETED)
        
        self.stop()
        
    def run_test(self, x1, y1, x2, y2, homing):
        # Ensure axis position is known
        if homing == 'xy':
            self.send('G27 X Y');
        elif homing == 'xyz':
            self.send('G27');
        elif homing == 'skip':
            # No homing
            pass
        
        self.trace('Homing finished')
        
        self.send('M746 S2');
            
        points = [
            (x1,y1),
            (x1, y2),
            (x2, y2),
            (x2,y1)
        ]
        
        self.trace('Starting test probing')
        
        done = False
        
        while not done:
            try:

                for pt in points:
                    result = self.probe( pt[0], pt[1] )
                    print result
                    if result is None:
                        raise Exception('Homing lost')
                    self.move_up()
                    time.sleep(0.5)
                    
                done = True
            except:
                self.trace('Motors were off. Forced homing...')
                self.send('G27');
            
        
        self.move_to_xy( x1, y1 )
        
        self.send('M746 S0');

def main():
    config = ConfigService()
    
    # SETTING EXPECTED ARGUMENTS
    destination = config.get('general', 'bigtemp_path')
    
    parser = argparse.ArgumentParser(add_help=False, formatter_class=argparse.ArgumentDefaultsHelpFormatter)
    
    parser.add_argument("-T", "--task-id",     help="Task ID.",              default=0)
    parser.add_argument("-U", "--user-id",     help="User ID. (future use)", default=0)
    parser.add_argument("-O", "--object-id",   help="Object ID.",            default=0)
    parser.add_argument("-N", "--object-name", help="Object name.",          default='')
    parser.add_argument("-F", "--file-name",   help="File name.",            default='')
    
    parser.add_argument("-d", "--dest",     help="Destination folder.",     default=config.get('general', 'bigtemp_path') )
    parser.add_argument("-o", "--output",   help="Output point cloud file.",default=os.path.join(destination, 'cloud.asc'))
    parser.add_argument("-n", "--n-probes", help="Number of probes.",       default=1)
    parser.add_argument("-x", "--x1",       help="X1.",                     default=0)
    parser.add_argument("-y", "--y1",       help="Y1.",                     default=0)
    parser.add_argument("-i", "--x2",       help="X2.",                     default=10)
    parser.add_argument("-j", "--y2",       help="Y2.",                     default=10)
    parser.add_argument("-z", "--safe-z",   help="Safe Z.",                 default=1)
    parser.add_argument("-t", "--threshold", help="Detail threshold.",      default=0)
    parser.add_argument("-s", "--max-skip",  help="Maximum number of skipped probes.",      default=10)
    parser.add_argument("--lang",            help="Output language", 		default='en_US.UTF-8' )
    parser.add_argument('--help', action='help', help="Show this help message and exit" )
    parser.add_argument("--email",             help="Send an email on task finish", action='store_true', default=False)
    parser.add_argument("--shutdown",          help="Shutdown on task finish", action='store_true', default=False )
    parser.add_argument("--test",              help="Only touch the 4 corners as a visual area test", action='store_true', default=False )
    parser.add_argument("--homing",            help="Homing action (xy|xyz|skip)", default='xyz' )
    
    # GET ARGUMENTS
    args = parser.parse_args()

    # INIT VARs
    gcode_file      = args.file_name     # GCODE FILE
    task_id         = int(args.task_id)
    user_id         = int(args.user_id)
    lang            = args.lang
    send_email      = bool(args.email)
    auto_shutdown   = bool(args.shutdown)
    
    destination     = args.dest
    x1              = float(args.x1)
    y1              = float(args.y1)
    x2              = float(args.x2)
    y2              = float(args.y2)
    probe_density   = float(args.n_probes)
    safe_z          = float(args.safe_z)
    threshold       = float(args.threshold)
    max_skip        = float(args.max_skip)
    test_only       = args.test
    homing          = args.homing
    
    object_id       = int(args.object_id)
    object_name     = args.object_name
    file_name       = args.file_name
    cloud_file      = args.output
    
    if task_id == 0:
        standalone  = True
    else:
        standalone  = False

    app = Application(standalone, lang, send_email)

    if test_only:
         app.run_test(x1, y1, x2, y2, homing)
    else:
        app_thread = Thread( 
                target = app.run, 
                args=( [task_id, object_id, object_name, file_name, x1, y1, x2, y2, probe_density, safe_z, threshold, max_skip, cloud_file] )
                )
        app_thread.start()

        # app.loop() must be started to allow callbacks
        app.loop()
        app_thread.join()

if __name__ == "__main__":
    main()
