<?php
/**
 * 
 * @author FABteam
 * @version 0.10.0
 * @license https://opensource.org/licenses/GPL-3.0
 * 
 */
 
defined('BASEPATH') OR exit('No direct script access allowed');
 
class Plugin_fab_digitizer extends FAB_Controller {

	function __construct()
	{
		parent::__construct();
		session_write_close(); //avoid freezing page
		if(!$this->input->is_cli_request()){ //avoid this form command line
			//check if there's a running task
			//load libraries, models, helpers
			$this->load->model('Tasks', 'tasks');
			$this->runningTask = $this->tasks->getRunning();
		}
	}

	public function index()
	{

	}
	
	public function make($objectID = -1)
	{
		$this->load->library('smart');
		$this->load->helper('form');
		$this->load->helper('fabtotum_helper');
		$this->load->helper('utility_helper');
		$this->load->helper('plugin_helper');
		$this->load->model('Objects', 'objects');
		
		$data = array();
		$data['runningTask'] = $this->runningTask;
		$data['object_id'] = '';
		
		$data['objectsForDropdown'] = $this->objects->getObjectsForDropdown();
		$data['suggestedObjectName'] = 'Probing - Object name';
		$data['suggestedFileName'] = 'Probing - File name';
		
		// Safety check
		if(!$task_is_running){
			
			$data['safety_check'] = safetyCheck("scan");
			$data['safety_check']['url'] = 'std/safetyCheck/scan/any';
			$data['safety_check']['content'] = $this->load->view( 'std/task_safety_check', $data, true );
			
			$scanconfiguration_raw = $this->load->view('scan/presets_json', $data, true );
			$scanconfiguration = json_decode($scanconfiguration_raw, true);
			$data['probingQualities'] = $scanconfiguration['probe_quality'];
		}
		
		$data['get_objects_url'] = plugin_url('getObjects');
		
		// task_wizard
		$data['start_task_url'] = plugin_url('startTask');
		$data['start_test_url'] = plugin_url('testProbingArea');
		
		$data['steps'] = array(
				array('number'  => 1,
				 'title'   => 'Settings',
				 'content' => !$task_is_running ? $this->load->view( plugin_url('make/wizard/settings'), $data, true ) : '',
				 'active'  => !$task_is_running
			    ),
				array('number'  => 2,
				 'title'   => 'Get Ready',
				 'content' => !$task_is_running ? $this->load->view( plugin_url('make/wizard/get_ready'), $data, true ) : '',
			    ),
				array('number'  => 4,
				 'title'   => 'Scanning',
				 'content' => $this->load->view( plugin_url('make/wizard/task_execute'), $data, true ),
				 'active' => $task_is_running
			    ),
				array('number'  => 5,
				 'title'   => 'Finish',
				 'content' => $this->load->view( plugin_url('make/wizard/task_finished'), $data, true )
			    )
			);
			
		$widgetOptions = array(
				'sortable'     => false, 'fullscreenbutton' => true,  'refreshbutton' => false, 'togglebutton' => false,
				'deletebutton' => false, 'editbutton'       => false, 'colorbutton'   => false, 'collapsed'    => false
		);
		
		$widgeFooterButtons = '';

		$widget         = $this->smart->create_widget($widgetOptions);
		$widget->id     = 'main-widget-make-digitizer';
		$widget->header = array('icon' => 'fa-cube', "title" => "<h2>Digitizer Scan</h2>");
		$widget->body   = array('content' => $this->load->view('std/task_wizard', $data, true ), 'class'=>'fuelux');
		
		if(!$task_is_running){
			$this->addJsInLine($this->load->view( 'std/task_safety_check_js', $data, true));
			$this->addJSFile('/assets/js/plugin/cropper/cropper.js');
			$this->addCssFile('/assets/js/plugin/cropper/cropper.min.css');
			//~ $this->addCssFile('/assets/css/std/select_file.css');
			//~ $this->addCssFile('/assets/css/std/jog_setup.css');
			//~ $this->addCssFile('/assets/css/std/jogtouch.css');
			//~ $this->addCssFile('/assets/css/std/jogcontrols.css');
			
			//~ $this->addJSFile('/assets/js/plugin/datatables/jquery.dataTables.min.js'); //datatable
			//~ $this->addJSFile('/assets/js/plugin/datatables/dataTables.colVis.min.js'); //datatable
			//~ $this->addJSFile('/assets/js/plugin/datatables/dataTables.tableTools.min.js'); //datatable
			//~ $this->addJSFile('/assets/js/plugin/datatables/dataTables.bootstrap.min.js'); //datatable
			//~ $this->addJSFile('/assets/js/plugin/datatable-responsive/datatables.responsive.min.js'); //datatable */
			
			//~ $this->addJSFile('/assets/js/std/raphael.min.js' ); //vector library
			//~ $this->addJSFile('/assets/js/std/modernizr-touch.js' ); //touch device detection
			//~ $this->addJSFile('/assets/js/std/jogcontrols.js' ); //jog controls
			//~ $this->addJSFile('/assets/js/std/jogtouch.js' ); //jog controls
			//~ $this->addJSFile('/assets/js/plugin/knob/jquery.knob.min.js');
		}
		
		//~ $this->addCssFile('/assets/js/plugin/cropper/cropper.min.css');
		$this->addCssFile('/assets/css/scan/style.css');
		
		$this->addCssFile( plugin_assets_url( 'css/style.css') );
		$this->addCssFile( plugin_assets_url( 'css/area_select.css') );
		$this->addJSFile( plugin_assets_url( 'js/area_select.js') );
		
		//~ $this->addJSFile('/assets/js/plugin/flot/jquery.flot.cust.min.js'); 
		//~ $this->addJSFile('/assets/js/plugin/flot/jquery.flot.resize.min.js');
		//~ $this->addJSFile('/assets/js/plugin/flot/jquery.flot.fillbetween.min.js');
		//~ $this->addJSFile('/assets/js/plugin/flot/jquery.flot.time.min.js');
		//~ $this->addJSFile('/assets/js/plugin/flot/jquery.flot.tooltip.min.js');
		$this->addJSFile('/assets/js/plugin/fuelux/wizard/wizard.min.old.js'); //wizard
		
		
		$this->addJsInLine($this->load->view( plugin_url('make/js'), $data, true));

		/*if(!$task_is_running){
			$this->addJsInLine($this->load->view( 'std/task_safety_check_js', $data, true));
			$this->addJsInLine($this->load->view( 'std/select_file_js', $data, true));
			$this->addJsInLine($this->load->view( 'std/jog_setup_js', $data, true));
		}*/
		
		$this->addJsInLine($this->load->view( 'std/task_wizard_js', $data, true));
		//$this->addJsInLine($this->load->view( 'std/task_execute_js', $data, true));
		//$this->addJsInLine($this->load->view( 'std/task_finished_js', $data, true));
		
		$this->content = $widget->print_html(true);
		$this->view();
	}
	
	public function getObjects()
	{
		
	}
	
	/**
	 * Execute task script with params from UI
	 */
	public function startTask()
	{
		//load helpers
		$this->load->helpers('fabtotum_helper');
		$this->load->helper('plugin_helper');
		$this->load->model('Files', 'files');
		$this->load->model('Objects', 'objects');
		
		$data = $this->input->post();
		
		$response = array(
			'start' => false, 
			'message' => _("Task Not Implemented yet."), 
			'trace' => '', 
			'error' => ''
			);
			
		$this->output->set_content_type('application/json')->set_output(json_encode($response));
	}

	/**
	 * Probe the test area at it's border points
	 */
	public function testProbingArea()
	{
		$params = $this->input->post();
		
		$testArgs = array(
				'-x' => $params['x1'],
				'-y' => $params['y1'],
				'-i' => $params['x2'],
				'-j' => $params['y2'],
				'--homing' => $params['homing'],
				'--test' => null
		);
		
		//if($params['skip_homing'] == 'true')  $scanArgs['-s'] = '';
		
		$this->load->helpers('fabtotum_helper');
		$this->load->helper('plugin_helper');
		
		startPluginPyScript('digitizer.py', $testArgs, true);
		
		$response = array(
			'start' => true, 
			'message' => '', 
			'trace' => '', 
			'error' => ''
			);
		
		$this->output->set_content_type('application/json')->set_output(json_encode(array($response)));
	}

 }
 
?>
