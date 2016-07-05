<?PHP
/**
 * Endpoint Manager Brand Model Manager File
 *
 * @author Andrew Nagy
 * @license MPL / GPLv2 / LGPL
 * @package Provisioner
 */

$check_for_updates = FALSE;
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

echo $endpoint->tpl->draw('global_header');
if (!isset($_REQUEST['subpage'])) {
    $_REQUEST['subpage'] = "manager";
    $endpoint->tpl->assign("subhead_area", 'manager');
} else {
    $endpoint->tpl->assign("subhead_area", $_REQUEST['subpage']);
}
echo $endpoint->tpl->draw('brand_model_subheader');

switch ($_REQUEST['subpage']) {
    case "manager":

        global $type;

        if((isset($_REQUEST['button_install'])) OR (isset($_REQUEST['button_update']))) {
            if(isset($_REQUEST['brand'])) {
                $installer = array("type" => 'brand', "id" => $_REQUEST['brand']);
            }
        } elseif(isset($_REQUEST['product'])) {
            if(isset($_REQUEST['button_install_firmware'])) {
                $installer = array("type" => 'firmware', "id" => $_REQUEST['product']);
            } elseif(isset($_REQUEST['button_update_firmware'])) {
                $endpoint->remove_firmware($_REQUEST['product']);
            $installer = array("type" => 'firmware', "id" => $_REQUEST['product']);
            } elseif(isset($_REQUEST['button_remove_firmware'])) {
                $endpoint->remove_firmware($_REQUEST['product']);
                $message = "Firmware Removed";
            }
        } elseif(isset($_REQUEST['button_disable'])) {
            if(isset($_REQUEST['model'])) {
                $sql = "UPDATE endpointman_model_list SET enabled = 0 WHERE id = '".$_REQUEST['model']."'";
            } elseif(isset($_REQUEST['brand'])) {
                $sql = "UPDATE endpointman_brand_list SET enabled = 0 WHERE id = '".$_REQUEST['model']."'";
            }
            $endpoint->eda->sql($sql);
        } elseif(isset($_REQUEST['button_enable'])) {
            if(isset($_REQUEST['model'])) {
                $sql = "UPDATE endpointman_model_list SET enabled = 1 WHERE id = '".$_REQUEST['model']."'";
            } elseif(isset($_REQUEST['brand'])) {
                $sql = "UPDATE endpointman_brand_list SET enabled = 1 WHERE id = '".$_REQUEST['model']."'";
            }
            $endpoint->eda->sql($sql);
        } elseif(isset($_REQUEST['button_uninstall'])) {
            if(isset($_REQUEST['brand'])) {
                $endpoint->remove_brand($_REQUEST['brand']);
            }
        } elseif(isset($_REQUEST['button_check_for_updates'])) {
            //die('hi');
            $brand_up = $endpoint->update_check();
                $endpoint->tpl->assign("update_check", 1);

            $check_for_updates = TRUE;
        } elseif(isset($_REQUEST['install-jstree'])) {
                $installer = array("type" => 'js-multiple', "id" => $_REQUEST['hidden']);
        }

        $sql="SELECT * from endpointman_brand_list WHERE id > 0 AND hidden = 0 ORDER BY id ASC ";
        $brand_list = $endpoint->eda->sql($sql,'getAll',DB_FETCHMODE_ASSOC);
        $i = 0;
        $note = "";
        $row_out = array();
        foreach($brand_list as $row) {
            $row_out[$i] = $row;
                $row_out[$i]['cfg_ver'] = date("n-j-y",$row['cfg_ver']) . " at " . date("g:ia",$row['cfg_ver']);

            $row_out[$i]['count'] = $i;
            if($check_for_updates) {
                $id = $endpoint->arraysearchrecursive($row['name'], $brand_up,'name');

                $id = $id[0];
                if((isset($brand_up[$id]['update'])) AND ($row['installed'] == 1)) {
                    $row_out[$i]['update'] = $brand_up[$id]['update'];
                    //$endpoint->add_freepbx_notification("PU_".$brand_up[$id]['name'], '', "Brand \'".$brand_up[$id]['name']."\' has updates available", "Old Version: ".$row['cfg_ver']." New Version: ".$brand_up[$id]['version']."<br/>"."Changes: ". $brand_up[$id]['changes'].$note, "");
                } else {
                    $row_out[$i]['update'] = NULL;
                }
                if(isset($brand_up[$id]['update_vers'])) {
                    $row_out[$i]['update_vers'] = date("n-j-y",$brand_up[$id]['update_vers']) . " at " . date("g:ia",$brand_up[$id]['update_vers']);
                } else {
                    $row_out[$i]['update_vers'] = NULL;
                }
            }
            if($row['installed']){
                $j = 0;
                $sql = 'SELECT * FROM endpointman_product_list WHERE hidden = 0 AND brand = '.$row['id'].' ORDER BY long_name ASC';
                        $product_list = $endpoint->eda->sql($sql,'getAll',DB_FETCHMODE_ASSOC);
                        foreach($product_list as $row2) {
                    $row_out[$i]['products'][$j] = $row2;
                    if($check_for_updates) {
                        if(($temp = $endpoint->firmware_update_check($row2['id'])) AND (array_key_exists('firmware_vers', $row2)) AND ($row2['firmware_vers'] > 0)) {
                            $row_out[$i]['products'][$j]['update_fw'] = 1;
                            $row_out[$i]['products'][$j]['update_vers_fw'] = $temp['data']['version'];
                            //$endpoint->add_freepbx_notification("PU_".$row2['long_name'], '', "There is a firmware update for phone module ".$row2['long_name'], "Old Version: ".$row2['firmware_vers']." New Version: ".$temp['data']['version'].$note, "");
                        } else {
                            $row_out[$i]['products'][$j]['update_fw'] = 0;
                        }
                    }

                    $row_out[$i]['products'][$j]['fw_type'] = $endpoint->firmware_local_check($row2['id']);


                    if(1 == 1) {
                        $sql = "SELECT * FROM endpointman_model_list WHERE hidden = 0 AND product_id = '".$row2['id']."'";
                                        $model_list = $endpoint->eda->sql($sql,'getAll',DB_FETCHMODE_ASSOC);
                        $k = 0;
                        foreach($model_list as $row3) {
                            $row_out[$i]['products'][$j]['models'][$k] = $row3;
                            if($row_out[$i]['products'][$j]['models'][$k]['enabled']){
                                $row_out[$i]['products'][$j]['models'][$k]['enabled_checked'] = 'checked';
                            }
                            $k++;
                        }
                    }
                    $j++;
                }
            }
            $i++;
        }

        if (!isset($error)) {
            $error = "";
        }
        if (!isset($_POST['brand'])) {
            $brand = NULL;
        } else {
            $brand = $_POST['brand'];
        }

        if (isset($installer)) {
            $endpoint->tpl->assign("brand", $brand);
                $endpoint->tpl->assign("installer", $installer);
        }

        $endpoint->tpl->assign("web_var", "?type=$type");
        $endpoint->tpl->assign("brand2_list", $row_out);

        $error_message = NULL;
        foreach($endpoint->error as $key => $error) {
            $error_message .= $error;
            if($endpoint->global_cfg['debug']) {
                $error_message .= " Function: [".$key."]";
            }
            $error_message .= "<br />";
        }
        if(isset($error_message)) {
            $endpoint->display_message_box($error_message,$endpoint->tpl,1);
        } elseif(isset($message)) {
            $endpoint->display_message_box($message,$endpoint->tpl,0);
        }

        //draw the template
        //echo "test";
        echo $endpoint->tpl->draw( 'brand_model_manager' );
        break;

    case "editor":
        if (isset($_REQUEST['button_hide'])) {
            if (isset($_REQUEST['model'])) {
                $sql = "UPDATE endpointman_model_list SET hidden = 1 WHERE id = '" . $_REQUEST['model'] . "'";
            } elseif (isset($_REQUEST['brand'])) {
                $sql = "UPDATE endpointman_brand_list SET hidden = 1 WHERE id = " . $_REQUEST['brand'];
            } elseif (isset($_REQUEST['product'])) {
                $sql = "UPDATE endpointman_product_list SET hidden = 1 WHERE id = '" . $_REQUEST['product'] . "'";
            }
            $endpoint->eda->sql($sql);
        } elseif (isset($_REQUEST['button_show'])) {
            if (isset($_REQUEST['model'])) {
                $sql = "UPDATE endpointman_model_list SET hidden = 0 WHERE id = '" . $_REQUEST['model'] . "'";
            } elseif (isset($_REQUEST['brand'])) {
                $sql = "UPDATE endpointman_brand_list SET hidden = 0 WHERE id = " . $_REQUEST['brand'];
            } elseif (isset($_REQUEST['product'])) {
                $sql = "UPDATE endpointman_product_list SET hidden = 0 WHERE id = '" . $_REQUEST['product'] . "'";
            }
            $endpoint->eda->sql($sql);
        }
        $sql = "SELECT * from endpointman_brand_list WHERE id > 0 ORDER BY id ASC ";
        $result = & $endpoint->eda->sql($sql, 'getAll', DB_FETCHMODE_ASSOC);
        $i = 0;
        foreach ($result as $row) {
            $row_out[$i] = $row;
            $row_out[$i]['count'] = $i;
            if ($row['installed']) {
                $j = 0;
                $sql = 'SELECT * FROM endpointman_product_list WHERE brand = ' . $row['id'] . ' ORDER BY long_name ASC';
                $result2 = & $endpoint->eda->sql($sql, 'getAll', DB_FETCHMODE_ASSOC);
                $xml_data = "";
                foreach ($result2 as $row2) {
                    $row_out[$i]['products'][$j] = $row2;
                    $sql = 'SELECT * FROM endpointman_model_list WHERE product_id = ' . $row2['id'];
                    $result3 = & $endpoint->eda->sql($sql, 'getAll', DB_FETCHMODE_ASSOC);
                    $k = 0;
                    foreach ($result3 as $row3) {
                        $row_out[$i]['products'][$j]['models'][$k] = $row3;
                        $k++;
                    }
                    $j++;
                }
            }
            $i++;
        }
        $endpoint->tpl->assign("brand2_list", $row_out);
        $endpoint->prepare_message_box();
        echo $endpoint->tpl->draw('brand_model_editor');
        break;
}
