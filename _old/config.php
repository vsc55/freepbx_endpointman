<?php
/**
 * Endpoint Manager config File
 *
 * @author Andrew Nagy
 * @license MPL / GPLv2 / LGPL
 * @package Provisioner
 */

include 'includes/functions.inc';

$debug = NULL;
$endpoint = new endpointmanager();

if (!is_writeable(PHONE_MODULES_PATH)) {
    chmod(PHONE_MODULES_PATH, 0764);
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(PHONE_MODULES_PATH), RecursiveIteratorIterator::SELF_FIRST);
    foreach ($iterator as $item) {
        chmod($item, 0764);
    }
}

if (isset($_REQUEST['page'])) {
    $page = $_REQUEST['page'];
} else {
    $page = "";
}


if ($endpoint->global_cfg['debug']) {
    $debug .= "Request Variables: \n" . print_r($_REQUEST, TRUE);
}