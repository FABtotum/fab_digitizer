<div class="row">
	<!--  -->
	<div class="col-sm-12">
		<div class="text-center">
			<h1 class="tada animated">
				<span class="fabtotum-icon">
					<i class="fa fa-play fa-rotate-90 fa-border border-black border-1px-solid fa-4x"></i>
					<span><b class="badge fabtotum-badge"><i class="fa fa-check"></i></b></span>
				</span>
			</h1>
			<h4 class="margin-bottom-20"><?php echo _("Scan completed");?></h4>
			<div class="button-container text-center">
				<button class="btn btn-default margin-bottom-10 go-to-projects-manager"><i class="fa fa-refresh"></i> <?php echo _("Go to projects manager"); ?></button>
				<button class="btn btn-default margin-bottom-10 restart-task"><i class="fa fa-refresh"></i> <?php echo _("Restart scan"); ?></button>
				<a class="btn btn-default margin-bottom-10 no-ajax download-task-file" href="#" target="_blank"><i class="fa fa-download"></i> <?php echo _("Download cloud points file")?> </a>
				<a class="btn btn-default margin-bottom-10 no-ajax download-missing-images" target="_blank"  href="<?php echo site_url('scan/downloadPhotogrammetryMissingImages'); ?>"><i class="fa fa-download"></i> <?php echo _("Download  missing images")?> </a>
			</div>
		</div>
	</div>
</div>
