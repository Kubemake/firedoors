<!-- Edit expert Modal -->
<script type="text/javascript" src="/js/uploader/src/dmuploader.min.js"></script>
<script type="text/javascript" src="/js/custom-upload.js"></script>
<div class="modal fade" id="EditExpertModal" tabindex="-1" role="dialog" aria-labelledby="EditExpertModal" aria-hidden="true">
	<div class="modal-dialog modal-md">
		<div class="modal-content">
	 		<form method="POST" name="edit_info_modal" id="editbtnform" class="form-horizontal">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
					<h4 class="modal-title text-center" id="myModalLabel">Edit record</h4>
				</div>
				<div class="modal-body">
					<div class="row pad15">
						<div class="form-group">
							<label for="name" class="control-label col-xs-4">Name</label>
							<div class="col-xs-8">
								<input name="name" id="name" class="form-control" value="<?=$expert['name']?>" />
							</div>
						</div>
						<div class="form-group">
							<label for="description" class="control-label col-xs-4">Description</label>
							<div class="col-xs-8">
								<textarea name="description" id="description" class="form-control"><?=$expert['description']?></textarea>
							</div>
						</div>
						<div class="form-group">
							<label for="expert_logo" class="control-label col-xs-4">Logo image</label>
							<div class="col-xs-8">
								<div id="drop-area" class="text-center"<?php echo (!empty($expert['logo'])) ? ' style="display:none;"' : ''; ?>><?//container for d&d upload?>
									<span class="upbtnwrap"><input type="file" class="fullwidth" name="expert_logo" multiple="multiple" title="Click to add Files" /></span>
									<div id="progress-files"></div><?//container for upload progress?>
								</div>
								<?php if (!empty($expert['logo'])): ?>
									<div id="drop-area-result" style="display:block;">
										<button type="button" class="close close-uploaded">×</button>
										<div id="upload-acceptor" class="text-center"><img id="upload-logo" src="<?=$expert['logo']?>" /></div>
									</div>
								<?php else: ?>
									<div id="drop-area-result">
										<button type="button" class="close close-uploaded">×</button>
										<div id="upload-acceptor" class="text-center"></div>
									</div>
								<?php endif; ?>
							</div>
						</div>
						<div class="form-group">
							<label for="link" class="control-label col-xs-4">Url</label>
							<div class="col-xs-8">
								<input name="link" id="link" class="form-control" value="<?=$expert['link']?>" />
							</div>
						</div>
					</div>
				</div>
				<div class="modal-footer">
					<input type="hidden" name="form_type" value="edit_expert">
					<input type="hidden" name="expert_id" value="<?=$expert['idExperts']?>">
					<input type="hidden" name="file_path" id="file_path" value="<?=$expert['logo']?>">
					<button type="submit" class="btn btn-primary">Accept chages</button>
					<button type="button" class="btn btn-default" data-dismiss="modal">Cancel changes</button>
				</div>
			</form>
		</div>
	</div>
</div>

<script type="text/javascript">
	makeupload('#drop-area', <?=$this->session->userdata('user_id')?>, function(data){
		$('#drop-area').hide();
		ftype = data.substr(-3);
		if ($.inArray(ftype,['jpg','png','jpeg','gif']) > -1)
			$('#upload-acceptor').html('<img id="upload-logo" src="' + data + '" />');
		$('#file_path').val(data);
		$('#drop-area-result').show();
	});

	$('.close-uploaded').on('click', function(){
		$('#upload-acceptor').html('');
		$('#file_path').val('');
		$('#drop-area').show();
		$('#drop-area-result').hide();
		$('#savemedia').addClass('disabled');
		$('#progress-files').prop('file-counter', '0').html('');
	})
</script>