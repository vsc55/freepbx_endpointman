<?php
/**
 * Endpoint Manager Object Module - Sec Config
 *
 * @author Javier Pastor
 * @license MPL / GPLv2 / LGPL
 * @package Provisioner
 */

namespace FreePBX\modules;

require_once('lib/epm_system.class.php');
require_once('lib/epm_packages.class.php');


#[\AllowDynamicProperties]
class Endpointman_Config
{
	public $error = array (
		'file2json' => '',
	);

	public function __construct($epm)
	{
		$this->epm 		 = $epm;
		$this->freepbx 	 = $epm->freepbx;
		$this->db 		 = $epm->freepbx->Database;
		$this->config 	 = $epm->freepbx->Config;
		$this->configmod = $epm->configmod;
		$this->system 	 = new Endpointman\epm_system();

		if (! file_exists($this->epm->MODULE_PATH))
		{
            die(sprintf(_("[%s] Can't Load Local Endpoint Manager Directory!"), __CLASS__));
        }
		if (! file_exists($this->epm->PHONE_MODULES_PATH))
		{
            die(sprintf(_('[%s] Endpoint Manager can not create the modules folder!'), __CLASS__));
        }        
	}

	public function myShowPage(&$pagedata) {

	}

	public function ajaxRequest($req, &$setting)
	{
		$arrVal = array(
			"saveconfig",
			"list_all_brand",
			"list_brand_model_hide"
		);

		if (in_array($req, $arrVal)) {
			$setting['authenticate'] = true;
			$setting['allowremote']   = false;
			return true;
		}
		return false;
	}

    public function ajaxHandler($module_tab = "", $command = "")
	{
		$txt = array(
			'ayuda_model' 		 => _("If we can activate the model set terminals of the models.<br /> If this model is disabled will not appear in the list of models that can be configured for PBX."),
			'ayuda_producto'	 => _('The button "Install Firmware" installs the necessary files to the server for the terminal alone are updated via TFTP or HTTP.<br /> The button "Remove frimware" delete files server products.<br /> The button "Update frimware" appears if a newer frimware detected on the server and asks if you want to update.<br /> The "Update" button appears when a new version of this model pack is detected.'),
			'ayuda_marca' 		 => _('The "Install" button installs the configuration package brand models we selected.<br /> The "Uninstall" button removes the package configuration models of the brand selected.<br /> The "Update" button appears if a new version of the package that is already installed to upgrade to the latest version is detected.'),
			'new_pack_mod' 		 => _("New Package Modified"),
			'pack_last_mod' 	 => _("Package Last Modified"),
			'check_update' 		 => _("Check for Update "),
			'check_online' 		 => _("Check Online "),
			'install'			 => _("Install"),
			'uninstall' 	 	 => _("Uninstall"),
			'update' 		 	 => _("Update"),
			'fw_install' 	 	 => _('FW Install'),
			'fw_uninstall' 	 	 =>  _('FW Delete'),
			'fw_update' 	 	 => _('FW Update'),
			'enable' 			 => _('Enable'),
			'disable' 			 => _('Disable'),
			'show' 				 => _("Show"),
			'hide' 				 => _("Hide"),
			'ready' 			 => _("Ready!"),
			'error' 			 => _("Error!"),
			'title_update' 		 => _("Update!"),
			'save_changes'		 => _("Saving Changes..."),
			'save_changes_ok' 	 => _("Saving Changes... Ok!"),
			'err_upload_content' => _("Upload Content!"),
			'check' 			 => _("Check for Updates..."),
			'check_ok' 			 => _("Check for Updates... Ok!"),
			'update_content' 	 => _("Update Content..."),
			'opt_invalid' 		 => _("Invalid Option!")
		);

		switch ($command)
		{
			case "saveconfig":
				$retarr = $this->epm_config_manager_saveconfig();
				break;

			case "list_all_brand":
				$retarr = array("status" => true, "message" => "OK", "datlist" => $this->epm_config_manager_hardware_get_list_all());
				break;

			case "list_brand_model_hide":
				$retarr = array("status" => true, "message" => "OK", "datlist" => $this->epm_config_manager_hardware_get_list_all_hide_show());
				break;

			default:
				$retarr = array("status" => false, "message" => _("Command not found!") . " [" .$command. "]");
				break;
		}
		$retarr['txt'] = $txt;
		return $retarr;
	}

	public function doConfigPageInit($module_tab = "", $command = "")
	{
		switch ($command)
		{
			case "check_for_updates":
				// Force flush all output buffers, need for AJAX
				$this->epm_config_manager_check_for_updates();
				echo "<br /><hr><br />";
				exit;
				break;

			case "manual_install":
				$this->epm_config_manual_install();
				echo "<br /><hr><br />";
				exit;
				break;

			case "firmware":
				$this->epm_config_manager_firmware();
				echo "<br /><hr><br />";
				exit;
				break;

			case "brand":
				$this->epm_config_manager_brand();
				echo "<br /><hr><br />";
				exit;
				break;
		}
	}

	public function getRightNav($request, $params = array()) {
		return "";
	}

	public function getActionBar($request) {
		return "";
	}


	/**** FUNCIONES SEC MODULO "epm_config\manager" ****/
	private function epm_config_manager_check_for_updates ()
	{
		// Force flush all output buffers, need for AJAX
		ob_implicit_flush(true);
		while (ob_get_level() > 0) {
			ob_end_flush();
		}

		out("<h3>"._("Update data...")."</h3>");
		if ($this->update_check(true) === false)
		{
			out (_("❌Something Went Wrong!"));
		}
		else
		{
			out (_("🔳 Process Completed!"));
		}
	}

	private function epm_config_manager_brand()
	{
		// Force flush all output buffers, need for AJAX
		ob_implicit_flush(true);
		while (ob_get_level() > 0) {
			ob_end_flush();
		}
						
		$request = freepbxGetSanitizedRequest();

		$id 	 	 = $request['idfw'] ?? '';
		$command_sub = $request['command_sub'] ?? '';
		
		if (!isset($id))
		{
			out(_("Error: No ID Received!"));
			return false;
		}
		else if (!is_numeric($id))
		{
			out(sprintf(_("Error: ID [%s] Received Is Not a Number!"), $id));
			return false;
		}

		switch(strtolower($command_sub))
		{
			case "brand_install":
			case "brand_update":
				$this->download_brand($id);
				break;

			case "brand_uninstall":
				$this->remove_brand($id);
				break;

			default:
				out ( sprintf(_("Error: Command [%s] not valid!"), $command_sub) );
		}
		$this->update_check();
		return true;
	}

	private function epm_config_manager_firmware()
	{
		$arrVal['VAR_REQUEST'] = array("command_sub", "idfw");
		foreach ($arrVal['VAR_REQUEST'] as $valor) {
			if (! array_key_exists($valor, $_REQUEST)) {
				out (_("Error: No send value!")." [".$valor."]");
				return false;
			}
		}

		$arrVal['VAR_IS_NUM'] = array("idfw");
		foreach ($arrVal['VAR_IS_NUM'] as $valor) {
			if (! is_numeric($_REQUEST[$valor])) {
				out (_("Error: Value send is not number!")." [".$valor."]");
				return false;
			}
		}

		$dget['command'] =  strtolower($_REQUEST['command_sub']);
		$dget['id'] = $_REQUEST['idfw'];

		switch($dget['command']) {
			case "fw_install":
			case "fw_update":
				$this->install_firmware($dget['id']);
				break;

			case "fw_uninstall":
				$this->remove_firmware($dget['id']);
				break;

			default:
				out (_("Error: Command not found!")." [" . $dget['command'] . "]");
		}

		unset ($dget);
		return true;
	}

	private function epm_config_manager_saveconfig()
	{
		$arrVal['VAR_REQUEST'] = array("typesavecfg", "value", "idtype", "idbt");
		foreach ($arrVal['VAR_REQUEST'] as $valor) {
			if (! array_key_exists($valor, $_REQUEST)) {
				return array("status" => false, "message" => _("No send value!")." [".$valor."]");
			}
		}

		$arrVal['VAR_IS_NUM'] = array("value", "idbt");
		foreach ($arrVal['VAR_IS_NUM'] as $valor) {
			if (! is_numeric($_REQUEST[$valor])) {
				return array("status" => false, "message" => _("Value send is not number!")." [".$valor."]");
			}
		}

		$dget['typesavecfg'] = strtolower($_REQUEST['typesavecfg']);
		$dget['value'] = strtolower($_REQUEST['value']);
		$dget['idtype'] = strtolower($_REQUEST['idtype']);
		$dget['id'] = $_REQUEST['idbt'];

		if (! in_array($dget['typesavecfg'], array("hidden", "enabled"))) {
			return array("status" => false, "message" => _("Type Save Config is not valid!")." [".$dget['typesavecfg']."]");
		}

		if (($dget['value'] > 1 ) and ($dget['value'] < 0)) {
			return array("status" => false, "message" => _("Invalid Value!"));
		}


		if ($dget['typesavecfg'] == "enabled") {
			if (($dget['idtype']) == "modelo") {
				$sql = "UPDATE endpointman_model_list SET enabled = " .$dget['value']. " WHERE id = '".$dget['id']."'";
			}
			else {
				$retarr = array("status" => false, "message" => _("IdType not valid to typesavecfg!"));
			}
		}
		else {
			switch($dget['idtype']) {
				case "marca":
					$sql = "UPDATE endpointman_brand_list SET hidden = '".$dget['value'] ."' WHERE id = '".$dget['id']."'";
					break;

				case "producto":
					$sql = "UPDATE endpointman_product_list SET hidden = '". $dget['value'] ."' WHERE id = '".$dget['id']."'";
					break;

				case "modelo":
					$sql = "UPDATE endpointman_model_list SET hidden = '". $dget['value'] ."' WHERE id = '".$dget['id']."'";
					break;

				default:
					$retarr = array("status" => false, "message" => _("IDType invalid: ") . $dget['idtype'] );
			}
		}
		if (isset($sql)) {
			sql($sql);
			$retarr = array("status" => true, "message" => "OK", "typesavecfg" => $dget['typesavecfg'], "value" => $dget['value'], "idtype" => $dget['idtype'], "id" => $dget['id']);
			unset($sql);
		}

		unset($dget);
		return $retarr;
	}

	public function epm_config_manager_hardware_get_list_all_hide_show()
	{
		$row_out	= [];
		$brand_list = $this->epm->get_hw_brand_list(true, "name");
		foreach ($brand_list as $brand)
		{
			// if ($brand['hidden'] == 1) { continue; }

			$brand_item = [
				'id' 		=> $brand['id'],
				'name' 		=> $brand['name'],
				'directory' => $brand['directory'],
				'installed' => $brand['installed'],
				'hidden' 	=> $brand['hidden'],
				'count' 	=> count($row_out),
				'products' 	=> []
			];
	
			$product_list = $this->epm->get_hw_product_list($brand['id'], true);
			foreach ($product_list as $product)
			{
				// if ($product['hidden'] == 1) { continue; }

				$product_item = [
					'id' 		 => $product['id'],
					'brand'		 => $product['brand'],
					'long_name'  => $product['long_name'],
					'short_name' => $product['short_name'],
					'hidden'	 => $product['hidden'],
					'count'		 => count($brand_item['products']),
					'models'	 => []
				];
	
				$model_list = $this->epm->get_hw_model_list($product['id'], true);
				foreach ($model_list as $model)
				{
					$model_item = [
						'id' 		 => $model['id'],
						'brand' 	 => $model['brand'],
						'model' 	 => $model['model'],
						'product_id' => $model['product_id'],
						'enabled' 	 => $model['enabled'],
						'hidden' 	 => $model['hidden'],
						'count' 	 => count($product_item['models']),
					];
					$product_item['models'][] = $model_item;
				}
	
				$brand_item['products'][] = $product_item;
			}
	
			$row_out[] = $brand_item;
		}
	
		return $row_out;
	}


	//TODO: PENDIENTE ACTUALIZAR Y ELIMINAR DATOS NO NECESARIOS (TEMPLATES)
	//http://pbx.cerebelum.lan/admin/ajax.php?module=endpointman&module_sec=epm_config&module_tab=manager&command=list_all_brand
	public function epm_config_manager_hardware_get_list_all()
	{
		$row_out 	= [];
		$brand_list = $this->epm->get_hw_brand_list(true, "name");
		//FIX: https://github.com/FreePBX-ContributedModules/endpointman/commit/2ad929d0b38f05c9da1b847426a4094c3314be3b
	

		
		
		// try
		// {
		// 	$master_json = $this->epm->packages->readMasterJSON(true);
		// }
		// catch (\Exception $e)
		// {
		// 	return [];
		// }
		// $master_json = $this->epm->packages->readMasterJSON(true);

		// $version = $master_json->getLastModifiedMaxBrands();
		// debug($version);

		// $version = array();
		// foreach ($master_json->getBrands() as &$brand)
		// {
		// 	$directory 			  = $brand->getDirectory();
		// 	$version[$directory] = $brand->getLastModifiedMax();
		// }
		// dbug($version);

		
		





		foreach ($brand_list as $i => $brand)
		{
			if ($brand['hidden'] == 1)
			{
				continue;
			}

			$brand['count'] 			   = count($row_out);
			$brand['cfg_ver_datetime']	   = $brand['cfg_ver'];
			$brand['cfg_ver_datetime_txt'] = date("c", $brand['cfg_ver']);
	
			$row_mod = $this->brand_update_check($brand['directory']);
			
			$brand['update_vers']	  = $row_mod['update_vers']		?? $brand['cfg_ver_datetime'];
			$brand['update_vers_txt'] = $row_mod['update_vers_txt'] ?? $brand['cfg_ver_datetime_txt'];
			$brand['update'] 		  = $row_mod['update']			?? false;
			$brand['products'] 		  = [];

			$product_list = $this->epm->get_hw_product_list($brand['id'], true);
			foreach ($product_list as $j => $product)
			{
				if ($product['hidden'] == 1)
				{
					continue;
				}

				$product['count'] 		  = count($brand['products']);
				$product['firmware_vers'] = $product['firmware_vers'] ?? 0;

				if ($product['firmware_vers'] > 0)
				{
					$temp = $this->firmware_update_check($product['id']);
					$product['update_fw'] = 1;
					$product['update_vers_fw'] = $temp['data']['firmware_ver'] ?? '';
				}
				else
				{
					$product['update_fw'] = 0;
					$product['update_vers_fw'] = "";
				}
	
				$product['fw_type'] = $this->firmware_local_check($product['id']);
				$product['models']  = [];
					
				$model_list = $this->epm->get_hw_model_list($product['id'], true);
				foreach ($model_list as $k => $model)
				{
					$model['count'] 		  = count($product['models']);
					$model['enabled_checked'] = $model['enabled'] ? 'checked' : '';
	
					unset($model['template_list'], $model['template_data']);
	
					$product['models'][] = $model;
				}
				$brand['products'][] = $product;
			}
			$row_out[] = $brand;
		}
	
		return $row_out;
	}
	/*** END SEC FUNCTIONS ***/












	/**
	 * Checks if a brand needs to be updated.
	 *
	 * @param string|null $brand_name_find The brand name to check for updates. If null, checks all brands.
	 * @return array An array containing the update status and version number.
	 *              - If the brand does not exist, the 'update' key will be set to -2.
	 *              - If the brand data file is missing or cannot be parsed, the 'update' key will be set to -3.
	 *              - If the brand is up to date, the 'update' key will be set to null.
	 *              - If the brand needs to be updated, the 'update' key will be set to 1 and the 'update_vers' key will contain the version number.
	 */


	
	public function brand_update_check($brand_name_find = NULL)
	{
		if (empty($brand_name_find))
		{
			return $this->brand_update_check_all();
		}
	
		$version = 0;
		$out 	 = array(
			'update' 		  => false,
			'update_vers' 	  => null,
			'update_vers_txt' => _("Error: No Version Found"),
		);

		$row 		 = $this->epm->get_hw_brand($brand_name_find);
		$directory 	 = $row['directory'] ?? NULL;
		$version_db  = $row['cfg_ver'] ?? 0;
		$json_master = $this->epm->packages->master_json;
		// $json_master = $this->epm->packages->readMasterJSON(true);
		
		if  (empty($directory))
		{
			$out['update'] = -2;
		}
		else
		{
			if (! $json_master->isBrandExist($directory, true))
			{
				$out['update'] = -3;
			}
			else
			{
				$brand = $json_master->getBrand($directory, true);
				if (! $brand->isJSONExist())
				{
					$out['update'] = -1;
				}
				else
				{
					$version = $brand->getLastModifiedMax();
					if ($version_db < $version)
					{
						$out['update'] 		= 1;
						$out['update_vers'] = $version;
					}
					else
					{
						$out['update'] 		= 0;
						$out['update_vers'] = $version;
					}
				}
			}
		}
	
		// TODO: Test Data
		// $out['update'] 		= 1;
		// $out['update_vers'] = '1724705560';

		switch($out['update'])
		{
			case -3:
				$out['update_vers_txt'] = _("Error: Brand Data File Missing or Cannot Be Parsed");
				break;

			case -2:
				$out['update_vers_txt'] = _("Error: Brand Not Found");
				break;

			case -1:
				$out['update_vers_txt'] = _("Error: Brand Data File Missing");
				break;

			case 0:
				// $out['update_vers_txt'] = _("Brand Up to Date");
			case 1:
				// $out['update_vers_txt'] = _("Brand Needs Update");
				$out['update_vers_txt'] = date("c", $out['update_vers']);
				break;
		}

		return $out;
	}


	/**
	 * Updates the check for all brands.
	 *
	 * This function retrieves the brand information from the master.json file and checks for updates in each brand's brand_data.json file.
	 * It compares the last modified dates of the brand_data.json files with the cfg_ver value of each brand in the endpointman_brand_list table.
	 * If a brand's cfg_ver is lower than the last modified date of its brand_data.json file, it marks the brand for update.
	 * The function returns an array containing the updated brand information.
	 *
	 * @return array The updated brand information.
	 */
	public function brand_update_check_all()
	{
		$master_json = $this->epm->packages->master_json;

		$out  = array();

		$version = $master_json->getLastModifiedMaxBrands();
		
		foreach ($this->epm->get_hw_brand_list(true) as $ava_brands)
		{
			$raw_name = $ava_brands['directory'];
			if (!$master_json->isBrandExists($raw_name))
			{
				$out[$raw_name] = array(
					'update' 		  => -1,
					'update_vers' 	  => 0,
				);
				continue;
			}

			if (! isset($version[$raw_name]))
			{
				$version[$raw_name] = 0;
			}

			if ($ava_brands['cfg_ver'] < $version[$raw_name])
			{
				$out[$raw_name]['update'] 	  = 1;
			}
			else
			{
				$out[$raw_name]['update'] = 0;
			}
			$out[$raw_name]['update_vers'] = $version[$raw_name];
		}
		return $out;
	}



	/**
	 * Check for new packges for brands. These packages will include phone models and such which the user can remove if they want
     * This function will alos auto-update the provisioner.net library incase anything has changed
	 *
	 * @param bool $echomsg (optional) Whether to echo error messages.
	 * @param array $error (optional) Reference to an array to store error messages.
	 * @return array An array of all the brands/products/models and information about what's  enabled, installed or otherwise
	 */
    public function update_check($echomsg = false, &$error=array())
	{
		$outputError = function($key, $errorMessage) use (&$error, $echomsg) {
			$error[$key] = $errorMessage;
			if ($echomsg) {
				out($errorMessage);
			}
		};
		$out = function($msg, $end_newline = true) use ($echomsg)
		{
			if ($echomsg)
			{
				if ($end_newline) { out($msg); }
				else 		      { outn($msg); }
			}
		};

		$url_status	    = $this->system->buildUrl($this->epm->URL_UPDATE, "update_status");
		$local_endpoint = $this->system->buildPath($this->epm->PHONE_MODULES_PATH, "endpoint");
		$temp_location  = $this->system->buildPath($this->epm->PHONE_MODULES_PATH, "temp", "provisioner");
		
		if (!file_exists($local_endpoint))
		{
			mkdir($local_endpoint, 0775, true);
		}
		if (!file_exists($temp_location))
		{
			mkdir($temp_location, 0775, true);
		}


        if (!$this->configmod->get('use_repo'))
		{
			$out("⚡ Checking status server...", false);
			try
			{
				if (($contents = file_get_contents($url_status)) === false)
				{
					$outputError('check_status_server', _("❌ The stream could not be opened: the requested url was not found or there was a problem with the request."));
					$contents = -2;
				}
			}
			catch (\Exception $e)
			{
				$outputError('check_status_server', "❌ ".$e->getMessage());
				$contents = -1;
			}
			
			if ($contents != '0')
			{
				$out ("❌");
				if (in_array($contents, [-1, -2]))
				{
					$outputError('remote_server', _("❌ The Remote server did not return any status information, Please try again later!"));
				}
				else
				{
					$outputError('remote_server', _("❌ The Remote Server Is Currently Syncing With the Master Server, Please try again later!"));
				}
				$out(" ");
				return false;
			}
			$out(" ✔");
			$out(" ");


			$master_json = $this->epm->packages->master_json;
			$out(_("Downloading master JSON file..."));
			$master_result = $master_json->downloadMaster($echomsg);
			if ($master_result !== false)
			{
				$this->epm->packages->reload_master_json();
				// Is needed to redefine the $master_json variable because the master_json object is not the same as the 
				// one we have in the $this->epm->packages->master_json after the reload_master_json() call
				$master_json = $this->epm->packages->master_json;
			}
			elseif (!file_exists($master_json->getJSONFile()))
			{
				$outputError('brand_update_check_master', _("❌ Not able to connect to repository and no local master file found!<br>"));
				return false;
			}
			else
			{
				$outputError('brand_update_check_master', _("💥 Not able to connect to repository. Using local master file instead.<br>"));
			}

			if (empty($master_json->getPackage()))
			{
				$outputError('brand_update_check_package', _("❌ No package found in master.json<br>"));
				return false;
			}

			//TODO: Ver el motivo por el que $local_endpoint_version es simpre 0!
			$local_endpoint_version = $this->epm->getConfig('endpoint_vers', null);
			$new_endpoint_version   = !$master_result ? false : $master_json->downloadPackage($local_endpoint_version);
			if ($new_endpoint_version !== false)
			{
				$this->epm->setConfig('endpoint_vers', $new_endpoint_version);
				$out( sprintf(_("✔ Package updated from version %s to %s <br>"), $local_endpoint_version, $new_endpoint_version) );
			}
			else
			{
				$outputError('brand_update_check_package', _("💥 Not able to connect to repository. Using local Provisioner.net Package<br>"));
			}

			$json_brands = $master_json->getBrands();
			// Assume that if we can't connect and find the master.json file then why should we try to find every other file.
			if (! $master_result)
			{
				$outputError('brand_update_check_master_file', _("❌ Aborting Brand Downloads. Can't Get Master File, Assuming Timeout Issues!<br>Learn how to manually upload packages here (it's easy!): <a href='%s' target='_blank'>Click Here!</a><br>"), "http://wiki.provisioner.net/index.php/Endpoint_manager_manual_upload");

				//TODO: El siguietne return antes estaba en false!!!!
				return false;
			}
			else
			{
				$version 	  = array();
				$local_brands = $this->epm->get_hw_brand_list(true);

				foreach ($json_brands as &$brand)
				{
					$brand_rawname = $brand->getDirectory();

					// Check if the brand is local and if not download the brand file
					if (! $this->epm->is_local_hw_brand($brand->getDirectory()))
					{
						$out(sprintf(_("Update Brand (%s):"), $brand->getName()));
						if (!$brand->downloadBrand($echomsg))
						{
							$outputError('brand_update_check', sprintf(_("💥 Not able to connect to repository. Using local brand [%s] file instead!"), $brand->getName()));
						}
					}

					// Check if the brand file exists and is not exist then skip the brand
					if (! file_exists($brand->getJSONFile()))
					{
						$outputError('brand_update_check_local_file', sprintf(_("❌ No Local File for %s!<br>Learn how to manually upload packages here (it's easy!): <a href='%s' target='_blank'> Click Here! </a>"), $brand->getName(), "http://wiki.provisioner.net/index.php/Endpoint_manager_manual_upload"));
						$out(" ");
						continue;
					}

					//If is necessary return more info in the exception (set the second parameter to false)
					if (! $brand->importJSON(null, true))
					{
						$outputError('brand_update_check_json', sprintf(_("❌ Unable to import JSON file for brand %s!"), $brand->getName()));
						$out(" ");
						continue;
					}
					
					// Update the OUIs for the brand
					$out( sprintf(_("⚡ Update OUIs for brand '%s' ◾◾◾"), $brand->getName()), false);
					if ($brand->countOUI() > 0 && $brand->isSetBrandID())
					{
						foreach ($brand->getOUI() as $oui)
						{
							if (empty($oui))
							{
								continue;
							}
							$out("◾", false);
							$this->epm->set_hw_brand_oui($oui, $brand->getBrandID());
						}
					}
					$out(_(" ✔"));
					

					// Get the maximum last modified date from the family list and the brand
					$version[$brand_rawname] = $brand->getLastModifiedMax();

					if ($this->epm->is_exist_hw_brand($brand->getBrandID(), 'id'))
					{
						$outputError('brand_update_id_exist', sprintf(_("✅ Brand '%s' already exists in the database."), $brand->getName()));
						$out(" ");
						continue;
					}
					$data_new = array(
						'id'		=> $brand->getBrandID(),
						'name'		=> $brand->getName(),
						'directory'	=> $brand->getDirectory(),
						'cfg_ver'	=> $version[$brand_rawname]
					);
					if (! is_numeric($this->epm->set_hw_brand($brand->getBrandID(), $data_new)))
					{
						$outputError('brand_update_check_add_brand', sprintf(_("❌ Unable to add brand '%s' to the database!"), $brand->getName()));
					}
					else
					{
						
						$outputError('brand_update_check_add_brand', sprintf(_("✅ Brand '%s' added to the database."), $brand->getName()));
					}
					unset($data_new);
					$out(" ");
				}

				$out("⚡ Remove Obsolete Brands ◾◾◾", false);
				foreach ($local_brands as $ava_brands)
				{
					$db_brand_rawname = $ava_brands['directory'];
					$db_brand_version = $ava_brands['cfg_ver'];
					if (empty($db_brand_rawname))
					{
						continue;
					}

					$out("◾", false);

					// Check if the brand is in the master.json file and if not remove the brand
					if ($master_json->isBrandExist($db_brand_rawname, true) === false)
					{
						$this->remove_brand($ava_brands['id']);
						continue;
					}

					//TODO: This seems old
					$json_brand_version = $version[$db_brand_rawname] ?? '';
					if ($db_brand_version < $json_brand_version)
					{ 
						$master_json->setBrandUpdate($db_brand_rawname, true, $json_brand_version, true);
					}
					else
					{
						$master_json->setBrandUpdate($db_brand_rawname, false, '', true);
					}
				}
				$out(" ✔");
			}
			$out(" ");
			return $json_brands;
        }
		else
		{
			//TODO: Pending the completion of the new system

            $o = getcwd();
            chdir(dirname($this->epm->PHONE_MODULES_PATH));
            $path = $this->epm->has_git();
            exec($path . ' git pull', $output);
            //exec($path . ' git checkout master', $output); //Why am I doing this?
            chdir($o);





			try
			{
				$master_json = $this->epm->packages->readMasterJSON();
				if ( empty($master_json->getPackage())) 
				{
					$outputError('brand_update_check_package', _("Error: No package found in master.json"));
					return false;
				}
			}
			catch (\Exception $e)
			{
				$outputError('read_json_master', $e->getMessage());
				return false;
			}
			$this->epm->setConfig('endpoint_vers', $master_json->getLastModified());


			// $local_brands = $this->epm->get_hw_brand_list(true); //$row

			$json_brands = $master_json->getBrands();	// $out
			foreach ($json_brands as &$brand) //data
			{
				$brand_id			  = $brand->getBrandID();
				$brand_name 		  = $brand->getName();
				$brand_rawname 		  = $brand->getDirectory();
				$brand_dir 			  = $brand->getDirectory();
				$brand_file_json_path = $this->system->buildPath($local_endpoint, $brand_dir, 'brand_data.json');

				//If is necessary return more info in the exception (set the second parameter to false)
				if (! $brand->importJSON($brand_file_json_path, true))
				{
					$outputError('brand_update_check_json', sprintf(_("Error: Unable to import JSON file for brand %s"), $brand_name));
					continue;
				}

				//Pull in all variables
				// $directory 		= $brand->getDirectory();
				// $brand_name		= $brand->getName();
				
				$data_new = array(
					'id'		=> $brand->getBrandID(),
					'name'		=> $brand->getName(),
					'cfg_ver'	=> $brand->getLastModified(),
					'local' 	=> 1,
					'installed' => 1,
				);
				$data_new_insert = array(
					'directory'	=> $brand->getDirectory(),
				);
				$this->epm->set_hw_brand($brand->getBrandID(), $data_new, 'id', $data_new_insert);
				unset($data_new);
				unset($data_new_insert);

				// Update the OUIs for the brand
				if ($brand->countOUI() > 0 && $brand->isSetBrandID())
				{
					foreach ($brand->getOUI() as $oui)
					{
						$this->epm->set_hw_brand_oui($oui, $brand->getBrandID());
					}
				}

				

				$last_mod = "";
				$brand_familys = $brand->getFamilyList();

				foreach ($brand_familys as &$family)
				{
					$last_mod 			   = max($last_mod, $family->getLastModified());
					$family_id 			   = $family->getFamilyID();
					$family_short_name 	   = $family->getShortName();
					$family_dir 		   = $family->getDirectory();
					$family_file_json_path = $this->system->buildPath($local_endpoint, $brand_dir, $family_dir, "family_data.json");	// $local_family_data
					
					//If is necessary return more info in the exception (set the second parameter to false)
					if (! $family->importJSON($family_file_json_path, true))
					{
						$outputError('family_update_check_json', sprintf(_("Error: Unable to import JSON file for family %s"), $family->getName()));
						continue;
					}

					
					

					/* DONT DO THIS YET
					$require_firmware = NULL;
					if ((key_exists('require_firmware', $family_line['data'])) && ($remote) && ($family_line['data']['require_firmware'] == "TRUE")) {
					echo "Firmware Requirment Detected!..........<br/>";
					$this->install_firmware($family_line['data']['id']);
					}
					*
					*/
					
					$data_product = array(
						':short_name' 	=> $family_short_name,
						':long_name' 	=> $family->getName(),
						':cfg_ver' 		=> $family->getLastModified(),
						':config_files' => $family->getConfigurationFiles()
					);
					$data_product_insert = array(
						':brand_id' 	=> $brand_id,
						':directory' 	=> $family->getDirectory(),
					);
					$this->epm->set_hw_product($family_id, $data_product, 'id', $data_product_insert);
					
					$models = $family->getModelList();
					foreach ($models as &$model)
					{
						$model_id = $model->getModelId();

						// $model->getConfigurationFiles() > old system implode(",", $model->getConfigurationFiles());
						$data_model = array(
							':model' 		=> $model->getModel(),
							':max_lines' 	=> $model->getMaxLines(),
							':template_list'=> $model->getConfigurationFiles()
						);
						$data_model_insert = array(
							':brand' 	=> $brand_id,
							':product_id' 	=> $family_id,
							':enabled' 		=> 0,
							':hidden' 		=> 0
						);
						$this->epm->set_hw_model($model_id, $data_model, 'id', $data_model_insert);

						$errsync_modal = array();
						if (!$this->sync_model($model_id, $errsync_modal))
						{
							foreach ($errsync_modal as $k => $v)
							{
								$outputError($k, $v);
							}
							$outputError('sync_module_error', sprintf(_("Error: System Error in Sync Model [%s] Function, Load Failure!"), $model->getModel()));
						}
						unset($errsync_modal);
					}

					//Phone Models Move Here
					foreach ($this->epm->get_hw_model_list($family_id, true) as $model)
					{
						if (! $family->isModelExist($model['model']))
						{
							if ($echomsg == true )
							{
								outn(sprintf(_("Moving/Removing Model '%s' not present in JSON file......"), $model['model']));
							}

							// Remove Brand Product Model
							if (! $this->epm->del_hw_model($model['id']) )
							{
								if ($echomsg == true ) { out(_("Error!")); }
								$outputError('del_hw_model', sprintf(_("Error: System Error in Delete Brand Product Model [%s] Function, Load Failure!"), $model['model']));
							}

							// Sync MAC Brand By Model
							if (! $this->epm->sync_mac_brand_by_model($model['model'], $model['id']))
							{
								if ($echomsg == true ) { out(_("Error!")); }
								$outputError('sync_mac_brand_by_model', sprintf(_("Error: System Error in Sync MAC Brand By Model [%s] Function, Load Failure!"), $model['model']));
							}
							if ($echomsg == true )
							{
								out (_("Done!"));
							}
						}
					}
				}
			}
        }
    }


	/**
	 * Synchronizes a model with its corresponding brand and product directories.
	 *
	 * @param int $model The ID of the model to sync.
	 * @param array $error (optional) An array to store any error messages encountered during the sync process.
	 * @return bool Returns true if the sync was successful, false otherwise.
	 */
    public function sync_model($model, &$error = array()) 
	{
		// Model allow > 0
		if (empty($model) || !is_numeric($model))
		{
			$error['sync_model'] = _("Model ID is empty or not numeric!");
			return false;
		}

		$sql = sprintf('SELECT eml.id, eml.product_id, ebl.id as brand_id, eml.brand, eml.model, epl.cfg_dir, ebl.directory FROM %s as eml
						JOIN %s as epl ON eml.product_id = epl.id
    					JOIN %s as ebl ON eml.brand = ebl.id
    					WHERE eml.id = :id', "endpointman_model_list", "endpointman_product_list", "endpointman_brand_list");
		$stmt = $this->db->prepare($sql);
		$stmt->execute([
			':id' => $model
		]);
		if ($stmt->rowCount() === 0)
		{
			$error['sync_model'] = _("Model not found!");
			return false;
		}
		$row = $stmt->fetch(\PDO::FETCH_ASSOC);

		if (empty($row['directory']) || empty($row['cfg_dir']))
		{
			
			$error['sync_model'] = sprintf(_("Brand or Product Directory is empty from the model '%s'!"), $model);
			return false;
		}

		$path_brand_dir 		 = $this->system->buildPath($this->epm->PHONE_MODULES_PATH, 'endpoint', $row['directory']);
		$path_brand_dir_cfg 	 = $this->system->buildPath($path_brand_dir, $row['cfg_dir']);
		$path_brand_dir_cfg_json = $this->system->buildPath($path_brand_dir_cfg, 'family_data.json');

		if (!file_exists($path_brand_dir))
		{
			$error['sync_model'] = sprintf(_("Brand Directory '%s' Doesn't Exist! (%s)"), $row['directory'], $path_brand_dir);
			return false;
		}
		elseif (!file_exists($path_brand_dir_cfg))
		{
			$error['sync_model'] = sprintf(_("Product Directory '%s' Doesn't Exist! (%s)"), $row['cfg_dir'], $path_brand_dir_cfg);
			return false;
		}
		elseif (!file_exists($path_brand_dir_cfg_json))
		{
			$error['sync_model'] = sprintf(_("File 'family_data.json' Doesn't exist in directory: %s"), $path_brand_dir_cfg);
			return false;
		}

		$family = null;
		try
		{
			$family = $this->epm->packages->readFamilyJSON($path_brand_dir_cfg_json, $row['brand_id']);
		}
		catch (\Exception $e)
		{
			$error['sync_model'] = $e->getMessage();
			return false;
		}

		if (! $family->isModelExist($row['model']))
		{
			$error['sync_model'] = "Can't locate model in family JSON file";
			return false;
		}

		$model_data    = $family->getModel($row['model']);
		$max_lines 	   = $model_data->getMaxLines();
		$template_list = $model_data->getTemplateList();

		$data_model = array(
			':model' 		=> $row['model'],
			':max_lines' 	=> $max_lines,
			':template_list'=> $template_list
		);
		$this->epm->set_hw_model($row['id'], $data_model);





		// $sql = sprintf("UPDATE %s SET max_lines = :maxlines, template_list = :template_list WHERE id = :model", "endpointman_model_list");
		// $stmt = $this->db->prepare($sql);
		// $stmt->execute([
		// 	':maxlines' 	 => $max_lines,
		// 	':template_list' => implode(",", $template_list),
		// 	':model' 		 => $model
		// ]);

		$this->epm->set_hw_product($row['product_id'], array(
			':short_name' => $family->getShortName(),
			':long_name'  => $family->getName(),
			':cfg_ver'	  => $family->getLastModified(),
		));

		// $sql = sprintf("UPDATE %s SET long_name = :long_name, short_name = :short_name, cfg_ver = :cfg_ver WHERE id = :id", "endpointman_product_list");
		// $stmt = $this->db->prepare($sql);
		// $stmt->execute([
		// 	':long_name'  => str_replace("'", "''", $family->getName()),
		// 	':short_name' => str_replace("'", "''", $family->getShortName()),
		// 	':cfg_ver' 	  => $family->getLastModified(),
		// 	':id' 		  => $row['product_id']
		// ]);

        $template_data_array = $this->merge_data($path_brand_dir_cfg, $template_list, true, true);

		$sql = sprintf("UPDATE %s SET template_data = :template_data WHERE id = :id", "endpointman_model_list");
		$stmt = $this->db->prepare($sql);
		$stmt->execute([
			':template_data' => serialize($template_data_array),
			':id' 			 => $model
		]);

		return true;
    }

	
	/**
	 * Downloads and installs/updates a brand.
	 *
	 * @param string $id The ID of the brand to download.
	 * @return bool Returns true if the brand was successfully installed/updated, false otherwise.
	 */
    public function download_brand($id)
	{
    	out(_("Install/Update Brand..."));
		
		if (!is_numeric($id))
		{
			out(_("Error: No Brand ID Given!"));
			return false;
		}
		elseif (! $this->epm->is_exist_hw_brand($id, 'id'))
		{
			out(sprintf(_("<b>Error: Brand with id '%s' not found!</b>"), $id));
			return false;
		}
		elseif ($this->configmod->get('use_repo'))
		{
			out(_("Error: Installing brands is disabled while in repo mode!"));
			return false;
		}

		outn(_("Downloading Brand JSON....."));
		
		$row = $this->epm->get_hw_brand($id, 'id');
			
		$url_brand_data   = $this->system->buildUrl($this->epm->URL_UPDATE, $row['directory'], $row['directory'].".json");
		$local_brand_data = $this->system->buildPath($this->epm->PHONE_MODULES_PATH, 'endpoint',  $row['directory'], "brand_data.json");

		try
		{
			if (! $this->system->download_file($url_brand_data, $local_brand_data))
			{
				out(_("Error!"));
				out(_("Error Connecting to the Package Repository. Module not installed. Please Try again later."));
				return false;
			}
			out(_("Done!"));
		}
		catch (\Exception $e)
		{
			// $error['download_file'] = $e->getMessage();

			out(_("Error!"));
			out(_("Error Connecting to the Package Repository. Module not installed. Please Try again later."));
			out(sprintf(_("You Can Also Manually Update The Repository By Downloading Files here: <a href='%s' target='_blank'> Release Repo </a>"), "http://www.provisioner.net/releases3"));
			out(_("Then Use Manual Upload in Advanced Settings."));
			return false;
		}

		$temp = null;
		try
		{
			$temp = $this->epm->file2json($local_brand_data);
			if ($temp === false)
			{
				out(sprintf(_("<b>Error file2json return false for the file '%s'!</b>"), $local_brand_data));
				return false;
			}
		}
		catch (\Exception $e)
		{
			out(sprintf(_("<b>Error read JSON  file: %s</b>"), $e->getMessage()));
			return false;
		}

		$package = $temp['data']['brands']['package'] ?? null;
		if (empty($package))
		{
			out(_("Error: No Package Found in JSON File!"));
			return false;
		}


		out(_("Downloading Brand Package..."));
		$temp_directory = $this->epm->PROVISIONER_PATH;
		$url_package  = $this->system->buildUrl($this->epm->URL_UPDATE, $row['directory'], $package);
		$path_package = $this->system->buildPath($temp_directory, $package);
		try
		{
			if (! $this->system->download_file_with_progress_bar($url_package, $path_package))
			{
				return false;
			}
		}
		catch (\Exception $e)
		{
			out(sprintf(_("Error: %s"), $e->getMessage()));
			return false;
		}

		if (! file_exists($path_package))
		{
			out(_("Error: Can't Find Downloaded File!"));
			return false;
		}

		$md5_json = $temp['data']['brands']['md5sum'] ?? 'skip';
		$md5_pkg  = md5_file($path_package);

		if ($md5_json == 'skip')
		{
			out(_("Skipping MD5 Check!"));
		}
		else
		{
			outn(_("Checking MD5sum of Package.... "));
			if ($md5_json != $md5_pkg)
			{
				out(_("MD5 Did not match!"));
				out(sprintf(_("- MD5 XML: %s"), $md5_json));
				out(sprintf(_("- MD5 PKG: %s"), $md5_pkg));
				return false;
			}
			else
			{
				out(_("Done!"));	
			}	
		}
		
		outn(_("Extracting Tarball........ "));
		try
		{
			if ($this->system->decompressTarGz($path_package, $temp_directory))
			{
				out(_("Done!"));
			}
		}
		catch (\Exception $e)
		{
			out(_("Error!"));
			out(sprintf(_("Error: %s"), $e->getMessage()));
			return false;
		}
		finally
		{
			if (file_exists($path_package))
			{
				unlink($path_package);
			}
		}

		

		$path_temp_extract_package = $this->system->buildPath($temp_directory, $row['directory']);
		$path_package_json 		   = $this->system->buildPath($path_temp_extract_package, "brand_data.json");


		//Update File in the temp directory
		//TODO: ????????? Why is this here?
		copy($local_brand_data, $path_package_json);


		$return_update_brand = $this->update_brand($row['directory'], TRUE);


		if (file_exists($path_temp_extract_package))
		{
			outn(_("Removing Temporary Files... "));
        	$this->system->rmrf($path_temp_extract_package);
        	out(_("Done!"));
		}

		return $return_update_brand;
    }

    /**
     * This will install or updated a brand package (which is the same thing to this)
     * Still needs way to determine when models move...perhaps another function?
     */
    public function update_brand($package, $remote = true)
	{
		$debug = $this->configmod->get('debug');

    	out(sprintf(_("Update Brand '%s' ..."), $package));

		// $temp_directory = $this->system->sys_get_temp_dir() . "/epm_temp/";

		$temp_directory  = $this->epm->PROVISIONER_PATH;
		$temp_brand 	 = $this->system->buildPath($temp_directory, $package);
		$temp_brand_json = $this->system->buildPath($temp_brand, "brand_data.json");
		
		out( sprintf(_("Processing '%s'..."), $package));

		$temp = null;
		try
		{
			$temp = $this->epm->file2json($temp_brand_json);
			if ($temp === false)
			{
				out(sprintf(_("❌<b>Error file2json return false for the file '%s'!</b>"), $temp_brand_json));
				return false;
			}
		}
		catch (\Exception $e)
		{
			out(sprintf(_("❌<b>Error file2json return false for the file '%s'!</b>"), $temp_brand_json));
			return false;
		}

		$brands		= $temp['data']['brands'] ?? array();
		$oui_list 	= $brands['oui_list'] 	  ?? array();
		$directory 	= $brands['directory'] 	  ?? '';
		$brand_id   = $brands['brand_id'] 	  ?? '';
		
		if (!key_exists('directory', $brands))
		{
			out(_("❌Error: Invalid JSON Structure in file json!"));
			return false;
		}
		elseif (empty($directory))
		{
			out(_("❌Error: Invalid brand directory in file json!"));
			return false;
		}
		elseif (empty($brand_id))
		{
			out(_("❌Error: Invalid brand ID in file json!"));
			return false;
		}
		else
		{
			out(_("Appears to be a valid Provisioner.net JSON file.....Continuing ✔"));
			$brand_name    = $brands['name'] 		  ?? _('Unknown');
			$brand_version = $brands['last_modified'] ?? '0';

			outn(sprintf(_("Creating Directory Structure for Brand '%s' and Moving Files ..."), $brand_name));


			$local_brand 	= $this->system->buildPath($this->epm->PHONE_MODULES_PATH, 'endpoint', $directory);
			$temp_brand_dir = $this->system->buildPath($temp_directory, $directory);
			$permission 	= 0755;
			$errors_move	= array();

			if (!file_exists($local_brand))
			{
				mkdir($local_brand, $permission, true);
			}
			else
			{
				chmod($local_brand, $permission);
			}
			
			$dir_iterator = new \RecursiveDirectoryIterator($temp_brand_dir);
			$iterator 	  = new \RecursiveIteratorIterator($dir_iterator, \RecursiveIteratorIterator::SELF_FIRST);
			foreach ($iterator as $file)
			{
				$dir      = str_replace($temp_brand_dir, "", $file);
				$path_dir = $this->system->buildPath($local_brand, $dir);

				if (is_dir($file))
				{
					if (!file_exists($path_dir))
					{
						mkdir($path_dir, $permission, true);
					}
					else
					{
						chmod($local_brand, $permission);
					}
				}
				else
				{
					if ((basename($file) != "brand_data.json") OR (!$remote))
					{
						$stats = rename($file, $path_dir);
						if ($stats === false)
						{
							$errors_move[] = $file;
						}

						if (file_exists($path_dir)) 
						{
							chmod($path_dir, $permission);
						}
					}
				}
				outn('.');
			}
			out(_(" Completed! ✔"));
			foreach ($errors_move as $error)
			{
				out(sprintf(_("‼ Warning: Unable to move file '%s'"), $error));
			}


			$local = $remote ? 0 : 1;




			if (! $this->epm->is_exist_hw_brand($directory))
			{
				outn( sprintf(_("Inserting Brand '%s' ... "), $brand_name));
			}
			else
			{
				outn( sprintf(_("Updating Brand '%s' ... "), $brand_name));
			}
			$new_brand = array(
				'id'		=> $brand_id,
				'name'		=> $brand_name,
				'cfg_ver'	=> $brand_version,
				'local' 	=> $local,
			);
			$new_brand_insert = array(
				'directory'	=> $directory,
				'installed' => 1,
			);
			$this->epm->set_hw_brand($brand_id, $new_brand, 'id', $new_brand_insert);
			unset($new_brand);
			unset($new_brand_insert);

			out(_("✔"));


			$last_mod = "";
			foreach ($brands['family_list'] ?? array() as $family_list)
			{
				out(_("Updating Family Lines ..."));

				$last_mod 		  = max($last_mod, $family_list['last_modified'] ?? '');
				$family_line_json = $this->system->buildPath($local_brand, $family_list['directory'], 'family_data.json');
				$family_line 	  = null;

				try
				{
					$family_line = $this->epm->file2json($family_line_json);
					if ($family_line === false)
					{
						out(sprintf(_("<b>Error file2json return false for the file '%s' in to brand '%s'!</b>"), $temp_brand_json, $brand_name));
						continue;
					}
				}
				catch (\Exception $e)
				{
					out(sprintf(_("<b>Error read JSON  file in to brand '%s': %s</b>"), $brand_name, $e->getMessage()));
					continue;
				}

				$family_line['data'] 					 = $family_line['data'] 					?? array();
				$family_line['data']['last_modified']	 = $family_line['data']['last_modified'] 	?? '';
				$family_line['data']['require_firmware'] = $family_line['data']['require_firmware'] ?? NULL;
				$family_line['data']['model_list']		 = $family_line['data']['model_list'] 		?? array();



				if (($remote) && ($family_line['data']['require_firmware'] == "TRUE"))
				{
					out(_("Firmware Requirment Detected!.........."));
					$this->install_firmware($family_line['data']['id']);
				}


				$brand_id_family_line = $brand_id . $family_line['data']['id'];
				$short_name 		  = preg_replace("/\[(.*?)\]/si", "", $family_line['data']['name']);

				$sql  = sprintf("SELECT id FROM %s WHERE id= :brand_id_family_line", "endpointman_product_list");
				$stmt = $this->db->prepare($sql);
				$stmt->execute([
					':brand_id_family_line' => $brand_id_family_line
				]);
				if ($stmt->rowCount() === 0)
				{
					if ($debug)
					{
						outn( sprintf(_("- Inserting Family '%s'"), $short_name));
					}
					$sql  = sprintf("INSERT INTO %s (`id`, `brand`, `short_name`, `long_name`, `cfg_dir`, `cfg_ver`, `config_files`, `hidden`) VALUES (:brand_id_family_line, :brand_id, :short_name, :long_name, :cfg_dir, :cfg_ver, :config_files, '0')", "endpointman_product_list");
					$stmt = $this->db->prepare($sql);
					$stmt->execute([
						':brand_id_family_line' => $brand_id_family_line,
						':brand_id'				=> $brand_id,
						':short_name'			=> str_replace("'", "''", $short_name),
						':long_name'			=> str_replace("'", "''", $family_line['data']['name']),
						':cfg_dir'				=> $family_line['data']['directory'] ?? '',
						':cfg_ver'				=> $family_line['data']['last_modified'] ?? '',
						':config_files'			=> $family_line['data']['configuration_files'] ?? '',
					]);
				}
				else
				{
					if ($debug)
					{
						out( sprintf(_("- Updating Family '%s'"), $short_name));
					}
					$sql  = sprintf("UPDATE %s SET short_name = :short_name, long_name = :long_name, cfg_ver = :cfg_ver, config_files = :config_files WHERE id = :brand_id_family_line", "endpointman_product_list");
					$stmt = $this->db->prepare($sql);
					$stmt->execute([
						':short_name'			=> str_replace("'", "''", $short_name),
						':long_name'			=> str_replace("'", "''", $family_line['data']['name']),
						':cfg_ver'				=> $family_line['data']['version'] ?? '',
						':config_files'			=> $family_line['data']['configuration_files'] ?? '',
						':brand_id_family_line' => $brand_id_family_line,
					]);
				}


				if (count($family_line['data']['model_list']) > 0)
				{
					out(_("-- Updating Model Lines ... "));
				}
				foreach ($family_line['data']['model_list'] as $model_list)
				{
					$template_list  			= implode(",", $model_list['template_data'] ?? array());
					$brand_id_family_line_model = $brand_id_family_line . $model_list['id'];
					
					

					$sql = sprintf('SELECT id, global_custom_cfg_data, global_user_cfg_data FROM %s WHERE model = :brand_id_family_line_model', "endpointman_mac_list");
					$stmt = $this->db->prepare($sql);
					$stmt->execute([
						':brand_id_family_line_model' => $brand_id_family_line_model
					]);
					$old_data = $stmt->fetchAll(\PDO::FETCH_ASSOC);

					foreach ($old_data as $data)
					{
						$global_custom_cfg_data = unserialize($data['global_custom_cfg_data']);
						if ((is_array($global_custom_cfg_data)) AND (!array_key_exists('data', $global_custom_cfg_data)))
						{
							outn(_("--- Old Data Detected! Migrating ... "));

							$new_data = array();
							$new_ari  = array();
							foreach ($global_custom_cfg_data as $key => $old_keys)
							{
								if (array_key_exists('value', $old_keys))
								{
									$new_data[$key] = $old_keys['value'];
								}
								else
								{
									$breaks = explode("_", $key);
									$new_data["loop|" . $key] = $old_keys[$breaks[2]];
								}
								if (array_key_exists('ari', $old_keys))
								{
									$new_ari[$key] = 1;
								}
							}

							$sql  = sprintf("UPDATE %s SET global_custom_cfg_data = :cfg_data WHERE id = :id", "endpointman_mac_list");
							$stmt = $this->db->prepare($sql);
							$stmt->execute([
								':cfg_data' => serialize(array('data' => $new_data, 'ari'  => $new_ari)),
								':id'	    => $data['id']
							]);

							out(_("Done!"));
						}

						$global_user_cfg_data = unserialize($data['global_user_cfg_data']);
						$old_check = FALSE;
						if (is_array($global_user_cfg_data))
						{
							foreach ($global_user_cfg_data as $stuff)
							{
								if (is_array($stuff))
								{
									if (array_key_exists('value', $stuff))
									{
										$old_check = true;
										break;
									}
									else
									{
										break;
									}
								}
								else
								{
									break;
								}
							}
						}

						if ((is_array($global_user_cfg_data)) AND ($old_check))
						{
							outn(_("--- Old Data Detected! Migrating ... "));
							$new_data = array();
							foreach ($global_user_cfg_data as $key => $old_keys)
							{
								if (array_key_exists('value', $old_keys))
								{
									$exploded = explode("_", $key);
									$counted  = count($exploded);
									$counted  = $counted - 1;
									if (is_numeric($exploded[$counted]))
									{
										$key = "loop|" . $key;
									}
									$new_data[$key] = $old_keys['value'];
								}
							}
							$sql  = sprintf("UPDATE %s SET global_user_cfg_data = :cfg_data WHERE id = :id", "endpointman_mac_list");
							$stmt = $this->db->prepare($sql);
							$stmt->execute([
								':cfg_data' => serialize($new_data),
								':id'	    => $data['id'],
							]);

							out(_("Done!"));
						}
					}
					unset($old_data);




					$sql = sprintf('SELECT id, global_custom_cfg_data FROM %s WHERE model_id = :brand_id_family_line_model', "endpointman_template_list");
					$stmt = $this->db->prepare($sql);
					$stmt->execute([
						':brand_id_family_line_model' => $brand_id_family_line_model
					]);
					$old_data = $stmt->fetchAll(\PDO::FETCH_ASSOC);

					foreach ($old_data as $data)
					{
						$global_custom_cfg_data = unserialize($data['global_custom_cfg_data']);
						if ((is_array($global_custom_cfg_data)) AND (!array_key_exists('data', $global_custom_cfg_data))) 
						{
							out(_("--- Old Data Detected! Migrating ... "));
							$new_data = array();
							$new_ari  = array();
							foreach ($global_custom_cfg_data as $key => $old_keys)
							{
								if (array_key_exists('value', $old_keys))
								{
									$new_data[$key] = $old_keys['value'];
								}
								else
								{
									$breaks = explode("_", $key);
									$new_data["loop|" . $key] = $old_keys[$breaks[2]];
								}
								if (array_key_exists('ari', $old_keys))
								{
									$new_ari[$key] = 1;
								}
							}
							$sql  = sprintf("UPDATE %s SET global_custom_cfg_data = :cfg_data WHERE id = :id", "endpointman_template_list");
							$stmt = $this->db->prepare($sql);
							$stmt->execute([
								':cfg_data' => serialize(array('data' => $new_data, 'ari'  => $new_ari)),
								':id'	    => $data['id']
							]);

							out(_("Done!"));
						}
					}
					unset($old_data);


					$sql  = sprintf("SELECT id FROM %s WHERE id= :brand_id_family_line", "endpointman_model_list");
					$stmt = $this->db->prepare($sql);
					$stmt->execute([
						':brand_id_family_line' => $brand_id_family_line
					]);
					if ($stmt->rowCount() === 0)
					{
						if ($debug)
						{
							// $this->epm->format_txt(_("---Inserting Model %_NAMEMOD_%"), "", array("%_NAMEMOD_%" => $model_list['model'])
							// outn( sprintf(_("- Inserting Family '%s'"), $short_name));
						}
						$sql  = sprintf("INSERT INTO %s (`id`, `brand`, `model`, `max_lines`, `product_id`, `template_list`, `enabled`, `hidden`) VALUES (:brand_id_family_line_model, :brand_id, :model , :max_lines, :product_id, :template_list, '0', '0')", "endpointman_model_list");
						$stmt = $this->db->prepare($sql);
						$stmt->execute([
							':brand_id_family_line_model' => $brand_id_family_line_model,
							':brand_id'					  => $brand_id,
							':model'					  => $model_list['model'],
							':max_lines'				  => $model_list['lines'],
							':product_id'				  => $brand_id_family_line,
							':template_list'			  => $template_list,
						]);
					}
					else 
					{
						if ($debug)
						{
							//echo $this->epm->format_txt(_("---Updating Model %_NAMEMOD_%"), "", array("%_NAMEMOD_%" => $model_list['model']));
						}
						$sql  = sprintf("UPDATE %s SET max_lines = :max_lines, model = :model, template_list = :template_list WHERE id = :brand_id_family_line_model", "endpointman_model_list");
						$stmt = $this->db->prepare($sql);
						$stmt->execute([
							':max_lines'				  => $model_list['lines'],
							':model'					  => $model_list['model'],
							':template_list'			  => $template_list,
							':brand_id_family_line_model' => $brand_id_family_line_model,
						]);
					}



					//echo "brand_id:".$brand_id. " - family_line:" . $family_line['data']['id'] . "- model_list:" . $model_list['id']."<br>";
					$errlog = array();
					if (! $this->sync_model($brand_id_family_line_model, $errlog))
					{
						out(_("Error: System Error in Sync Model Function, Load Failure!"));
						out(_("Error: ").$errlog['sync_model']);
					}
					unset ($errlog);
				}
				//END Updating Model Lines................


				//Phone Models Move Here
				$sql = sprintf('SELECT * FROM %s WHERE product_id = :brand_id_family_line', "endpointman_model_list");
				$stmt = $this->db->prepare($sql);
				$stmt->execute([
					':brand_id_family_line' => $brand_id_family_line
				]);
				$products = $stmt->fetchAll(\PDO::FETCH_ASSOC);
				foreach ($products as $data)
				{
					$model_name = $data['model'] ?? '';

					if (empty($model_name))
					{
						continue;
					}

					if (!$this->system->arraysearchrecursive($model_name, $family_line['data']['model_list'], 'model'))
					{
						outn(sprintf(_("Moving/Removing Model '%s' not present in JSON file ... "), $model_name));
						
						$sql = sprintf('DELETE FROM %s WHERE id = :id', "endpointman_model_list");
						$stmt = $this->db->prepare($sql);
						$stmt->execute([
							':id' => $data['id']
						]);


						$sql = sprintf('SELECT id FROM %s WHERE model LIKE :model_name', "endpointman_model_list");
						$stmt = $this->db->prepare($sql);
						$stmt->execute([
							':model_name' => $model_name
						]);
						$new_model_id = $stmt->rowCount() === 0 ? false : ($stmt->fetchColumn() ?? false);
						if ($new_model_id)
						{
							$sql  = sprintf('UPDATE %s SET model = :new_id WHERE  model = :id', "endpointman_mac_list");
							$stmt = $this->db->prepare($sql);
							$stmt->execute([
								':new_id' => $new_model_id,
								':id'	  => $data['id'],
							]);
						}
						else
						{
							$sql  = sprintf("UPDATE %s SET model = '0' WHERE  model = :id", "endpointman_mac_list");
							$stmt = $this->db->prepare($sql);
							$stmt->execute([
								':id'	  => $data['id'],
							]);
						}
						
						out(_("Done!"));
					}
				}
			}
			out(_("All Done!"));
			//END Updating Family Lines

			outn(_("Updating OUI list in DB ... "));
			foreach ($oui_list as $oui)
			{
				$sql  = sprintf("REPLACE INTO %s (`oui`, `brand`, `custom`)  VALUES (:oui, :brand_id, '0')", "endpointman_oui_list");
				$stmt = $this->db->prepare($sql);
				$stmt->execute([
					':oui' 	 => $oui,
					':brand_id' 	 => $brand_id,
				]);
			}
			out(_("Done!"));
		}

		if (file_exists($temp_brand))
		{
			outn(_("Removing Temporary Files... "));
			$this->system->rmrf($temp_brand);
			out(_("Done!"));
		}
    }

	
	/**
	 * Removes a brand from the system.
	 *
	 * @param int|null $id The ID of the brand to be removed.
	 * @param bool $remove_configs Whether to remove the brand configurations or not.
	 * @param bool $force Whether to force the removal even in repo mode.
	 * @return bool Returns true if the brand is successfully removed, false otherwise.
	 */
    public function remove_brand($id = null, $remove_configs = false, $force = false)
	{
		out(_("Uninstalla Brand..."));

		if (is_numeric($id) === false)
		{
			out(_("Error: No ID Given!"));
			return false;
		}

		if ($this->configmod->get('use_repo') && !$force)
		{
			out(_("Error: Not allowed in repo mode!!"));
			return false;
        }

		$sql = sprintf('SELECT directory FROM %s WHERE id = :id', "endpointman_brand_list");
		$stmt = $this->db->prepare($sql);
		$stmt->execute([
			':id' => $id
		]);
		$brand_dir = $stmt->rowCount() === 0 ? false : ($stmt->fetchColumn() ?? false);

        if (!$this->configmod->get('use_repo') && !$force)
		{
			$sql = sprintf('SELECT id, firmware_vers FROM %s WHERE brand = :id', "endpointman_product_list");
			$stmt = $this->db->prepare($sql);
			$stmt->execute([
				':id' => $id
			]);
			$products = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($products as $data)
			{
                if ($data['firmware_vers'] != "")
				{
                    $this->remove_firmware($data['id']);
                }
			}

			if (!empty($brand_dir))
			{
				$brand_dirs_full = array(
					$this->system->buildPath($this->epm->PHONE_MODULES_PATH, 'endpoint', $brand_dir),
					$this->system->buildPath($this->epm->PHONE_MODULES_PATH, $brand_dir)
				);
				foreach ($brand_dirs_full as $brand_dir_full)
				{
					if (file_exists($brand_dir_full))
					{
						$this->system->rmrf($brand_dir_full);
					}
				}
			}
		}

		$tables_clean = [
			"endpointman_model_list" 	=> 'brand',
			"endpointman_product_list" 	=> 'brand',
			"endpointman_oui_list" 		=> 'brand',
			"endpointman_brand_list"	=> 'id'
		];
		foreach ($tables_clean as $table => $where)
		{
			$sql = sprintf('DELETE FROM %s WHERE %s = :id', $table, $where);
			$stmt = $this->db->prepare($sql);
			$stmt->execute([
				':id' => $id
			]);
		}

		out(_("Done!"));
		return true;
    }


	/**
	 * Installs firmware for a specific product.
	 *
	 * @param int $product_id The ID of the product.
	 * @return bool Returns true if the firmware is successfully installed, false otherwise.
	 */
    public function install_firmware($product_id)
	{
    	out(_("Installa frimware... "));

		if (is_numeric($product_id) === false)
		{
			out(_("Error: No ID Given!"));
			return false;
		}

		//TOOD: Review howto create temp directory
        // $temp_directory = $this->system->buildPath($this->system->sys_get_temp_dir(), "/epm_temp/");

		$temp_directory = $this->epm->PROVISIONER_PATH;

		$sql = sprintf('SELECT t_product_list.*, t_brand_list.directory FROM %s as t_product_list, %s as t_brand_list WHERE t_product_list.brand = t_brand_list.id AND t_product_list.id = :product_id', "endpointman_product_list", "endpointman_brand_list");
		$stmt = $this->db->prepare($sql);
		$stmt->execute([
			':product_id' => $product_id
		]);
		$row = $stmt->fetch(\PDO::FETCH_ASSOC);

		$path_json_family = $this->system->buildPath($this->epm->PHONE_MODULES_PATH, 'endpoint', $row['directory'], $row['cfg_dir'], "family_data.json");

		$json_data = null;
		try
		{
			$json_data = $this->epm->file2json($path_json_family);
			if ($json_data === false)
			{
				out(sprintf(_("<b>Error file2json return false for the file '%s'!</b>"), $path_json_family));
				return false;
			}
		}
		catch (\Exception $e)
		{
			out(sprintf(_("<b>Error read JSON  file: %s</b>"), $e->getMessage()));
			return false;
		}

		$firmware_ver = $json_data['data']['firmware_ver'] ?? '';
		$firmware_pkg = $json_data['data']['firmware_pkg'] ?? '';

        if (empty($firmware_ver))
		{
        	out (_("Error: The version of the firmware package is blank!"));
        	return false;
        }

        if ((empty($firmware_pkg)) OR ($firmware_pkg == "NULL"))
		{
        	out (_("Error: The package name of the firmware to be downloaded is Null or blank!"));
        	return false;
        }

        if ($firmware_ver > $row['firmware_vers'])
		{
            $md5_json			  = $json_data['data']['firmware_md5sum'];
			$firmware_pkg_path	  = $this->system->buildPath($temp_directory, $firmware_pkg);
			$firmware_pkg_url	  = $this->system->buildUrl($this->epm->URL_UPDATE, $row['directory'], $firmware_pkg);
			$go_download_firmware = true;

            if (file_exists($firmware_pkg_path))
			{
                $md5_pkg = md5_file($firmware_pkg_path);
                if ($md5_json == $md5_pkg)
				{
					out(_("Skipping download, updated local version..."));
					$go_download_firmware = false;
                }
            }

			if ($go_download_firmware)
			{
				out(_("Downloading firmware..."));
				try
				{
					if (! $this->system->download_file_with_progress_bar($firmware_pkg_url, $firmware_pkg_path))
					{
						out(_("Error download frimware package!"));
						return false;
					}
				}
				catch (\Exception $e)
				{
					return false;
				}
				$md5_pkg = md5_file($firmware_pkg_path);
			}

			outn(_("Checking MD5sum of Package... "));
            if ($md5_json == $md5_pkg)
			{
				out(_("Matches!"));

				$path_brand_dir    = $this->system->buildPath($temp_directory, $row['directory']);
				$path_cfg_dir 	   = $this->system->buildPath($temp_directory, $row['directory'], $row['cfg_dir']);
				$path_firmware_dir = $this->system->buildPath($temp_directory, $row['directory'], $row['cfg_dir'], "firmware");
								
                if (file_exists($path_firmware_dir))
				{
                    $this->system->rmrf($path_firmware_dir);
                }
                mkdir($path_firmware_dir, 0777, true);

				out(_("Installing Firmware..."));
				$this->system->decompressTarGz($path_firmware_dir, $path_cfg_dir);

				$firmware_files = array();
				$copy_error 	= false;
                foreach (glob($this->system->buildPath($path_firmware_dir, "*")) as $src_path)
				{
                    $file	   		  = basename($src_path);
                    $firmware_files[] = $file;
					$dest_path 		  = $this->system->buildPath($this->configmod->get('config_location'), $file);
                    if (!@copy($src_path, $dest_path))
					{
                    	out(sprintf(_("- Failed To Copy %s!"), $file));
                        $copy_error = true;
                    }
					elseif ($this->configmod->get('debug'))
					{
						out(sprintf(_("- Copied %s to %s."), $file, $this->configmod->get('config_location')));
                    }
                }

				if (file_exists($path_brand_dir))
				{
					$this->system->rmrf($path_brand_dir);
				}

				$sql = sprintf('UPDATE %s SET firmware_vers = :firmware_ver, firmware_files = :firmware_files WHERE id = :id', "endpointman_product_list");
				$stmt = $this->db->prepare($sql);
				$stmt->execute([
					':firmware_ver'	  => $firmware_ver,
					':firmware_files' => implode(",", $firmware_files),
					':id' 			  => $row['id'],
				]);

                if ($copy_error)
				{
					out(_("Copy Error Detected! Aborting Install!"));
                    $this->remove_firmware($product_id);
					out(_("Info: Please Check Directory/Permissions!"));
                }
				else
				{
					out(_("Done!"));
                }
            }
			else
			{
				out(_("Firmware MD5 didn't match!"));
				return false;
            }
        }
		else
		{
			out(_("Your Firmware is already up to date."));
        }
		return true;
    }

	
	/**
	 * Removes firmware files associated with a specific ID.
	 *
	 * @param int $id The ID of the firmware to be removed.
	 * @return bool Returns true if the firmware files were successfully removed, false otherwise.
	 */
    public function remove_firmware($id)
	{
		outn(_("Uninstalla frimware... "));

		if (is_numeric($id) === false)
		{
			out(_("Error: No ID Given!"));
			return false;
		}

		$sql = sprintf('SELECT firmware_files FROM %s WHERE id = :id', "endpointman_product_list");
		$stmt = $this->db->prepare($sql);
		$stmt->execute([
			':id' => $id
		]);
		$files = $stmt->rowCount() === 0 ? false : ($stmt->fetchColumn() ?? false);

		if (empty($files))
		{
			out(_("Skip!"));
			out(_("The brand does not have any firmware files."));
			return true;
		}

        $file_list = explode(",", $files);
        foreach ($file_list as $file)
		{
			if (empty(trim($file)))
			{
				continue;
			}
			$file_path = $this->epm->brindPath($this->configmod->get('config_location'), $file);
            if (! file_exists($file_path) || ! is_file($file_path))
			{
				continue;
			}
			// Remove files from tftp directory
			unlink($file_path);
        }

		$sql = sprintf("UPDATE %s SET firmware_files = '', firmware_vers = '' WHERE id = :id", "endpointman_product_list");
		$stmt = $this->db->prepare($sql);
		$stmt->execute([
			':id' => $id
		]);

		out(_("Done!"));
    }


	/**
	 * Checks if a firmware update is available for a given product ID.
	 *
	 * @param int|null $id The ID of the product to check for firmware update. Defaults to NULL.
	 * @return mixed Returns the firmware update data if an update is available, otherwise returns false.
	 */
    public function firmware_update_check($id = null)
	{
		if (is_numeric($id) === false)
		{
			return false;
		}

		$sql = sprintf('SELECT * FROM %s WHERE id = :id', "endpointman_product_list");
		$stmt = $this->db->prepare($sql);
		$stmt->execute([
			':id' => $id
		]);
		$row = $stmt->fetch(\PDO::FETCH_ASSOC);

        //config drive unknown!
        if ($row['cfg_dir'] == "")
		{
            return false;
        }

		$sql = sprintf('SELECT directory FROM %s WHERE id = :id', "endpointman_brand_list");
		$stmt = $this->db->prepare($sql);
		$stmt->execute([
			':id' =>  $row['brand']
		]);
		$brand_directory  = $stmt->rowCount() === 0 ? false : ($stmt->fetchColumn() ?? false);

		$path_json_family = $this->system->buildPath($this->epm->PHONE_MODULES_PATH, 'endpoint', $brand_directory, $row['cfg_dir'], "family_data.json");
		$json_data = null;
		try
		{
			$json_data = $this->epm->file2json($path_json_family);
			if ($json_data === false || !is_array($json_data))
			{
				return false;
			}
		}
		catch (\Exception $e)
		{
			return false;
		}

		$firmware_ver = $json_data['data']['firmware_ver'] ?? array();
		
		if (! array_key_exists('data', $json_data))
		{
			return false;
		}
		elseif (!is_array($firmware_ver))
		{
			return false;
		}
		elseif ($row['firmware_vers'] < $firmware_ver)
		{
			return $json_data;
		}
		else
		{
			return FALSE;
		}
    }

	
	/**
	 * Checks the local firmware for a given ID.
	 *
	 * @param int|null $id The ID of the firmware to check.
	 * @return string Returns "nothing" if the ID is not numeric, the firmware is not found, or the configuration drive is unknown.
	 *                Returns "install" if the firmware version is not empty and the firmware versions in the database is empty.
	 *                Returns "remove" if the firmware version is not empty and the firmware versions in the database is not empty.
	 */
    public function firmware_local_check($id = null)
	{
		if (is_numeric($id) === false)
		{
			return "nothing";
		}

		if (! $this->epm->is_exist_hw_product($id, 'id', true))
		{
			return "nothing";
		}

		$sql = sprintf('SELECT * FROM %s WHERE hidden = 0 AND id = :id', "endpointman_product_list");
		$stmt = $this->db->prepare($sql);
		$stmt->execute([
			':id' => $id
		]);
		if ($stmt->rowCount() === 0)
		{
			return "nothing";
		}

		$row = $stmt->fetch(\PDO::FETCH_ASSOC);
		//config drive unknown!
		if ($row['cfg_dir'] == "")
		{
			return "nothing";
		}

		$sql  = sprintf('SELECT directory FROM %s WHERE hidden = 0 AND id = :id', "endpointman_brand_list");
		$stmt = $this->db->prepare($sql);
		$stmt->execute([
			':id' => $row['brand']
		]);
		$brand_directory = $stmt->rowCount() === 0 ? false : ($stmt->fetchColumn() ?? false);

		if ($brand_directory === false)
		{
			return "nothing";
		}

		$json_file = $this->system->buildPath($this->epm->PHONE_MODULES_PATH, 'endpoint', $brand_directory, $row['cfg_dir'], "family_data.json");
		$json_data = null;
		try
		{
			$json_data = $this->epm->file2json($json_file);
			if ($json_data === false || !is_array($json_data))
			{
				return "nothing";
			}
		}
		catch (\Exception $e)
		{
			return "nothing";
		}

		$firmware_ver = $json_data['data']['firmware_ver'] ?? '';
		if (!empty($firmware_ver))
		{
			if ($row['firmware_vers'] != "")
			{
				return "install";
			}
			else
			{
				return "remove";
			}
		}

		return "nothing";
    }










	/**
     * Reads a file. Json decodes it and will report any errors back
     * @param string $file location of file
     * @return mixed false on error, array on success
     */
    public function file2json($file)
	{
		$data_return = false;
        if (file_exists($file))
		{
            $json = file_get_contents($file);
            $data = json_decode($json, TRUE);
			

			switch (json_last_error())
			{
				case JSON_ERROR_NONE:
					$data_return = $data;
					break;

				case JSON_ERROR_DEPTH:
					$this->error['file2json'] = _('Maximum stack depth exceeded');
					// throw new \Exception(_('Maximum stack depth exceeded'));
					break;

				case JSON_ERROR_STATE_MISMATCH:
					$this->error['file2json'] = _('Underflow or the modes mismatch');
					// throw new \Exception(_('Underflow or the modes mismatch'));
					break;

				case JSON_ERROR_CTRL_CHAR:
					$this->error['file2json'] = _('Unexpected control character found');
					// throw new \Exception(_('Unexpected control character found'));
					break;

				case JSON_ERROR_SYNTAX:
					$this->error['file2json'] = _('Syntax error, malformed JSON');
					// throw new \Exception(_('Syntax error, malformed JSON'));
					break;

				case JSON_ERROR_UTF8:
					$this->error['file2json'] = _('Malformed UTF-8 characters, possibly incorrectly encoded');
					// throw new \Exception(_('Malformed UTF-8 characters, possibly incorrectly encoded'));
					break;

				default:
					$this->error['file2json'] = _('Unknown error');
					// throw new \Exception(_('Unknown error'));
					break;
			}
        }
		else
		{
            $this->error['file2json'] = sprintf(_('Cant find file: %s'), $file);
        }
		return $data_return;
    }


	/**
	 * Merges data from JSON files into a structured array based on category and subcategory data.
	 * This function processes each file specified in the template list, extracting and organizing 
	 * data according to its defined structure. It handles various item types within subcategories,
	 * applies transformations to loop structures, and aggregates final results into a comprehensive array.
	 * Errors during processing are collected and can be output or handled based on parameters.
	 *
	 * @param string $path The base path where the JSON files are located.
	 * @param array $template_list An array of filenames (relative to the base path) to process.
	 * @param int $maxlines The maximum number of lines to process in 'loop_line_options' type items.
	 * @param bool $show_error Determines whether to output errors.
	 * @param bool $show_error_mode_out Determines the mode of error output; `true` will use the `out()` function, `false` will use `echo`.
	 * @return array Returns a nested array of processed data categorized by 'category' and 'subcategory' names.
	 * @note Errors related to file existence, JSON decoding, and data structure validations are handled internally and reported as part of the function's error management system.
	 */	
	public function merge_data($path, $template_list, $maxlines = 12, $show_error = true, $show_error_mode_out = true)
	{
		$data  = array();
		$errors = [];

    	foreach ($template_list as $files_data)
		{
    		$full_path = $this->system->buildPath($path, $files_data);

			try
			{
				$temp_files_data = $this->epm->file2json($full_path);
				if ($temp_files_data === false)
				{
					$errors[] = sprintf(_("<b>ERROR:</b> file2json return false for the file '%s'."), $full_path);
					continue;
				}
			}
			catch (\Exception $e)
			{
				$errors[] = sprintf(_("<b>ERROR:</b> %s"), $e->getMessage());
				continue;
			}

			if (empty($temp_files_data['template_data']['category']) || !is_array($temp_files_data['template_data']['category']) )
			{
				$errors[] = sprintf(_("ERROR: Category in the file <b>'%s'</b> is not array!"), $full_path);
				continue;
			}

			foreach ($temp_files_data['template_data']['category'] as $category)
			{
				$category_name = $category['name'];

				if (empty($category['subcategory']) || !is_array($category['subcategory']) )
				{
					$errors[] = sprintf(_("ERROR: Subcategory <b>'%s'</b> in the file <b>'%s'</b> is not array!"), $category['name'], $full_path);
					continue;
				}
				foreach ($category['subcategory'] as $subcategory)
				{
					$subcategory_name = $subcategory['name'] ?? 'Settings';
					$items_fin 		  = array();
					$items_loop 	  = array();
					$break_count 	  = 0;
					foreach ($subcategory['item'] as $item)
					{
						switch ($item['type']) 
						{
							case 'loop_line_options':
								for ($i = 1; $i <= $maxlines; $i++) 
								{
									$var_nam = "lineloop|line_" . $i;
									foreach ($item['data']['item'] as $item_loop)
									{
										if ($item_loop['type'] != 'break')
										{
											$z = str_replace("\$", "", $item_loop['variable']);
											$items_loop[$var_nam][$z] 					= $item_loop;
											$items_loop[$var_nam][$z]['description'] 	= str_replace('{$count}', $i, $items_loop[$var_nam][$z]['description']);
											$items_loop[$var_nam][$z]['default_value'] 	= $items_loop[$var_nam][$z]['default_value'];
											$items_loop[$var_nam][$z]['default_value'] 	= str_replace('{$count}', $i, $items_loop[$var_nam][$z]['default_value']);
											$items_loop[$var_nam][$z]['line_loop'] 		= TRUE;
											$items_loop[$var_nam][$z]['line_count'] 	= $i;
										}
										elseif ($item_loop['type'] == 'break')
										{
											$items_loop[$var_nam]['break_' . $break_count]['type'] = 'break';
											$break_count++;
										}
									}
								}
								$items_fin = array_merge($items_fin, $items_loop);
								break;

							case 'loop':
								for ($i = $item['loop_start']; $i <= $item['loop_end']; $i++)
								{
									$name 	 = explode("_", $item['data']['item'][0]['variable']);
									$var_nam = "loop|" . str_replace("\$", "", $name[0]) . "_" . $i;
									foreach ($item['data']['item'] as $item_loop)
									{
										if ($item_loop['type'] != 'break')
										{
											$z_tmp = explode("_", $item_loop['variable'] ?? '');
											if (count($z_tmp) < 2) {
												$errors[] = sprintf(_("<b>Skip:</b> Loop Variable <b>'%s'</b> format not valid!"), $item_loop['variable'] ?? '');
												continue;
											}
											$z = $z_tmp[1];
											$items_loop[$var_nam][$z] 					= $item_loop;
											$items_loop[$var_nam][$z]['description'] 	= str_replace('{$count}', $i, $items_loop[$var_nam][$z]['description']);
											$items_loop[$var_nam][$z]['variable'] 		= str_replace('_', '_' . $i . '_', $items_loop[$var_nam][$z]['variable']);
											$items_loop[$var_nam][$z]['default_value'] 	= isset($items_loop[$var_nam][$z]['default_value']) ? $items_loop[$var_nam][$z]['default_value'] : '';
											$items_loop[$var_nam][$z]['loop'] 			= TRUE;
											$items_loop[$var_nam][$z]['loop_count'] 	= $i;
										}
										elseif ($item_loop['type'] == 'break')
										{
											$items_loop[$var_nam]['break_' . $break_count]['type'] = 'break';
											$break_count++;
										}
									}
								}
								$items_fin = array_merge($items_fin, $items_loop);
								break;

							case 'break':
								$items_fin['break|' . $break_count]['type'] = 'break';
								$break_count++;
								break;

							default:
								$var_nam = "option|" . str_replace("\$", "", (isset($item['variable'])? $item['variable'] : ""));
								$items_fin[$var_nam] = $item;
								break;
						}
					}
					if (isset($data['data'][$category_name][$subcategory_name]))
					{
						$old_sc 										 = $data['data'][$category_name][$subcategory_name];
						$sub_cat_data[$category_name][$subcategory_name] = array();
						$sub_cat_data[$category_name][$subcategory_name] = array_merge($old_sc, $items_fin);
					}
					else
					{
						$sub_cat_data[$category_name][$subcategory_name] = $items_fin;
					}
				}
				if (isset($data['data'][$category_name]))
				{
					$old_c 						  = $data['data'][$category_name];
					$new_c 						  = $sub_cat_data[$category_name];
					$sub_cat_data[$category_name] = array();
					$data['data'][$category_name] = array_merge($old_c, $new_c);
				}
				else
				{
					$data['data'][$category_name] = $sub_cat_data[$category_name];
				}
			}
    	}

		if ($show_error)
		{
			foreach ($errors as $error)
			{
				if ($show_error_mode_out)
				{
					out($error);
				}
				else {
					echo $error;
				}
			}
		}

    	return($data);
    }

}