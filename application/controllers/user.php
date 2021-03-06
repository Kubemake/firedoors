<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class User extends CI_Controller {

	function __construct()
	{
		parent::__construct();
		$this->load->model('user_model');
	}

	function index()
	{
	}
	
	function profile()
	{
		verifyLogged();
		
		if ($this->input->post('updateProfile')) {
			$postData = $this->input->post();

			$this->load->library('History_library');

			if (strlen($postData['logoFilePath']) > 0 )
			{
				$postData['logoFilePath'] = str_replace('http://firedoortracker.org', '', $postData['logoFilePath']);

				if (!file_exists( $_SERVER['DOCUMENT_ROOT'] . $postData['logoFilePath']))
					$postData['logoFilePath'] = get_image_by_height($postData['logoFilePath'], 100, 'resize');
				
			}
			else
				$postData['logoFilePath'] = '/images/head-logo.png';

			$updateData = array(
				'firstName'		=> str_replace(array("'", '"'), '', $postData['firstName']),
				'lastName'		=> str_replace(array("'", '"'), '', $postData['lastName']),
				'officePhone'	=> $postData['officePhone'],
				'mobilePhone'	=> $postData['mobilePhone'],
				'logoFilePath' 	=> $postData['logoFilePath'],
			);
			if (   !empty($postData['inputPassword']) 
			 	&& !empty($postData['confirmPassword'])
			 	&& $postData['inputPassword']==$postData['confirmPassword'])
			{
				$updateData['password'] = pass_crypt($postData['inputPassword']);
			}
			
			$this->history_library->saveUsers(array('line_id' => $this->session->userdata('user_id'), 'new_val' => json_encode($updateData), 'type' => 'edit'));

			$this->user_model->update_user_data($this->session->userdata('user_id'), $updateData);

			$this->session->set_userdata('logoFilePath', $postData['logoFilePath']);

			$header['msg'] = msg('success', 'Profile updated successfuly');

			$this->session->set_userdata($updateData); 	//update session data
		}

		$data['profile'] = $this->user_model->get_user_info_by_user_id($this->session->userdata('user_id'));
		
		//uploader
		$footer['scripts']  = '<script type="text/javascript" src="/js/uploader/src/dmuploader.min.js"></script>' . "\n";
		$footer['scripts'] .= '<script type="text/javascript" src="/js/custom-upload.js"></script>' . "\n";

		$header['page_title'] = 'User profile';
		$this->load->view('header', $header);
		$this->load->view('user/user_profile', $data);
		$this->load->view('footer', $footer);
		
	}

	function address()
	{
		verifyLogged();
		$this->load->model('address_model');
		$this->load->helper('form');
		
		if ($postdata = $this->input->post())
		{
			$this->load->library('History_library');
			
			$insdata = array(
				'address' 	=> $postdata['address'],
			    'city' 		=> $postdata['city'],
			    'state' 	=> $postdata['state'],
			    'zip' 		=> $postdata['zip']
			);

			$idaddress = $this->address_model->update_address($insdata);

			$this->history_library->saveAddress(array('line_id' => $idaddress, 'new_val' => json_encode($insdata), 'type' => 'add'));

			$this->history_library->saveUsers(array('line_id' => $this->session->userdata('user_id'), 'new_val' => json_encode(array('idAddress' => $idaddress)), 'type' => 'edit'));

			$this->address_model->update_user_address($idaddress, $this->session->userdata('user_id'));

			$header['msg'] = '<div class="aler alert-success">Address successfully updated!</div>';
		}		

		$data['address'] = $this->address_model->get_user_address($this->session->userdata('user_id'));

		$footer['scripts'] = '<script type="text/javascript" src="/js/bootstrap3-typeahead.min.js"></script>';
		
		$header['page_title'] = 'Address';

		$this->load->view('header', $header);
		$this->load->view('user/user_address', $data);
		$this->load->view('footer', $footer);
	}

	function login()
	{
		$this->load->library('form_validation');
		$this->load->helper('form');
		$logged_in = $this->session->userdata('islogged');
		if ($logged_in!=FALSE) redirect('user/profile/');
		$this->form_validation->set_rules('username', 'Login', 'required|xss_clean');
		$this->form_validation->set_rules('password', 'Password', 'required|xss_clean|callback__check_in_database');
		$this->form_validation->set_message('required', 'Field %s is required');
		
		if($this->form_validation->run() == FALSE)
		{
			
			$this->session->set_flashdata('refferer', $this->session->flashdata('refferer'));
			$this->load->view('user/user_login');
		}
		else
		{
			if ($this->input->post('rememberme'))  //если запомнить сохраняем в куку на месяц
			{
				$sessiondata = array( 
					'isadmin' 		=> $this->session->userdata('isadmin'),
					'islogged' 		=> $this->session->userdata('islogged'),
					'user_id' 		=> $this->session->userdata('user_id'),
					'user_parent' 	=> $this->session->userdata('user_parent'),
					'user_role' 	=> $this->session->userdata('user_role'),
					'firstName'		=> $this->session->userdata('firstName'),
					'lastName'		=> $this->session->userdata('lastName'),
					'lastlogin'		=> $this->session->userdata('lastLogin'),
					'logoFilePath'	=> $this->session->userdata('logoFilePath'),
				);
				setcookie('islogged', $this->session->userdata('user_id'), time()+60*60*24, '/');
			}

			if ($this->session->flashdata('refferer') && strpos($this->session->flashdata('refferer'), 'ajax') === FALSE)
				redirect($this->session->flashdata('refferer'));
			redirect('/');
		}
	}

	function _check_in_database($password)
	{
		$login = $this->input->post('username');
	
		$valid_login = $this->user_model->verifyUserLogin($login, pass_crypt($password));

		if (!$valid_login or empty($valid_login['idUsers']))
		{
			$this->form_validation->set_message('_check_in_database', '<div class="alert alert-danger">Wrong login or password</div>');
			return FALSE;
		}

		$this->load->model('licensing_model');
		$licensing = $this->licensing_model->get_lic_info_by_client_id($valid_login['parent']);
		
		//записываем данные авторизации
		$sessiondata = array( 
			'isadmin' 		=> ($valid_login['role']==4) ? 1 : 0,
			'islogged' 		=> 1,
			'user_id' 		=> $valid_login['idUsers'],
			'user_parent' 	=> $valid_login['parent'],
			'user_role'		=> $valid_login['role'],
			'firstName'		=> $valid_login['firstName'],
			'lastName'		=> $valid_login['lastName'],
			'lastlogin'		=> $valid_login['lastLogin'],
			'logoFilePath'	=> $valid_login['logoFilePath'],
		);

		if ($valid_login['role'] != 4)
		{
			if (!empty($licensing))
			{
				if (empty($licensing['expired']) or strtotime($licensing['expired'] . ' 23:59:59') < time())
				{
					$this->form_validation->set_message('_check_in_database', '<div class="alert alert-danger">Your license term has expired; please <a target="_blank" href="https://firedoortracker.com/pricing/">RENEW NOW</a> in order to continue using the app. <br>If there are any questions, please call us at 844.524.1212 or visit our website at <a target="_blank" href="https://www.firedoortracker.com">www.firedoortracker.com</a></div>');
					return FALSE;
				}

				$days = floor((strtotime($licensing['expired'] . ' 23:59:59') - time()) / (60*60*24));

				if ($days < 8)
					$this->session->set_flashdata('showlicwarn', array(1=>$days));

				/*deactivate employees by license limitation*/
				$users = $this->licensing_model->get_active_users_by_client_id($valid_login['parent']);
				
				if ($users[1] > $licensing['dir'])
					$this->licensing_model->deactivate_by_limitation(1, $users[1] - $licensing['dir'], $valid_login['parent']);
				if ($users[2] > $licensing['sv'])
					$this->licensing_model->deactivate_by_limitation(2, $users[2] - $licensing['sv'], $valid_login['parent']);
				if ($users[3] > $licensing['mech'])
					$this->licensing_model->deactivate_by_limitation(3, $users[3] - $licensing['mech'], $valid_login['parent']);

				/*END deactivate employees by license limitation*/
			}
			else
			{
				$this->form_validation->set_message('_check_in_database', '<div class="alert alert-danger">Your license term has expired; please <a target="_blank" href="https://firedoortracker.com/pricing/">RENEW NOW</a> in order to continue using the app. <br>If there are any questions, please call us at 844.524.1212 or visit our website at <a target="_blank" href="https://www.firedoortracker.com">www.firedoortracker.com</a></div>');
				return FALSE;
			}
		}

		$this->session->set_userdata($sessiondata);
		$this->user_model->update_user_data($valid_login['idUsers'], array('LastLogin' => date('Y-m-d H:i:s')));

		return TRUE;
	}

	function recovery()
	{
		$this->load->helper('form');

		$data['msg'] = '';
		
		if ($this->input->post())
		{
			$email = $this->input->post('email');
			if ($email && !empty($email))
			{
				if ($userinfo = $this->user_model->get_user_info_by_email($email))
				{
					$this->load->helper('string');
					$pass = random_string('alnum', 10);
					$this->user_model->update_user_data($userinfo['idUsers'], array('password' => pass_crypt($pass)));
					$this->user_model->delete_user_tokens($userinfo['idUsers']);
					
					$ans = 'Your new account details:<br>';
					$ans .= 'Login: ' . $userinfo['email'] . '<br>';
					$ans .= 'Password: ' . $pass . '<br>';
					send_mail($email, 'Recovery user password', $ans);
					$data['msg'] = msg('success', 'Your registration data sent to the specified mailbox');
				}
				else
					$data['msg'] = msg('danger', 'This mailbox is not registered in the system');
				
			}
		}
		$this->load->view('user/user_recovery', $data);
	}

	function leave()
	{
		setcookie('islogged', '', time() - 86600, '/'); 	//clear cookie

		$this->session->sess_destroy();
	
		redirect('/user/login');
	}
	
	function buildings()
	{
		verifyLogged();

		if ($this->input->post())
		{
			if ($this->input->post('form_type'))
			{
				$postdata = $this->input->post();

				$this->load->library('History_library');

				switch ($postdata['form_type'])
				{
					case 'add_user_building':
						unset($postdata['form_type']);

						$bid = $this->user_model->add_building($postdata);

						$this->history_library->saveBuildings(array('line_id' => $bid, 'new_val' => json_encode($postdata), 'type' => 'add'));

						$data['msg'] = msg('success', 'Element successfully added');
		
						echo '<script type="text/javascript">location.replace("/user/buildings");</script>
							<noscript><meta http-equiv="refresh" content="0; url=/user/buildings"></noscript>';
						exit;
					break;
				}
			}
		}

		$data['buildings'] = array();

		if (has_permission('Allow view buildings tree tab'))
		{
			$user_buildings = $this->user_model->get_all_buildings($this->session->userdata('user_parent'));

			$data['buildings'] = '';

			$result = '<ol class="dd-list">' . "\n";
			if (!empty($user_buildings))
			{
				foreach ($user_buildings as $buildingdata)
				{
					if (empty($buildingdata) or $buildingdata['level'] > 0) //this part only for parent=0
						continue;

					$result .= '<li class="dd-item" data-id="' . $buildingdata['idBuildings'] . '">' . "\n";
					$result .= '<div class="dd-handle"><span class="glyphicon glyphicon-align-justify"></span><span class="label-text">' . $buildingdata['name'] . '</span></div>';
					$result .= (has_permission('Allow modify buildings tree')) ? '<a onclick="button_add_element_action(' . $buildingdata['idBuildings'] . ', ' . ($buildingdata['level']+1) . ');return false;" class="btn btn-xs btn-default btn-plus"><span class="glyphicon glyphicon-plus"></span></a>
								<a onclick="editfield(this);return false;" class="btn btn-xs btn-default btn-pencil"><span class="glyphicon glyphicon-pencil"></span></a>
								<a onclick="deletefield(this);return false;" class="btn btn-xs btn-default btn-trash"><span class="glyphicon glyphicon-trash"></span></a>' . "\n" : '';
					$result .=  $this->_get_buildings_by_parent($buildingdata['idBuildings']);
					$result .= '</li>' . "\n";
				}
			}

			$result .= '</ol>' . "\n";

			$data['buildings'] = $result;
		}
		
		$header['page_title'] = 'Buildings';
		$footer['scripts'] = '<script type="text/javascript" src="/js/jquery.nestable.js"></script>';

		$this->load->view('header', $header);
		$this->load->view('user/user_buildings', $data);
		$this->load->view('footer', $footer);
	}

	function doors()
	{
		verifyLogged();

		$this->load->model('resources_model');
		$this->load->library('table');

		$data = array();
		if ($postdata = $this->input->post())
		{
			$this->load->library('History_library');

			$adddata = array(
			    'Building'		=> $postdata['building'],
			    'barcode'		=> $postdata['barcode'],
			    'UserId'		=> $this->session->userdata('user_parent'),
			);
			
			
			$adddata['Floor'] = ($postdata['floor'] && $postdata['floor'] > 0) ? $postdata['floor'] : 0;
			$adddata['Wing']  = ($postdata['wing'] && $postdata['wing'] > 0)   ? $postdata['wing']  : 0;
			$adddata['Area']  = ($postdata['area'] && $postdata['area'] > 0)   ? $postdata['area']  : 0;
			$adddata['Level'] = ($postdata['level'] && $postdata['level'] > 0) ? $postdata['level'] : 0;

			switch ($postdata['form_type'])
			{
				case 'add_aperture':
					$exist = array();
					$exist = $this->resources_model->get_aperture_info_by_barcode($adddata['barcode']);
					if (!empty($exist)) {
						$header['msg'] = msg('warning', 'Door allready exist!');
						break;
					}
					$newemp = $this->resources_model->add_aperture($adddata);	//add new aperture
					
					$this->history_library->saveDoors(array('line_id' => $newemp, 'new_val' => json_encode($adddata), 'type' => 'add'));
					
					if ($newemp)
						$header['msg'] = msg('success', 'Door successfully added');
				break;

				case 'edit_aperture':
					
					$exist = array();
					$exist = $this->resources_model->get_aperture_info_by_barcode($adddata['barcode']);
					if (!empty($exist) && $exist['idDoors'] != $postdata['aperture_id']) {
						$header['msg'] = msg('warning', 'Door allready exist!');
						break;
					}

					$this->history_library->saveDoors(array('line_id' => $postdata['aperture_id'], 'new_val' => json_encode($adddata), 'type' => 'edit'));

					$this->resources_model->update_aperture_data($postdata['aperture_id'], $adddata);

					$header['msg'] = msg('success', 'Door successfully updated');
				break;
				default:
				break;
			}
		}

		if (has_permission('Allow view doors tab'))
		{
			$this->table->set_heading(
				array('data' => ''   , 'style' => 'display: none !important;'),
				'Door Id',
				'Building',
				array('data' => 'Floor'  		, 'class' => 'not-mobile'),
				array('data' => 'Wing'  		, 'class' => 'not-mobile'),
				array('data' => 'Area'   		, 'class' => 'not-mobile'),
				array('data' => 'Level'  	 	, 'class' => 'not-mobile')/*,
				array('data' => 'Wall Rating'   , 'class' => 'not-mobile'),
				array('data' => 'Smoke Rating'	, 'class' => 'not-mobile'),
				array('data' => 'Material'		, 'class' => 'not-mobile'),
				array('data' => 'Rating'		, 'class' => 'not-mobile')*/
			);

	/*		$data['wall_Rating'] 	= $this->config->item('wall_rates');
			$data['smoke_Rating'] 	= $this->config->item('rates_types');
			$data['material'] 		= $this->config->item('door_matherial');
			$data['rating'] 		= $this->config->item('door_rating');*/

			$apertures = $this->resources_model->get_all_user_apertures();

			if (!empty($apertures))
			{
				foreach ($apertures as $aperture)
				{
					$cell = array('data' => $aperture['idDoors'], 'style' => 'display: none !important;');
					$this->table->add_row($cell, $aperture['barcode'], @$aperture['Building'], @$aperture['Floor'], @$aperture['Wing'], @$aperture['Area'], @$aperture['Level']/*, @$data['wall_Rating'][$aperture['wall_Rating']], @$data['smoke_Rating'][$aperture['smoke_Rating']], @$data['material'][$aperture['material']], @$data['rating'][$aperture['rating']]*/);
				}
			}
			

			$tmpl = array ( 'table_open'  => '<table class="table table-striped table-hover table-bordered table-responsive table-condensed" width="100%">' );
			$this->table->set_template($tmpl); 
			$data['result_table'] = $this->table->generate(); 
		}

		$header['page_title'] = 'Doors';

		//datatables
		$header['styles']  = addDataTable('css');
		$footer['scripts'] = addDataTable('js');

		$this->load->view('header', $header);
		$this->load->view('user/user_apertures', $data);
		$this->load->view('footer', $footer);
	}

	function employees()
	{
		verifyLogged();

		$this->lang->load('resources');
		$this->load->library('table');
		$this->load->model('resources_model');
		
		$data = array();

		if ($this->input->post())
		{
			if ($this->input->post('form_type'))
			{
				$postdata = $this->input->post();
				
				$roles = array(
					1 => 'dir',
					2 => 'sv',
					3 => 'mech'
				);
				$messageroles = array(
					1 => 'Directors',
					2 => 'Supervisors',			///ПРОВЕРИТЬ НА НЕАКТИВНЫХ И ДОБАВИТЬ ПРОВЕРКУ ПРИ АКТИВАЦИ!! ПОТОМ ПРОДУБЛИРОВАТЬ ПРИ ПОСТЕ!!!
					3 => 'Mechanics'
				);

				$this->load->model('licensing_model');
				$licensing = $this->licensing_model->get_lic_info_by_client_id($this->session->userdata('user_parent'));

				$role_users = $this->user_model->get_all_users_by_role($postdata['user_role']);
				$role_users = count($role_users);

				if (($role_users + 1) > $licensing[$roles[$postdata['user_role']]]) /*LICENSING CHECKS*/
					$header['msg'] = msg('danger', 'Important message about your license limitation<br>
										Please note you\'ve exceeded the maximum number of ' . $messageroles[$postdata['user_role']] . '  for your account.<br>
						 				In order to add more users to your account, please call us at 844.524.1212, or visit our website at <a target="_blank" href="https://www.firedoortracker.com">www.firedoortracker.com</a> for assistance.');
				else
				{
					$this->load->library('History_library');

					$adddata = array(
						'email'			=> $postdata['email'],
					    'FirstName'		=> $postdata['first_name'],
					    'LastName'		=> $postdata['last_name'],
					    'officePhone'	=> $postdata['officePhone'], 
					    'mobilePhone'	=> $postdata['mobilePhone'], 
					    'role'			=> $postdata['user_role'],
					    'parent'		=> $this->session->userdata('user_parent')
					);

					if ($postdata['user_role'] == 4)
						$adddata['parent'] = 0;

					if (isset($postdata['new_password']) && !empty($postdata['new_password']))
						$adddata['password'] = pass_crypt($postdata['new_password']);

					if (isset($postdata['password_generator']) && $postdata['password_generator']=='generate')			//if selected generate password - send it by email
					{
						$mail = send_mail(
							$adddata['email'], //to
							$this->lang->line('email_add_employeer_subject'), //subj
							sprintf($this->lang->line('email_add_employeer_body'),  $_SERVER['HTTP_HOST'], @$adddata['email'], @$postdata['new_password'])//body
						);
					}

					switch ($postdata['form_type'])
					{
						case 'add_employeer':
							$user = $this->resources_model->get_user_by_email($adddata['email']); //check if it email used
							if (!empty($user)) {
								$header['msg'] = msg('warning', 'This email allready used');
								break;
							}
							$newemp = $this->resources_model->add_employer($adddata);	//add new user
							$mail 	= TRUE;
							
							$this->history_library->saveUsers(array('line_id' => $newemp, 'new_val' => json_encode($adddata), 'type' => 'add'));

							$header['msg'] = msg('warning', 'Something wrong!');
							if ($newemp && $mail)
								$header['msg'] = msg('success', 'User successfully added');
						break;

						case 'edit_employeer':
							$this->history_library->saveUsers(array('line_id' => $postdata['user_id'], 'new_val' => json_encode($adddata), 'type' => 'edit'));

							$this->resources_model->update_employer_data($postdata['user_id'], $adddata);

							$header['msg'] = '<div class="alert alert-success alert-dismissable">User successfully updated</div>';
						break;
						default:
						break;
					}

					echo '<script type="text/javascript">location.replace("/user/employees");</script>
						<noscript><meta http-equiv="refresh" content="0; url=/user/employees"></noscript>';
					exit;
				}


			}
		}

		if (has_permission('Allow view users tab'))
		{
			$heading = array(
				'id',
				'First Name',
				'Last Name',
				array('data' => 'Office Phone'	, 'class' => 'not-mobile'),
				array('data' => 'Mobile Phone'	, 'class' => 'not-mobile'),
				array('data' => 'Email'			, 'class' => 'not-mobile'),
				array('data' => 'Role'			, 'class' => 'not-mobile')
			);

			if (has_permission('Allow Activate/Deactivate users'))
				$heading[] = array('data' => 'State'			, 'class' => 'not-mobile');

			$this->table->set_heading($heading);

			$users = $this->resources_model->get_all_user_data();

			if (!empty($users))
			{
				foreach ($users as $user)
				{
					$row = array($user['idUsers'], $user['firstName'], $user['lastName'], $user['officePhone'], $user['mobilePhone'], $user['email'], $user['role_name']);
					if (has_permission('Allow Activate/Deactivate users'))
						$row[] = ($user['deleted']>0) ? 'Disabled' : 'Active';
					else
						if ($user['deleted']>0) continue;

					$this->table->add_row($row);
				}
			}
			

			$tmpl = array ( 'table_open'  => '<table class="table table-striped table-hover table-bordered table-responsive table-condensed" width="100%">' );
			$this->table->set_template($tmpl); 
			$data['result_table'] = $this->table->generate(); 
		}

		$header['page_title'] = 'EMPLOYEES';

		//datatables
		$header['styles']  = addDataTable('css');
		$footer['scripts'] = addDataTable('js');

		//datepicker
		$header['styles']  .= '<link href="/js/bootstrap-datepicker/datepicker.css" rel="stylesheet">';
		$footer['scripts'] .= '<script type="text/javascript" src="/js/bootstrap-datepicker/bootstrap-datepicker.js"></script>';

		//password
		$footer['scripts'] .= '<script type="text/javascript" src="/js/bootstrap-show-password.min.js"></script>';

		$this->load->view('header', $header);
		$this->load->view('user/user_employeers', $data);
		$this->load->view('footer', $footer);
	}

	function ajax_employeer_delete()
	{
		verifyLogged();

		$this->load->model('resources_model');
		$this->load->model('service_model');

		if (!$employeer_id = $this->input->post('id')) return print('empty id');
		
		$user = $this->resources_model->get_employeer_info_by_employeer_id($employeer_id);

		$this->load->model('licensing_model');
		$licensing = $this->licensing_model->get_lic_info_by_client_id($user['parent']);

		$role_users = $this->user_model->get_all_users_by_role($user['role']);
		$role_users = count($role_users);
		
		$roles = array(
			1 => 'dir',
			2 => 'sv',
			3 => 'mech'
		);
		$messageroles = array(
			1 => 'Directors',
			2 => 'Supervisors',
			3 => 'Mechanics'
		);

		if ($user['deleted'] > 0)
		{
			if (!empty($licensing) && ($role_users + 1) > $licensing[$roles[$user['role']]])
			{
				echo '<!-- Show Warn Modal -->
				<div class="modal fade" id="ShowWarnModal" tabindex="-1" role="dialog" aria-labelledby="ShowWarnModal" aria-hidden="true">
					<div class="modal-dialog">
						<div class="modal-content">
							<div class="modal-header">
								<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
								<h4 class="modal-title text-center" id="myModalLabel">Important message about your license limitation</h4>
							</div>
							<div class="modal-body">
								Please note you\'ve exceeded the maximum number of ' . $messageroles[$user['role']] . '  for your account.<br>
		 					  In order to add more users to your account, please call us at 844.524.1212, or visit our website at <a target="_blank" href="https://www.firedoortracker.com">www.firedoortracker.com</a> for assistance.
							</div>
							<div class="modal-footer">
								<button type="button" class="btn btn-default" data-dismiss="modal">OK</button>
							</div>
						</div>
					</div>
				</div>';
				exit;
			}
			else
				$delnumber = '0';
		}
		else
			$delnumber = $employeer_id;

		if ($delnumber != $user['parent'])
		{
			if (!$this->resources_model->delete_employeer_by_id($employeer_id, $delnumber))  return print('can not delete employee by id');
			$this->service_model->delete_user_token($employeer_id);
		}

		return print('done');
	}

	function _get_buildings_by_parent($parent_id)
	{
		$buildings = $this->user_model->get_all_buildings_by_parent($parent_id);

		if (empty($buildings))
			return '';

		$result = '<ol class="dd-list">' . "\n";
		foreach ($buildings as $building)
		{
			$add = (($building['level']+1) < 5) ? '<a onclick="button_add_element_action(' . $building['idBuildings'] . ',' . ($building['level']+1) . ');return false;" class="btn btn-xs btn-default btn-plus"><span class="glyphicon glyphicon-plus"></span></a>' : '';
			$result .= '<li class="dd-item" data-id="' . $building['idBuildings'] . '">' . "\n";
			$result .= '<div class="dd-handle"><span class="glyphicon glyphicon-align-justify"></span><span class="label-text">' . $building['name'] . '</span> <small>(id:' . $building['idBuildings'] . ')</small></div>';
			$result .= (has_permission('Allow modify buildings tree')) ? $add.'<a onclick="editfield(this);return false;" class="btn btn-xs btn-default btn-pencil"><span class="glyphicon glyphicon-pencil"></span></a>
								<a onclick="deletefield(this);return false;" class="btn btn-xs btn-default btn-trash"><span class="glyphicon glyphicon-trash"></span></a>' . "\n" : '';
			$result .=  $this->_get_buildings_by_parent($building['idBuildings']);
			$result .= '</li>' . "\n";
		}
		$result .= '</ol>' . "\n";

		return $result;
	}

	function ajax_buildings_reorder()
	{
		verifyLogged();

		if (!$postdata = $this->input->post()) die('no post');

		if (!isset($postdata['buildings']) or empty($postdata['buildings']) or strpos($postdata['buildings'], 'jQuery') !== FALSE) 
			return;

		$all_elem_list = $this->user_model->get_all_buildings();

		// $order = array();

		foreach (json_decode($postdata['buildings']) as $building) {
			
			// $order = array();

			$issdata = $all_elem_list[$building->id];
			$issdata['parent'] = 0;
			$issdata['level'] = 0;
			$issdata['root'] = $building->id;

			// if (!isset($order[$issdata['parent']]))
			// 	$order[$issdata['parent']] = 0;
			// else
			// 	$order[$issdata['parent']]++;
			
			if (!isset($zeroorder))
				$zeroorder = 0;
			else
				$zeroorder++;

			// if ($issdata['buildingOrder'] != $order[$issdata['parent']]) //if saved as final but it is not or if changed order UPDATE building data
			// {
				$issdata['buildingOrder'] = $zeroorder;
				$this->user_model->update_building_data($issdata);
			// }

			if (isset($building->children)) {
				$order = $this->_submit_reorder($building->children, $all_elem_list, /*$order,*/ $building->id, $building->id, 1);
			}
		}

	}
	
	function _submit_reorder($elemtree, $all_elem_list, /*$order,*/ $parent_id, $root, $level)
	{
		foreach ($elemtree as $building) {
			
			$issdata = $all_elem_list[$building->id];
			$issdata['parent'] = $parent_id;
			$issdata['level'] = $level;
			$issdata['root'] = $root;

			// if (!isset($order[$issdata['parent']]))
			// 	$order[$issdata['parent']] = 0;
			// else
			// 	$order[$issdata['parent']]++;

			// if ($issdata['buildingOrder'] != $order[$issdata['parent']]) //if saved as final but it is not or if changed order UPDATE building data
			// {
			if (!isset($zeroorder))
				$zeroorder = 0;
			else
				$zeroorder++;
			
				$issdata['buildingOrder'] = $zeroorder;
				$this->user_model->update_building_data($issdata);
			// }

			if (isset($building->children)) {
				$order = $this->_submit_reorder($building->children, $all_elem_list, /*$order,*/ $building->id, $root, $level+1);
			}
		}
		return $order;
	}

	function ajax_get_building_by_id()
	{
		verifyLogged();

		if (!$id = $this->input->post('id')) return '';
		
		$data['building'] = $this->user_model->get_building_by_id($id);
		
		$this->load->view('user/user_buildings_edit', $data);
	}
	
	function ajax_delete_building()
	{
		verifyLogged();

		if (!$field_id = $this->input->post('id')) die(json_encode(array('status' => 'error')));
		
		$this->load->library('history_library');
	
		$this->history_library->saveBuildings(array('line_id' => $field_id, 'new_val' => json_encode(array('deleted' => $this->session->userdata('user_id'))), 'type' => 'edit'));

		$result = $this->user_model->delete_building_by_id($field_id);

		if (!$result) {
            echo json_encode(array('status' => 'error'));
            exit;
        }
		echo json_encode(array('status' => 'ok'));
	}

	function ajax_delete_aperture()
	{
		verifyLogged();

		if (!$aperture_id = $this->input->post('id')) return print('empty id');
		
		$this->load->model('resources_model');
		$this->load->library('history_library');

		$this->history_library->saveDoors(array('line_id' => $aperture_id, 'new_val' => json_encode(array('deleted' => $this->session->userdata('user_id'))), 'type' => 'edit'));

		if (!$this->resources_model->delete_aperture_by_id($aperture_id))  return print('can\'t delete door by id');

		return print('done');
	}

	function ajax_update_building()
	{
		verifyLogged();

		if (!$fielddata = $this->input->post()) die(json_encode(array('status' => 'error')));
		
		$this->load->library('History_library');
		
		$this->history_library->saveBuildings(array('line_id' =>  $fielddata['idBuildings'], 'new_val' => json_encode($fielddata), 'type' => 'edit'));
		
		$this->user_model->update_building_data($fielddata);

		echo json_encode(array('status' => 'ok'));
	}

	function ajax_city_autocomplpite($type = 'city')
	{
		verifyLogged();
		$this->load->model('address_model');

		if (!$text = $this->input->post('text')) die(json_encode(array('status' => 'error')));

		$cities = $this->address_model->get_cities_by_text($text);

		$result = array();
		foreach ($cities as $city) {
			$result[] = array(
				'name' 	=> $city['city'] . ', ' . $city['state'] . ', ' . $city['zip'],
				'city' 	=> $city['city'],
				'zip'	=> $city['zip'],
				'state'	=> $city['state'],
			);
		}
		
		echo json_encode($result);die;
	}

	function ajax_get_building_childs($parent_id, $selected = FALSE)
	{
		$this->load->model('resources_model');
		$builds = $this->resources_model->get_user_buildings_by_building_parent($parent_id);
		
		$out = '';
		foreach ($builds as $key => $value)
		{
			$sel = ($key == $selected) ? ' selected="selected"' : '';
			$out .= '<option' . $sel . ' value="' . $key . '">' . $value['name'] . '</option>';
		}
		echo $out;
	}

	function ajax_check_barcode()
	{
		if (!$barcode = $this->input->post('barcode')) return print('empty barcode');
		if (!$doorid = $this->input->post('doorid')) return print('empty doorid');

		$this->load->model('resources_model');
		$exist = array();
		$exist = $this->resources_model->get_aperture_info_by_barcode($barcode);
		if (!empty($exist) && ($doorid == '-' or $exist['idDoors'] != $doorid)) {
			echo 'exist';die();
		}
		echo 'ok'; die();
	}

	function ajax_check_lic_limit()
	{
		if (!$role = $this->input->post('role')) return print('empty role');

		$roles = array(
			1 => 'dir',
			2 => 'sv',
			3 => 'mech'
		);
		$messageroles = array(
			1 => 'Directors',
			2 => 'Supervisors',
			3 => 'Mechanics'
		);

		$this->load->model('licensing_model');
		$licensing = $this->licensing_model->get_lic_info_by_client_id($this->session->userdata('user_parent'));

		$role_users = $this->user_model->get_all_users_by_role($role);
		$role_users = count($role_users);
		
		if (($role_users + 1) > $licensing[$roles[$role]]) 
		{
			echo '<!-- Show Warn Modal -->
				<div class="modal fade" id="ShowWarnModal" tabindex="-1" role="dialog" aria-labelledby="ShowWarnModal" aria-hidden="true">
					<div class="modal-dialog">
						<div class="modal-content">
							<div class="modal-header">
								<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
								<h4 class="modal-title text-center" id="myModalLabel">Important message about your license limitation</h4>
							</div>
							<div class="modal-body">
								Please note you\'ve exceeded the maximum number of ' . $messageroles[$role] . '  for your account.<br>
		 					  In order to add more users to your account, please call us at 844.524.1212, or visit our website at <a target="_blank" href="https://www.firedoortracker.com">www.firedoortracker.com</a> for assistance.
							</div>
							<div class="modal-footer">
								<button type="button" class="btn btn-default" data-dismiss="modal">OK</button>
							</div>
						</div>
					</div>
				</div>';
		}
		else
			echo 'ok';
		exit;
	}
}

/* End of file resources.php */
/* Location: ./application/controllers/resources.php */