<div class="row">
	<div class="container">
		<div class="container">
			<ul class="nav nav-tabs nav-justified" id="user-submenu">
				<li><a href="/user/profile">Profile</a></li>
				<li><a href="/user/address">Address</a></li>
				<?php if (has_permission('Allow view buildings tree tab')): 			?><li><a href="/user/buildings">Buildings</a></li><?php endif; ?>
				<?php if (has_permission('Allow view doors tab')): 						?><li><a href="/user/doors">Doors</a></li><?php endif; ?>
				<?php if (has_permission('Allow view users tab')):						?><li><a href="/user/employees">Employees</a></li><?php endif; ?>
				<?php if (has_permission('Allow view clients tab')):					?><li><a href="/admin/clients">Clients</a></li><?php endif; ?>
				<?php if (in_array($this->session->userdata('user_role'), array(1,4))):	?><li><a href="/admin/rules">Rules</a></li><?php endif; ?>
				<?php if ($this->session->userdata('user_role') == 1):					?><li><a href="/licensing">Licensing</a></li><?php endif; ?>
			</ul>
		</div>
	</div>
</div>
<div class="row bottomline"></div>
<script type="text/javascript">
	$(document).ready(function() {
		ttl = location.href.replace('http://'+location.host,'');
		$('ul#user-submenu li').each(function()
		{
			lival = $(this).find('a');
			if (ttl == lival.attr('href'))
			{
				$(this).addClass('active');
			};
		})
	}) 
</script>