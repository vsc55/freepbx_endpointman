<?PHP

function getMethod()
{
	$method   = $_SERVER['REQUEST_METHOD'];
	$override = isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']) ? $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] : (isset($_GET['method']) ? $_GET['method'] : '');
	if ($method == 'POST' && strtoupper($override) == 'PUT') {
		$method = 'PUT';
	}
    elseif ($method == 'POST' && strtoupper($override) == 'DELETE')
    {
		$method = 'DELETE';
	}
	return $method;
}





$bootstrap_settings['freepbx_auth'] = false;
if (!@include_once(getenv('FREEPBX_CONF') ? getenv('FREEPBX_CONF') : '/etc/freepbx.conf')) {
    include_once('/etc/asterisk/freepbx.conf');
}

$epm = FreePBX::create()->Endpointman;


define('PROVISIONER_BASE', $amp_conf['AMPWEBROOT'].'/admin/modules/_ep_phone_modules/');
$server_type = FreePBX::Endpointman()->configmod->get("server_type");


//Check if it's allowed in FreePBX through Endpoint Manager first
if ((!isset($server_type)) OR ($server_type != 'http')) {
	header('HTTP/1.1 403 Forbidden', true, 403);
	echo "<h1>"._("Error 403 Forbidden")."</h1>";
	echo _("Access denied!");
	die();
}


$provis_ip = FreePBX::Endpointman()->configmod->get("srvip");

if(((getMethod() == 'PUT') OR (getMethod() == 'POST'))) {
    //write log files or other files to drive. not sussed out yet completely.

    /* PUT data comes in on the stdin stream */
    //$putdata = fopen("php://input", "r");

    /* Open a file for writing */
    //$fp = fopen($endpoint->global_cfg['config_location'] . $_SERVER['REDIRECT_URL'], "a");

    /* Read the data 1 KB at a time
        and write to the file */
    //while ($data = fread($putdata, 1024))
    //    fwrite($fp, $data);

    /* Close the streams */
    //fclose($fp);
    //fclose($putdata);
    header('HTTP/1.1 200 OK', true, 200);
    die();
}

if(getMethod() == "GET")
{
    # Workaround for SPAs that don't actually request their type of device
    # Assume they're 504G's. Faulty in firmware 7.4.3a
    $filename = basename($_SERVER["REQUEST_URI"]);
    $web_path = 'http://'.$_SERVER["SERVER_NAME"].dirname($_SERVER["PHP_SELF"]).'/';
    /*
    if ($filename == "p.php") { 
            $filename = "spa502G.cfg";
            $_SERVER['REQUEST_URI']=$_SERVER['REQUEST_URI']."/spa502G.cfg";
            $web_path = $web_path."p.php/";
    }
     */
    
    # Firmware Linksys/SPA504G-7.4.3a is broken and MUST be upgraded.
    if (preg_match('/7.4.3a/', $_SERVER['HTTP_USER_AGENT'])) {
            $str = '<flat-profile><Upgrade_Enable group="Provisioning/Firmware_Upgrade">Yes</Upgrade_Enable>';
            $str .= '<Upgrade_Rule group="Provisioning/Firmware_Upgrade">http://'.$provis_ip.'/current.bin</Upgrade_Rule></flat-profile>';
            echo $str;
            exit;
    }
	
    $filename = str_replace('p.php/','', $filename);
    $strip = str_replace('spa', '', $filename);
    if(preg_match('/[0-9A-Fa-f]{12}/i', $strip, $matches) && !(preg_match('/[0]{10}[0-9]{2}/i',$strip))) {
        
        #Just moved this Block of code up to fix the provisioning for Snom Phones
        require_once (PROVISIONER_BASE.'endpoint/base.php');
        $data = Provisioner_Globals::dynamic_global_files($filename, FreePBX::Endpointman()->configmod->get("config_location"), $web_path);
        if($data !== FALSE) {
            echo $data;
        } 
        else {
        	header("HTTP/1.0 404 Not Found", true, 404);
        	echo "<h1>"._("Error 404 Not Found")."</h1>";
        	echo _("File not Found!");
        	die();
        }
        
    	exit;
        $mac_address = $matches[0];
        
        $sql = "SELECT id FROM `endpointman_mac_list` WHERE `mac` LIKE '%" . $mac_address . "%'";
        $mac_id = sql($sql, 'getOne');
        $phone_info = FreePBX::Endpointman()->get_phone_info($mac_id);
		$files = FreePBX::Endpointman()->prepare_configs($phone_info, FALSE, FALSE);
        
        if(!$files) {
            header("HTTP/1.0 500 Internal Server Error", true, 500);
            echo "<h1>"._("Error 500 Internal Server Error")."</h1>";
            echo _("System Failure!");
            die();
        }
        
        if (array_key_exists($filename, $files))
        {
            echo $files[$filename];
        }
        else
        {
            header("HTTP/1.0 404 Not Found", true, 404);
            echo "<h1>"._("Error 404 Not Found")."</h1>";
            echo _("File not Found!");
            die();
        }

    } 
} 
else {
    header('HTTP/1.1 403 Forbidden', true, 403);
    echo "<h1>"._("Error 403 Forbidden")."</h1>";
    echo _("Access denied!");
    die();
}
