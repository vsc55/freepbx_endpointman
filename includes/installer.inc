<?PHP
/**
 * Endpoint Manager Install File
 *
 * @author Andrew Nagy
 * @license MPL / GPLv2 / LGPL
 * @package Provisioner
 */

require 'functions.inc';

$endpoint = new endpointmanager();

echo "<html><head><title>Installer</title></head><body>";

if(!function_exists("out")) {
    function out($text){
        echo $text."<br />";
    }
}

if($_REQUEST['type'] == "brand") {
    $endpoint->download_brand($_REQUEST['id']);
} elseif($_REQUEST['type'] == "js-multiple") {
    $list = explode(",",$_REQUEST['id']);
    sort($list,SORT_STRING);

    foreach($list as $data) {

    }
} elseif($_REQUEST['type'] == "firmware") {
    $endpoint->install_firmware($_REQUEST['id']);
} elseif($_REQUEST['type'] == "manual_install") {
    switch($_REQUEST['install_type']) {
        case "export_brand":
            $sql = 'SELECT `name`, `directory` FROM `endpointman_brand_list` WHERE `id` = '.$_REQUEST['package'].'';
            $row = $endpoint->eda->sql($sql, 'getRow', \PDO::FETCH_ASSOC);
            echo "Exporting ". $row['name']."<br/>";
            if(!file_exists(PHONE_MODULES_PATH."/temp/export/")) {
                mkdir(PHONE_MODULES_PATH."/temp/export/");
            }
            $time = time();
            exec("tar zcf ".PHONE_MODULES_PATH."temp/export/".$row['directory']."-".$time.".tgz --exclude .svn --exclude firmware -C ".PHONE_MODULES_PATH."/endpoint ".$row['directory']);
            echo "Done! Click this link to download:<a href='modules/_ep_phone_modules/temp/export/".$row['directory']."-".$time.".tgz'>Here</a>";
            break;
        case "upload_master_xml":
            if (file_exists(PHONE_MODULES_PATH."temp/master.xml")) {
                $handle = fopen(PHONE_MODULES_PATH."temp/master.xml", "rb");
                $contents = stream_get_contents($handle);
                fclose($handle);
                @$a = simplexml_load_string($contents);
                if($a===FALSE) {
                    echo "Not a valid xml file";
                    break;
                } else {
                    rename(PHONE_MODULES_PATH."temp/master.xml", PHONE_MODULES_PATH."master.xml");
                    echo "Move Successful<br />";
                    $endpoint->brand_update_check();
                    echo "Updating Brands<br />";
                }
            } else {
            }
            break;
        case "upload_provisioner":
            if (file_exists(PHONE_MODULES_PATH."temp/".$_REQUEST['package'])) {
                echo "Extracting Provisioner Package <br />";
                exec("tar -xvf ".PHONE_MODULES_PATH.'temp/'. $_REQUEST['package'] ." -C ".PHONE_MODULES_PATH."temp/");

                if(!file_exists(PHONE_MODULES_PATH."endpoint")) {
                    echo "Creating Provisioner Directory <br />";
                    mkdir(PHONE_MODULES_PATH."endpoint");
                }

                $endpoint_last_mod = filemtime(PHONE_MODULES_PATH."temp/endpoint/base.php");

                rename(PHONE_MODULES_PATH."temp/endpoint/base.php", PHONE_MODULES_PATH."endpoint/base.php");

                echo "Updating Last Modified <br />";
                $sql = "UPDATE endpointman_global_vars SET value = '".$endpoint_last_mod."' WHERE var_name = 'endpoint_vers'";
                $endpoint->eda->sql($sql);
            }
            break;
        case "upload_brand":
	    if ($endpoint->global_cfg['debug']) echo "upload_brand(): Debug is set to ". $endpoint->global_cfg['debug'] ." <br />";
//	    Bramd file (*.tgz) will have been copied into the location under which all endpoint definitions reside + /temp/
            if (file_exists(PHONE_MODULES_PATH."temp/".$_REQUEST['package'])) {
//		The untarred brand file will go into /tmp (or wherever the "temporary" location the OS tells us) + /epm-temp/
                $temp_directory = sys_get_temp_dir() . "/epm_temp/";
		if (!file_exists($temp_directory)) {
		    echo "Creating EPM temp directory <br />";
                    mkdir($temp_directory);
                }
		if ($endpoint->global_cfg['debug']) {
		    echo "Extracting Tarball ".PHONE_MODULES_PATH.'temp/'. $_REQUEST['package']." to ".$temp_directory." ........";
		} else {
		    echo "Extracting Tarball........ ";
		}
                exec("tar -xvf ".PHONE_MODULES_PATH.'temp/'. $_REQUEST['package'] ." -C ".$temp_directory);
                echo "Done!<br />";

                $package = basename($_REQUEST['package'], ".tgz");    
                $package = explode("-",$package);

		if ($endpoint->global_cfg['debug']) echo "Looking for file ".$temp_directory.$package[0]." to pass on to update_brand() <br />";
                if(file_exists($temp_directory.$package[0])) {
		    $endpoint->update_brand($package[0],FALSE);
//                  Note: no need to delete/unlink/rmdir as this is handled in update_brand()
                } else {
                    echo "Please name the Package the same name as your brand! <br />";
                }       
            } else {
//		Did not find the <brand>.tgz file
                $endpoint->error['upload'] = "No File Provided";
		echo "File ".PHONE_MODULES_PATH."temp/".$_REQUEST['package']." not found. <br />";
            }
            break;
    }
}

echo "<hr>\n\t<a href=\"#\" onclick=\"parent.close_module_actions(true);\" style=\"text-decoration:none\" />"._("Return")."</a></body></html>";
