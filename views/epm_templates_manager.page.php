<?php
	if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }
	
	if ($_REQUEST['subpage'] != "manager") {
		echo _("Invalid page!");
		return;
	}
	
	
	$product_list = "SELECT * FROM endpointman_product_list WHERE id > 0";
	$product_list = sql($product_list,'getAll', DB_FETCHMODE_ASSOC);
	
	$mac_list = "SELECT * FROM endpointman_mac_list";
	$mac_list = sql($mac_list, 'getAll', DB_FETCHMODE_ASSOC);
	
	if((!$product_list) && (!$mac_list))
	{
		echo '<div class="alert alert-warning" role="alert">';
		echo '<strong>'._("Warning!").'</strong><br>'._("Welcome to Endpoint Manager. You have no products (Modules) installed, click").' <a href="config.php?display=epm_config"><b>'._("here").'</b></a> '._(" to install some");
		echo '</div>';
		return;
	}
	elseif(!$product_list)
	{
		echo '<div class="alert alert-warning" role="alert">';
		echo '<strong>'._("Warning!").'</strong>'.(" Thanks for upgrading to version 2.0! Please head on over to ").' <a href="config.php?display=epm_config"><b>'._("Brand Configurations/Setup").'</b></a> '._(" to setup and install phone configurations");
		echo '</div>';
		return;
	}
	unset ($product_list);
	unset ($mac_list);
	
	
	echo load_view(__DIR__.'/epm_templates/manager.views.grid.php', array('request' => $_REQUEST));
	echo load_view(__DIR__.'/epm_templates/manager.views.new.modal.php', array('request' => $_REQUEST));
	
 
/*
 <script type="text/javascript" charset="utf-8">
 $(function(){
 $("select#model_class").change(function(){
 $.ajaxSetup({ cache: false });
 $.getJSON("config.php?type=tool&quietmode=1&handler=file&module=endpointman&file=ajax_select.html.php&atype=model_clone",{id: $(this).val()}, function(j){
 var options = '';
 for (var i = 0; i < j.length; i++) {
 options += '<option value="' + j[i].optionValue + '">' + j[i].optionDisplay + '</option>';
 }
 $("#model_clone").html(options);
 $('#model_clone option:first').attr('selected', 'selected');
 })
 })
 })
 </script>
 */
?>