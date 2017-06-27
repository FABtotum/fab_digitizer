<ul id="step4-tab" class="nav nav-tabs bordered">
	<li class="active">
		<a href="#live-feeds-tab" data-toggle="tab"> <?php echo _("Live feed");?></a>
	</li>
</ul>
<div id="myTabContent1" class="tab-content padding-10">
	<div class="tab-pane fade in active" id="live-feeds-tab">
		<div class="row">
			<div class="col-sm-6 show-stats">
				<div class="row">
					<div class="col-sm-12"> <span class="text"> <?php echo _("Progress");?> <span class="pull-right task-progress"></span> </span>
						<div class="progress">
							<div class="progress-bar" id="task-progress-bar"></div>
						</div> 
					</div>
					<div class="col-sm-12 postprocessing" style="display:none;"> <span class="text"> <?php echo _("Post processing");?> <span class="pull-right postprocessing-progress"></span> </span>
						<div class="progress">
							<div class="progress-bar" id="postprocessing-progress-bar"></div>
						</div> 
					</div>
				</div>
			</div>
			<div class="col-sm-6 show-stats">
				
				<div class="row">
					<div class="col-sm-12"> <span class="text"> <?php echo _("Slice");?> <span class="pull-right"> <span class="current-scan">0</span> of <span class="total-scan">0</span> </span> </span>
						<div class="fake-progress"></div>
					</div>
					<div class="imageinfo" style="display:none">
						<div class="col-sm-12"> <span class="text"> <?php echo _("Image resolution");?> <span class="pull-right"> <span class="resolution-width"></span> x <span class="resolution-height"></span> </span> </span>
							<div class="fake-progress"></div>
						</div>
						<div class="col-sm-12"> <span class="text"> <?php echo _("ISO");?> <span class="pull-right"> <span class="iso"></span>  </span> </span>
							<div class="fake-progress"></div>
						</div>
					</div>
					<hr class="simple">
					<div class="pointcloudinfo" style="display:none">
						<div class="col-sm-12"> <span class="text"> <?php echo _("Cloud points");?> <span class="pull-right"> # <span class="cloud-points"></span>  </span> </span>
							<div class="fake-progress"></div>
						</div>
						<div class="col-sm-12"> <span class="text"> <?php echo _("Cloud size");?><span class="pull-right">  <span class="cloud-size"></span>  </span> </span>
							<div class="fake-progress"></div>
						</div>
					</div>
					<hr class="simple">
					<div class="col-sm-12"> <span class="text"> <?php echo _("Elapsed time");?> <span class="pull-right"><span class="elapsed-time">Initializing...</span> </span> </span>
						<div class="fake-progress"></div>
					</div>
				</div>
				<hr class="simple">
				<div class="row">
					<div class="col-sm-6 margin-bottom-10">
						<button type="button" data-action="pause" class="btn btn-default btn-sm btn-block action"><i class="fa fa-pause"></i> <?php echo _("Pause");?></button>
					</div>
					<div class="col-sm-6">
						<button type="button" data-action="abort" class="btn btn-default btn-sm btn-block action"><i class="fa fa-stop"></i> <?php echo _("Abort");?></button>
					</div>
				</div>
			</div>
		</div>
		
		<div class="row">
			<div class="col-sm-12">
				<div class="textarea-div">
					<div class="typearea">
						<div class="custom-scroll trace-console" ></div>
					</div>
				</div>
			</div>
		</div>
		
	</div>
</div>
