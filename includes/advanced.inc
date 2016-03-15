<?PHP

/**
 * Endpoint Manager Advanced File
 *
 * @author Andrew Nagy
 * @license MPL / GPLv2 / LGPL
 * @package Provisioner
 */
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

echo $endpoint->tpl->draw('global_header');
if (!isset($_REQUEST['subpage'])) {
    $_REQUEST['subpage'] = "";
    $endpoint->tpl->assign("subhead_area", 'settings');
} else {
    $endpoint->tpl->assign("subhead_area", $_REQUEST['subpage']);
}
echo $endpoint->tpl->draw('advanced_subheader');



switch ($_REQUEST['subpage']) {
    case "iedl":
        $endpoint->tpl->assign("exporter_address", "config.php?type=tool&amp;display=epm_config&amp;quietmode=1&amp;handler=file&amp;file=export.html.php&amp;module=endpointman&amp;rand=" . rand());
        //Dave B's Q&D file upload security code (http://us2.php.net/manual/en/features.file-upload.php)
        if ((isset($_REQUEST['button_import'])) AND ($action == "import")) {
            $allowedExtensions = array("csv", "txt");
            foreach ($_FILES as $file) {
                if ($file['tmp_name'] > '') {
                    if (!in_array(end(explode(".", strtolower($file['name']))), $allowedExtensions)) {
                        $endpoint->message['iedl'] = "We support only CVS and TXT files";
                    } else {
                        $uploaddir = LOCAL_PATH;
                        $uploadfile = $uploaddir . basename($_FILES['import_csv']['name']);
                        if (move_uploaded_file($_FILES['import_csv']['tmp_name'], $uploadfile)) {
                            //Parse the uploaded file
                            $handle = fopen(LOCAL_PATH . $_FILES['import_csv']['name'], "r");
                            $i = 1;
                            while (($device = fgetcsv($handle, filesize(LOCAL_PATH . $_FILES['import_csv']['name']))) !== FALSE) {
                                if ($device[0] != "") {
                                    if ($mac = $endpoint->mac_check_clean($device[0])) {
                                        $sql = "SELECT id FROM endpointman_brand_list WHERE name LIKE '%" . $device[1] . "%' LIMIT 1";
                                        $res = $endpoint->eda->sql($sql);
                                        if ($res->numRows() > 0) {
                                            $brand_id = $endpoint->eda->sql($sql, 'getOne');
                                            $brand_id = $brand_id[0];

                                            $sql_model = "SELECT id FROM endpointman_model_list WHERE brand = " . $brand_id . " AND model LIKE '%" . $device[2] . "%' LIMIT 1";
                                            $sql_ext = "SELECT extension, name FROM users WHERE extension LIKE '%" . $device[3] . "%' LIMIT 1";
					    
                                           if ($device[5] != "") {
                                            	$sql_template = "Select id From endpointman_template_list WHERE name ='" . $device[5] . "' LIMIT 1"; 
	                                    		$template = $endpoint->eda->sql($sql_template, 'getOne');
                                            	$template = $template[0];
                                            } else {
												$template = 0;
                                            }

                                            $line_id = isset($device[4]) ? $device[4] : 1;

                                            $res_model = $endpoint->eda->sql($sql_model);
                                            if ($res_model->numRows()) {
                                                $model_id = $endpoint->eda->sql($sql_model, 'getRow', DB_FETCHMODE_ASSOC);
                                                $model_id = $model_id['id'];

                                                $res_ext = $endpoint->eda->sql($sql_ext);
                                                if ($res_ext->numRows()) {
                                                    $ext = $endpoint->eda->sql($sql_ext, 'getRow', DB_FETCHMODE_ASSOC);
                                                    $description = $ext['name'];
                                                    $ext = $ext['extension'];

                                                    $endpoint->add_device($mac, $model_id, $ext, $template, $line_id, $description);
                                                } else {
                                                    $endpoint->error['csv_upload'] .= "Invalid Extension Specified on line " . $i . "<br />";
                                                }
                                            } else {
                                                $endpoint->error['csv_upload'] .= "Invalid Model Specified on line " . $i . "<br />";
                                            }
                                        } else {
                                            $endpoint->error['csv_upload'] .= "Invalid Brand Specified on line " . $i . "<br />";
                                        }
                                    } else {
                                        $endpoint->error['csv_upload'] .= "Invalid Mac on line " . $i . "<br />";
                                    }
                                }
                                $i++;
                            }
                            fclose($handle);
                            unlink(LOCAL_PATH . $_FILES['import_csv']['name']);
                            $endpoint->message['file_upload'] = "Please reboot & rebuild all imported phones<br />";
                        } else {
                            $endpoint->error['file_upload'] = "Possible file upload attack!";
                        }
                    }
                }
            }
        } elseif (isset($_REQUEST['action'])) {
            $endpoint->error['iedl'] = "No File uploaded";
        }
        $endpoint->prepare_message_box();
        echo $endpoint->tpl->draw('advanced_settings_iedl');
        break;
    case "manual_upload":
        $sql = "SELECT value FROM endpointman_global_vars WHERE var_name LIKE 'endpoint_vers'";
        $provisioner_ver = $endpoint->eda->sql($sql, 'getOne');
        $provisioner_ver = date("d-M-Y", $provisioner_ver) . " at " . date("g:ia", $provisioner_ver);
        $endpoint->tpl->assign("provisioner_ver", $provisioner_ver);
        $endpoint->tpl->assign("brand_ava", $endpoint->brands_available());

        $provisioner_ver = "";
        $endpoint->tpl->assign("master_ver", $provisioner_ver);
        $uploads_dir = PHONE_MODULES_PATH . "temp";

        if (isset($_REQUEST['upload_provisioner'])) {

            $extension = pathinfo($_FILES["package"]["name"], PATHINFO_EXTENSION);
            if ($extension == "tgz") {
                if ($_FILES['package']['error'] == UPLOAD_ERR_OK) {
                    $tmp_name = $_FILES["package"]["tmp_name"];
                    $name = $_FILES["package"]["name"];
                    move_uploaded_file($tmp_name, "$uploads_dir/$name");
                    $endpoint->tpl->assign("show_installer", 1);
                    $endpoint->tpl->assign("package", $name);
                    $endpoint->tpl->assign("type", "upload_provisioner");
                    $endpoint->tpl->assign("xml", 0);
                } else {
                    $endpoint->error['manual_upload'] = $endpoint->file_upload_error_message($_FILES['package']['error']);
                }
            } else {
                $endpoint->error['manual_upload'] = "Invalid File Extension";
            }
        } elseif (isset($_REQUEST['upload_brand'])) {
            $error = FALSE;
            $files_list = array();
            $i = 0;
            foreach ($_FILES as $files) {
                $extension = pathinfo($files["name"], PATHINFO_EXTENSION);
                if ($extension == "tgz") {
                    if ($files['error'] == UPLOAD_ERR_OK) {
                        $tmp_name = $files["tmp_name"];
                        $name = $files["name"];
                        move_uploaded_file($tmp_name, "$uploads_dir/$name");
                        $files_list[$i] = $name;
                        $i++;
                    } else {
                        $endpoint->error['manual_upload'] = $endpoint->file_upload_error_message($files['error']);
                        $error = TRUE;
                    }
                } else {
                    $endpoint->error['manual_upload'] = "Invalid File Extension";
                    $error = TRUE;
                }
            }
            if (!$error) {
                $endpoint->tpl->assign("show_installer", 1);
                $endpoint->tpl->assign("package", $files_list[0]);
                $endpoint->tpl->assign("type", "upload_brand");
            }
        } elseif (isset($_REQUEST['export_brand'])) {
            $endpoint->tpl->assign("show_installer", 1);
            $endpoint->tpl->assign("type", "export_brand");
            $endpoint->tpl->assign("package", $_REQUEST['exp_brand']);
        }
        $endpoint->prepare_message_box();
        echo $endpoint->tpl->draw('advanced_settings_manual_upload');
        break;
    case "oui_manager":
        if ((isset($_REQUEST['oui_sub'])) AND ($_REQUEST['rb_brand'] > 0) AND ($_REQUEST['oui'] != "")) {
            $sql = "INSERT INTO  endpointman_oui_list (oui, brand, custom) VALUES ('" . $_REQUEST['oui'] . "',  '" . $_REQUEST['rb_brand'] . "',  '1')";
            $endpoint->eda->sql($sql);
            $endpoint->message['oui_manager'] = "Added!";
        } elseif (isset($_REQUEST['oui_sub'])) {
            $endpoint->error['oui_manager'] = "No OUI Set!";
        }
        if ((isset($_REQUEST['delete'])) AND ($_REQUEST['id'] > 0)) {
            $sql = "DELETE FROM endpointman_oui_list WHERE id = " . $_REQUEST['id'];
            $endpoint->eda->sql($sql);
            $endpoint->message['oui_manager'] = "Deleted!";
        }
        $sql = 'SELECT endpointman_oui_list.id, endpointman_oui_list.oui , endpointman_brand_list.name, endpointman_oui_list.custom FROM endpointman_oui_list , endpointman_brand_list WHERE endpointman_oui_list.brand = endpointman_brand_list.id ORDER BY endpointman_oui_list.oui ASC';
        $data = & $endpoint->eda->sql($sql, 'getAll', DB_FETCHMODE_ASSOC);
        $i = 0;
        $row_out = array();
        foreach ($data as $row) {
            $row_out[$i] = $row;
            $endpoint->tpl->assign("oui_list", $row_out);
            $i++;
        }
        $endpoint->tpl->assign("brand_ava", $endpoint->brands_available());
        $endpoint->prepare_message_box();
        echo $endpoint->tpl->draw('advanced_settings_oui');
        break;
    case "poce":
        $sql = 'SELECT * FROM `endpointman_product_list` WHERE `hidden` = 0 AND `id` > 0';
        $data = & $endpoint->eda->sql($sql, 'getAll', DB_FETCHMODE_ASSOC);
        $i = 0;
        foreach ($data as $row) {
            $product_list[$i]['value'] = $row['id'];
            $product_list[$i]['text'] = $row['long_name'];
            if ((isset($_REQUEST['product_select'])) AND ($_REQUEST['product_select'] == $row['id'])) {
                $product_list[$i]['selected'] = 1;
            }
            $i++;
        }
        if (isset($_REQUEST['delete'])) {
            $sql = "DELETE FROM endpointman_custom_configs WHERE id =" . $_REQUEST['sql'];
            $endpoint->eda->sql($sql);
            $endpoint->message['poce'] = "Deleted!";
        }
        if (isset($_REQUEST['file'])) {
            $sql = "SELECT cfg_dir,directory,config_files FROM endpointman_product_list,endpointman_brand_list WHERE endpointman_product_list.brand = endpointman_brand_list.id AND endpointman_product_list.id = '" . $_REQUEST['product_select'] . "'";
            $row = $endpoint->eda->sql($sql, 'getRow', DB_FETCHMODE_ASSOC);

            $config_files = explode(",", $row['config_files']);
            $file = PHONE_MODULES_PATH . 'endpoint/' . $row['directory'] . "/" . $row['cfg_dir'] . "/" . $config_files[$_REQUEST['file']];
            if (isset($_REQUEST['config_text'])) {
                if (isset($_REQUEST['button_save'])) {
                    $wfh = fopen($file, 'w');
                    fwrite($wfh, $_REQUEST['config_text']);
                    fclose($wfh);
                    $endpoint->message['poce'] = "Saved to Hard Drive!";
                } elseif (isset($_REQUEST['button_save_as'])) {
                    $sql = 'INSERT INTO endpointman_custom_configs (name, original_name, product_id, data) VALUES ("' . addslashes($_REQUEST['save_as_name']) . '","' . addslashes($config_files[$_REQUEST['file']]) . '","' . $_REQUEST['product_select'] . '","' . addslashes($_REQUEST['config_text']) . '")';
                    $endpoint->eda->sql($sql);
                    $endpoint->message['poce'] = "Saved to Database!";
                }
            }

            $handle = fopen($file, "rb");
            $contents = fread($handle, filesize($file));
            fclose($handle);

            if (isset($_REQUEST['sendid'])) {
                $error = $endpoint->submit_config($row['directory'], $row['cfg_dir'], $config_files[$_REQUEST['file']], $contents);
                $endpoint->message['poce'] = 'Sent! Thanks :-)';
            }

            $endpoint->tpl->assign("save_as_name_value", $config_files[$_REQUEST['file']]);

            $contents = $endpoint->display_htmlspecialchars($contents);

            $endpoint->tpl->assign("config_data", $contents);
            $endpoint->tpl->assign("filename", $config_files[$_REQUEST['file']]);
            $endpoint->tpl->assign('sendid', $_REQUEST['file']);
            $endpoint->tpl->assign("type", 'file');
            $endpoint->tpl->assign("location", $file);
        } elseif (isset($_REQUEST['sql'])) {
            if (isset($_REQUEST['config_text'])) {
                if (isset($_REQUEST['button_save'])) {
                    $sql = "UPDATE endpointman_custom_configs SET data = '" . addslashes($_REQUEST['config_text']) . "' WHERE id = " . $_REQUEST['sql'];
                    $endpoint->eda->sql($sql);
                    $endpoint->message['poce'] = "Saved to Database!";
                } elseif (isset($_REQUEST['button_save_as'])) {
                    $sql = 'SELECT original_name FROM endpointman_custom_configs WHERE id = ' . $_REQUEST['sql'];
                    $file_name = $endpoint->eda->sql($sql, 'getOne');

                    $sql = "INSERT INTO endpointman_custom_configs (name, original_name, product_id, data) VALUES ('" . addslashes($_REQUEST['save_as_name']) . "','" . addslashes($file_name) . "','" . $_REQUEST['product_select'] . "','" . addslashes($_REQUEST['config_text']) . "')";
                    $endpoint->eda->sql($sql);
                    $endpoint->message['poce'] = "Saved to Database!";
                }
            }
            $sql = 'SELECT * FROM endpointman_custom_configs WHERE id =' . $_REQUEST['sql'];
            $row = & $endpoint->eda->sql($sql, 'getrow', DB_FETCHMODE_ASSOC);

            if (isset($_REQUEST['sendid'])) {
                $sql = "SELECT cfg_dir,directory,config_files FROM endpointman_product_list,endpointman_brand_list WHERE endpointman_product_list.brand = endpointman_brand_list.id AND endpointman_product_list.id = '" . $_REQUEST['product_select'] . "'";
                $row22 = & $endpoint->eda->sql($sql, 'getrow', DB_FETCHMODE_ASSOC);
                $endpoint->submit_config($row22['directory'], $row22['cfg_dir'], $row['original_name'], $row['data']);
                $endpoint->message['poce'] = 'Sent! Thanks! :-)';
            }

            $row['data'] = $endpoint->display_htmlspecialchars($row['data']);

            $endpoint->tpl->assign("save_as_name_value", $row['name']);
            $endpoint->tpl->assign("filename", $row['original_name']);
            $endpoint->tpl->assign('sendid', $_REQUEST['sql']);
            $endpoint->tpl->assign("type", 'sql');
            $endpoint->tpl->assign("config_data", $row['data']);
        }
        if (isset($_REQUEST['product_select'])) {
            $sql = "SELECT cfg_dir,directory,config_files FROM endpointman_product_list,endpointman_brand_list WHERE endpointman_product_list.brand = endpointman_brand_list.id AND endpointman_product_list.id ='" . $_REQUEST['product_select'] . "'";

            $row = & $endpoint->eda->sql($sql, 'getrow', DB_FETCHMODE_ASSOC);
            $config_files = explode(",", $row['config_files']);
            $i = 0;
            foreach ($config_files as $config_files_data) {
                $file_list[$i]['value'] = $i;
                $file_list[$i]['text'] = $config_files_data;
                $i++;
            }
            $sql = "SELECT * FROM endpointman_custom_configs WHERE product_id = '" . $_REQUEST['product_select'] . "'";
            $res = & $endpoint->eda->sql($sql);
            $i = 0;
            if ($res->numRows()) {
                $data = & $endpoint->eda->sql($sql, 'getall', DB_FETCHMODE_ASSOC);
                foreach ($data as $row2) {
                    $sql_file_list[$i]['value'] = $row2['id'];
                    $sql_file_list[$i]['text'] = $row2['name'];
                    $sql_file_list[$i]['ref'] = $row2['original_name'];
                    $i++;
                }
            } else {
                $sql_file_list = NULL;
            }
            require(PHONE_MODULES_PATH . 'setup.php');

            $class = "endpoint_" . $row['directory'] . "_" . $row['cfg_dir'] . '_phone';
            $base_class = "endpoint_" . $row['directory'] . '_base';
            $master_class = "endpoint_base";
            /*             * Quick Fix for FreePBX Distro
             * I seriously want to figure out why ONLY the FreePBX Distro can't do autoloads.
             * */
            if (!class_exists($master_class)) {
                ProvisionerConfig::endpointsAutoload($master_class);
            }
            if (!class_exists($base_class)) {
                ProvisionerConfig::endpointsAutoload($base_class);
            }
            if (!class_exists($class)) {
                ProvisionerConfig::endpointsAutoload($class);
            }
            //end quick fix

            $phone_config = new $class();

            //TODO: remove
            $template_file_list[0]['value'] = "template_data_custom.xml";
            $template_file_list[0]['text'] = "template_data_custom.xml";

            $sql = "SELECT model FROM `endpointman_model_list` WHERE `product_id` LIKE '1-2' AND `enabled` = 1 AND `hidden` = 0";
            $data = & $endpoint->eda->sql($sql, 'getall', DB_FETCHMODE_ASSOC);
            $i = 1;
            foreach ($data as $list) {
                $template_file_list[$i]['value'] = "template_data_" . $list['model'] . "_custom.xml";
                $template_file_list[$i]['text'] = "template_data_" . $list['model'] . "_custom.xml";
            }

            $endpoint->tpl->assign("template_file_list", $template_file_list);
            if (isset($_REQUEST['temp_file'])) {
                $endpoint->tpl->assign("temp_file", 1);
            } else {
                $endpoint->tpl->assign("temp_file", NULL);
            }

            $endpoint->tpl->assign("file_list", $file_list);
            $endpoint->tpl->assign("sql_file_list", $sql_file_list);
            $endpoint->tpl->assign("product_selected", $_REQUEST['product_select']);
        }
        $endpoint->tpl->assign("product_list", $product_list);
        $endpoint->prepare_message_box();
        echo $endpoint->tpl->draw('advanced_settings_poce');
        break;
    case "settings":
    default:
        if (isset($_REQUEST['button_update_globals'])) {
            $_POST['srvip'] = trim($_POST['srvip']);  #trim whitespace from IP address

            $_POST['config_loc'] = trim($_POST['config_loc']);  #trim whitespace from Config Location

            $sql = "UPDATE endpointman_global_vars SET value='" . $_POST['srvip'] . "' WHERE var_name='srvip'";
            $endpoint->eda->sql($sql);
            $sql = "UPDATE endpointman_global_vars SET value='" . $_POST['tz'] . "' WHERE var_name='tz'";
            $endpoint->eda->sql($sql);

            if ($_POST['cfg_type'] == 'http') {
                $symlink = $amp_conf['AMPWEBROOT'] . "/provisioning";
                $reallink = LOCAL_PATH . "provisioning";
                if ((!is_link($symlink)) OR (!readlink($symlink) == $reallink)) {
                    if (!symlink($reallink, $symlink)) {
                        $endpoint->error['config_dir'] = "Your permissions are wrong on " . $amp_conf['AMPWEBROOT'] . ", web provisioning link not created!";
                        $_POST['cfg_type'] = 'file';
                    } else {
                        $_POST['cfg_type'] = 'http';
                    }
                } else {
                    $_POST['cfg_type'] = 'http';
                }
            } else {
                $_POST['cfg_type'] = 'file';
            }
            $sql = "UPDATE endpointman_global_vars SET value='" . $_POST['cfg_type'] . "' WHERE var_name='server_type'";
            $endpoint->eda->sql($sql);

            //No trailing slash. Help the user out and add one :-)
            if ($_POST['config_loc'][strlen($_POST['config_loc']) - 1] != "/") {
                $_POST['config_loc'] = $_POST['config_loc'] . "/";
            }

            $tftp_writable = FALSE;
            if ((isset($_POST['config_loc'])) AND ($_POST['config_loc'] != "")) {
                if ((file_exists($_POST['config_loc'])) AND (is_dir($_POST['config_loc']))) {
                    if (is_writable($_POST['config_loc'])) {
                        $sql = "UPDATE endpointman_global_vars SET value='" . $_POST['config_loc'] . "' WHERE var_name='config_location'";
                        $endpoint->eda->sql($sql);
                        $tftp_writable = TRUE;
                    } else {
                        $endpoint->error['config_dir'] = "Directory Not Writable!";
                    }
                } else {
                    $endpoint->error['config_dir'] = "Not a Vaild Directory <br /> Try to run 'mkdir " . $_POST['config_loc'] . "' as root";
                }
            } else {
                $endpoint->error['config_dir'] = "No Configuration Location Defined!";
            }

            if ((isset($_POST['enable_ari'])) AND ($_POST['enable_ari'] == "on")) {
                $_POST['enable_ari'] = 1;
            } else {
                $_POST['enable_ari'] = 0;
            }
            if ((isset($_POST['enable_debug'])) AND ($_POST['enable_debug'] == "on")) {
                $_POST['enable_debug'] = 1;
            } else {
                $_POST['enable_debug'] = 0;
            }
            if ((isset($_POST['disable_help'])) AND ($_POST['disable_help'] == "on")) {
                $_POST['disable_help'] = 1;
            } else {
                $_POST['disable_help'] = 0;
            }
            if ((isset($_POST['allow_dupext'])) AND ($_POST['allow_dupext'] == "on")) {
                $_POST['allow_dupext'] = 1;
            } else {
                $_POST['allow_dupext'] = 0;
            }
            if ((isset($_POST['allow_hdfiles'])) AND ($_POST['allow_hdfiles'] == "on")) {
                $_POST['allow_hdfiles'] = 1;
            } else {
                $_POST['allow_hdfiles'] = 0;
            }
            if ((isset($_POST['tftp_check'])) AND ($_POST['tftp_check'] == "on")) {
                $_POST['tftp_check'] = 1;
            } else {
                $_POST['tftp_check'] = 0;
            }
            if ((isset($_POST['backup_check'])) AND ($_POST['backup_check'] == "on")) {
                $_POST['backup_check'] = 1;
            } else {
                $_POST['backup_check'] = 0;
            }
            if ((isset($_POST['use_repo'])) AND ($_POST['use_repo'] == "on")) {
                if ($endpoint->has_git()) {
                    $_POST['use_repo'] = 1;
                    if (!file_exists(PHONE_MODULES_PATH . '/.git')) {
                        $o = getcwd();
                        chdir(dirname(PHONE_MODULES_PATH));
                        $endpoint->rmrf(PHONE_MODULES_PATH);
                        $path = $endpoint->has_git();
                        exec($path . ' clone https://github.com/provisioner/Provisioner.git _ep_phone_modules', $output);
                        chdir($o);
                    }
                } else {
                    $endpoint->error['use_repo'] = 'Git not installed!';
                }
            } else {
                $_POST['use_repo'] = 0;
                if (file_exists(PHONE_MODULES_PATH . '/.git')) {
                    $endpoint->rmrf(PHONE_MODULES_PATH);
                    $sql = "SELECT * FROM  `endpointman_brand_list` WHERE  `installed` =1";
                    $result = & $endpoint->eda->sql($sql, 'getAll', DB_FETCHMODE_ASSOC);
                    foreach ($result as $row) {
                        $endpoint->remove_brand($row['id'], FALSE, TRUE);
                    }
                }
            }

            $sql = "UPDATE endpointman_global_vars SET value='" . $_POST['tftp_check'] . "' WHERE var_name='tftp_check'";
            $endpoint->eda->sql($sql);

            //Check tftp server to make sure it's functioning if we are using it
            if (($_POST['cfg_type'] == 'file') AND ($tftp_writable)) {
                $endpoint->tftp_check();
            }

            $sql = "UPDATE endpointman_global_vars SET value='" . $_POST['package_server'] . "' WHERE var_name='update_server'";
            $endpoint->eda->sql($sql);

            $sql = "UPDATE endpointman_global_vars SET value='" . $_POST['allow_hdfiles'] . "' WHERE var_name='allow_hdfiles'";
            $endpoint->eda->sql($sql);

            $sql = "UPDATE endpointman_global_vars SET value='" . $_POST['allow_dupext'] . "' WHERE var_name='show_all_registrations'";
            $endpoint->eda->sql($sql);

            $sql = "UPDATE endpointman_global_vars SET value='" . $_POST['enable_ari'] . "' WHERE var_name='enable_ari'";
            $endpoint->eda->sql($sql);

            $sql = "UPDATE endpointman_global_vars SET value='" . $_POST['enable_debug'] . "' WHERE var_name='debug'";
            $endpoint->eda->sql($sql);

            $sql = "UPDATE endpointman_global_vars SET value='" . $_POST['enable_debug'] . "' WHERE var_name='debug'";
            $endpoint->eda->sql($sql);

            $sql = "UPDATE endpointman_global_vars SET value='" . $_POST['ntp_server'] . "' WHERE var_name='ntp'";
            $endpoint->eda->sql($sql);

            $sql = "UPDATE endpointman_global_vars SET value='" . $_POST['nmap_loc'] . "' WHERE var_name='nmap_location'";
            $endpoint->eda->sql($sql);

            $sql = "UPDATE endpointman_global_vars SET value='" . $_POST['arp_loc'] . "' WHERE var_name='arp_location'";
            $endpoint->eda->sql($sql);

            $sql = "UPDATE endpointman_global_vars SET value='" . $_POST['disable_help'] . "' WHERE var_name='disable_help'";
            $endpoint->eda->sql($sql);

            $sql = "UPDATE endpointman_global_vars SET value='" . $_POST['backup_check'] . "' WHERE var_name='backup_check'";
            $endpoint->eda->sql($sql);

            $sql = "UPDATE endpointman_global_vars SET value='" . $_POST['use_repo'] . "' WHERE var_name='use_repo'";
            $endpoint->eda->sql($sql);

            if ($_POST['cfg_type'] == 'http') {
                $endpoint->message['advanced_settings'] = "Updated! - Point your phones to: http://" . $_SERVER['SERVER_ADDR'] . "/provisioning/p.php/";
            } else {
                $endpoint->message['advanced_settings'] = "Updated!";
            }
        }
        //Because we are working with global variables we probably updated them, so lets refresh those variables
        $endpoint->global_cfg = $endpoint->eda->sql("SELECT var_name, value FROM endpointman_global_vars", 'getAssoc');

        if ($endpoint->global_cfg['server_type'] == 'http') {
            $endpoint->tpl->assign("type_http", 'yes');
        } else {
            $endpoint->tpl->assign("type_file", 'yes');
        }

        if ($endpoint->global_cfg['show_all_registrations']) {
            $dupext_selected = "checked";
        } else {
            $dupext_selected = "";
        }

        if ($endpoint->global_cfg['enable_ari']) {
            $ari_selected = "checked";
        } else {
            $ari_selected = "";
        }

        if ($endpoint->global_cfg['disable_help']) {
            $help_selected = "checked";
        } else {
            $help_selected = "";
        }

        if ($endpoint->global_cfg['allow_hdfiles']) {
            $allow_hdfiles = "checked";
        } else {
            $allow_hdfiles = "";
        }

        if ($endpoint->global_cfg['tftp_check']) {
            $tftp_checked = "checked";
        } else {
            $tftp_checked = "";
        }

        if ($endpoint->global_cfg['backup_check']) {
            $backup_checked = "checked";
        } else {
            $backup_checked = "";
        }

        if ($endpoint->global_cfg['use_repo']) {

            $endpoint->tpl->assign("git_enabled", TRUE);
            $use_repo = "checked";
            $path = $endpoint->has_git();
            $o = getcwd();
            chdir(PHONE_MODULES_PATH);
            
            if(isset($_REQUEST['git_branch'])) {
                if(preg_match('/remotes\/origin\/(.*)/i', $_REQUEST['git_branch'],$matches)) {
                    //Pull from a remote.
                    if(!exec($path.' checkout origin_'. $matches[1])) {
                        //We must remote track this branch
                        exec($path . ' branch --track origin_'.$matches[1].' origin/'.$matches[1]);
                        exec($path . ' checkout origin_'.$matches[1]);
                    }
                    exec($path . ' pull origin ' . $matches[1]);
                } else {
                    exec($path.' checkout '. $_REQUEST['git_branch'],$output);
                }
            }
            
            exec($path . ' fetch origin');
            exec($path . ' branch -a',$output);
            
            foreach($output as $data) {
                if(!preg_match('/Your branch is ahead of/i', $data)) {
                    $selected = preg_match('/\*/i', $data) ? TRUE : FALSE;
                    $data = preg_replace('/\*/i', '', $data);
                    $branchoutput[] = array(
                        'name' => $data,
                        'selected' => $selected
                    );
                }
            }
            $endpoint->tpl->assign("branches", $branchoutput);
            chdir($o);
        } else {
            $use_repo = "";
        }

        if ($endpoint->global_cfg['debug']) {
            $debug_selected = "checked";
            global $debug;
            $debug = $debug . print_r($_REQUEST, true);
            $endpoint->tpl->assign("debug", $debug);
        } else {
            $debug_selected = "";
        }


        $endpoint->tpl->assign("package_server", $endpoint->global_cfg['update_server']);

        $endpoint->tpl->assign("help_selected", $help_selected);
        $endpoint->tpl->assign("dupext_selected", $dupext_selected);

        $endpoint->tpl->assign("ari_selected", $ari_selected);
        $endpoint->tpl->assign("debug_selected", $debug_selected);

        $endpoint->tpl->assign("hdfiles_selected", $allow_hdfiles);

        $endpoint->tpl->assign("tftp_selected", $tftp_checked);

        $endpoint->tpl->assign("backup_selected", $backup_checked);

        $endpoint->tpl->assign("use_repo", $use_repo);

        $endpoint->tpl->assign("ip", $_SERVER["SERVER_ADDR"]);
        $endpoint->tpl->assign("srvip", $endpoint->global_cfg['srvip']);
        $endpoint->tpl->assign("arp_location", $endpoint->global_cfg['arp_location']);
        $endpoint->tpl->assign("nmap_location", $endpoint->global_cfg['nmap_location']);
        $endpoint->tpl->assign("asterisk_location", $endpoint->global_cfg['asterisk_location']);
        $endpoint->tpl->assign("ntp_server", $endpoint->global_cfg['ntp']);

        $endpoint->tpl->assign("config_location", $endpoint->global_cfg['config_location']);
        $endpoint->tpl->assign("list_tz", $endpoint->listTZ($endpoint->global_cfg['tz']));
        $endpoint->tpl->assign("brand_list", $endpoint->brands_available());

        $endpoint->prepare_message_box();

        echo $endpoint->tpl->draw('advanced_settings_settings');
        break;
}
