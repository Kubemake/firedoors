<!-- Add Client Modal -->
<div class="modal fade" id="AddClientModal" tabindex="-1" role="dialog" aria-labelledby="AddClientModal" aria-hidden="true">
	<div class="modal-dialog modal-md">
		<div class="modal-content">
	 		<form method="POST" name="add_client_modal" id="addbtnform" class="form-horizontal">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
					<h4 class="modal-title text-center" id="myModalLabel">Add record</h4>
				</div>
				<div class="modal-body">
					<div class="row pad15">
						<div class="form-group">
							<label class="control-label col-xs-4 text-right">Role</label>
							<div class="col-xs-8">
								<?php foreach ($user_roles as $role_id => $role_name): ?>
									<label class="radio-inline">
										<input type="radio" name="user_role" id="user_role<?=$role_id?>" value="<?=$role_id?>" required="required" />
										<?=$role_name?>
									</label>
								<?php endforeach; ?>
							</div>
						</div>
					</div>
					<div class="row pad15">
						<div class="form-group">
							<label for="first_name" class="control-label col-xs-4">First Name</label>
							<div class="col-xs-8">
								<input name="first_name" id="first_name" class="form-control" value="" />
							</div>
						</div>
						<div class="form-group">
							<label for="last_name" class="control-label col-xs-4">Last Name</label>
							<div class="col-xs-8">
								<input name="last_name" id="last_name" class="form-control" value="" />
							</div>
						</div>
						<div class="form-group">
							<label for="officePhone" class="control-label col-xs-4">Office Phone</label>
							<div class="col-xs-8">
								<input name="officePhone" id="officePhone" class="form-control" value="" />
							</div>
						</div><div class="form-group">
							<label for="phone" class="control-label col-xs-4">Mobile Phone</label>
							<div class="col-xs-8">
								<input name="mobilePhone" id="mobilePhone" class="form-control" value="" />
							</div>
						</div>
						<div class="form-group">
							<label for="email" class="control-label col-xs-4">Email</label>
							<div class="col-xs-8">
								<input type="email" name="email" id="email" class="form-control" required="required" value="" pattern="[a-zA-Z0-9._%+-]+@[a-zA-Z0-9\.-]+\.[a-zA-Z]{2,4}" placeholder="Format ex.: john@yahoo.com" onchange="checkEmail()" />
							</div>
						</div>
						<div class="row" id="licensing" style="display:none;">
							<div class="panel panel-danger">
								<div class="panel-heading">
									<div class="row pad15">
										<p class="lead text-center"><strong>LICENSING</strong></p>
										<div class="form-group">
											<label for="license_expiration_date" class="control-label col-xs-4">Expiration Date</label>
											<div class="col-xs-8">
												 <div class="input-group date" id="license_expiration_date">
													<input name="license_expiration_date" class="form-control" value="" />
													<span class="input-group-addon"><i class="glyphicon glyphicon-calendar"></i></span>
												</div>
											</div>
										</div>
										<div class="form-group">
											<label for="license_dir" class="control-label col-xs-4">Directors count</label>
											<div class="col-xs-8">
												<input name="license_dir" id="license_dir" class="form-control" value="" />
											</div>
										</div>
										<div class="form-group">
											<label for="license_sv" class="control-label col-xs-4">Supervisors count</label>
											<div class="col-xs-8">
												<input name="license_sv" id="license_sv" class="form-control" value="" />
											</div>
										</div>
										<div class="form-group">
											<label for="license_mech" class="control-label col-xs-4">Mechanics count</label>
											<div class="col-xs-8">
												<input name="license_mech" id="license_mech" class="form-control" value="" />
											</div>
										</div>
										<div class="form-group">
											<label for="license_inspections" class="control-label col-xs-4">Reviews count</label>
											<div class="col-xs-8">
												<input name="license_inspections" id="license_inspections" class="form-control" value="" />
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="panel panel-primary">
							<div class="panel-heading">
								<div class="row pad15">
									<p class="lead text-center"><strong>PASSWORD GENERATION</strong></p>
									<div class="form-group">
										<div class="col-xs-4">
											<label class="radio-inline">
												<input type="radio" value="manually" name="password_generator" required="required" />
												Manually
											</label>
										</div>
										<div class="col-xs-8">
											<label class="radio-inline">
												<input id="generatepass" type="radio" value="generate" name="password_generator" required="required" />
												Generate and send by email
											</label>
										</div>
									</div>
									<div class="form-group">
										<label for="new_password" class="control-label col-xs-4">New password</label>
										<div class="col-xs-8">
											<input type="password" name="new_password" id="new_password" class="form-control" required="required" value="" onchange="checkPasswords()" onclick="$('input:radio[name=password_generator]:nth(0)').attr('checked',true);"/>
										</div>
									</div>
									<div class="form-group">
										<label for="repeat_password" class="control-label col-xs-4">Repeat password</label>
										<div class="col-xs-8">
											<input type="password" name="repeat_password" id="repeat_password" class="form-control" required="required" value="" onchange="checkPasswords()" />
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="modal-footer">
					<input type="hidden" name="form_type" value="add_client">
					<button type="submit" class="btn btn-primary">Accept chages</button>
					<button type="button" class="btn btn-default" data-dismiss="modal">Cancel changes</button>
				</div>
				<script type="text/javascript">
					$(function () {
						$('#license_expiration_date').datepicker({format:'yyyy-mm-dd'}).on('changeDate', function(){
							$('#license_expiration_date').datepicker('hide');
						});
						$('#new_password,#repeat_password').password();

						$('#generatepass').on('click', function(){
							pass=str_rand();
							$('#new_password, #repeat_password').val(pass);
						});
					});

					$('#user_role1').on('click', function(){
						$('#licensing').show();
					});
					$('#user_role4').on('click', function(){
						$('#licensing').hide();
					});

					function str_rand() {
						var result	   		= '';
						var words			= '0123456789qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM';
						var max_position 	= words.length - 1;
							for( i = 0; i < 12; ++i ) {
								position = Math.floor ( Math.random() * max_position );
								result = result + words.substring(position, position + 1);
							}
						return result;
					}
					function checkPasswords() {
						var passl = document.getElementById('new_password');
						var pass2 = document.getElementById('repeat_password');
						if (passl.value!=pass2.value)
							passl.setCustomValidity("Password mismatch. Please check password in both fields!");
						else
							passl.setCustomValidity("");
					}

					function checkEmail()
					{
						$.ajax({
							url: '/ajax/ajax_check_email/',
							type: 'POST',
							data: {'email': $('#email').val()},
							success: function(msg)
							{
								if (msg=='busy')
									document.getElementById('email').setCustomValidity("Email allready used");
								else
									document.getElementById('email').setCustomValidity("");
							}
						});
					}
				</script>
			</form>
		</div>
	</div>
</div>