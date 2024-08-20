<?php
/**
 * Ajax Select File
 *
 * @author Andrew Nagy
 * @license MPL / GPLv2 / LGPL
 * @package Provisioner
 */

require 'functions.inc';
$endpoint = new endpointmanager();

include 'json.inc';

function in_array_recursive($needle, $haystack) {
    $it = new RecursiveIteratorIterator(new RecursiveArrayIterator($haystack));
    foreach($it AS $element) {
        if($element == $needle) {
            return TRUE;
        }
    } 
    return FALSE;
}

if(($_REQUEST['id'] == "") OR ($_REQUEST['id'] == "0")) {
	$out[0]['optionValue'] = "";
	$out[0]['optionDisplay'] = "";
	echo json_encode($out);
	die();
}

if($_REQUEST['atype'] == "model") {
	$sql = "SELECT * FROM endpointman_model_list WHERE enabled = 1 AND brand =". $_GET['id'];
} elseif ($_REQUEST['atype'] == "template") {
	$sql = "SELECT id, name as model FROM  endpointman_template_list WHERE  product_id = '". $_GET['id']."'";
} elseif ($_REQUEST['atype'] == "mtemplate") {
	$sql = "SELECT id, name as model FROM  endpointman_template_list WHERE  model_id = '". $_GET['id']."'";
} elseif ($_REQUEST['atype'] == "template2") {
	$sql = "SELECT DISTINCT endpointman_template_list.id, endpointman_template_list.name as model FROM endpointman_template_list, endpointman_model_list, endpointman_product_list WHERE endpointman_template_list.product_id = endpointman_model_list.product_id AND endpointman_model_list.product_id = endpointman_product_list.id AND endpointman_model_list.id = '". $_GET['id']."'";
} elseif ($_REQUEST['atype'] == "model_clone") {
        $sql = "SELECT endpointman_model_list.id, endpointman_model_list.model as model FROM endpointman_model_list, endpointman_product_list WHERE endpointman_product_list.id = endpointman_model_list.product_id AND endpointman_model_list.enabled = 1 AND endpointman_model_list.hidden = 0 AND product_id = '". $_GET['id']."'";
} elseif ($_REQUEST['atype'] == "lines") {
    if(isset($_REQUEST['macid'])) {
        $sql = "SELECT endpointman_model_list.max_lines FROM endpointman_model_list,endpointman_line_list,endpointman_mac_list WHERE endpointman_mac_list.id = endpointman_line_list.mac_id AND endpointman_model_list.id = endpointman_mac_list.model AND endpointman_line_list.luid = ". $_REQUEST['macid'];
    } elseif(isset($_REQUEST['mac'])) {
        $sql = "SELECT id FROM endpointman_mac_list WHERE mac = '".$endpoint->mac_check_clean($_REQUEST['mac'])."'";
        $macid = $endpoint->eda->sql($sql,'getOne');
        if($macid) {
            $_REQUEST['mac'] = $macid;
            $sql = "SELECT endpointman_model_list.max_lines FROM endpointman_model_list,endpointman_line_list,endpointman_mac_list WHERE endpointman_mac_list.id = endpointman_line_list.mac_id AND endpointman_model_list.id = endpointman_mac_list.model AND endpointman_mac_list.id = ". $macid;
        } else {
            unset($_REQUEST['mac']);
            $sql = "SELECT max_lines FROM endpointman_model_list WHERE id = '". $_GET['id']."'";
        }
    } else {
        $sql = "SELECT max_lines FROM endpointman_model_list WHERE id = '". $_GET['id']."'";
    }
}

if (($_REQUEST['atype'] == "template") OR ($_REQUEST['atype'] == "template2") OR ($_REQUEST['atype'] == "mtemplate")) {
	$out[0]['optionValue'] = 0;
	$out[0]['optionDisplay'] = "Custom...";
	$i=1;
} elseif ($_REQUEST['atype'] == "model") {
	$out[0]['optionValue'] = 0;
	$out[0]['optionDisplay'] = "";
	$i=1;
} else {
	$i=0;
}

if(($_REQUEST['atype'] == "lines") && (!isset($_REQUEST['mac'])) && (!isset($_REQUEST['macid']))) {
    $count = $endpoint->eda->sql($sql,'getOne');
    for($z=0;$z<$count;$z++) {
        $result[$z]['id'] = $z + 1;
        $result[$z]['model'] = $z + 1;
    }
} elseif(isset($_REQUEST['macid'])) {
    $result = $endpoint->linesAvailable($_REQUEST['macid']);
} elseif(isset($_REQUEST['mac'])) {
    $result = $endpoint->linesAvailable(NULL,$_REQUEST['mac']);
} else {
    $result = $endpoint->eda->sql($sql,'getAll', \PDO::FETCH_ASSOC);
}

foreach($result as $row) {
        if((isset($_REQUEST['macid'])) OR (isset($_REQUEST['mac']))) {
            $out[$i]['optionValue'] = $row['value'];
            $out[$i]['optionDisplay'] = $row['text'];
        } else {
            $out[$i]['optionValue'] = $row['id'];
            $out[$i]['optionDisplay'] = $row['model'];
        }
	$i++;
}


echo json_encode($out);