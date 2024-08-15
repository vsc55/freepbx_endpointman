<?php
	if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }
?>

<div class="panel panel-primary"> 
	<div class="panel-heading"> 
		<h3 class="panel-title"><?= _("List Packages Manager") ?></h3>
	</div> 
	<div class="panel-body">
		<!-- INI PANEL-BODY -->    
		<ul class="list-group" id="epm_config_manager_list_loading">
			<li class="list-group-item text-center bg-info"><i class="fa fa-spinner fa-pulse"></i>&nbsp; <?= _("Loading...")?></li>
		</ul>
		<div id="epm_config_manager_all_list_box"></div>
        <!-- END PANEL-BODY -->
	</div> 
</div> 