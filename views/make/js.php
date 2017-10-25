<?php
/**
 * 
 * @author FABteam
 * @version 0.10.0
 * @license https://opensource.org/licenses/GPL-3.0
 * 
 */
?>

<script type="text/javascript">

	var buildPlateDimensions = {
		probe: {
			minX : 0,
			maxX : 210,
			minY : 12,
			maxY : 230,
			
			
			offsetX : 2.7,
			offsetY : -10.3
		},
		
		width : 214,
		height : 234
	};

	var idTask <?php echo $runningTask ? ' = '.$runningTask['id'] : ''; ?>;
	
	var probingQualities = new Array();
	<?php if(isset($probingQualities)): ?>
	<?php     foreach($probingQualities as $quality):?>
	probingQualities.push(<?php echo json_encode($quality);?>);
	<?php     endforeach;?>
	<?php endif; ?>
	
	var objectMode = '<?php echo $object_id ? 'add':'new'; ?>';
	var idObject <?php echo $object_id ? ' = '.$object_id : ''; ?>;
	var idFile = '';
	
	var area_select;
	/******************************************************************/
	
	/**
	 * On UI load
	 */
	$(document).ready(function() {
		$('[data-toggle="tooltip"]').tooltip();
		
		<?php if(!$runningTask): ?>

		if(objectMode == 'new'){
			$(".section-existing-object").hide();
			$(".section-new-object").show();
		}else{
			$(".section-existing-object").show();
			$(".section-new-object").hide();
		}
		
			<?php if($object_id): ?>
			
			$(".section-existing-object option").prop('selected', false).filter(function() {
				return $(this).val() == idObject;  
			}).prop('selected', true);
			<?php endif;?>

		$(':radio[name="object_type"]').on('change', setObjectMode);
		setProbingQuality(probingQualities[0], 0);
		initProbingSlider();
		initProbingAreaSelector();
		<?php endif; ?>
		
		$("#test-area-button").on('click', testProbingArea);
		
		// x1,x2,y1,y2 inputs
		$(".area-data").on('change', areaDataChange );
	});
	
	function areaDataChange()
	{
		var x1 = parseInt($(".probing-x1").val());
		var y1 = parseInt($(".probing-y1").val());
		var x2 = parseInt($(".probing-x2").val());
		var y2 = parseInt($(".probing-y2").val());
		
		var result = area_select.areaselect('transform', x1, y1, Math.abs(x2-x1), Math.abs(y2-y1));
		console.log(result);
		
		x1 = result.x;
		y1 = result.y;
		x2 = x1 + result.width;
		y2 = y1 + result.height;
		
		
		$(".probing-x1").val(x1.toFixed());
		$(".probing-y1").val(y1.toFixed());
		$(".probing-x2").val(x2.toFixed());
		$(".probing-y2").val(y2.toFixed());
	}
	
	function initProbingAreaSelector()
	{
		
		$(".probing-x1").attr( {'min' : buildPlateDimensions.probe.minX,
								'max' : buildPlateDimensions.probe.maxX} );
		$(".probing-x2").attr( {'min' : buildPlateDimensions.probe.minX,
								'max' : buildPlateDimensions.probe.maxX} );
		$(".probing-y1").attr( {'min' : buildPlateDimensions.probe.minY,
								'max' : buildPlateDimensions.probe.maxY} );
		$(".probing-y2").attr( {'min' : buildPlateDimensions.probe.minY,
								'max' : buildPlateDimensions.probe.maxY} );
		 
		var realWidth  = buildPlateDimensions.width;
		var realHeight = buildPlateDimensions.height;

		var probeMinX = buildPlateDimensions.probe.minX;
		var probeMaxX = buildPlateDimensions.probe.maxX;
		
		var probeMaxY = buildPlateDimensions.probe.maxY;
		var probeMinY = buildPlateDimensions.probe.minY;
		
		var probeMaxWidth = buildPlateDimensions.probe.maxX - buildPlateDimensions.probe.minX;
		var probeMaxHeight = buildPlateDimensions.probe.maxY - buildPlateDimensions.probe.minY;
		
		//var realWidth
		
		var area_options = {
			guides: false,
			center: false,
			highlight: false,
			background: false,
			disabled: false,

			// Bed mapping
			useMappedDimensions : true,
			mappedWidth : realWidth,
			mappedHeight : realHeight,
			
			/*initX:      probeMinX,
			initY:      probeMinY,
			initWidth:  50,
			initHeight: 50,*/
			
			initX:      115,
			initY:      50,
			initWidth:  30,
			initHeight: 30,
			
			
			minX: probeMinX,
			maxX: probeMaxX,
			minY: probeMinY,
			maxY: probeMaxY,
			
			minWidth:  10,
			maxWidth:  probeMaxWidth,
			
			minHeight: 10,
			maxHeight: probeMaxHeight,
		 };
		 
		 area_select = $('.bed-image').areaselect(area_options);
		 area_select.on({
			'change':function(data){
				var x1 = data.x;
				var y1 = data.y;
				var x2 = data.x + data.width;
				var y2 = data.y + data.height;
				$(".probing-x1").val(x1.toFixed());
				$(".probing-y1").val(y1.toFixed());
				$(".probing-x2").val(x2.toFixed());
				$(".probing-y2").val(y2.toFixed());
			}
		});
		
		$(".probing-x1").val(area_options.initX);
		$(".probing-y1").val(area_options.initY);
		$(".probing-x2").val(area_options.initX+area_options.initWidth);
		$(".probing-y2").val(area_options.initY+area_options.initHeight);
	}
	
	/**
	 * Initialize probing quality slider in the UI
	 */
	function initProbingSlider()
	{
		noUiSlider.create(document.getElementById('probing-slider'), {
			start: 0,
			step: 20,
			connect: "lower",
			range: {'min': 0, 'max' : 100},
		});
		probingSlider = document.getElementById('probing-slider');
		
		probingSlider.noUiSlider.on('slide',  function(e){
			var qualityIndex;
			switch(parseInt(e)){
				case 0:
					qualityIndex = 0;
					break;
				case 20:
					qualityIndex = 1;
					break;
				case 40:
					qualityIndex = 2;
					break;
				case 60:
					qualityIndex = 3;
					break;
				case 80:
					qualityIndex = 4;
					break;
				case 100:
					qualityIndex = 5;
					break;
				default:
					qualityIndex = 0;
					break;
			}
			setProbingQuality(probingQualities[qualityIndex], qualityIndex);
		});
	}

	/**
	 * Handle wizard step actions
	 */
	function handleStep()
	{
		var step = $('.wizard').wizard('selectedItem').step;
		console.log('handleStep', step);
		
		if(step == 2)
		{
			<?php if($runningTask): ?>;
			// do nothing
			<?php else: ?>
				startTask();
				return false;
			<?php endif; ?>
			return false;
		}
		
		return true;
	}
	
	/** 
	 * Check if next/prev step is allowed. Also adjust next/prev
	 * button labels if needed.
	 */
	function checkWizard()
	{
		console.log('check Wizard');
		var step = $('.wizard').wizard('selectedItem').step;
		console.log(step);
		switch(step){
			case 1: // Settings
				disableButton('.button-prev');
				enableButton('.button-next');
				$('.button-next').find('span').html('Next');
				break;
				
			case 2: // Get ready
				enableButton('.button-prev');
				enableButton('.button-next');
				$('.button-next').find('span').html('Scan');
				break;
				
			case 3: // Scan/Execution
				break;
				
			case 4: // Finished
				$('.button-next').find('span').html('');
				break;
		}
	}
	
	/**
	 * Set probing qality in the UI
	 */
	function setProbingQuality(object, index)
	{
		$(".scan-probing-quality-name").html(object.info.name);
		$(".scan-probing-sqmm").html(object.values.sqmm);
	}
	
	/**
	 * Update cloud point stats in the UI
	 */
	function updateClouds(number, size)
	{
		$(".cloud-points").html(number);
		$(".cloud-size").html(humanFileSize(size));
	}
	
	/**
	 * Set object mode. New means a new object will be created for the
	 * new cloud points.
	 */
	function setObjectMode()
	{
		var radio = $(this);
		objectMode = radio.val();
		if(objectMode == 'new'){
			$(".section-existing-object").hide();
			$(".section-new-object").show();
		}else{
			$(".section-existing-object").show();
			$(".section-new-object").hide();
		}
	}
	
	/**
	 *
	 */
	function testProbingArea(e)
	{
		var button = $(e.toElement);
		
		var offsetX = buildPlateDimensions.probe.offsetX;
		var offsetY = buildPlateDimensions.probe.offsetY;
		
		var homing = button.attr('data-homing');
		button.attr('data-homing', 'skip');
		
		var data = {
			'x1' : 			( parseInt($(".probing-x1").val()) + offsetX), 
			'y1' : 			( parseInt($(".probing-y1").val()) + offsetY), 
			'x2' : 			( parseInt($(".probing-x2").val()) + offsetX), 
			'y2' : 			( parseInt($(".probing-y2").val()) + offsetY),
			'homing' : homing
		};
		
		openWait(_("Probing selected area"));
		$.ajax({
			type: 'post',
			data: data,
			url: '<?php echo site_url($start_test_url); ?>',
			dataType: 'json'
		}).done(function(response) {
			if(response.start == false){
				fabApp.showErrorAlert(response.message);
			}else{
				// do nothing
			}
			closeWait();
		})
	}

	/**
	 * Start task script with params provided from the UI
	 */
	function startTask()
	{
		console.log('Starting task');
		openWait('<i class="fa fa-spinner fa-spin "></i> ' + "<?php echo _('Preparing {0}');?>".format("<?php echo _(ucfirst($type)); ?>"), _("Checking safety measures...") );
		
		var $radio = $(':radio[name="object_type"]:checked');
		var object_mode = $radio.val();
		// As the carriage position and probe position are not the same
		// here we compensate for the offset
		var offsetX = buildPlateDimensions.probe.offsetX;
		var offsetY = buildPlateDimensions.probe.offsetY;
		
		var data = {
			'safe_z': 		parseFloat( $(".probing-z-hop").val() ), 
			'threshold': 	parseFloat( $(".probing-probe-skip").val() ), 
			'density' : 	parseInt( $(".scan-probing-sqmm").html() ),
			'x1' : 			( parseInt($(".probing-x1").val()) + offsetX), 
			'y1' : 			( parseInt($(".probing-y1").val()) + offsetY), 
			'x2' : 			( parseInt($(".probing-x2").val()) + offsetX), 
			'y2' : 			( parseInt($(".probing-y2").val()) + offsetY),
			'object_mode' : object_mode,
			'object'      : object_mode == 'new' ? $("#scan-object-name").val() : $("#scan-objects-list option:selected").val(),
			'file_name'   : $("#scan-file-name").val()
		};
		
		console.log('DATA', data);
		
		$.ajax({
			type: 'post',
			data: data,
			url: '<?php echo site_url($start_task_url); ?>',
			dataType: 'json'
		}).done(function(response) {
			if(response.start == false){
				gotoWizardStep(2);
				fabApp.showErrorAlert(response.message);
			}else{
				gotoWizardStep(3);
				idTask = response.id_task;
				//updateFileInfo(response.file);
				initRunningTaskPage('digitizer');
				ga('send', 'event', 'digitizer', 'start', 'scan started');
			}
			closeWait();
		})
	}

</script>
