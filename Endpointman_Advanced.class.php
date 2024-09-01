<?php
/**
 * Endpoint Manager Object Module - Sec Advanced
 *
 * @author Javier Pastor
 * @license MPL / GPLv2 / LGPL
 * @package Provisioner
 */

namespace FreePBX\modules;
use FreePBX;

#[\AllowDynamicProperties]
class Endpointman_Advanced
{
	public function __construct($epm)
	{
		$this->epm 		  = $epm;
		$this->freepbx    = $epm->freepbx;
		$this->db 		  = $epm->freepbx->Database;
		$this->config 	  = $epm->freepbx->Config;
		$this->epm_config = $epm->epm_config;

        if (! file_exists($this->epm->MODULE_PATH))
		{
            die(sprintf(_("[%s] Can't Load Local Endpoint Manager Directory!"), __CLASS__));
        }
		if (! file_exists($this->epm->PHONE_MODULES_PATH))
		{
            die(sprintf(_('[%s] Endpoint Manager can not create the modules folder!'), __CLASS__));
        }
	}

	// public function myShowPage(&$pagedata)
	// {
	// 	if(empty($pagedata))
	// 	{
	// 		$pagedata['settings'] = array(
	// 			"name" => _("Settings"),
	// 			"page" => '/views/epm_advanced_settings.page.php'
	// 		);
	// 		$pagedata['oui_manager'] = array(
	// 			"name" => _("OUI Manager"),
	// 			"page" => '/views/epm_advanced_oui_manager.page.php'
	// 		);
	// 		$pagedata['poce'] = array(
	// 			"name" => _("Product Configuration Editor"),
	// 			"page" => '/views/epm_advanced_poce.page.php'
	// 		);
	// 		$pagedata['iedl'] = array(
	// 			"name" => _("Import/Export My Devices List"),
	// 			"page" => '/views/epm_advanced_iedl.page.php'
	// 		);
	// 		$pagedata['manual_upload'] = array(
	// 			"name" => _("Package Import/Export"),
	// 			"page" => '/views/epm_advanced_manual_upload.page.php'
	// 		);
	// 	}
	// }

	public function ajaxRequest($req, &$setting, array $data)
	{
		$allowRequest = array(
			"oui",
			"oui_add",
			"oui_del",
			"poce_list_brands",
			"poce_select",
			"poce_select_file",
			"poce_save_file",
			"poce_save_as_file",
			"poce_sendid",
			"poce_delete_config_custom",
			"list_files_brands_export",
			"saveconfig"
		);
		if (in_array(strtolower($req), $allowRequest))
		{
			$setting['authenticate'] = true;
			$setting['allowremote'] = false;
			return true;
		}
		return false;
	}

    public function ajaxHandler(array $data)
	{
		$txt = array(
			'settings' => array(
				'error' 		  => _("Error!"),
				'save_changes' 	  => _("Saving Changes..."),
				'save_changes_ok' => _("Saving Changes... Ok!"),
				'opt_invalid' 	  => _("Invalid Option!")
			)
		);

		if (empty($data) || !is_array($data))
		{
			$retarr = array(
				"status" => false,
				"message" => _("Empty data received or data is not foromatted correctly!")
			);
		}
		else
		{
			$command_allow  = true;
			$command 		= $data['command'] 	  ?? '';
			$request		= $data['request'] 	  ?? array();
			$module_tab		= $data['module_tab'] ?? '';

			switch($module_tab)
			{
				case 'settings':
					switch ($command)
					{
						case "saveconfig":
							$retarr = $this->epm_advanced_settings_saveconfig($request);
							break;
		
						default:
							$command_allow = false;
					}
					break;

				case 'oui_manager':
					switch ($command)
					{
						case "oui":
							//$sql = 'SELECT endpointman_oui_list.id, endpointman_oui_list.oui , endpointman_brand_list.name, endpointman_oui_list.custom FROM endpointman_oui_list , endpointman_brand_list WHERE endpointman_oui_list.brand = endpointman_brand_list.id ORDER BY endpointman_oui_list.oui ASC';
							$sql = 'SELECT T1.id, T1.oui, T2.name, T1.custom FROM endpointman_oui_list as T1 , endpointman_brand_list as T2 WHERE T1.brand = T2.id ORDER BY T1.oui ASC';
							$data = sql($sql, 'getAll', \PDO::FETCH_ASSOC);
							$ret = array();
							foreach ($data as $item) {
								$ret[] = array('id' => $item['id'], 'oui' => $item['oui'], 'brand' => $item['name'], 'custom' => $item['custom']);
							}
							return $ret;
							break;
		
						case "oui_add":
							$retarr = $this->epm_advanced_oui_add();
							break;
		
						case "oui_del":
							$retarr = $this->epm_advanced_oui_remove();
							break;
		
						default:
							$command_allow = false;
					}
					break;


				case 'iedl':
					switch ($command)
					{
						default:
							$command_allow = false;
					}
					
					break;
				
				case 'poce':
					switch ($command)
					{
						case "poce_list_brands":
							$retarr = $this->epm_advanced_poce_list_brands();
							break;

						case "poce_select":
							$retarr = $this->epm_advanced_poce_select($data);
							break;

						case "poce_select_file":
							$retarr = $this->epm_advanced_poce_select_file();
							break;

						case "poce_save_file":
						case "poce_save_as_file":
							$retarr = $this->epm_advanced_poce_save_file();
							break;

						case "poce_sendid":
							$retarr = $this->epm_advanced_poce_sendid();
							break;

						case "poce_delete_config_custom":
							$retarr = $this->epm_advanced_poce_delete_config_custom();
							break;

						default:
							$command_allow = false;
					}
					break;
				
				case 'manual_upload':
					switch ($command)
					{
						case "list_files_brands_export":
							$retarr = $this->epm_advanced_manual_upload_list_files_brans_export();
							break;

						default:
							$command_allow = false;
					}
					break;

				default:
					$retarr = array(
						"status" => false,
						"message" => sprintf(_("Tab '%s' not valid!"), $module_tab)
					);
			}



			if (! $command_allow)
			{
				$retarr = array(
					"status" => false,
					"message" => sprintf(_("Command '%s' not found!"), $command)
				);
			}
			else
			{
				if (! empty($txt[strtolower($module_tab)]))
				{
					$retarr['txt'] = $txt[strtolower($module_tab)];
				}
			}
		}
		return $retarr;
	}

	public function doConfigPageInit($module_tab = "", $command = "") {
		switch ($module_tab)
		{
			case "oui_manager":
				break;

			case "iedl":
				switch ($command) {
					case "export":
						$this->epm_advanced_iedl_export();
						break;

					case "import":
						$this->epm_advanced_iedl_import();
						echo "<br /><hr><br />";
						exit;
						break;
				}
				break;

			case "manual_upload":
				switch ($command) {
					case "export_brands_availables":
						$this->epm_advanced_manual_upload_export_brans_available();
						echo "<br /><hr><br />";
						exit;
						break;

					case "export_brands_availables_file":
						$this->epm_advanced_manual_upload_export_brans_available_file();
						exit;
						break;

					case "upload_brand":
						$this->epm_advanced_manual_upload_brand();
						echo "<br /><hr><br />";
						exit;
						break;

					case "upload_provisioner":
						$this->epm_advanced_manual_upload_provisioner();
						echo "<br /><hr><br />";
						exit;
						break;
				}
				break;
		}
	}

	public function getRightNav($request, $params = array()) {
		return "";
	}

	public function getActionBar($request) {
		return "";
	}


	/**** FUNCIONES SEC MODULO "epm_advanced\settings" ****/
	public function epm_advanced_config_loc_is_writable()
	{
		$config_loc    = $this->epm->getConfig("config_loc");
		$tftp_writable = FALSE;
		if ((isset($config_loc)) AND ($config_loc != ""))
		{
			if ((file_exists($config_loc)) AND (is_dir($config_loc)))
			{
				if (is_writable($config_loc))
				{
					$tftp_writable = TRUE;
				}
			}
		}
		return $tftp_writable;
	}

	private function epm_advanced_settings_saveconfig (array $request)
	{
		$request_args 	  = array("name", "value");
		$request_args_int = array();
		$args_check 	  = $this->epm->system->check_request_args($request, $request_args, $request_args_int);

		switch(true)
		{
			case (empty($args_check)):
			case ($args_check === false):
				return array("status" => false, "message" => _("Error in the process of checking the request arguments!"));

			case ($args_check === true):
				break;

			case (is_string($args_check)):
			default:
				return array("status" => false, "message" => $args_check);
		}

		$name  = strtolower($request['name']);
		$value = $request['value'];

		switch($name)
		{
			case "enable_ari":
				$this->epm->setConfig('enable_ari', in_array($value, array("0", "1")) ? $value : "0");
				break;

			case "enable_debug":
				$this->epm->setConfig('debug', in_array($value, array("0", "1")) ? $value : "0");
				break;

			case "disable_help":
				$this->epm->setConfig('disable_help', in_array($value, array("0", "1")) ? $value : "0");
				break;
	
			case "disable_endpoint_warning":
				$this->epm->setConfig('disable_endpoint_warning', in_array($value, array("0", "1")) ? $value : "0");
				break;

			case "allow_dupext":
				$this->epm->setConfig('show_all_registrations', in_array($value, array("0", "1")) ? $value : "0");
				break;

			case "allow_hdfiles":
				$this->epm->setConfig('allow_hdfiles', in_array($value, array("0", "1")) ? $value : "0");
				break;

			case "tftp_check":
				$this->epm->setConfig('tftp_check', in_array($value, array("0", "1")) ? $value : "0");
				break;

			case "backup_check":
				$this->epm->setConfig('backup_check', in_array($value, array("0", "1")) ? $value : "0");
				break;

			case "use_repo":
				$value = strtolower($value);
				if (($value == "yes") and (! $this->epm->has_git()))
				{
					return array("status" => false, "message" => _("Git not installed!"));
				}
				else
				{
					$this->epm->setConfig('use_repo', $value == "yes" ? "1" : "0");
				}
				break;

			case "config_loc":
				//No trailing slash. Help the user out and add one :-)
				$value = $this->epm->system->buildPath($value);
				if (empty($value))
				{
					return array("status" => false, "message" => _("No Configuration Location Defined!"));
				}
				elseif (!file_exists($value))
				{
					return array("status" => false, "message" => _("Directory does not exist!"));
				}
				elseif (!is_dir($value))
				{
					return array("status" => false, "message" => _("The path '%s' is not a directory!", $value));
				}
				elseif (!is_writable($value))
				{
					return array("status" => false, "message" => _("Directory '%s' Not Writable!", $value));
				}
				else
				{
					$this->epm->setConfig('config_location', $value);
				}
				break;

			case "srvip":
				$this->epm->setConfig('srvip', trim($value));
				break;

			case "intsrvip":
				$this->epm->setConfig('intsrvip', trim($value));
				break;

			case "tz":
				$this->epm->setConfig('tz', $value);
				break;

			case "adminpass":
				$this->epm->setConfig('adminpass', trim($value));
				break;

			case "userpass":
				$this->epm->setConfig('userpass', trim($value));
				break;
				
			case "ntp_server":
				$this->epm->setConfig('ntp', trim($value));
				break;

			case "nmap_loc":
				$this->epm->setConfig('nmap_location', trim($value));
				break;

			case "arp_loc":
				$this->epm->setConfig('arp_location', trim($value));
				break;

			case "asterisk_loc":
				$this->epm->setConfig('asterisk_location', trim($value));
				break;

			case "tar_loc":
				$this->epm->setConfig('tar_location', trim($value));
				break;

			case "netstat_loc":
				$this->epm->setConfig('netstat_location', trim($value));
				break;

			case "package_server":
				$this->epm->setConfig('update_server', trim($value));
				break;
			
			case "whoami_loc":
				$this->epm->setConfig('whoami_location', trim($value));
				break;

			case "nohup_loc":
				$this->epm->setConfig('nohup_location', trim($value));
				break;

			case "groups_loc":
				$this->epm->setConfig('groups_location', trim($value));
				break;

			case "cfg_type":
				$value = strtolower($value);
				if (!in_array($value, array("http", "https", 'file')))
				{
					return array("status" => false, "message" => _("Invalid server type '%s'!", $value));
				}
				if (in_array($value, array("http", "https")))
				{
					$symlink  = $this->epm->system->buildPath($this->config->get('AMPWEBROOT'), "provisioning");
					$reallink = $this->epm->system->buildPath($this->epm->MODULE_PATH, "provisioning");
					if ((!is_link($symlink)) OR (!readlink($symlink) == $reallink))
					{
						if (!symlink($reallink, $symlink))
						{
							return array("status"  => false, "message" => sprintf(_("Your permissions are wrong on %s, web provisioning link not created!"), $this->config->get('AMPWEBROOT')));
						}
					}
				}
				$this->epm->setConfig('server_type', $value);
				break;

			default:
				return array("status" => false, "message" => sprintf(_("Name invalid: %s"), $name) );
		}

		return array("status" => true, "message" => "OK", "name" => $name, "value" => $value);
	}


	/**** FUNCIONES SEC MODULO "epm_advanced\poce" ****/
	public function epm_advanced_poce_list_brands()
	{
		//$sql = 'SELECT * FROM endpointman_product_list WHERE hidden = 0 AND id > 0 ORDER BY long_name ASC';
		//$sql = 'SELECT * FROM endpointman_product_list WHERE hidden = 0 AND id > 0 AND brand IN (SELECT id FROM asterisk.endpointman_brand_list where hidden = 0) ORDER BY long_name ASC';
		$sql = 'SELECT * FROM endpointman_product_list WHERE hidden = 0 AND id IN (SELECT DISTINCT product_id FROM asterisk.endpointman_model_list where enabled = 1) AND brand IN (SELECT id FROM asterisk.endpointman_brand_list where hidden = 0) ORDER BY long_name ASC';
		$product_list = sql($sql, 'getAll', \PDO::FETCH_ASSOC);
		$i = 0;
		$temp = array();
		foreach ($product_list as $srow)
		{
			$temp[$i]['id'] = $srow['id'];
			$temp[$i]['name'] = $srow['long_name'];
			$temp[$i]['name_mini'] = substr($srow['long_name'], 0, 40).(strlen($srow['long_name']) > 40 ? "..." : "");
			$i++;
		}
		return array("status" => true, "message" => _("Ok!"), "ldatos" => $temp);
	}

	public function epm_advanced_poce_select(array $data)
	{
		$request 		= $data['request'] ?? array();
		$product_select = $request['product_select'] ?? '';
		$path_setup 	= $this->epm->system->buildPath($this->epm->PHONE_MODULES_PATH, 'setup.php');


		if (empty($product_select))
		{
			return array("status" => false, "message" => _("No send Product Select!"));
		}
		elseif (! is_numeric($product_select))
		{
			return array("status" => false, "message" => _("Product Select send is not number!"));
		}
		elseif ($product_select < 0)
		{
			return array("status" => false, "message" => _("Product Select send is number not valid!"));
		}
		elseif (!file_exists($path_setup))
		{
			return array("status" => false, "message" => _("File setup.php not found!"));
		}
		else
		{
			// $sql = 'SELECT * FROM `endpointman_product_list` WHERE `hidden` = 0 AND `id` = '.$dget['product_select'];
			// $product_select_info = sql($sql, 'getRow', \PDO::FETCH_ASSOC);
			$product_select_info = $this->epm->get_hw_product($product_select);


			$sql			= sprintf("SELECT epl.cfg_dir, ebl.directory, epl.config_files FROM %s as epl, %s as ebl WHERE epl.brand = ebl.id AND epl.id ='%s'", "endpointman_product_list", "endpointman_brand_list", $product_select);
			$row 			= sql($sql, 'getRow', \PDO::FETCH_ASSOC);
			$config_files	= explode(",", $row['config_files'] ?? ''); 
			$file_list		= array();
			$sql_file_list	= array();
			
			foreach ($config_files as $config_files_data)
			{
				$file_list[] = array(
					'value' => $product_select,
					'text'  => $config_files_data
				);
			}
			// if (empty($file_list)) { $file_list = NULL; }

			$sql = sprintf("SELECT * FROM %s WHERE product_id = '%s'", "endpointman_custom_configs", $product_select);
			$data = sql($sql,'getAll', \PDO::FETCH_ASSOC);
			foreach ($data as $row2)
			{
				$sql_file_list[] = array(
					'value' => $row2['id'],
					'text'	=> $row2['name'],
					'ref' 	=> $row2['original_name']
				);
			}
			// if (empty($sql_file_list)) { $sql_file_list = NULL; }


			

			require_once($path_setup);

			$class 		  = sprintf("endpoint_%s_%s_phone", $row['directory'], $row['cfg_dir']);
			$base_class   = sprintf("endpoint_%s_base", $row['directory']);
			$master_class = "endpoint_base";

			/*********************************************************************************
			 *** Quick Fix for FreePBX Distro
			 *** I seriously want to figure out why ONLY the FreePBX Distro can't do autoloads.
			 **********************************************************************************/
			if (!class_exists($master_class))
			{
				\ProvisionerConfig::endpointsAutoload($master_class);
			}
			if (!class_exists($base_class))
			{
				\ProvisionerConfig::endpointsAutoload($base_class);
			}
			if (!class_exists($class))
			{
				\ProvisionerConfig::endpointsAutoload($class);
			}
			
			//end quick fix
			$phone_config = new $class();

			//TODO: remove
			$template_file_list = array(
				array(
					'value' => 'template_data_custom.xml',
					'text'  => 'template_data_custom.xml'
				)
			);

			$sql = sprintf("SELECT id, model FROM %s WHERE product_id = '%s' AND enabled = 1 AND hidden = 0", "endpointman_model_list", $product_select);
			$data = sql($sql, 'getAll', \PDO::FETCH_ASSOC);
			foreach ($data as $list)
			{
				$template_file_list[] = array(
					'value' => $list['id'],
					'text'  => "template_data_" . $list['model'] . "_custom.xml"
				);
			}

			$retarr = array("status" => true, "message" => _("OK"),
							"product_select" 	  => $product_select,
							"product_select_info" => $product_select_info,
							"file_list" 		  => $file_list,
							"template_file_list"  => $template_file_list,
							"sql_file_list" 	  => $sql_file_list);
		}
		return $retarr;
	}

	public function epm_advanced_poce_select_file()
	{
		$arrVal['VAR_REQUEST'] = array("product_select", "file_id", "file_name", "type_file");
		foreach ($arrVal['VAR_REQUEST'] as $valor) {
			if (! array_key_exists($valor, $_REQUEST)) {
				return array("status" => false, "message" => _("No send value!")." [".$valor."]");
			}
		}
		if (! is_numeric($_REQUEST['product_select'])) {
			return array("status" => false, "message" => _("Product Select send is not number!"));
		}
		elseif ($_REQUEST['product_select'] < 0) {
			return array("status" => false, "message" => _("Product Select send is number not valid!"));
		}

		$dget['product_select'] = $_REQUEST['product_select'];
		$dget['file_name'] 		= $_REQUEST['file_name'];
		$dget['file_id'] 		= $_REQUEST['file_id'];
		$dget['type_file'] 		= $_REQUEST['type_file'];

		if ($dget['type_file'] == "sql") {
			$sql = 'SELECT * FROM endpointman_custom_configs WHERE id =' . $dget['file_id'];
			$row = sql($sql, 'getrow', \PDO::FETCH_ASSOC);

			$type 				= $dget['type_file'];
			$sendidt 			= $row['id'];
			$product_select 	= $row['product_id'];
			$save_as_name_value = $row['name'];
			$original_name 		= $row['original_name'];
			$filename 			= $row['name'];
			$location 			= "SQL: ". $row['name'];
			$config_data 		= $this->display_htmlspecialchars($row['data']);

		}
		elseif ($dget['type_file'] == "file") {
			$sql = "SELECT cfg_dir,directory,config_files FROM endpointman_product_list,endpointman_brand_list WHERE endpointman_product_list.brand = endpointman_brand_list.id AND endpointman_product_list.id = '" . $dget['product_select'] . "'";
			$row = sql($sql, 'getRow', \PDO::FETCH_ASSOC);

			$config_files = explode(",", $row['config_files']);
			//TODO: Añadir validacion para ver si $dget['file_name'] esta en el array $config_files

			$filename = $dget['file_name'];

			

			$pathfile = $this->epm->system->buildPath($this->epm->PHONE_MODULES_PATH, 'endpoint', $row['directory'], $row['cfg_dir'] , $filename);


			if (is_readable($pathfile)) {
				if(filesize($pathfile)>0) {
					$handle   = fopen($pathfile, "rb");
					$contents = fread($handle, filesize($pathfile));
					fclose($handle);
					$contents = $this->display_htmlspecialchars($contents);
				}
				else {
					$contents = "";
				}

				$type 				= $dget['type_file'];
				$sendidt 			= $dget['file_id'];
				$product_select 	= $dget['product_select'];
				$save_as_name_value = $filename;
				$original_name 		= $filename;
				$filename 			= $filename;
				$location 			= $pathfile;
				$config_data 		= $contents;
			}
			else {
				$retarr = array("status" => false, "message" => _("File not readable, check the permission! ").$filename);
			}
		}
		elseif ($dget['type_file'] == "tfile")
		{
			if ($dget['file_id'] == "template_data_custom.xml")
			{
				$sendidt = "";
				$original_name = $dget['file_name'];
				$config_data = "";
			}
			else {

				$sql = "SELECT * FROM endpointman_model_list WHERE id = '" . $dget['file_id'] . "'";
				$data = sql($sql, 'getRow', \PDO::FETCH_ASSOC);

				$sendidt = $data['id'];
				$original_name = $dget['file_name'];
				$config_data = unserialize($data['template_data']);
				$config_data = $this->epm->generate_xml_from_array ($config_data, 'node');
			}

			$type = $dget['type_file'];
			$product_select = $dget['product_select'];
			$save_as_name_value = $dget['file_name'];
			$filename = $dget['file_name'];
			$location = $dget['file_name'];
		}

		$retarr = array("status" 			 => true,
						"message" 			 => _("OK"),
						"type" 				 => $type,
						"sendidt" 			 => $sendidt,
						"product_select" 	 => $product_select,
						"save_as_name_value" => $save_as_name_value,
						"original_name" 	 => $original_name,
						"filename" 			 => $filename,
						"location" 			 => $location,
						"config_data" 		 => $config_data
					);

		unset($dget);
		return $retarr;
	}







	//TODO: PENDIENTE REVISAR
	function epm_advanced_poce_sendid()
	{
		if (! isset($_REQUEST['product_select'])) {
			$retarr = array("status" => false, "message" => _("No send Product Select!"));
		}
		elseif (! isset($_REQUEST['type_file'])) {
			$retarr = array("status" => false, "message" => _("No send Type File!"));
		}
		elseif (! isset($_REQUEST['sendid'])) {
			$retarr = array("status" => false, "message" => _("No send SendID!"));
		}
		else {
			$dget['product_select'] = $_REQUEST['product_select'];
			$dget['type_file'] = $_REQUEST['type_file'];
			$dget['sendid'] = $_REQUEST['sendid'];
			$dget['original_name'] = $_REQUEST['original_name'];
			$dget['config_text'] = $_REQUEST['config_text'];



			//DEBUGGGGGGGGGGGGG
			return;
			if ($dget['type_file'] == "sql") {
				$sql = "SELECT cfg_dir,directory,config_files FROM endpointman_product_list,endpointman_brand_list WHERE endpointman_product_list.brand = endpointman_brand_list.id AND endpointman_product_list.id = '" . $dget['product_select'] . "'";
				$row = sql($sql, 'getrow', \PDO::FETCH_ASSOC);
				$this->submit_config($row['directory'], $row['cfg_dir'], $dget['original_name'], $dget['config_text']);
				$retarr = array("status" => true, "message" => "Sent! Thanks :-)");
			}
			elseif ($dget['type_file'] == "file") {
				$sql = "SELECT cfg_dir,directory,config_files FROM endpointman_product_list,endpointman_brand_list WHERE endpointman_product_list.brand = endpointman_brand_list.id AND endpointman_product_list.id = '" . $dget['product_select'] . "'";
				$row = sql($sql, 'getRow', \PDO::FETCH_ASSOC);
				$error = $this->submit_config($row['directory'], $row['cfg_dir'], $dget['original_name'], $dget['config_text']);
				$retarr = array("status" => true, "message" => "Sent! Thanks :-)");
			}
			else {
				$retarr = array("status" => false, "message" => "Type not valid!");
			}
			unset ($dget);
		}
		return $retarr;
	}







	function epm_advanced_poce_save_file()
	{
		$arrVal['VAR_REQUEST'] = array("product_select", "sendid", "type_file", "config_text", "save_as_name", "file_name", "original_name");
		foreach ($arrVal['VAR_REQUEST'] as $valor) {
			if (! array_key_exists($valor, $_REQUEST)) {
				return array("status" => false, "message" => _("No send value!")." [".$valor."]");
			}
		}

		$dget['command'] = $_REQUEST['command'];
		$dget['type_file'] = $_REQUEST['type_file'];
		$dget['sendid'] = $_REQUEST['sendid'];
		$dget['product_select'] = $_REQUEST['product_select'];
		$dget['save_as_name'] = $_REQUEST['save_as_name'];
		$dget['original_name'] = $_REQUEST['original_name'];
		$dget['file_name'] = $_REQUEST['file_name'];
		$dget['config_text'] = $_REQUEST['config_text'];

		if ($dget['type_file'] == "file") {
			if ($dget['command'] == "poce_save_file")
			{
				$sql = "SELECT cfg_dir,directory,config_files FROM endpointman_product_list,endpointman_brand_list WHERE endpointman_product_list.brand = endpointman_brand_list.id AND endpointman_product_list.id = '" . $dget['product_select'] . "'";
				$row = sql($sql, 'getRow', \PDO::FETCH_ASSOC);
				$config_files = explode(",", $row['config_files']);

				if ((is_array($config_files)) AND (in_array($dget['file_name'], $config_files)))
				{
					$pathdir = $this->epm->system->buildPath($this->epm->PHONE_MODULES_PATH, 'endpoint', $row['directory'], $row['cfg_dir']);


					$pathfile = $this->epm->system->buildPath($pathdir, $dget['file_name']);
					if ((! file_exists($pathfile)) AND (! is_writable($pathdir))) {
						$retarr = array("status" => false, "message" => "Directory is not Writable (".$pathdir.")!");
					}
					elseif (! is_writable($pathfile)) {
						$retarr = array("status" => false, "message" => "File is not Writable (".$pathfile.")!");
					}
					else
					{
						$wfh = fopen($pathfile, 'w');
						fwrite($wfh, $dget['config_text']);
						fclose($wfh);
						$retarr = array("status" => true, "message" => "Saved to Hard Drive!");
					}
				}
				else {
					$retarr = array("status" => false, "message" => "The File no existe in the DataBase!");
				}
			}
			elseif ($dget['command'] == "poce_save_as_file")
			{
				$db = $this->db;
				$sql = 'INSERT INTO endpointman_custom_configs (name, original_name, product_id, data) VALUES (?,?,?,?)';
				$q = $db->prepare($sql);
				$ob = $q->execute(array(addslashes($dget['save_as_name']), addslashes($dget['original_name']), $dget['product_select'], addslashes($dget['config_text'])));
				$newidinsert = $db->lastInsertId();
				$retarr = array("status" => true, "message" => "Saved to Database!");

				$retarr['type_file'] = "sql";
				$retarr['location'] = "SQL: ". $dget['save_as_name'];
				$retarr['sendidt'] = $newidinsert;
			}
			else {
				$retarr = array("status" => false, "message" => "Command not valid!");
			}
		}
		elseif ($dget['type_file'] == "sql")
		{
			if ($dget['command'] == "poce_save_file")
			{
				$sql = "UPDATE endpointman_custom_configs SET data = '" . addslashes($dget['config_text']) . "' WHERE id = " . $dget['sendid'];
				sql($sql);
				$retarr = array("status" => true, "message" => "Saved to Database!");
			}
			elseif ($dget['command'] == "poce_save_as_file")
			{
				$db = $this->db;
				$sql = 'INSERT INTO endpointman_custom_configs (name, original_name, product_id, data) VALUES (?,?,?,?)';
				$q = $db->prepare($sql);
				$ob = $q->execute(array(addslashes($dget['save_as_name']), addslashes($dget['original_name']), $dget['product_select'], addslashes($dget['config_text'])));
				$newidinsert = $db->lastInsertId();
				$retarr = array("status" => true, "message" => "Saved to Database!");

				$retarr['type_file'] = "sql";
				$retarr['location'] = "SQL: ". $dget['save_as_name'];
				$retarr['sendidt'] = $newidinsert;
			}
			else {
				$retarr = array("status" => false, "message" => "Command not valid!");
			}
		}
		elseif ($dget['type_file'] == "tfile")
		{
			/*
			
			$db = $this->db;
			$sql = 'INSERT INTO endpointman_custom_configs (name, original_name, product_id, data) VALUES (?,?,?,?)';
			$q = $db->prepare($sql);
@ -790,7 +790,7 @@ class Endpointman_Advanced
			$retarr['type_file'] = "sql";
			$retarr['location'] = "SQL: ". $dget['save_as_name'];
			$retarr['sendidt'] = $newidinsert;
			*/
		}
		else {
			$retarr = array("status" => false, "message" => "Type not valid!");
		}

		$retarr['original_name'] = $dget['original_name'];
		$retarr['file_name'] = $dget['file_name'];
		$retarr['save_as_name'] = $dget['save_as_name'];

		unset($dget);
		return $retarr;
	}
	
	function epm_advanced_poce_delete_config_custom()
	{
		$arrVal['VAR_REQUEST'] = array("product_select", "type_file", "sql_select");
		foreach ($arrVal['VAR_REQUEST'] as $valor) {
			if (! array_key_exists($valor, $_REQUEST)) {
				return array("status" => false, "message" => _("No send value!")." [".$valor."]");
			}
		}

		$dget['type_file'] = $_REQUEST['type_file'];
		$dget['product_select'] = $_REQUEST['product_select'];
		$dget['sql_select'] = $_REQUEST['sql_select'];

		if ($dget['type_file'] == "sql") {
			$sql = "DELETE FROM endpointman_custom_configs WHERE id =" . $dget['sql_select'];
			sql($sql);
			unset ($sql);
			$retarr = array("status" => true, "message" => "File delete ok!");
		}
		else { $retarr = array("status" => false, "message" => _("Type File not valid!")); }

		unset($dget);
		return $retarr;
	}


	/**** FUNCIONES SEC MODULO "epm_advanced\manual_upload" ****/
	public function epm_advanced_manual_upload_list_files_brans_export()
	{
		$path_tmp_dir = $this->epm->EXPORT_PATH;

		$array_list_files = array();
		$array_count_brand = array();


		$array_list_exception= array(".", "..", ".htaccess");
		if(file_exists($path_tmp_dir))
		{
			if(is_dir($path_tmp_dir))
			{
				$l_files = scandir($path_tmp_dir, 1);
				$i = 0;
				foreach ($l_files as $archivo) {
					if (in_array($archivo, $array_list_exception)) { continue; }

					$pathandfile =  $this->epm->system->buildPath($path_tmp_dir, $archivo);
					$brand = substr(pathinfo($archivo, PATHINFO_FILENAME), 0, -11);
					$ftime = substr(pathinfo($archivo, PATHINFO_FILENAME), -10);
					$datetime = new \DateTime();
					$datetime->setTimestamp($ftime);

					$array_count_brand[] = $brand;
					$array_list_files[$i] = array(
							"brand" 	=> $brand,
							"pathall" 	=> $pathandfile,
							"path" 		=> $path_tmp_dir,
							"file" 		=> $archivo,
							"filename" 	=> pathinfo($archivo, PATHINFO_FILENAME),
							"extension" => pathinfo($archivo, PATHINFO_EXTENSION),
							"basename"  => basename($archivo),
							"size" 		=> filesize($pathandfile),
							"timer" 	=> $ftime,
							"timestamp" => $datetime->format('[Y-m-d H:i:s]'),
							"mime_type" => mime_content_type($pathandfile),
							"is_dir" 	=> is_dir($pathandfile),
							"is_file"	=> is_file($pathandfile),
							"is_link" 	=> is_link($pathandfile),
							"readlink" 	=> (is_link($pathandfile) == true ? readlink($pathandfile) : NULL)
						);

					$i++;
				}
				unset ($l_files);

				$array_count_brand = array_count_values($array_count_brand);
				ksort ($array_count_brand);
				$array_count_brand_end = array();

				foreach($array_count_brand as $key => $value) {
					$array_count_brand_end[] = array('name' => $key , 'num' => $value);
				}

				$retarr = array(
					"status" 	  => true,
					"message" 	  => _("List Done!"),
					"countlist"   => count($array_list_files),
					"list_files"  => $array_list_files,
					"list_brands" => $array_count_brand_end
				);
				unset ($array_count_brand_end);
				unset ($array_count_brand);
				unset ($array_list_files);
			}
			else {
				$retarr = array("status" => false, "message" => _("Not is directory: ") . $path_tmp_dir);
			}
		} else {
			$retarr = array("status" => false, "message" => _("Directory no exists: ") . $path_tmp_dir);
		}
		return $retarr;
	}

	public function epm_advanced_manual_upload_brand()
	{
		if (count($_FILES["files"]["error"]) == 0) {
			out(_("Error: Can Not Find Uploaded Files!"));
		}
		else {
			foreach ($_FILES["files"]["error"] as $key => $error) {
				out(sprintf(_("Importing brand file %s..."), $_FILES["files"]["name"][$key]));

				if ($error != UPLOAD_ERR_OK) {
					out(sprintf(_("Error: %s"), $this->file_upload_error_message($error)));
				}
				else
				{
					$uploads_dir = $this->epm->TEMP_PATH;
					$name 		 = $_FILES["files"]["name"][$key];
					$extension 	 = pathinfo($name, PATHINFO_EXTENSION);

					if ($extension == "tgz")
					{
						$tmp_name 		  = $_FILES["files"]["tmp_name"][$key];
						$uploads_dir_file = $this->epm->system->buildPath($uploads_dir, $name);
						move_uploaded_file($tmp_name, $uploads_dir_file);

						if (file_exists($uploads_dir_file))
						{
							// $temp_directory = $this->epm->system->buildPath(sys_get_temp_dir(), "/epm_temp/");
							$temp_directory = $this->epm->PROVISIONER_PATH;
							if (!file_exists($temp_directory))
							{
								outn(_("Creating EPM temp directory..."));
								if (mkdir($temp_directory) == true) {
									out(_("Done!"));
								}
								else {
									out(_("Error!"));
								}
							}
							if (file_exists($temp_directory))
							{
								if ($this->epm->getConfig('debug')) {
									outn(sprintf(_("Extracting Tarball %s to %s... "), $uploads_dir_file, $temp_directory));
								} else {
									outn(_("Extracting Tarball... "));
								}
								//TODO: PENDIENTE VALIDAR SI EL EXEC NO DA ERROR!!!!!
								exec( sprintf("%s -xvf %s -C %s", $this->epm->getConfig("tar_location"), $uploads_dir_file, $temp_directory) );
								// exec("tar -xvf ".$uploads_dir_file." -C ".$temp_directory);
								out(_("Done!"));

								$package 	  = basename($name, ".tgz");
								$package 	  = explode("-",$package);
								$package_path = $this->epm->system->buildPath($temp_directory, $package[0]);

								if ($this->epm->getConfig('debug')) {
									out(sprintf(_("Looking for file %s to pass on to update_brand() ... "), $package_path));
								} else {
									out(_("Looking file and update brand's ... "));
								}
								if(file_exists($package_path))
								{
									$this->epm_config->update_brand($package[0], FALSE);
									//Note: no need to delete/unlink/rmdir as this is handled in update_brand()
								} else {
									out(_("Please name the Package the same name as your brand!"));
								}
							}
						}
						else {
							out(_("Error: No File Provided!"));
							//echo "File ".$this->epm->PHONE_MODULES_PATH."/temp/".$_REQUEST['package']." not found. <br />";
						}
					}
					else {
						out(_("Error: Invalid File Extension!"));
					}
		 		}
			}
		}
	}

	public function epm_advanced_manual_upload_provisioner ()
	{
		if (count($_FILES["files"]["error"]) == 0) {
			out(_("Error: Can Not Find Uploaded Files!"));
		}
		else
		{
			foreach ($_FILES["files"]["error"] as $key => $error) {
				out(sprintf(_("Importing Provisioner file %s..."), $_FILES["files"]["name"][$key]));

				if ($error != UPLOAD_ERR_OK) {
					out(sprintf(_("Error: %s"), $this->file_upload_error_message($error)));
				}
				else {
					$uploads_dir = $this->epm->EXPORT_PATH;

					$name = $_FILES["files"]["name"][$key];
					$extension = pathinfo($name, PATHINFO_EXTENSION);
					if ($extension == "tgz")
					{
						$tmp_name = $_FILES["files"]["tmp_name"][$key];
						$uploads_dir_file = $this->epm->system->buildPath($uploads_dir, $name);
						move_uploaded_file($tmp_name, $uploads_dir_file);

						if (file_exists($uploads_dir_file))
						{
							outn(_("Extracting Provisioner Package... "));
							//TODO: Pendiente añadir validacion si exec no da error!!!!
							exec( sprintf("%s -xvf %s -C %s", $this->epm->getConfig("tar_location"), $uploads_dir_file, $uploads_dir) );
							// exec("tar -xvf ".$uploads_dir_file." -C ".$uploads_dir."/");
							out(_("Done!"));

							
							$endpoint_dir = $this->epm->system->buildPath($this->epm->PHONE_MODULES_PATH, 'endpoint');
							if(!file_exists($endpoint_dir))
							{
								outn(_("Creating Provisioner Directory... "));
								if (mkdir($endpoint_dir) == true) {
									out(_("Done!"));
								}
								else {
									out(_("Error!"));
								}
							}

							if(file_exists($endpoint_dir))
							{
								$path_file_base_src  = $this->epm->system->buildPath($this->epm->TEMP_PATH, 'endpoint', 'base.php');
								$path_file_base_dest = $this->epm->system->buildPath($this->epm->PHONE_MODULES_PATH, 'endpoint', 'base.php');

								$endpoint_last_mod = filemtime($path_file_base_src);
								rename($path_file_base_src, $path_file_base_dest);

								outn(_("Updating Last Modified... "));
								$sql = "UPDATE endpointman_global_vars SET value = '".$endpoint_last_mod."' WHERE var_name = 'endpoint_vers'";
								sql($sql);
								out(_("Done!"));
							}

						} else {
							out(_("Error: File Temp no Exists!"));
						}
					} else {
						out(_("Error: Invalid File Extension!"));
					}
				}
			}
		}
	}

	public function epm_advanced_manual_upload_export_brans_available_file()
	{
		// Security Bug - GHSA-x9wc-qjrc-j7ww

		$request = $_REQUEST;

		$file_package = $request['file_package'] ?? '';
		$error 		  = 0;

		if (empty($request['file_package']))
		{
			$error = 404;
		}
		else
		{
			$file_package 	   = basename($file_package);
			$file_package_temp = $this->epm->system->buildPath($this->epm->EXPORT_PATH, $file_package);

			if (! file_exists($file_package_temp))
			{
				$error = 404;
			}
			else
			{
				header('Content-Description: File Transfer');
				header('Content-Type: application/octet-stream');
				header('Content-Disposition: attachment; filename="'.$file_package.'"');
				header('Expires: 0');
				header("Cache-Control: no-cache, must-revalidate");
				header('Pragma: public');
				header('Content-Length: ' . filesize($file_package_temp));
				readfile($file_package_temp);
				exit;
			}
			unset($file_package);
			unset($file_package_temp);
		}

		if ($error == 404)
		{
			header("HTTP/1.0 404 Not Found", true, $error);
			echo "404 Not Found";
			die();
		}
		exit;
	}

	public function epm_advanced_manual_upload_export_brans_available()
	{
		if ((! isset($_REQUEST['package'])) OR ($_REQUEST['package'] == "")) {
			out(_("Error: No package set!"));
		}
		elseif ((! is_numeric($_REQUEST['package'])) OR ($_REQUEST['package'] < 0)) {
			out(_("Error: Package not valid!"));
		}
		else {
			$dget['package'] = $_REQUEST['package'];

			$sql = 'SELECT `name`, `directory` FROM `endpointman_brand_list` WHERE `id` = '.$dget['package'].'';
			$row = sql($sql, 'getRow', \PDO::FETCH_ASSOC);

			if ($row == "") {
				out(_("Error: ID Package send not valid, brand not exist!"));
			}
			else {
				outn(sprintf(_("Exporting %s ... "), $row['name']));

				$time = time();
				//TODO: Pendiente validar si exec no retorna error!!!!!
				exec( sprintf("%s zcf %s --exclude .svn --exclude .git --exclude firmware -C %s %s",
						$this->epm->getConfig("tar_location"), 
						$this->epm->system->buildPath($this->epm->EXPORT_PATH, sprintf("%s-%s.tgz", $row['directory'], $time)), 
						$this->epm->system->buildPath($this->epm->PHONE_MODULES_PATH, 'endpoint'), 
						$row['directory']
					)
				);
				// exec("tar zcf ".$this->epm->PHONE_MODULES_PATH."/temp/export/".$row['directory']."-".$time.".tgz --exclude .svn --exclude firmware -C ".$this->epm->PHONE_MODULES_PATH."/endpoint ".$row['directory']);
				out(_("Done!") . "<br />");


				out(_("Click this link to download:"). "<br />");
				out("<a href='config.php?display=epm_advanced&subpage=manual_upload&command=export_brands_availables_file&file_package=".$row['directory']."-".$time.".tgz' class='btn btn-success btn-lg active btn-block' role='button' target='_blank'>" . _("Here")."</a>");
				//echo "Done! Click this link to download:<a href='modules/_ep_phone_modules/temp/export/".$row['directory']."-".$time.".tgz' target='_blank'>Here</a>";
			}
			unset ($dget);
		}
	}


	/**** FUNCIONES SEC MODULO "epm_advanced\iedl" ****/
	public function epm_advanced_iedl_export($sFileName = "devices_list.csv")
	{
		header("Content-type: text/csv");
		header('Content-Disposition: attachment; filename="'.$sFileName.'"');
		$outstream = fopen("php://output",'w');
		$sql = 'SELECT endpointman_mac_list.mac, endpointman_line_list.ipei, endpointman_brand_list.name, endpointman_model_list.model, endpointman_line_list.ext,endpointman_line_list.line FROM endpointman_mac_list, endpointman_model_list, endpointman_brand_list, endpointman_line_list WHERE endpointman_line_list.mac_id = endpointman_mac_list.id AND endpointman_model_list.id = endpointman_mac_list.model AND endpointman_model_list.brand = endpointman_brand_list.id';
		$result = sql($sql,'getAll',\PDO::FETCH_ASSOC);
		foreach($result as $row) {
			fputcsv($outstream, $row);
		}
		fclose($outstream);
		exit;
	}

	//Dave B's Q&D file upload security code (http://us2.php.net/manual/en/features.file-upload.php)
	public function epm_advanced_iedl_import()
	{
		if (count($_FILES["files"]["error"]) == 0) {
			out(_("Error: Can Not Find Uploaded Files!"));
		}
		else
		{
			//$allowedExtensions = array("application/csv", "text/plain", "text/csv", "application/vnd.ms-excel");
			$allowedExtensions = array("csv", "txt");
			foreach ($_FILES["files"]["error"] as $key => $error) {
				outn(sprintf(_("Importing CSV file %s ...<br />"), $_FILES["files"]["name"][$key]));

				if ($error != UPLOAD_ERR_OK) {
					out(sprintf(_("Error: %s"), $this->file_upload_error_message($error)));
				}
				else
				{
					//if (!in_array($_FILES["files"]["type"][$key], $allowedExtensions)) {
					if (!in_array(substr(strrchr($_FILES["files"]["name"][$key], "."), 1), $allowedExtensions)) {
						out(sprintf(_("Error: We support only CSV and TXT files, type file %s no support!"), $_FILES["files"]["name"][$key]));
					}
					elseif ($_FILES["files"]["size"][$key] == 0) {
						out(sprintf(_("Error: File %s size is 0!"), $_FILES["files"]["name"][$key]));
					}
					else {
						$uploadfile = $this->epm->system->buildPath($this->epm->MODULE_PATH, basename($_FILES["files"]["name"][$key]));
						$uploadtemp = $_FILES["files"]["tmp_name"][$key];

						if (move_uploaded_file($uploadtemp, $uploadfile)) {
							//Parse the uploaded file
							$handle = fopen($uploadfile, "r");
							$i = 1;
							while (($device = fgetcsv($handle, filesize($uploadfile))) !== FALSE) {
								if ($device[0] != "") {
									if ($mac = $this->mac_check_clean($device[0])) {
										$sql = "SELECT id FROM endpointman_brand_list WHERE name LIKE '%" . $device[1] . "%' LIMIT 1";
										//$res = sql($sql);
										$res = sql($sql, 'getAll', \PDO::FETCH_ASSOC);

										if (count(array($res)) > 0) {
											$brand_id = sql($sql, 'getOne');
										//	$brand_id = $brand_id[0];

											$sql_model = "SELECT id FROM endpointman_model_list WHERE brand = " . $brand_id . " AND model LIKE '%" . $device[2] . "%' LIMIT 1";
											$sql_ext = "SELECT extension, name FROM users WHERE extension LIKE '%" . $device[3] . "%' LIMIT 1";

											$line_id = isset($device[4]) ? $device[4] : 1;

											$res_model = sql($sql_model);
											if (count(array($res_model))) {
												$model_id = sql($sql_model, 'getRow', \PDO::FETCH_ASSOC);
												$model_id = $model_id['id'];

												$res_ext = sql($sql_ext);
												if (count(array($res_ext))) {
													$ext = sql($sql_ext, 'getRow', \PDO::FETCH_ASSOC);
													$description = $ext['name'];
													$ext = $ext['extension'];
//TODO: PENDIENTE ASIGNAR OBJ
FreePBX::Endpointman()->add_device($mac, $model_id, $ext, 0, $line_id, $description);

													//out(_("Done!"));
												} else {
													out(sprintf(_("Error: Invalid Extension Specified on line %d!"), $i));
												}
											} else {
												out(sprintf(_("Error: Invalid Model Specified on line %d!"), $i));
											}
										} else {
											out(sprintf(_("Error: Invalid Brand Specified on line %d!"), $i));
										}
									} else {
										out(sprintf(_("Error: Invalid Mac on line %d!"), $i));
									}
								}
								$i++;

							}
							fclose($handle);
							unlink($uploadfile);
							out(_("<font color='#FF0000'><b>Please reboot & rebuild all imported phones</b></font>"));
						} else {
							out(_("Error: Possible file upload attack!"));
						}
					}
				}
			}
		}
	}


	/**** FUNCIONES SEC MODULO "epm_advanced\oui_manager" ****/
	private function epm_advanced_oui_remove()
	{
		//TODO: Añadir validacion de si es custom o no
		if ((! isset($_REQUEST['id_del'])) OR ($_REQUEST['id_del'] == "")) {
			$retarr = array("status" => false, "message" => _("No ID set!"));
		}
		elseif ((! is_numeric($_REQUEST['id_del'])) OR ($_REQUEST['id_del'] < 0)) {
			$retarr = array("status" => false, "message" => _("ID  not valid!"), "id" => $_REQUEST['id']);
		}
		else
		{
			$dget['id'] = $_REQUEST['id_del'];

			$sql = "DELETE FROM endpointman_oui_list WHERE id = " . $dget['id'];
			sql($sql);

			$retarr = array("status" => true, "message" => "OK", "id" => $dget['id']);
			unset($dget);
		}
		return $retarr;
	}

	private function epm_advanced_oui_add()
	{
		//TODO: Pendiente añadir isExiste datos.
		if ((! isset($_REQUEST['number_new_oui'])) OR ($_REQUEST['number_new_oui'] == "")) {
			$retarr = array("status" => false, "message" => _("No OUI set!"));
		}
		elseif ((! isset($_REQUEST['brand_new_oui'])) OR ($_REQUEST['brand_new_oui'] == "")) {
			$retarr = array("status" => false, "message" => _("No Brand set!"));
		}
		else {
			$dget['oui'] = $_REQUEST['number_new_oui'];
			$dget['brand'] = $_REQUEST['brand_new_oui'];

			$sql = "INSERT INTO  endpointman_oui_list (oui, brand, custom) VALUES ('" . $dget['oui'] . "',  '" . $dget['brand'] . "',  '1')";
			sql($sql);

			$retarr = array("status" => true, "message" => "OK", "oui" => $dget['oui'], "brand" => $dget['brand']);
			unset($dget);
		}
		return $retarr;
	}


















    /**
     * Fixes the display are special strings so we can visible see them instead of them being transformed
     * @param string $contents a string of course
     * @return string fixed string
     */
    function display_htmlspecialchars($contents) {
    	$contents = str_replace("&amp;", "&amp;amp;", $contents);
    	$contents = str_replace("&lt;", "&amp;lt;", $contents);
    	$contents = str_replace("&gt;", "&amp;gt;", $contents);
    	$contents = str_replace("&quot;", "&amp;quot;", $contents);
    	$contents = str_replace("&#039;", "&amp;#039;", $contents);
    	return($contents);
    }

    /**
     * Taken from PHP.net. A list of errors returned when uploading files.
     * @param <type> $error_code
     * @return string
     */
    function file_upload_error_message($error_code) {
    	switch ($error_code) {
    		case UPLOAD_ERR_INI_SIZE:
    			return _('The uploaded file exceeds the upload_max_filesize directive in php.ini');
    		case UPLOAD_ERR_FORM_SIZE:
    			return _('The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form');
    		case UPLOAD_ERR_PARTIAL:
    			return _('The uploaded file was only partially uploaded');
    		case UPLOAD_ERR_NO_FILE:
    			return _('No file was uploaded');
    		case UPLOAD_ERR_NO_TMP_DIR:
    			return _('Missing a temporary folder');
    		case UPLOAD_ERR_CANT_WRITE:
    			return _('Failed to write file to disk');
    		case UPLOAD_ERR_EXTENSION:
    			return _('File upload stopped by extension');
    		default:
    			return _('Unknown upload error');
    	}
    }

	/**
     * This function takes a string and tries to determine if it's a valid mac addess, return FALSE if invalid
     * @param string $mac The full mac address
     * @return mixed The cleaned up MAC is it was a MAC or False if not a mac
     */
    function mac_check_clean($mac)
	{
		// regular expression that validates mac with :, -, spaces, or without separators
		$pattern = '/^([0-9a-f]{2}[:-]?){5}([0-9a-f]{2})$/i';

		// check if the mac complies with the pattern
		if (preg_match($pattern, $mac))
		{
			// clean the mac of non-hexadecimal characters and convert them to uppercase
			return strtoupper(preg_replace('/[^0-9a-f]/i', '', $mac));
		}
		// return false if not a valid mac address
		return false;
    }

}