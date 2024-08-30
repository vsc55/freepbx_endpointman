<?php

/**
 * Endpoint Manager Functions File
 *
 * @author Andrew Nagy
 * @license MPL / GPLv2 / LGPL
 * @package Provisioner
 */
class endpointmanager {

    //Load this class upon construction of the class

  

    /**
     *
     * @global array $amp_conf Data taken about FreePBX from FreePBX as a global, we move this into a public variable
     * @global object $db Pear DB information, we move this into a public variable
     */

    function __construct() {
        
    }


}

function endpointman_flush_buffers() {
    ob_end_flush();
    //ob_flush();
    flush();
    ob_start();
}

function endpointman_update_progress_bar($out) {
    echo '<script type="text/javascript">document.getElementById(\'DivExample\').innerHTML="%' . $out . '";</script>';
}

function endpointmanager_read_header($ch, $string) {
    global $file_size, $fout;
    $length = strlen($string);
    $regs = "";
    preg_match("/(Content-Length:) (.*)/i", $string, $regs);
    if ((isset($regs[2])) AND ($regs[2] <> "")) {
        $file_size = intval($regs[2]);
    }
    //ob_flush();
    endpointman_flush_buffers();
    return $length;
}

function endpointmanager_read_body($ch, $string) {
    global $fout, $file_size, $downloaded, $lastseen, $progress_bar;
    $length = strlen($string);
    $downloaded += intval($length);
    $downloadProgress = round(100 * (1 - $downloaded / $file_size), 0);
    $downloadProgress = 100 - $downloadProgress;
    if ($lastseen <> $downloadProgress and $downloadProgress < 101) {
        if ($progress_bar) {
            endpointman_update_progress_bar($downloadProgress);
        }
        $lastseen = $downloadProgress;
    }
    if ($fout)
        fwrite($fout, $string);
    //ob_flush();
    endpointman_flush_buffers();
    return $length;
}
