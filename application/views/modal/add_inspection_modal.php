<!-- Add Inspection Modal -->
<div class="modal fade" id="AddInspectionModal" tabindex="-1" role="dialog" aria-labelledby="AddInspectionModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-md">
		<div class="modal-content">
	 		<form method="POST" name="add_inspection_modal" id="addbtnform" class="form-horizontal">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
					<h4 class="modal-title text-center" id="AddInspectionModalLabel">Add review</h4>
				</div>
				<div class="modal-body">
					<div class="row pad15">
						<div class="form-group">
							<label for="first_name" class="control-label col-xs-4">Select aperture location</label>
							<div class="col-xs-8">
								<div class="dropdown locationselect">
									<button type="button" role="button" data-toggle="dropdown" class="btn btn-primary fullwidth" data-target="#">Select location <span class="caret"></span></button>
									<?php echo make_buildings_dropdown($user_buildings); ?>
									<input id="location" name="location" type="hidden" />
								</div>
							</div>
						</div>
						<div class="form-group">
							<label for="aperture" class="control-label col-xs-4">Select aperture</label>
							<div class="col-xs-8 apertureselect">
								<select name="aperture" id="aperture" class="selectpicker fullwidth" data-live-search="true">
									<option value="0">Choose aperture</option>
									<?php foreach ($user_apertures as $aperture): ?>
										<option value="<?=$aperture['idDoors']?>"><?=$aperture['name']?></option>
									<?php endforeach; ?>
								</select>
							</div>
						</div>
						<div class="form-group">
							<label for="start_date" class="control-label col-xs-4">Start date</label>
							<div class="col-xs-8">
								 <div class="input-group date" id="start_date">
									<input name="start_date" class="form-control" value="" />
									<span class="input-group-addon"><i class="glyphicon glyphicon-calendar"></i></span>
								</div>
							</div>
						</div>
						<?/*<div class="form-group">
							<label for="completion_date" class="control-label col-xs-4">Completion date</label>
							<div class="col-xs-8">
								 <div class="input-group date" id="completion_date">
									<input name="completion_date" class="form-control" value="" />
									<span class="input-group-addon"><i class="glyphicon glyphicon-calendar"></i></span>
								</div>
							</div>
						</div>*/?>
						<div class="form-group">
							<label for="reviewer" class="control-label col-xs-4">Reviewer</label>
							<div class="col-xs-8">
								<select name="reviewer" class="selectpicker fullwidth" data-live-search="true">
									<option value="0">Choose reviewer</option>
									<?php foreach ($users_reviewer as $reviewer): ?>
										<option value="<?=$reviewer['idUsers']?>"><?=$reviewer['firstName'] . ' ' . $reviewer['lastName']?></option>
									<?php endforeach; ?>
									
									
								</select>
							</div>
						</div>
						<div class="form-group">
							<label for="state" class="control-label col-xs-4">Review state</label>
							<div class="col-xs-8">
								<select name="state" class="selectpicker fullwidth" data-live-search="true">
									<?php foreach ($inspection_statuses as $status): ?>
										<?php $select = ('New' == $status) ? ' selected="selected"' : '';?>
										<option<?=$select?> value="<?=$status?>"><?=$status?></option>
									<?php endforeach; ?>
								</select>
							</div>
						</div>
					</div>
				</div>
				<div class="modal-footer">
					<input type="hidden" name="form_type" value="add_inspection" />
					<button type="submit" class="btn btn-primary">Accept chages</button>
					<button type="button" class="btn btn-default" data-dismiss="modal">Cancel changes</button>
				</div>
			</form>
		</div>
	</div>
</div>
<script type="text/javascript">
	$(function () {
		$('.selectpicker').selectpicker();
		$('#start_date').datepicker({format:'yyyy-mm-dd'}).on('changeDate', function(){
			$('#start_date').datepicker('hide');
		});
		$('#completion_date').datepicker({format:'yyyy-mm-dd'}).on('changeDate', function(){
			$('#completion_date').datepicker('hide');
		});
	});

	$('.locationselect ul li a').on('click', function(){
		$('.locationselect button').html($(this).html());
		$('.locationselect input').val($(this).data('id'));
		$.ajax({
			url: '/dashboard/ajax_get_apertures',
			type: 'POST',
			data: {locid: $(this).data('id')},
			success: function(result) {
				$('.apertureselect .dropdown-menu').remove();
				$('.apertureselect').html(result);
				$('.selectpicker').selectpicker();
			}

		});
	});

	$("#addbtnform").submit(function(e){
	    if ($('.apertureselect select').val()=='Choose aperture')
		{
			alert('Please choose aperture!');
			return false;
		}
		 if ($('#location').val()=='')
		{
			alert('Please select aperture location!');
			return false;
		}

	});
</script>