<?PHP
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

//Set opened variables
$message = NULL;
$error_message = NULL;
$no_add = FALSE;
$final = NULL;
$button = NULL;
$searched = NULL;
$edit = NULL;
$mode = NULL;

$family_list = $endpoint->eda->all_products();
$full_device_list = $endpoint->eda->all_devices();
$ava_exts = $endpoint->display_registration_list();

if((empty($family_list)) && (empty($full_device_list))) {
    $endpoint->message['page:devices_manager'] = _("Welcome to Endpoint Manager")."!<br />"._("You have no products (Modules) installed, click")." <a href=\"config.php?display=epm_config&type=$type\">"._("here")."</a> "._("to install some");
    $no_add = TRUE;
    $endpoint->global_cfg['new'] = 1;
} elseif($endpoint->global_cfg['srvip'] == "") {
    $endpoint->error['page:devices_manager'] = "Your Global Variables are not set! Please head on over to <a href=\"config.php?type=$type&display=epm_advanced\">\"Advanced Settings\"</a> to setup your configuration";
    $no_add = TRUE;
} elseif(empty($ava_exts)) {
    //$message = "You have no more devices or extensions avalible to configure!";
    //$no_add = TRUE;
}

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

                $lines_list = $endpoint->eda->sql($sql,'getAll',DB_FETCHMODE_ASSOC);

                foreach($lines_list as $row) {
                    $sql = "SELECT description FROM devices WHERE id = ".$_REQUEST['ext_list_'.$row['luid']];
                    $name = $endpoint->eda->sql($sql,'getOne');

                    $sql = "UPDATE endpointman_line_list SET ipei = '".$_REQUEST['ipei_'.$row['luid']]."', line = '".$_REQUEST['line_list_'.$row['luid']]."', ext = '".$_REQUEST['ext_list_'.$row['luid']]."', description = '".$endpoint->eda->escapeSimple($name)."' WHERE luid =  ". $row['luid'];
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
            $sql = "SELECT mac_id, ext FROM endpointman_line_list";
					$endpoint->eda->sql($sql);
					$lines = $endpoint->eda->sql($sql,'getAll',DB_FETCHMODE_ASSOC);		
			
			foreach($_REQUEST['selected'] as $key => $data) {
			
				foreach($lines as $line){
						if ($data == $line[ext]){
						$sql = "SELECT mac_id FROM endpointman_line_list WHERE ext = " . $data;
						$endpoint->eda->sql($sql);
						$macid = $endpoint->eda->sql($sql,'getrow',DB_FETCHMODE_ASSOC);
						$_REQUEST['selected'][$key] = $macid[mac_id];
						
						}
				}

					
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
        $mac_list = $endpoint->eda->sql($sql,'getAll',DB_FETCHMODE_ASSOC);
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
            $data = $endpoint->eda->sql($sql,'getAll',DB_FETCHMODE_ASSOC);
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

                    $provisioner_lib->settings['line'][0] = array('username' => $phone_info['line'][1]['ext'], 'authname' => $phone_info['line'][1]['ext'], 'tech' => $phone_info['line'][1]['tech']);
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
                $model_list = $endpoint->eda->sql($sqln,'getAll',DB_FETCHMODE_ASSOC);
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
                            $sql = "SELECT mac_id, ext FROM endpointman_line_list";
					$endpoint->eda->sql($sql);
					$lines = $endpoint->eda->sql($sql,'getAll',DB_FETCHMODE_ASSOC);		
			
			foreach($_REQUEST['selected'] as $key => $data) {
			
				foreach($lines as $line){
						if ($data == $line[ext]){
						$sql = "SELECT mac_id FROM endpointman_line_list WHERE ext = " . $data;
						$endpoint->eda->sql($sql);
						$macid = $endpoint->eda->sql($sql,'getrow',DB_FETCHMODE_ASSOC);
						$_REQUEST['selected'][$key] = $macid[mac_id];
						
						}
				}
					
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
            $data = $endpoint->eda->sql($sql,'getAll',DB_FETCHMODE_ASSOC);
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
            $data = $endpoint->eda->sql($sql,'getAll',DB_FETCHMODE_ASSOC);
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

//Refresh the list after processing
$devices_list = $endpoint->eda->all_devices();;

$i = 0;
$list = array();

$device_statuses = shell_exec($endpoint->global_cfg['asterisk_location']." -rx 'sip show peers'");

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

// Do the same for the pjsip peers
$device_statuses = shell_exec($endpoint->global_cfg['asterisk_location']." -rx 'pjsip show contacts'");
$device_statuses = explode("\n", $device_statuses);
foreach($device_statuses as $key => $data) {
    preg_match('/Contact:\s+(\d+)\/sip:.*/i', $data, $extout);
    preg_match('/\b(?:\d{1,3}\.){3}\d{1,3}\b/i', $data, $ipaddress);
    if(!empty($extout[1])) {
        if(preg_match('/Avail\s+\d+/i', $data)) {
           $devices_status[$extout[1]]['status'] = TRUE;
           $devices_status[$extout[1]]['ip'] = $ipaddress[0];
        } else {
            $devices_status[$extout[1]]['status'] = FALSE;
        }
    }
}

foreach($devices_list as $devices_row) {
    $line_list = $endpoint->eda->get_lines_from_device($devices_row['id']);
    $list[$i] = $devices_row;
    $z = 0;
    if (($devices_row['template_id'] == 0) && (isset($devices_row['global_custom_cfg_data'])) ) {
        $list[$i]['template_name'] = "Custom-".$devices_row['mac'];
    } elseif((!isset($devices_row['custom_cfg_data'])) && ($devices_row['template_id'] == 0)) {
        $list[$i]['template_name'] = "N/A";
    } else {
        $sql = "SELECT name FROM endpointman_template_list WHERE id =".$devices_row['template_id'];
        $template_name = $endpoint->eda->sql($sql,'getOne');
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

$unknown_list = $endpoint->eda->all_unknown_devices();

foreach($unknown_list as $row) {	#Displays unknown phones in the database with edit and delete buttons
    $list[$i] = $row;

    $brand_info = $endpoint->get_brand_from_mac($row['mac']);

    $list[$i]['name'] = $brand_info['name'];
    $list[$i]['template_name'] = "N/A";
    $list[$i]['model'] = _("Unknown");
    $i++;
}

$amp_send['AMPDBUSER'] = $amp_conf['AMPDBUSER'];
$amp_send['AMPDBPASS'] = $amp_conf['AMPDBPASS'];
$amp_send['AMPDBNAME'] = $amp_conf['AMPDBNAME'];

$sql = "SELECT DISTINCT endpointman_product_list.* FROM endpointman_product_list, endpointman_model_list WHERE endpointman_product_list.id = endpointman_model_list.product_id AND endpointman_model_list.hidden = 0 AND endpointman_model_list.enabled = 1 AND endpointman_product_list.hidden != 1 AND endpointman_product_list.cfg_dir !=  ''";

$template_list = $endpoint->eda->sql($sql, 'getAll', DB_FETCHMODE_ASSOC);
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
$template_list = $endpoint->eda->sql($sql, 'getAll', DB_FETCHMODE_ASSOC);
$i = 1;
$model_list = array();
$model_list[0]['value'] = 0;
$model_list[0]['text'] = "";
foreach($template_list as $row) {
        $model_list[$i]['value'] = $row['id'];
        $model_list[$i]['text'] = $row['model'];
        $i++;
}

//initialize a Rain TPL object
if (isset($template_editor)) {

} else {
    $endpoint->tpl->assign("list", $list);
    $endpoint->tpl->assign("error", "");
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
    $endpoint->tpl->assign("no_add", $no_add);
    $endpoint->tpl->assign("mode", $mode);

	
    $edit_row['id'] = $edit_row['id'] ?? '0';
    $endpoint->tpl->assign("edit_id", $edit_row['id']);

    $edit_row['template_id'] = $edit_row['template_id'] ?? '0';
	$endpoint->tpl->assign("template_id", $edit_row['template_id']);
	
	if ($edit_row['template_id'] == "0")
    {
	    $endpoint->tpl->assign("custom", 1);
	
	    $endpoint->tpl->assign("template_id", $edit_row['id']);
	}
	else
    {
	    $endpoint->tpl->assign("custom", 0);
	}

    if(isset($final)) {
        $_SESSION['dev_cache'] = base64_encode(serialize($final));
    }

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

    //Prepare Message Box
    $endpoint->prepare_message_box();

    //draw the template
    echo $endpoint->tpl->draw( 'devices_manager' );
}
