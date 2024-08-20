<?php
/**
 * Endpoint Manager Devices Manager File
 *
 * BLEEEECKKKKKK, There I just puked all over this file. That's basically what it looks like in terms of code.
 * It's a hacked-together POS written by me (Andrew) and I really need to fix it ASAP!! ah!
 *
 * @author Andrew Nagy
 * @license MPL / GPLv2 / LGPL
 * @package Provisioner
 *
 */

	if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }

	//Set opened variables
	$message = NULL;
	$error_message = NULL;
	$final = NULL;
	$button = NULL;
	$searched = NULL;
	$edit = NULL;
	$mode = NULL;
	
	$family_list = FreePBX::Endpointman()->eda->all_products();
	$full_device_list = FreePBX::Endpointman()->eda->all_devices();
	$ava_exts = FreePBX::Endpointman()->display_registration_list();
	
	if((empty($family_list)) && (empty($full_device_list))) 
	{
		echo '<div class="alert alert-warning" role="alert">';
		echo '<strong>'._("Warning!").'</strong>'.(" Welcome to Endpoint Manager. You have no products (Modules) installed, click").' <a href="config.php?display=epm_config"><b>'._("here").'</b></a> '._(" to install some");
		echo '</div>';
//$endpoint->global_cfg['new'] = 1;
	} 
	elseif(FreePBX::Endpointman()->configmod->get("srvip") == "") 
	{
		echo '<div class="alert alert-warning" role="alert">';
		echo '<strong>'._("Warning!").'</strong>'.(" Your Global Variables are not set! Please head on over to ").'<a href="config.php?display=epm_advanced"><b>'._("Advanced Settings").'</b></a>'._(" to setup your configuration");
		echo '</div>';
	} 
	elseif(empty($ava_exts)) {
		//$message = "You have no more devices or extensions avalible to configure!";
		//$no_add = TRUE;
	}

	
	
	
	
	
	
	
	
	
	

	//Refresh the list after processing
	$devices_list = $full_device_list;
	
	$i = 0;
	$list = array();
	$device_statuses = shell_exec(FreePBX::Endpointman()->configmod->get("asterisk_location")." -rx 'sip show peers'");
	
	$device_statuses = explode("\n", $device_statuses);
	$devices_status = array();
	foreach($device_statuses as $key => $data) {
		preg_match('/(\d*)\/[\d]*/i', $data, $extout);
		preg_match('/\b(?:\d{1,3}\.){3}\d{1,3}\b/i', $data, $ipaddress);
		if(!empty($extout[1])) {
			if(preg_match('/OK \(.*\)/i', $data)) {
				$devices_status[$extout[1]]['status'] = TRUE;
				$devices_status[$extout[1]]['ip'] = $ipaddress[0];
			} else {
				$devices_status[$extout[1]]['status'] = FALSE;
			}
		}
	}
	
	foreach($devices_list as $devices_row) {
		$line_list = FreePBX::Endpointman()->eda->get_lines_from_device($devices_row['id']);
		$list[$i] = $devices_row;
		$z = 0;
		if (($devices_row['template_id'] == 0) && (isset($devices_row['global_custom_cfg_data'])) ) {
			$list[$i]['template_name'] = "Custom-".$devices_row['mac'];
		} elseif((!isset($devices_row['custom_cfg_data'])) && ($devices_row['template_id'] == 0)) {
			$list[$i]['template_name'] = "N/A";
		} else {
			$sql = "SELECT name FROM endpointman_template_list WHERE id =".$devices_row['template_id'];
			$template_name = sql($sql,'getOne');
			$list[$i]['template_name'] = $template_name;
		}
		if (!$devices_row['enabled']) {
			$list[$i]['model'] = $devices_row['model']."<i>(Disabled)</i>";
		}
		$list[$i]['master_id'] = $i;
		foreach($line_list as $line_row) {
			$list[$i]['line'][$z]['ext'] = $line_row['ext'];
			$list[$i]['line'][$z]['line'] = $line_row['line'];
			$list[$i]['line'][$z]['description'] = $line_row['description'];
			$list[$i]['line'][$z]['luid'] = $line_row['luid'];
			$list[$i]['line'][$z]['ipei'] = $line_row['ipei'];
			$list[$i]['line'][$z]['master_id'] = $i;
			$z++;
		}
		$ext = $list[$i]['line'][0]['ext'];
	
		$list[$i]['status']['status'] = isset($devices_status[$ext]['status']) ?$devices_status[$ext]['status'] : FALSE;
		$list[$i]['status']['ip'] = isset($devices_status[$ext]['ip']) ? $devices_status[$ext]['ip'] : FALSE;
		$list[$i]['status']['port'] = '';
		$i++;
	}
	
	$unknown_list = FreePBX::Endpointman()->eda->all_unknown_devices();
	
	foreach($unknown_list as $row) {	#Displays unknown phones in the database with edit and delete buttons
		$list[$i] = $row;
	
		$brand_info = FreePBX::Endpointman()->get_brand_from_mac($row['mac']);
	
		$list[$i]['name'] = $brand_info['name'];
		$list[$i]['template_name'] = "N/A";
		$list[$i]['model'] = _("Unknown");
		$i++;
	}
	
$amp_send['AMPDBUSER'] = $amp_conf['AMPDBUSER'];
$amp_send['AMPDBPASS'] = $amp_conf['AMPDBPASS'];
$amp_send['AMPDBNAME'] = $amp_conf['AMPDBNAME'];
	
	$sql = "SELECT DISTINCT endpointman_product_list.* FROM endpointman_product_list, endpointman_model_list WHERE endpointman_product_list.id = endpointman_model_list.product_id AND endpointman_model_list.hidden = 0 AND endpointman_model_list.enabled = 1 AND endpointman_product_list.hidden != 1 AND endpointman_product_list.cfg_dir !=  ''";
	$template_list = sql($sql, 'getAll', \PDO::FETCH_ASSOC);
	$i = 1;
	$product_list = array();
	$product_list[0]['value'] = 0;
	$product_list[0]['text'] = "";
	foreach($template_list as $row) {
		$product_list[$i]['value'] = $row['id'];
		$product_list[$i]['text'] = $row['short_name'];
		$i++;
	}
	
	$sql = "SELECT DISTINCT endpointman_model_list.* FROM endpointman_product_list, endpointman_model_list WHERE endpointman_product_list.id = endpointman_model_list.product_id AND endpointman_model_list.hidden = 0 AND endpointman_model_list.enabled = 1 AND endpointman_product_list.hidden != 1 AND endpointman_product_list.cfg_dir !=  ''";
	$template_list = sql($sql, 'getAll', \PDO::FETCH_ASSOC);
	$i = 1;
	$model_list = array();
	$model_list[0]['value'] = 0;
	$model_list[0]['text'] = "";
	foreach($template_list as $row) {
		$model_list[$i]['value'] = $row['id'];
		$model_list[$i]['text'] = $row['model'];
		$i++;
	}
	
	
	
	
	
/*
	
$endpoint->tpl->assign("list", $list);

$serv_address = !empty($endpoint->global_cfg['nmap_search']) ? $endpoint->global_cfg['nmap_search'] : $_SERVER["SERVER_ADDR"].'/24';

$endpoint->tpl->assign("netmask", $serv_address);
$endpoint->tpl->assign("web_var", "?type=$type");

$ma = $endpoint->models_available();

if($ma != FALSE) {
	$endpoint->tpl->assign("models_ava", $ma);
}

$endpoint->tpl->assign("product_list", $product_list);
$endpoint->tpl->assign("model_list", $model_list);
$endpoint->tpl->assign("display_ext", $endpoint->display_registration_list());
$endpoint->tpl->assign("brand_ava", $endpoint->brands_available());
$endpoint->tpl->assign("unmanaged", $final);
$endpoint->tpl->assign("button", $button);
$endpoint->tpl->assign("searched", $searched);
$endpoint->tpl->assign("edit", $edit);
$endpoint->tpl->assign("amp_conf_serial", base64_encode(serialize($amp_send)));
$endpoint->tpl->assign("mode", $mode);
	
$edit_row['id'] = isset($edit_row['id']) ? $edit_row['id'] : '0';
$endpoint->tpl->assign("edit_id", $edit_row['id']);

*/


/*
if(isset($final)) {
	$_SESSION['dev_cache'] = base64_encode(serialize($final));
}
*/




/*
if (isset($mode) && ($mode == "EDIT")) {
	$ma = $endpoint->models_available($edit_row['model_id'],$edit_row['brand_id']);
	if($ma != FALSE) {
		$endpoint->tpl->assign("mac", $edit_row['mac']);
		$endpoint->tpl->assign("name", $edit_row['name']);
		$b=0;
		foreach($edit_row['line'] as $data) {
			$edit_row['line'][$data['line']]['reg_list'] = $endpoint->display_registration_list($data['luid']);
			$edit_row['line'][$data['line']]['line_list'] = $endpoint->linesAvailable($data['luid']);
			$b++;
		}
		if($b == 1) {
			$endpoint->tpl->assign("disabled_delete_line", 1);
		}
		$endpoint->tpl->assign("line_list_edit", $edit_row['line']);
	
		$endpoint->tpl->assign("brand_id", $edit_row['brand_id']);
		$endpoint->tpl->assign("models_ava", $ma);

		$endpoint->tpl->assign("display_templates", $endpoint->display_templates($edit_row['product_id'],$edit_row['template_id']));
	
	} else {
		$message = _("You have disabled/removed all models that correspond to this brand. Please enable them in 'Brand Configurations/Setup' before trying to edit this phone");
		$endpoint->tpl->assign("mode", NULL);
	}
}
*/
	
		
	
	
	
	
	//echo load_view(__DIR__.'/epm_templates/main.views.grid.php', array('request' => $_REQUEST));
	//echo load_view(__DIR__.'/epm_templates/main.views.new.modal.php', array('request' => $_REQUEST));
?>


<h3><?php echo _('Device')?></h3>
<table align='center' width='97%'>
	<thead>
		<tr>
			<th width="7%""></th>
			<th width="13%" align='center'><?php echo _('MAC Address')?></th>
			<th width="13%" align='center'><?php echo _('Brand')?></th>
			<th width="10%" align='center'><?php echo _('Model of Phone')?></th>
			<th width="10%" align='center'><?php echo _('Line')?></th>
			<th width="19%" align='center'><?php echo _('Extension Number')?></th>
			<th width="15%" align='center'><?php echo _('Template')?></th>
			<th width="6%"></th>
			<th width="7%"></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td align='center' width='2%'>&nbsp;</td>
    		<td align='center'>
				{$mac}<input name='mac' type='text' tabindex='1' size="17" maxlength="17">
			</td>
			<td align='center'>
				<label>
				{$name}
				<select name="brand_list" id="brand_edit">		
				<?php
				$brand_ava = FreePBX::Endpointman()->brands_available();
				foreach ($brand_ava as $row) 
				{
					echo '<option value="'.$row['value'].'" '.(isset($row['selected']) ? "selected" : "").'>'.$row['text'].'</option>';
				} 	
				?>
				</select>
				</label>
			</td>
			<td align='center'>
				<label>
					<input name="display" type="hidden" value="epm_devices">
					{loop name="models_ava"}
					<select name="model_list" id="model_new">
						<option value="{$value.value}" {if condition="!empty($value.selected)"}selected{/if}>{$value.text}</option>
					</select>
					{else}
					<select name="model_list" id="model_new"><option></option></select>
				</label>
			</td>
			<td align='center'>
				<label>
					<select name="line_list" id="line_list" >
						<option></option>
					</select>
				</label>
			</td>
			<td align='center'>
				<label>
				{loop name="display_ext"}
				<select name="ext_list" id="select">
            		<option value="{$value.value}">{$value.text}</option>
				</select>
				</label>
    		</td>
    
    		<td align='center'>
    			<label>  
    				<div id="demo">
    					{loop name="display_templates"}
    					<select name="template_list" id="template_list">
                			<option value="{$value.value}" {if condition="isset($value.selected)"}selected{/if}>{$value.text}</option>
            			</select>
            			<a href="#" onclick="return popitup('config.php?display=epm_config&amp;quietmode=1&amp;handler=file&amp;file=popup.html.php&amp;module=endpointman&amp;pop_type=edit_template&amp;edit_id={$edit_id}', 'Template Editor', '{$edit_id}')"><i class='icon-pencil'></i></a>
            		</div>
        		</label>
        	</td>
    		<td align='center'>
        		<button type='submit' name='button_save' onclick="edit_device('edit',{$edit_id},'button_save');"><i class='icon-save blue'></i> <?php echo _('Save')?></button>
				{else}
        		<button type='button' name='button_add' onclick="add_device();"><i class='icon-plus success'></i>&nbsp;<?php echo _('Add')?></button>
    		</td>

			<td align='center'>
				{if condition="$mode != 'EDIT'"}
				<button type='reset'><i class='icon-rotate-left red'></i> <?php echo _('Reset')?>{/if}
			</td>
		</tr>
			
			
	
		<!-- 
		{loop name="line_list_edit"}
		<tr>
			<td align='center' width='2%'>&nbsp;</td>
			<td align='center'></td>
    		<td align='center'></td>
    		<td align='center'></td>
    		<td align='center'>
        		<label>
        			{loop name="value.line_list"}
            		<select name="line_list_{$value.luid}" id="line_list" >
                		<option value="{$value.value}" {if condition="isset($value.selected)"}selected{/if}>{$value.text}</option>
            		</select>
        		</label>
        	</td>
			<td align='center'>
				<label>
					{loop name="value.reg_list"}
					<select name="ext_list_{$value.luid}" id="select">
            			<option value="{$value.value}" {if condition="isset($value.selected)"}selected{/if}>{$value.text}</option>
        			</select>
        		</label>
    		</td>
    		<td align='center'></td>
    		<td align='center'>
    			{if condition="!isset($disabled_delete_line)"}
    			<div id="demo"><a href="#" onclick="edit_device('edit',{$value.luid},'delete');"><i class="red icon-remove" title="Delete Line from Device to the Left"></i></a></div>
    			{/if}
    		</td>
    		<td align='center'></td>
		</tr>
		{/loop}
		 -->
	</tbody>
</table>





<!-- 
{if condition="$mode == 'EDIT'"}
<td align='center'><div id="demo"><a href="#" onclick="edit_device('edit',{$edit_id},'add_line_x');"><i class="green icon-plus" title="Add a Line to the device currently being edited"></i></a></div></td>
 -->





<?php 



return;
?>




{if condition="$searched == 1"}
<table width='90%' align='center'>
    <tr>
        <td align='center'>&nbsp;</td>
        <td align='center'>&nbsp;</td>
        <td align='center'>&nbsp;</td>
        <td colspan="3" align='center'><h3><?php echo _('Unmanaged Extensions')?></h3></td>
        <td align='center'>&nbsp;</td>
        <td align='center'>&nbsp;</td>
        <td align='center'>&nbsp;</td>
    </tr>
	{if condition="is_array($unmanaged)"}
    <form id="unmanaged" action='' method='POST'>
		{loop name="unmanaged"}
        <input name="mac_{$value.id}" type="hidden" value="{$value.mac_strip}">
        <input name="brand_{$value.id}" type="hidden" value="{$value.brand_id}">
        <tr id="{$value.mac_strip}">
            <td align='center' width='20'><input type="checkbox" name="add[]" value="{$value.id}"></td>
            <td align='center' width='148'>{$value.mac_strip}<br />({$value.ip})</td>
            <td width="188" align='center'>{$value.brand}</td>
            <td width="216" align='center'>

                <select name="model_list_{$value.id}">

	    {loop name="value.list"}

                    <option value="{$value.id}">{$value.model}</option>

	      {/loop}

                </select></td>
            <td width="141" align='center'>

            </td>

            <td width="276" align='center'>
                <select name="ext_list_{$value.id}" id="ext">

	    {loop name="display_ext"}

                    <option value="{$value.value}">{$value.text}</option>

	      {/loop}

                </select></td>
            <td align='center' width='220'>&nbsp;</td>
            <td align='center' width='154'></td>
            <td align='center' width='73'>&nbsp;</td>
        </tr>
		{/loop}
        <tr>
        <table width="90%" border="0" cellspacing="0" cellpadding="0">
            <tr>
                <td><center><input type="submit" name="button_add_selected_phones" onclick="add_searched_devices();" value="<?php echo _('Add Selected Phones')?>"><br /><input type="checkbox" name="reboot_sel">Reboot Phones</center></td>
            </tr>
        </table>
        </tr>
    </form>
	{/if}
</table>
{/if}










<form id="managed" action='config.php?type=tool&amp;display=epm_devices' method='POST'>
<h3><?php echo _('Current Managed Extensions')?></h3>

<button type="button" id="selecter"   style="zoom: 0.8" onclick="togglePhones(true)"  ><i class="info icon-check"       id="toggle_all_phones_on"  title="Click to Select All Phones"  ></i> Select All</button>
<button type="button" id="deselecter" style="zoom: 0.8" onclick="togglePhones(false)" ><i class="info icon-check-empty" id="toggle_all_phones_off" title="Click to Deselect All Phones"></i> Deselect All</button>
<button type="button" id="expander"   style="zoom: 0.8" onclick="toggleDisplayAll('expand')"  ><i class="info icon-chevron-down" id="toggle_all_img" title="Click to Expand All Line Information"  ></i> Expand All</button>
<button type="button" id="collapser"  style="zoom: 0.8" onclick="toggleDisplayAll('collapse')"><i class="info icon-chevron-up"   id="toggle_all_img" title="Click to Collapse All Line Information"></i> Collapse All</button>
<table width='97%' align='center' id='devList'>
    <thead>
    <tr class="headerRow">
        <th width="7%""></th>
        <th width="13%" align='center'><?php echo _('MAC Address')?></th>
        <th width="13%" align='center'><?php echo _('Brand')?></th>
        <th width="10%" align='center'><?php echo _('Model of Phone')?></th>
        <th width="10%" align='center'><?php echo _('Line')?></th>
        <th width="19%" align='center'><?php echo _('Extension Number')?></th>
        <th width="15%" align='center'><?php echo _('Template')?></th>
        <th width="6%"><?php echo _('Edit')?></th>
        <th width="7%"><?php echo _('Delete')?></th>
    </tr>
    </thead>
    <tbody>
    
    
    $list
    
	{loop name="list"}
        <tr class="headerRow">
            <td align='center' width="7%"><i class="icon-off icon-large {if condition="$value.status.status === TRUE"}green{else}red{/if}" alt="{$value.status.ip}:{$value.status.port}"></i><input type="checkbox" class="device" name="selected[]" value="{$value.id}"></td>
            <td align='center' width='13%'>{$value.mac}</td>
            <td width="13%" align='center'>{$value.name}</td>
            <td width="10%" align='center'>{$value.model}</td>
            <td width="10%" align='center'><div id="demo"><a><i class="info icon-chevron-down" id="img2rowGroup{$value.master_id}" onclick="toggleDisplay(document.getElementById('devList'),'rowGroup{$value.master_id}')" title="Click to Expand Line Information"></i></a></div></td>
            <td width="19%" align='center'><div id="demo"><a><i class="info icon-chevron-down" id="img3rowGroup{$value.master_id}" onclick="toggleDisplay(document.getElementById('devList'),'rowGroup{$value.master_id}')" title="Click to Expand Line Information"></i></a></div></td>
            <td align='center' width='15%'><a href="#" onclick="submit_stype('edit',{$value.id});">{$value.template_name}</a></td>
            <td align='center' width='6%'><div id="demo"><a href="#" onclick="submit_wtype('edit',{$value.id});"><i class='blue icon-pencil' alt='<?php echo _('Edit')?>' title="Edit phone"></i></a></div></td>
            <td align='center' width='7%'><div id="demo"><a href="#" onclick="delete_device({$value.id});"><i class='red icon-trash' alt='<?php echo _('Delete')?>' title="Delete phone"></i></a></div></td>
        </tr>
        
        {loop name="value.line"}
        <tr class="rowGroup{$value.master_id} toggle_all" id="{$value.master_id}" style="display:none;">
            <td align='center' width='7%' ></td>
            <td align='center' width='13%'></td>
            <td width="13%" align='center'></td>
            <td width="10%" align='center'></td>
            <td width="10%" align='center'>{$value.line}</td>
            <td width="19%" align='center'>{$value.ext} - {$value.description}</td>
            <td align='center' width='15%'></td>
            <td align='center' width='6%'></td>
            <td align='center' width='7%'><div id="demo"><a href="#" onclick="submit_wtype('delete_line',{$value.luid});"><i class="red icon-remove" alt='<?php echo _('Delete')?>' title='Delete Line'></i></a></div></td>
        </tr>
        {/loop}
        
	{/loop}
	
    </tbody>
</table>

<h4><?php echo _('Selected Phone(s) Options')?></h4>
<p><button style="width: 100px" type="submit" style="vertical-align:middle" name="button_delete_selected_phones" onclick="managed_options('delete_selected_phones');"><i class="icon-trash red"></i> <?php echo _('Delete')?></button> <?php echo ('Delete Selected Phones')?></p>
<p><button style="width: 100px" type="submit" name="button_rebuild_selected" onclick="managed_options('rebuild_selected_phones');"><i class="icon-refresh green"></i> <?php echo _('Rebuild')?></button> <?php echo _('Rebuild Configs for Selected Phones')?> (<label><input type="checkbox" name="reboot"><font size="-1">Reboot Phones</font></label>)</p>
<p><button style="width: 100px" style="vertical-align:middle" type="submit" name="button_update_phones" onclick="managed_options('change_brand');"><i class='blue icon-random'></i> <?php echo _('Update')?></button>
<?php echo _('Change Selected Phones to')?>&nbsp;
<select name="brand_list_selected" id="brand_list_selected"><option><?php echo _('Brand')?></option>{loop name="brand_ava"}<option value="{$value.value}" {if condition="isset($value.selected)"}selected{/if}>{$value.text}</option>{/loop}</select> <select name="model_list_selected" id="model_list_selected"><option><?php echo _('Model')?></option></select> (<label><input type="checkbox" name="reboot_change"><font size="-1">Reboot Phones</font></label>)</p>
</form>











<h4><?php echo _('Global Phone Options')?></h4>
{if condition="$no_add == FALSE"}
<p>
<form id='go' action='config.php?type=tool&amp;display=epm_devices' method='POST'>
  <button style="width: 100px" type="Submit" name="button_go" id="button_go" onclick="find_devices();"><i class='icon-search blue'></i> <?php echo _('Search')?></button>
  <?php echo _('Search for new devices in netmask')?>
  <input name="netmask" type="text" value="{$netmask}">
  (<label><input name="nmap" type="checkbox" value="1" checked><font size="-1"><?php echo _('Use NMAP')?></font></label>)
</form>
</p>
{/if}

<p><form action='' name='globalmanaged' id='globalmanaged' method='POST'>
<button style="width: 100px" type='Submit' name='button_rebuild_configs_for_all_phones' onclick="submit_global('rebuild_configs_for_all_phones');"><i class='icon-refresh green'></i> <?php echo _('Rebuild')?></button> <?php echo _('Rebuild Configs for All Phones')?>&nbsp;(<label><input type="checkbox" name="reboot"><font size="-1">Reboot Phones</font></label>)
</form></p>

<p><form action='' name='globalmanaged2' id='globalmanaged2' method='POST'>
<button style="width: 100px" type='Submit' name='button_reboot_this_brand' onclick="submit_global2('reboot_brand');"><i class='icon-off red'></i> <?php echo _('Reboot')?></button> <?php echo _('Reboot This Brand')?> <select name="rb_brand">{loop name="brand_ava"}<option value="{$value.value}">{$value.text}</option>{/loop}</select>
</form></p>

<p><form action='' name='globalmanaged3' id='globalmanaged3' method='POST'>
<button style="width: 100px" type="submit" name="button_rebuild_reboot" onclick="submit_global3('rebuild_reboot');"><i class="icon-random blue"></i> <?php echo _('Configure')?></button>
<?php echo _('Reconfigure all')?> (products) <select name="product_select" id="product_select">{loop name="product_list"}<option value="{$value.value}">{$value.text}</option>{/loop}</select> <?php echo _('with')?>
<label><select name="template_selector" id="template_selector"><option></option></select></label>
(<label><input type="checkbox" name="reboot"><font size='-1'>Reboot Phones</font></label>)
</form></p>

<p><form action='' name='globalmanaged4' id='globalmanaged4' method='POST'>
<button style="width: 100px" type="submit" name="button_rebuild_reboot" onclick="submit_global3('mrebuild_reboot');"><i class="icon-random blue"></i> <?php echo _('Configure')?></button>
<?php echo _('Reconfigure all')?> (models) <select name="model_select" id="model_select">{loop name="model_list"}<option value="{$value.value}">{$value.text}</option>{/loop}</select> <?php echo _('with')?>
<label><select name="model_template_selector" id="model_template_selector"><option></option></select></label>
(<label><input type="checkbox" name="reboot"><font size='-1'>Reboot Phones</font></label>)
</form></p>

</form>

{if condition="!isset($disable_help)"}
<script>
    $("#demo img[title]").tooltip();
</script>
{/if}
<script>
    $("#collapser").hide();
</script>







<!--<hr>
{include="global_footer"}
<h6 align='center'>The Endpoint Configuration Manager is currently maintained by <a target="_blank" href=http://www.andrewsnagy.com/>Andrew Nagy</a>
<br/><?php echo _("The Endpoint Configuration Manager was originally written by")?> <a target="_blank" href=http://www.mymcs.us>Ed Macri</a>, <a target="_blank" href=http://www.cohutta.com> John Mullinix.</a> and <a target="_blank" href=http://www.colsolgrp.com>Tony Shiffer</a>
<br/>Endpoint Configuration Manager uses code from the MPL licensed project Provisioner.net at <a href="http://provisioner.net" target="_blank">http://www.provisioner.net</a> co-written by Darren Schreiber &amp Andrew Nagy
<br> <?php echo _("The project is maintained at")?> 
<a target="_blank" href="http://projects.colsolgrp.net/projects/show/endpointman"> CSG Software Projects.</a>
-->











<?PHP






return;










/*



<script type="text/javascript" charset="utf-8">
$(function(){
	$("select#brand_edit").change(function(){
		$.ajaxSetup({ cache: false });
		$.getJSON("config.php?type=tool&quietmode=1&handler=file&module=endpointman&file=ajax_select.html.php&atype=model",{id: $(this).val()}, function(j){
			var options = '';
			for (var i = 0; i < j.length; i++) {
				options += '<option value="' + j[i].optionValue + '">' + j[i].optionDisplay + '</option>';
			}
			$("#model_new").html(options);
			$('#model_new option:first').attr('selected', 'selected');
			$("#template_list").html('<option></option>');
			$('#template_list option:first').attr('selected', 'selected');
		})
	})
})
$(function(){
	$("select#product_select").change(function(){
		$.ajaxSetup({ cache: false });
		$.getJSON("config.php?type=tool&quietmode=1&handler=file&module=endpointman&file=ajax_select.html.php&atype=template",{id: $(this).val()}, function(j){
			var options = '';
			for (var i = 0; i < j.length; i++) {
				options += '<option value="' + j[i].optionValue + '">' + j[i].optionDisplay + '</option>';
			}
			$("#template_selector").html(options);
			$('#template_selector option:first').attr('selected', 'selected');
		})
	})
})
$(function(){
	$("select#model_select").change(function(){
		$.ajaxSetup({ cache: false });
		$.getJSON("config.php?type=tool&quietmode=1&handler=file&module=endpointman&file=ajax_select.html.php&atype=mtemplate",{id: $(this).val()}, function(j){
			var options = '';
			for (var i = 0; i < j.length; i++) {
				options += '<option value="' + j[i].optionValue + '">' + j[i].optionDisplay + '</option>';
			}
			$("#model_template_selector").html(options);
			$('#model_template_selector option:first').attr('selected', 'selected');
		})
	})
})
$(function(){
	$("select#model_new").change(function(){
		$.ajaxSetup({ cache: false });
		$.getJSON("config.php?type=tool&quietmode=1&handler=file&module=endpointman&file=ajax_select.html.php&atype=template2",{id: $(this).val()}, function(j){
			var options = '';
			for (var i = 0; i < j.length; i++) {
				options += '<option value="' + j[i].optionValue + '">' + j[i].optionDisplay + '</option>';
			}
			$("#template_list").html(options);
			$('#template_list option:first').attr('selected', 'selected');
		}),
		$.ajaxSetup({ cache: false });
		$.getJSON("config.php?type=tool&quietmode=1&handler=file&module=endpointman&file=ajax_select.html.php&atype=lines",{id: $(this).val()}, function(j){
			var options = '';
			for (var i = 0; i < j.length; i++) {
				options += '<option value="' + j[i].optionValue + '">' + j[i].optionDisplay + '</option>';
			}
			$("#line_list").html(options);
			$('#line_list option:first').attr('selected', 'selected');
		})
	})
})

$(function(){
	$("select#brand_list_selected").change(function(){
		$.ajaxSetup({ cache: false });
		$.getJSON("config.php?type=tool&quietmode=1&handler=file&module=endpointman&file=ajax_select.html.php&atype=model",{id: $(this).val()}, function(j){
			var options = '';
			for (var i = 0; i < j.length; i++) {
				options += '<option value="' + j[i].optionValue + '">' + j[i].optionDisplay + '</option>';
			}
			$("#model_list_selected").html(options);
			$('#model_list_selected option:first').attr('selected', 'selected');
		})
	})
})
function toggleDisplayAll(mode) {
	$(".toggle_all").each(function (i) {
		var rowClass = 'rowGroup'+$(this).attr('id');
		if(mode == 'expand') {
			$("#img2"+rowClass).removeClass().addClass('info icon-chevron-up');
			$("#img3"+rowClass).removeClass().addClass('info icon-chevron-up');
			$('.'+rowClass).show();
			$('#expander').hide();
			$('#collapser').show();
		} else {
			$("#img2"+rowClass).removeClass().addClass('info icon-chevron-down');
			$("#img3"+rowClass).removeClass().addClass('info icon-chevron-down');
			$('.'+rowClass).hide();
			$('#expander').show();
			$('#collapser').hide();
		}
	});
}

function toggleDisplay(tbl, rowClass) {
	if($("#img2"+rowClass).hasClass("icon-chevron-down")) {
		$("#img2"+rowClass).removeClass().addClass('info icon-chevron-up');
		$("#img3"+rowClass).removeClass().addClass('info icon-chevron-up');
		$('.'+rowClass).show();
	} else {
		$("#img2"+rowClass).removeClass().addClass('info icon-chevron-down');
		$("#img3"+rowClass).removeClass().addClass('info icon-chevron-down');
		$('.'+rowClass).hide();
	}
}
function addTableRow(jQtable){
	jQtable.each(function(){
		var $table = $(this);
		// Number of td's in the last table row
		var n = $('tr:last td', this).length;
		var tds = '<tr>';
		for(var i = 0; i < n; i++){
			tds += '<td>&nbsp;</td>';
		}
		tds += '</tr>';
		if($('tbody', this).length > 0){
			$('tbody', this).append(tds);
		}else {
			$(this).append(tds);
		}
	});
}
function add_device() {
	$('#adding').append('<input type="hidden" name="sub_type" value="add"/>');
//
//	$('#adding').ajaxSubmit(function(responseText, statusText, xhr, $form) {
//	//ffalert('status: ' + statusText + '\n\nresponseText: \n' + responseText + '\n\nThe output div should have already been updated with the responseText.');
//	$(this).resetForm();
//	$('#devList tr:last').after('<tr><td>stuff</td></tr>');
//	return false;
//	});
//	
	//return false //to prevent normal browser submit and page navigation
	document.adding.submit();
}
function find_devices() {
	$('#spinner').toggle();
	$('#go').append('<input type="hidden" name="sub_type" value="go"/>');
	document.go.submit();
}
function managed_options(type) {
	$('#managed').append('<input type="hidden" name="sub_type" value="'+ type +'"/>');
	document.managed.submit();
}
function add_searched_devices() {
	$('#unmanaged').append('<input type="hidden" name="sub_type" value="add_selected_phones"/>');
	//$('#devList tr:last').after('<tr><td>stuff</td></tr>');
	document.unmanaged.submit();
}
function submit_global(type) {
	$('#globalmanaged').append('<input type="hidden" name="sub_type" value="'+ type +'"/>');
	document.globalmanaged.submit();
}
function submit_global2(type) {
	$('#globalmanaged2').append('<input type="hidden" name="sub_type" value="'+ type +'"/>');
	document.globalmanaged2.submit();
}
function submit_global3(type) {
	$('#globalmanaged3').append('<input type="hidden" name="sub_type" value="'+ type +'"/>');
	document.globalmanaged3.submit();
}
function submit_wtype(type,id) {
	$('#adding').append('<input type="hidden" name="edit_id" value="'+ id +'"/><input type="hidden" name="sub_type" value="'+ type +'"/>');
	document.adding.submit();
}
function edit_device(type,id,sub) {
	$('#adding').append('<input type="hidden" name="edit_id" value="'+ id +'"/><input type="hidden" name="sub_type" value="'+ type +'"/><input type="hidden" name="sub_type_sub" value="'+ sub +'"/>');
	document.adding.submit();
}
function delete_device(id) {
	if (confirm('Are you sure you want to delete this device?')) {
		submit_wtype('delete_device',id);
	}
}
function popitup(url, name, id) {
	if(id != '0') {
		newwindow=window.open(url + '&model_list=' + document.getElementById('model_new').value + '&template_list=' + document.getElementById('template_list').value + '&rand=' + new Date().getTime(),'name2','height=1000,width=950,scrollbars=yes,location=no');
		if (window.focus) {newwindow.focus()}
		return false;
	}
}

function submit_stype(type,id) {
	newwindow=window.open('config.php?display=epm_config&quietmode=1&handler=file&file=popup.html.php&module=endpointman&pop_type=edit_specifics&edit_id=' + id + '&rand=' + new Date().getTime(),'name2','height=700,width=750,scrollbars=yes,location=no');
	if (window.focus) {newwindow.focus()}
	return false;
}
function togglePhones(action) {
	$('#devList .device').prop('checked', action);
}
</script>
*/




















































if((isset($_REQUEST['sub_type'])) AND ((!$no_add) OR (($_REQUEST['sub_type'] == "edit")))) {
    $sub_type = $_REQUEST['sub_type'];
    if(isset($_REQUEST['sub_type_sub'])) {
        $sub_type_sub = $_REQUEST['sub_type_sub'];
    } else {
        $sub_type_sub = "";
    }
} else {
    $sub_type = "";
}

switch ($sub_type) {
    //Edit Mode
	
	
	
    case "edit":
        $mode = "EDIT";
        switch ($sub_type_sub) {
            case "add_line_x":
                $_REQUEST['id'] = $_REQUEST['edit_id'];
                $mac_id = $endpoint->add_line($_REQUEST['id']);
                break;
            case "button_edit":
                if(empty($_REQUEST['edit_id'])) {
                    $endpoint->error['page:devices_manager'] = _("No Device Selected to Edit!")."!";
                } else {
                    $template_editor = TRUE;
                    $sql = "UPDATE  endpointman_mac_list SET  model =  '".$_REQUEST['model_list']."' WHERE  id =".$_REQUEST['edit_id'];
                    $endpoint->eda->sql($sql);
                    if ($_REQUEST['template_list'] == 0) {
                        $endpoint->edit_template_display($_REQUEST['edit_id'],1);
                    } else {
                        $endpoint->edit_template_display($_REQUEST['template_list'],0);
                    }
                }
                break;
            case "button_save":

                $sql = 'SELECT * FROM endpointman_line_list WHERE mac_id = '. $_REQUEST['edit_id'];

                $lines_list = $endpoint->eda->sql($sql,'getAll',\PDO::FETCH_ASSOC);

                foreach($lines_list as $row) {
                    $sql = "SELECT description FROM devices WHERE id = ".$_REQUEST['ext_list_'.$row['luid']];
                    $name = $endpoint->eda->sql($sql,'getOne');

                    $sql = "UPDATE endpointman_line_list SET line = '".$_REQUEST['line_list_'.$row['luid']]."', ext = '".$_REQUEST['ext_list_'.$row['luid']]."', description = '".$endpoint->eda->escapeSimple($name)."' WHERE luid =  ". $row['luid'];
                    $endpoint->eda->sql($sql);
                }

                $sql = "UPDATE endpointman_mac_list SET template_id = '".$_REQUEST['template_list']."', model = '".$_REQUEST['model_list']."' WHERE id =  ". $_REQUEST['edit_id'];
                $endpoint->eda->sql($sql);


                $row = $endpoint->get_phone_info($_REQUEST['edit_id']);
                $endpoint->prepare_configs($row);

                $endpoint->message['edit_save'] = _("Saved")."!";
                $mode = NULL;
                break;
            case "delete":
                $sql = 'SELECT mac_id FROM endpointman_line_list WHERE luid = '.$_REQUEST['edit_id'] ;
                $mac_id = $endpoint->eda->sql($sql,'getOne');
                $row = $endpoint->get_phone_info($mac_id);

                $endpoint->delete_line($_REQUEST['edit_id'],FALSE);
                $_REQUEST['edit_id'] = $mac_id;
                break;
        }
        $edit_row=$endpoint->get_phone_info($_REQUEST['edit_id']);
        $edit_row['id'] = $_REQUEST['edit_id'];
        break;
		
		
		
		
    case "add" :
        $mac_id = $endpoint->add_device($_REQUEST['mac'],$_REQUEST['model_list'],$_REQUEST['ext_list'],$_REQUEST['template_list'],$_REQUEST['line_list']);
        if($mac_id) {
            $phone_info = $endpoint->get_phone_info($mac_id);
            $endpoint->prepare_configs($phone_info);
        }
        break;
		
		
		
		
    case "edit_template" :
        if(empty($_REQUEST['edit_id'])) {
            $endpoint->error['page:devices_manager'] = _("No Device Selected to Edit!")."!";
        } else {
            $template_editor = TRUE;
            $sql = "UPDATE  endpointman_mac_list SET  model =  '".$_REQUEST['model_list']."' WHERE  id =".$_REQUEST['edit_id'];
            $endpoint->eda->sql($sql);
            if ($_REQUEST['template_list'] == 0) {
                $endpoint->edit_template_display($_REQUEST['edit_id'],1);
            } else {
                $endpoint->edit_template_display($_REQUEST['template_list'],0);
            }
        }
        break;
		
		
		
    case "delete_selected_phones":
        if(isset($_REQUEST['selected'])) {
            foreach($_REQUEST['selected'] as $key => $data) {
                $endpoint->delete_device($_REQUEST['selected'][$key]);
            }
        } else {
            $endpoint->error['page:devices_manager'] = _("No Phones Selected")."!";
        }
        break;
		
		
		
    case "delete_device":
        $endpoint->delete_device($_REQUEST['edit_id']);
        break;
		
		
		
    case "delete_line" :
        $endpoint->delete_line($_REQUEST['edit_id']);
        break;
		
		
		
		
    case "rebuild_selected_phones":
        if(isset($_REQUEST['selected'])) {
            foreach($_REQUEST['selected'] as $key => $data) {
                $phone_info = $endpoint->get_phone_info($_REQUEST['selected'][$key]);
                if(isset($_REQUEST['reboot'])) {
                    $endpoint->prepare_configs($phone_info);
                    $rebooted_msg = "& Rebooted";
                } else {
                    $endpoint->prepare_configs($phone_info,FALSE);
                    $rebooted_msg = "For";
                }
            }
            $endpoint->message['page:devices_manager'] = "Rebuilt Configs ".$rebooted_msg." Selected Phones";
        } else {
            $endpoint->message['page:devices_manager'] = _("No Phones Selected")."!";
        }
        break;
		
		
		
		
    case "rebuild_configs_for_all_phones" :
        $sql = "SELECT endpointman_mac_list.id FROM endpointman_mac_list, endpointman_brand_list, endpointman_product_list, endpointman_model_list WHERE endpointman_brand_list.id = endpointman_product_list.brand AND endpointman_product_list.id = endpointman_model_list.product_id AND endpointman_mac_list.model = endpointman_model_list.id ORDER BY endpointman_product_list.cfg_dir ASC";
        $mac_list =& $endpoint->eda->sql($sql,'getAll',\PDO::FETCH_ASSOC);
        foreach($mac_list as $data) {
            $phone_info = $endpoint->get_phone_info($data['id']);
            foreach($phone_info['line'] as $line) {
                $sql = "UPDATE endpointman_line_list SET description = '".$endpoint->eda->escapeSimple($line['description'])."' WHERE luid = ".$line['luid'];
                $endpoint->eda->sql($sql);
            }
            if(isset($_REQUEST['reboot'])) {
                $endpoint->prepare_configs($phone_info);
                    $rebooted_msg = "& Rebooted";
                } else {
                    $endpoint->prepare_configs($phone_info,FALSE);
                    $rebooted_msg = "For";
                }
            }
            $endpoint->message['page:devices_manager'] = "Rebuilt Configs ".$rebooted_msg." All Phones";
        break;
		
		
		
		
    case "reboot_brand" :
        if($_REQUEST['rb_brand'] != "") {
            $sql = 'SELECT endpointman_mac_list.id FROM endpointman_mac_list , endpointman_model_list , endpointman_brand_list , endpointman_product_list WHERE endpointman_brand_list.id = endpointman_model_list.brand AND endpointman_model_list.id = endpointman_mac_list.model AND endpointman_model_list.product_id = endpointman_product_list.id AND endpointman_brand_list.id = '.$_REQUEST['rb_brand'].' ORDER BY endpointman_product_list.cfg_dir ASC';
            $data =& $endpoint->eda->sql($sql,'getAll',\PDO::FETCH_ASSOC);
            if(!empty($data)) {
                foreach($data as $row) {
                    if(!class_exists('ProvisionerConfig')) {
                        require(PHONE_MODULES_PATH.'setup.php');
                    }
                    $phone_info = $endpoint->get_phone_info($row['id']);

                    $class = "endpoint_" . $phone_info['directory'] . "_" . $phone_info['cfg_dir'] . '_phone';
					$base_class = "endpoint_" . $phone_info['directory']. '_base';
					$master_class = "endpoint_base";
					/**Fix for FreePBX Distro
					* I seriously want to figure out why ONLY the FreePBX Distro can't do autoloads.
					**/
					if(!class_exists($master_class)) {
						ProvisionerConfig::endpointsAutoload($master_class);
					}
					if(!class_exists($base_class)) {
						ProvisionerConfig::endpointsAutoload($base_class);
					}
					if(!class_exists($class)) {
						ProvisionerConfig::endpointsAutoload($class);
					}
					//end quick fix

                    $provisioner_lib = new $class();

                    $provisioner_lib->root_dir = PHONE_MODULES_PATH;

                    $provisioner_lib->engine = 'asterisk';
                    $provisioner_lib->engine_location = !empty($endpoint->global_cfg['asterisk_location']) ? $endpoint->global_cfg['asterisk_location'] : 'asterisk';
                    $provisioner_lib->system = 'unix';

                    //have to because of versions less than php5.3
                    $provisioner_lib->brand_name = $phone_info['directory'];
                    $provisioner_lib->family_line = $phone_info['cfg_dir'];

                    $provisioner_lib->settings['line'][0] = array('username' => $phone_info['line'][1]['ext'], 'authname' => $phone_info['line'][1]['ext']);
                    $provisioner_lib->reboot();
                    unset($provisioner_lib);
                }
                $endpoint->message['page:devices_manager'] = "Rebooted all ". $phone_info['name'] . " phones";
            } else {
                $endpoint->error['page:devices_manager'] = _("No Phones to Reboot");
            }
        } else {
            $endpoint->error['page:devices_manager'] = _("No Brand Selected for Reboot");
        }
        break;
		
		
		
		
    case "go" :
        $sql = "UPDATE endpointman_global_vars SET value = '".$_REQUEST['netmask']."' WHERE var_name = 'nmap_search'";
        $endpoint->eda->sql($sql);
        $endpoint->global_cfg['nmap_search'] = $_REQUEST['netmask'];
        if ((isset($_REQUEST['nmap'])) AND ($_REQUEST['nmap'] == 1)) {
            $temp = $endpoint->discover_new($_REQUEST['netmask']);
        } else {
            $temp = $endpoint->discover_new($_REQUEST['netmask'], FALSE);
        }

        foreach($temp as $key => $data) {
            if ((!$data['endpoint_managed']) AND ($data['brand'])) {
                $final[$key] = $data;
                $final[$key]['id'] = $key;
                $sqln = "SELECT * FROM endpointman_model_list WHERE enabled = 1 AND brand =".$data['brand_id'];
                $model_list =& $endpoint->eda->sql($sqln,'getAll',\PDO::FETCH_ASSOC);
                $j = 0;
                foreach($model_list as $row) {
                    $final[$key]['list'][$j] = $row;
                    $j++;
                }
            }
        }

        if(!$final) {
            $final = NULL;
            $endpoint->message['page:devices_manager'] = _("No Devices Found");
        }
        $searched = 1;
        break;
		
		
		
		
    case "add_selected_phones" :
        if(isset($_REQUEST['add'])) {
            foreach($_REQUEST['add'] as $num) {
                $mac_id = $endpoint->add_device($_REQUEST['mac_'.$num],$_REQUEST['model_list_'.$num],$_REQUEST['ext_list_'.$num]);
                if($mac_id) {
                    $phone_info = $endpoint->get_phone_info($mac_id);
                    if(isset($_REQUEST['reboot_sel'])) {
                        $endpoint->prepare_configs($phone_info,TRUE);
                    } else {
                        $endpoint->prepare_configs($phone_info,FALSE);
                    }
                }
            }
        }
        break;
		
		
		
		
    case "change_brand" :
        if(isset($_REQUEST['selected'])) {
            if(($_REQUEST['brand_list_selected'] > 0) AND ($_REQUEST['model_list_selected'] > 0)) {
                foreach($_REQUEST['selected'] as $key => $data) {
                    $sql = "UPDATE endpointman_mac_list SET global_custom_cfg_data = '', template_id = 0, global_user_cfg_data = '', config_files_override = '', model = '".$_REQUEST['model_list_selected']."' WHERE id =  ". $_REQUEST['selected'][$key];
                    $endpoint->eda->sql($sql);

                    $phone_info = $endpoint->get_phone_info($_REQUEST['selected'][$key]);
                    $endpoint->prepare_configs($phone_info);
                    $rebooted = "";
                    if(isset($_REQUEST['reboot_change'])) {
                        $endpoint->prepare_configs($phone_info);
                        $rebooted = " & Rebooted";
                    } else {
                        $endpoint->prepare_configs($phone_info,FALSE);
                    }
                    $endpoint->message['page:devices_manager'] = _("Saved").$rebooted."!";
                }
            } else {
                $endpoint->error['page:devices_manager'] = _("Please select a Brand and/or Model");
            }
        } else {
            $endpoint->error['page:devices_manager'] = _("No Phones Selected!");
        }
        break;
		
		
		
    case "rebuild_reboot" :
        if($_REQUEST['product_select'] == "") {
            $message = _("Please select a product");
        } elseif($_REQUEST['template_selector'] == "") {
            $message = _("Please select a template");
        } else {
            $sql = "SELECT endpointman_mac_list.id FROM endpointman_mac_list, endpointman_brand_list, endpointman_product_list, endpointman_model_list WHERE endpointman_brand_list.id = endpointman_product_list.brand AND endpointman_product_list.id = endpointman_model_list.product_id AND endpointman_mac_list.model = endpointman_model_list.id AND endpointman_product_list.id = '".$_REQUEST['product_select']."'";
            $data = $endpoint->eda->sql($sql,'getAll',\PDO::FETCH_ASSOC);
            foreach($data as $row) {
                $sql = "UPDATE endpointman_mac_list SET template_id = '".$_REQUEST['template_selector']."' WHERE id =  ". $row['id'];
                $endpoint->eda->sql($sql);
                $phone_info = $endpoint->get_phone_info($row['id']);
                if(isset($_REQUEST['reboot'])) {
                    $endpoint->prepare_configs($phone_info);
                    $rebooted_msg = "& Rebooted Phones";
                } else {
                    $endpoint->prepare_configs($phone_info,FALSE);
                    $rebooted_msg = "";
                }
                foreach($phone_info['line'] as $line) {
                    $sql = "UPDATE endpointman_line_list SET description = '".$endpoint->eda->escapeSimple($line['description'])."' WHERE luid = ".$line['luid'];
                    $endpoint->eda->sql($sql);
                }
            }
            $endpoint->message['page:devices_manager'] = "Rebuilt Configs " . $rebooted_msg;
        }
        break;
		
		
		
    case "mrebuild_reboot" :
        if($_REQUEST['model_select'] == "") {
            $message = _("Please select a model");
        } elseif($_REQUEST['model_template_selector'] == "") {
            $message = _("Please select a template");
        } else {
            $sql = "SELECT endpointman_mac_list.id FROM endpointman_mac_list, endpointman_brand_list, endpointman_product_list, endpointman_model_list WHERE endpointman_brand_list.id = endpointman_product_list.brand AND endpointman_product_list.id = endpointman_model_list.product_id AND endpointman_mac_list.model = endpointman_model_list.id AND endpointman_model_list.id = '".$_REQUEST['model_select']."'";
            $data = $endpoint->eda->sql($sql,'getAll',\PDO::FETCH_ASSOC);
            foreach($data as $row) {
                $sql = "UPDATE endpointman_mac_list SET template_id = '".$_REQUEST['model_template_selector']."' WHERE id =  ". $row['id'];
                $endpoint->eda->sql($sql);
                $phone_info = $endpoint->get_phone_info($row['id']);
                if(isset($_REQUEST['reboot'])) {
                    $endpoint->prepare_configs($phone_info);
                    $rebooted_msg = "& Rebooted Phones";
                } else {
                    $endpoint->prepare_configs($phone_info,FALSE);
                    $rebooted_msg = "";
                }
                foreach($phone_info['line'] as $line) {
                    $sql = "UPDATE endpointman_line_list SET description = '".$endpoint->eda->escapeSimple($line['description'])."' WHERE luid = ".$line['luid'];
                    $endpoint->eda->sql($sql);
                }
            }
            $endpoint->message['page:devices_manager'] = "Rebuilt Configs " . $rebooted_msg;
        }
        break;
}

?>