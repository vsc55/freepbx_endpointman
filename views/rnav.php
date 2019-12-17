<?php
	if (!defined('FREEPBX_IS_AUTH')) { exit('No direct script access allowed'); }

	$list = array(
		'epm_oss' 			=> _('About OSS Endpoint Manager'),
		'epm_advanced'		=> _('Settings'),
		'epm_devices'		=> _('Extension Mapping'),
		'epm_config'		=> _('Package Manager'),
		'epm_templates'		=> _('Template Manager'),
		'epm_placeholders'	=> _('Config File Placeholders')
	);
	$li = array();
		
	foreach ($list as $k => $v) {
		// If current user does not have access to this sub-menu then don't display it
		//

		if (is_object($_SESSION["AMP_user"]) && !$_SESSION["AMP_user"]->checkSection($k)) {
			continue;
		}
		$li[$k] = $v;	
	}
	
	function rnav_sub_menu_add_button($list, $name, $ico){
		if (array_key_exists($name, $list)){
			echo '<a href="?display=' . $name . '" class="btn list-group-item"><i class="fa ' . $ico . '"></i>&nbsp; ' . $list[$name] . '</a>';
		}
	}

	echo '<br/>';
	echo '<div id="toolbar-all-side list-group">';
	if (array_key_exists('epm_oss', $li)) 
	{
		echo '<span class="list-group-item"><h3>' . _("Open Source Information") . '</h3>';
		rnav_sub_menu_add_button($li, "epm_oss", "fa-info-circle");
		echo '</span>';
	}
	if (array_key_exists('epm_advanced', $li) or array_key_exists('epm_devices', $li)) 
	{
		echo '<span class="list-group-item"><h3>' . _("Endpoint Manager") . '</h3>';
		rnav_sub_menu_add_button($li, "epm_advanced", "fa-cog");
		rnav_sub_menu_add_button($li, "epm_devices", "fa-list");
		echo '</span>';
	}
	if (array_key_exists('epm_config', $li)) 
	{
		echo '<span class="list-group-item"><h3>' . _("Brands") . '</h3>';
		rnav_sub_menu_add_button($li, "epm_config", "fa-folder-open");
		echo '</span>';
	}
	if (array_key_exists('epm_templates', $li) or array_key_exists('epm_placeholders', $li)) 
	{
		echo '<span class="list-group-item"><h3>' . _("Advanced") . '</h3>';
		rnav_sub_menu_add_button($li, "epm_templates", "fa-list");
		rnav_sub_menu_add_button($li, "epm_placeholders", "fa-hashtag");
		echo '</span>';
	}
	echo '</div>';
	echo '<br/>';
?>