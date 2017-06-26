<div class="row">
	<div class="col-sm-3">
		<div class="smart-form">
			<fieldset>
				<section>
					<label class="radio">
						<input type="radio" id="object_type" name="object_type" checked="checked" value="new"><i></i> <?php echo _("Create new object");?>
					</label>
					<label class="radio">
						<input type="radio" id="object_type" name="object_type" value="add"><i></i> <?php echo _("Add to an existing object");?>
					</label>
				</section>
			</fieldset>
		</div>
	</div>
	<div class="col-sm-9">
		<div class="smart-form">
			<fieldset>
				<section class="section-new-object">
					<label class="input">
						<i class="icon-prepend fa fa-folder-open"></i>
						<input type="text" id="scan-object-name" placeholder="Type object name" value="<?php echo $suggestedObjectName; ?>">
					</label>
				</section>
				<section class="section-existing-object" style="display: none;">
					<label class="select">
						<?php echo form_dropdown('shirts', $objectsForDropdown, '', 'id="scan-objects-list"'); ?> <i></i>
					</label>
				</section>
				<section>
					<label class="input">
						<i class="icon-prepend fa fa-file-o"></i>
						<input type="text" id="scan-file-name" placeholder="Type file name" value="<?php echo $suggestedFileName; ?>">
					</label>
				</section>
			</fieldset>
		</div>
	</div>
</div>
<hr class="simple">
<div class="row">
	<div class="col-sm-3 text-center">
			<div class="area-section">
				<div class="touch-container">
					<img class="bed-image" src="/assets/img/std/hybrid_bed_v2_small.jpg" >
				</div>
				<div>
					<button type="button" class="btn btn-default btn-sm btn-block" data-homing="xyz" id="test-area-button"><?php echo _("Test area");?> <i class="fa fa-level-down"></i> </button>
					<div class="note">
						<p><?php echo _("Press to test the selected area"); ?></p>
					</div>
				</div>
			</div>
	</div>
	<div class="col-sm-9">
		<div class="row">
			<div class="col-sm-6 col-xs-6  margin-top-10">
				<div class="form-group">
					<label><?php echo _("First point");?></label>
					<div class="input-group">
						<span class="input-group-addon">x</span>
						<input class="form-control probing-x1 area-data" type="number">
					</div>  
				</div>
			</div>
			<div class="col-sm-6 col-xs-6  margin-top-10">
				<div class="form-group">
					<label>&nbsp;</label>
					<div class="input-group">
						<span class="input-group-addon">y</span>
						<input class="form-control probing-y1 area-data"  type="number">
					</div>  
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-sm-6 col-xs-6">
				<div class="form-group">
					<label><?php echo _("Second point");?></label>
					<div class="input-group">
						<span class="input-group-addon">x</span>
						<input class="form-control probing-x2 area-data" type="number">
					</div>  
				</div>
			</div>
			<div class="col-sm-6 col-xs-6"> 
				<div class="form-group"> 
					<label>&nbsp;</label>
					<div class="input-group">
						<span class="input-group-addon">y</span>
						<input class="form-control probing-y2 area-data" type="number">
					</div>  
				</div>
			</div>
		</div>
		<hr class="simple">
		<div class="row">
			<div class="col-sm-12">
				<p class="font-sm"><?php echo _("Slide to select quality");?></p>
				<div id="probing-slider" class="noUiSlider"></div>
			</div>
		</div>
		<div class="row">
			<div class="col-sm-12">
				<h5><?php echo _("Quality");?>: <span class="scan-probing-quality-name"></span></h5>
				<h5><?php echo _("Probes per square millimeters");?>: <span class="scan-probing-sqmm"></span></h5>
			</div>
		</div>
		<hr class="simple">
		<div class="row">
			<div class="col-sm-6 col-xs-6">
				<div class="form-group">
					<label><?php echo _("Z Jump (mm)");?></label>
					<input type="number"  class="form-control probing-z-hop" value="1" step="0.5">  
				</div>
				<div class="note">
					<p><?php echo _("This is the maximum difference in height of the different portions of the object to probe");?></p>
				</div>
			</div>
			<div class="col-sm-6 col-xs-6">
				<div class="form-group">
					<label><?php echo _("Detail threshold (mm)");?></label>
					<input type="number"  class="form-control probing-probe-skip" value="0" step="0.01"> 
				</div>
				<div class="note">
					<p><?php echo _("If Z height change is minor than detail threshold adaptive auto skipping is automatically enabled. Lower values give finer details. 0 = disable");?></p>
				</div>
			</div>
		</div>
	</div>
</div>
