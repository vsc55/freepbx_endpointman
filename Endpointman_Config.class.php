<?php
/**
 * Endpoint Manager Object Module - Sec Config
 *
 * @author Javier Pastor
 * @license MPL / GPLv2 / LGPL
 * @package Provisioner
 */

namespace FreePBX\modules;

use FreePBX\modules\Endpointman\Provisioner\ProvisionerBrand;
use FreePBX\modules\Endpointman\Provisioner\ProvisionerModel;

require_once('lib/epm_system.class.php');
require_once('lib/epm_packages.class.php');
require_once('lib/provisioner/ProvisionerBrand.class.php');
require_once('lib/provisioner/ProvisionerModel.class.php');


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

	public function myShowPage(&$pagedata) { }

	/**
	 * Check if the user has access to the module
	 * 
	 */
	public function ajaxRequest($req, &$setting)
	{
		$allowCommand = array(
			"saveconfig",
			"list_all_brand",
			"list_brand_model_hide"
		);
		if (in_array($req, $allowCommand)) {
			$setting['authenticate'] = true;
			$setting['allowremote']   = false;
			return true;
		}
		return false;
	}

	/**
	 * Handle AJAX requests
	 * 
	 * @param string $module_tab tab section of the command to execute
	 * @param string $command Command to execute
	 * @return array Returns an array with data that will be sent to the client.
	 */
    public function ajaxHandler(?string $module_tab = "", ?string $command = "")
	{
		$request = freepbxGetSanitizedRequest();

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
			'fw_uninstall' 	 	 => _('FW Delete'),
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
				$retarr = $this->epm_config_manager_saveconfig($request);
				break;

			case "list_all_brand":
				$retarr = array(
					"status" => true,
					"message" => "OK",
					"datlist" => $this->epm_config_manager_hardware_get_list_all()
				);
				break;

			case "list_brand_model_hide":
				$retarr = array(
					"status" => true,
					"message" => "OK",
					"datlist" => $this->epm_config_manager_hardware_get_list_all_hide_show()
				);
				break;

			default:
				$retarr = array(
					"status" => false,
					"message" => sprintf(_("Command not found [%s]!"), $command)
				);
				break;
		}
		$retarr['txt'] = $txt;
		return $retarr;
	}

	/**
	 * Initialize the configuration page
	 * 
	 * @param string $module_tab tab section of the command to execute
	 * @param string $command Command to execute
	 */
	public function doConfigPageInit(?string $module_tab = "", ?string $command = "")
	{
		$request = freepbxGetSanitizedRequest();

		// Force flush all output buffers, need for AJAX

		$endprocess = true;
		switch ($command)
		{
			case "check_for_updates":
				$this->activeFlush();
				$this->epm_config_manager_check_for_updates();
				break;

			// case "manual_install":
			// 	$this->epm_config_manual_install();
			// 	break;

			case "firmware":
				$this->activeFlush();
				$this->epm_config_manager_firmware($request);
				break;

			case "brand":
				$this->activeFlush();
				$id 	 	 = $request['idfw'] 	   ?? '';
				$command_sub = $request['command_sub'] ?? '';
				$this->epm_config_manager_brand($id , $command_sub);
				break;

			default:
				$endprocess = false;
				break;
		}

		if ($endprocess)
		{
			echo "<br /><hr><br />";
			flush();
			exit;
		}
	}

	private function activeFlush()
	{
		ob_implicit_flush(true);
		while (ob_get_level() > 0)
		{
			ob_end_flush();
		}
	}


	public function getRightNav($request, $params = array()) { return ""; }

	public function getActionBar($request) { return ""; }

	
	private function epm_config_manager_check_for_updates ()
	{
		out("<h3>"._("Update data...")."</h3>");
		if (! $this->update_check(true))
		{
			out (_("❌Something Went Wrong!"));
			return false;
		}
		else
		{
			out (_("🔳 Process Completed!"));
			return true;
		}
	}

	private function epm_config_manager_brand($id = "", ?string $command_sub = "")
	{
		if (!isset($id))
		{
			out(_("❌ No ID Received!"));
			return false;
		}
		else if (!is_numeric($id))
		{
			out(sprintf(_("❌ ID [%s] Received Is Not a Number!"), $id));
			return false;
		}
		else if (empty($command_sub) || !is_string($command_sub))
		{
			out(_("❌ No Command Received or Command Is Not a String!"));
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
				out ( sprintf(_("❌ Command [%s] not valid!"), $command_sub) );
				return false;
		}
		$this->update_check();
		return true;
	}

	private function epm_config_manager_firmware($request = array())
	{
		$request_args 	  = array("command_sub", "idfw");
		$request_args_int = array("idfw");
		$args_check 	  = $this->epm->system->check_request_args($request, $request_args, $request_args_int);

		switch(true)
		{
			case (empty($args_check)):
			case ($args_check === false):
				out(_("❌ Error in the process of checking the request arguments!"));
				return false;

			case ($args_check === true):
				break;

			case (is_string($args_check)):
			default:
				out($args_check);
				return false;
		}

		$id 	 = $request['idfw'];
		$command = $request['command_sub'];

		switch(strtolower($command))
		{
			case "fw_install":
			case "fw_update":
				$this->install_firmware($id);
				break;

			case "fw_uninstall":
				$this->remove_firmware($id);
				break;

			default:
				out (sprintf(_("❌ Command '%s' not found!"), $command));
				return false;
		}
		return true;
	}

	private function epm_config_manager_saveconfig($request = array())
	{
		$request_args 	  = array("typesavecfg", "value", "idtype", "idbt");
		$request_args_int = array("value", "idbt");
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

		$id 		 = $request['idbt'];
		$idtype 	 = strtolower($request['idtype']);
		$typesavecfg = strtolower($request['typesavecfg']);
		$value 		 = strtolower($request['value']);
		
		
		if (! in_array($typesavecfg, array("hidden", "enabled")))
		{
			return array("status" => false, "message" => sprintf(_("Invalid TypeSaveCfg '%s'!"), $typesavecfg));
		}
		if (($value > 1 ) and ($value < 0))
		{
			return array("status" => false, "message" => sprintf(_("Invalid Value '%s'!"), $value));
		}

		if ($typesavecfg == "enabled")
		{
			switch($idtype)
			{
				case 'modelo':
					$this->epm->set_hw_model($id, array("enabled" => $value), 'id');
					break;

				default:
					return array("status" => false, "message" => sprintf(_("IDType '%s' invalid for Enabled!"), $idtype));
			}
		}
		else
		{
			switch($idtype)
			{
				case "marca":
					$this->epm->set_hw_brand($id, array("hidden" => $value), 'id');
					break;

				case "producto":
					$this->epm->set_hw_product($id, array("hidden" => $value), 'id');
					break;

				case "modelo":
					$this->epm->set_hw_model($id, array("hidden" => $value), 'id');
					break;

				default:
					return array("status" => false, "message" => sprintf(_("IDType '%s' invalid!"), $idtype));
			}
		}
		return array("status" => true, "message" => "OK", "typesavecfg" => $typesavecfg, "value" => $value, "idtype" => $idtype, "id" => $id);
	}


	/**
	 * Get a list of all brands, products, and models.
	 * 
	 * @return array An array of all the brands/products/models and information about what's  enabled, installed or otherwise
	 * 
	 * Url Query: http://{serverpbx}/admin/ajax.php?module=endpointman&module_sec=epm_config&module_tab=manager&command=list_brand_model_hide
	 */
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


	/**
	 * Get a list of all brands, products, and models.
	 * 
	 * @return array An array of all the brands/products/models and information about what's  enabled, installed or otherwise
	 * 
	 * Url Query: http://{serverpbx}/admin/ajax.php?module=endpointman&module_sec=epm_config&module_tab=manager&command=list_all_brand
	 */
	public function epm_config_manager_hardware_get_list_all()
	{
		$row_out 	= [];
		$brand_list = $this->epm->get_hw_brand_list(true, "name");
		//FIX: https://github.com/FreePBX-ContributedModules/endpointman/commit/2ad929d0b38f05c9da1b847426a4094c3314be3b
	
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
					$product['update_fw'] = 1;
					$product['update_vers_fw'] = $this->firmware_update_check($product['id']);
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


	/**
	 * Check for new packges for brands. These packages will include phone models and such which the user can remove if they want
	 * This function will alos auto-update the provisioner.net library incase anything has changed
	 *
	 * @param bool $echomsg (optional) Whether to echo error messages.
	 * @param array $error (optional) Reference to an array to store error messages.
	 * @return array An array of all the brands/products/models and information about what's  enabled, installed or otherwise
	 * 
	 * $out['update'] = -4; // Error: No Version Found
	 * $out['update'] = -3; // Error: Brand Data File Missing or Cannot Be Parsed
	 * $out['update'] = -2; // Error: Brand Not Found
	 * $out['update'] = -1; // Error: Brand Data File Missing
	 * $out['update'] = 0;  // Brand Up to Date
	 * $out['update'] = 1;  // Brand Needs Update
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

		$row 		 = $this->epm->get_hw_brand($brand_name_find, 'directory', '*', true);
		$directory 	 = $row['directory'] ?? NULL;
		$version_db  = $row['cfg_ver'] ?? 0;
		$json_master = $this->epm->packages->master_json;

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
				if (empty($out['update_vers']))
				{
					$out['update'] = -4;
				}
			}
		}
	
		// TODO: Test Data
		// $out['update'] 		= 1;
		// $out['update_vers'] = '1724705560';

		switch($out['update'])
		{
			case -4:
				$out['update_vers_txt'] = _("Error: No Version Found");
				break;

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
				$out['update_vers_txt'] = empty($out['update_vers']) ? _("Undefinde") : date("c", $out['update_vers']);
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
	 * @return array An array of all the brands/products/models and information about what's  enabled, installed or otherwise
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
			$url_status	= $this->system->buildUrl($this->epm->URL_UPDATE, "update_status");

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

			$out("⚡ Checking for package updates: ", false);
			$local_endpoint_version = $this->epm->getConfig('endpoint_vers', null);
			$new_endpoint_version   = !$master_result ? false : $master_json->downloadPackage($local_endpoint_version);
			if ($new_endpoint_version !== false)
			{
				if ($local_endpoint_version != $new_endpoint_version)
				{
					$this->epm->setConfig('endpoint_vers', $new_endpoint_version);
					$out( sprintf(_("The package has been updated from version %s to version %s ✔."), $local_endpoint_version, $new_endpoint_version) );	
				}
				else
				{
					$out( sprintf(_("Package is up to date at version %s ✔"), $local_endpoint_version) );
				}
			}
			else
			{
				$out(_("Error ❌"));
				$outputError('brand_update_check_package', _("💥 Not able to connect to repository. Using local Provisioner.net Package"));
			}
			$out(" ");

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
							$this->epm->set_hw_oui($oui, $brand->getBrandID());
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
						$this->epm->set_hw_oui($oui, $brand->getBrandID());
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
						'short_name' 	=> $family_short_name,
						'long_name' 	=> $family->getName(),
						'cfg_ver' 		=> $family->getLastModified(),
						'config_files' => $family->getConfigurationFiles()
					);
					$data_product_insert = array(
						'brand_id' 	=> $brand_id,
						'directory' 	=> $family->getDirectory(),
					);
					$this->epm->set_hw_product($family_id, $data_product, 'id', $data_product_insert);
					
					$models = $family->getModelList();
					foreach ($models as &$model)
					{
						$model_id = $model->getModelId();

						// $model->getConfigurationFiles() > old system implode(",", $model->getConfigurationFiles());
						$data_model = array(
							'model' 		=> $model->getModel(),
							'max_lines' 	=> $model->getMaxLines(),
							'template_list'=> $model->getConfigurationFiles()
						);
						$data_model_insert = array(
							'brand' 		=> $brand_id,
							'product_id' 	=> $family_id,
							'enabled' 		=> 0,
							'hidden' 		=> 0
						);
						$this->epm->set_hw_model($model_id, $data_model, 'id', $data_model_insert);

						$errsync_modal = array();
						// if (!$this->sync_model($model_id, $errsync_modal))
						if (!$this->sync_model($model, $errsync_modal))
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
	 * Syncs a model with the database.
	 * 
	 * @param ProvisionerModel $model The model to sync.
	 * @param array $error Reference to an array to store error messages.
	 * @return bool Returns true if the model was successfully synced, false otherwise.
	 */
    public function sync_model(?ProvisionerModel $model, &$error = array()) 
	{
		if (empty($model))
		{
			$error['sync_model'] = _("Model is empty!");
			return false;
		}

		//TODO: Is it necessary to bring all the data configured in the database query?
		$sql = sprintf('SELECT eml.id, eml.product_id, ebl.id as brand_id, eml.brand, eml.model, epl.cfg_dir, ebl.directory FROM %s as eml
						JOIN %s as epl ON eml.product_id = epl.id
    					JOIN %s as ebl ON eml.brand = ebl.id
    					WHERE eml.id = :id', "endpointman_model_list", "endpointman_product_list", "endpointman_brand_list");
		$stmt = $this->db->prepare($sql);
		$stmt->execute([
			':id' => $model->getModelId()
		]);
		if ($stmt->rowCount() === 0)
		{
			$error['sync_model'] = _("Model not found!");
			return false;
		}
		$row = $stmt->fetch(\PDO::FETCH_ASSOC);

		$brand_dir = $row['directory'];
		$model_dir = $row['cfg_dir'];

		if (empty($brand_dir) || empty($model_dir))
		{
			$error['sync_model'] = sprintf(_("Brand or Product Directory is empty from the model '%s'!"), $model->getModel());
			return false;
		}

		if ($model->isParentSet() === false)
		{
			$error['sync_model'] = _("Family Parent not set!");
			return false;
		}
		$family = $model->getParent();

		// if ($family->isParentSet() === false)
		// {
		// 	$error['sync_model'] = _("Brand Parent not set!");
		// 	return false;
		// }
		// $brand = $family->getParent();

		if (! $family->isModelExist($row['model']))
		{
			$error['sync_model'] = _("Can't locate model in family JSON file");
			return false;
		}

		$data_model = array(
			'model' 		=> $row['model'],
			'max_lines'		=> $model->getMaxLines(),
			'template_list'	=> $model->getTemplateList(true)
		);
		$this->epm->set_hw_model($row['id'], $data_model);
		unset($data_model);

		$data_product = array(
			'short_name' => $family->getShortName(),
			'long_name'  => $family->getName(),
			'cfg_ver' 	 => $family->getLastModified(),
		);
		$this->epm->set_hw_product($row['product_id'], $data_product);
		unset($data_product);
		
		try
		{
			$errorImportTemplates = array();
			$template_data_array = $model->importTemplates(12, $errorImportTemplates, false);
			
			// Important to serialize $template_data_array before saving it to the database
			$update_model = array('template_data' => serialize($template_data_array));
			$this->epm->set_hw_model($row['id'], $update_model, 'id');
			unset($update_model);
		}
		catch (\Exception $e)
		{
			$error['sync_model'] = sprintf("❌ %s", $e->getMessage());
			$template_data_array = array();
		}
		finally
		{
			if (!empty($errorImportTemplates))
			{
				foreach ($errorImportTemplates as $errorMsg)
				{
					out($errorMsg);
				}
			}
			unset($errorImportTemplates);
		}
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
    	out(_("⚡ Install/Update Brand..."));
		
		if (!is_numeric($id))
		{
			out(_("❌ No Brand ID Given!"));
			return false;
		}
		elseif (! $this->epm->is_exist_hw_brand($id, 'id'))
		{
			out(sprintf(_("❌ Brand with id '%s' not found!"), $id));
			return false;
		}
		elseif ($this->configmod->get('use_repo'))
		{
			out(_("❌ Installing brands is disabled while in repo mode!"));
			return false;
		}


		$row = $this->epm->get_hw_brand($id, 'id', '*', true);
		if (empty($row['directory']))
		{
			out(_("❌ Brand Directory Is Not Set!"));
			return false;
		}


		$brand = $this->epm->packages->getBrandByDirectory($row['directory']);
		if(empty($brand))
		{
			out(_("❌ Brand Object is Empty!"));	
			return false;
		}

		out(_("⚡ Downloading Brand JSON ..."));
		try
		{
			if (! $brand->downloadBrand(true, false, true))
			{
				out(_("💥 Error Connecting to the Package Repository. Module not installed. Please Try again later."));
				return false;
			}
		}
		catch (\Exception $e)
		{
			out(sprintf(_("❌ %s"), $e->getMessage()));
			out(_("❌ Error Connecting to the Package Repository. Module not installed. Please Try again later."));
			out(sprintf(_("❌ You Can Also Manually Update The Repository By Downloading Files here: <a href='%s' target='_blank'> Release Repo </a>"), "http://www.provisioner.net/releases3"));
			out(_("❌ Then Use Manual Upload in Advanced Settings."));
			return false;
		}

		$package = $brand->getPackage();
		if (empty($package))
		{
			out(_("❌ No Package Found in JSON File!"));
			return false;
		}

		out(_("⚡ Downloading Brand Package ..."));
		try
		{
			if (! $brand->downloadPackage(true, false))
			{
				out(_("❌ Error Connecting to the Package Repository. Module not installed. Please Try again later."));
				return false;
			}
		}
		catch (\Exception $e)
		{
			out(sprintf("❌ %s", $e->getMessage()));
			return false;
		}
		if (!$brand->isExistPackageFile())
		{
			out(_("❌ Can't Find Downloaded File!"));
			return false;
		}

		if (empty($brand->getMd5Sum()))
		{
			out(_("💥 Skipping MD5 Check!"));
		}
		else
		{
			outn(_("⚡ Checking MD5sum of Package ..."));
			if (! $brand->checkMD5PackageFile(true, true))
			{
				out(" ❌");
				out(_("💥 Error MD5 Check Failed!"));
				
				return false;
			}
			else
			{
				out(" ✔");
				out(_("✅ MD5 Check Passed!"));
			}
		}
		
		outn(_("⚡ Extracting Tarball ..."));
		try
		{
			if (! $brand->extractPackage(false))
			{
				out(_(" ❌"));
				out(_("💥 Error Extracting Package!"));
				return false;
			}
			out(" ✔");
		}
		catch (\Exception $e)
		{
			out(" ❌");
			out(sprintf("💥 %s", $e->getMessage()));
			return false;
		}

		$return_update_brand = $this->update_brand($brand, true);

		if ($brand->isPackageExtractFolderExist())
		{
			outn(_("⚡ Removing Temporary Files ..."));
			$brand->removePackageExtract();
        	out(" ✔");
		}


		out(" ");
		out(_("✅ Brand Installed/Updated Successfully!"));

		return $return_update_brand;
    }


    /**
     * This will install or updated a brand package (which is the same thing to this)
     * Still needs way to determine when models move...perhaps another function?
	 * 
	 * @param ProvisionerBrand $brand The brand object to update.
	 * @param bool $remote (optional) Whether the brand is remote or local.
	 * @return bool Returns true if the brand was successfully installed/updated, false otherwise.
     */
    public function update_brand(?ProvisionerBrand $brand, $remote = true)
	{
		if (empty($brand))
		{
			out(_("❌ Update Brand Error, No Brand Object Given!"));
			return false;
		}

    	out(sprintf(_("⚡ Update Brand '%s' ..."), $brand->getName()));
		out( sprintf(_("⚡ Processing '%s' ..."), $brand->getName()));
		if (empty($brand->getDirectory()) || empty($brand->getBrandID()))
		{
			out(_("❌ Error: Invalid JSON Structure in file json!"));
			return false;
		}
		else
		{
			out(_("✅ Appears to be a valid Provisioner.net JSON file.....Continuing ✔"));
			outn(sprintf(_("⚡ Creating Directory Structure for Brand '%s' and Moving Files ..."), $brand->getName()));
			try
			{
				$error_move  = array();
				$return_move = $brand->movePackageExtracted($remote, $error_move, false);
				out($return_move ? " ✔": " ❌");

				foreach ($error_move as $error)
				{
					out(sprintf(_("💥 Warning: Unable to move file '%s'"), $error));
				}
				unset($error_move);

				if ($return_move === false)
				{
					out(_("❌ Error: Unable to move files!"));
					return false;
				}
			}
			catch (\Exception $e)
			{
				out(sprintf("❌ %s", $e->getMessage()));
				return false;
			}
			finally
			{
				$brand->removePackageExtract();
				unset($return_move);
			}

			try
			{
				$brand->importJSON(null, false, true);
			}
			catch (\Exception $e)
			{
				out(sprintf("❌ %s", $e->getMessage()));
				return false;
			}

			if (! $this->epm->is_exist_hw_brand($brand->getDirectory()))
			{
				outn( sprintf(_("⚡ Inserting Brand '%s' ..."), $brand->getName()));
			}
			else
			{
				outn( sprintf(_("⚡ Updating Brand '%s' ..."), $brand->getName()));
			}
			$new_brand = array(
				'id'		=> $brand->getBrandID(),
				'name'		=> $brand->getName(),
				'cfg_ver'	=> $brand->getLastModified(),
				'local' 	=> $remote ? 0 : 1,
				'installed' => 1,
			);
			$new_brand_insert = array(
				'directory'	=> $brand->getDirectory(),
			);
			$this->epm->set_hw_brand($brand->getBrandID(), $new_brand, 'id', $new_brand_insert);
			unset($new_brand);
			unset($new_brand_insert);

			out(" ✔");


			foreach ($brand->getFamilyList() as &$family)
			{
				out(_("⚡ Updating Family Lines ..."));

				
				//TODO: Code obsolete not present in new system
				// if (($remote) && ($family_line['data']['require_firmware'] == "TRUE"))
				// {
				// 	out(_("Firmware Requirment Detected!.........."));
				// 	$this->install_firmware($family_line['data']['id']);
				// }


				
				outn( sprintf(_("⚡ - Inserting/Updating Family '%s'..."), $family->getShortName()));
				$new_product = array(
					'short_name'	=> $family->getShortName(),
					'long_name'		=> $family->getName(),
					'cfg_ver'		=> $family->getLastModified(),
					'config_files'	=> $family->getConfigurationFiles(true),
				);
				$new_product_insert = array(
					// 'id' 	  => $family->getID(),
					'id' 	  => $family->getFamilyId(),
					'brand'	  => $family->getBrandID(),
					'cfg_dir' => $family->getDirectory(),
					'hidden'  => 0,
				);
				$this->epm->set_hw_product($family->getFamilyId(), $new_product, 'id', $new_product_insert);
				out(" ✔");


				if ($family->countModels() > 0)
				{
					out(_("⚡ - Updating Model Lines ... "));
				}
				foreach ($family->getModelList() as &$model)
				{

					foreach ($this->epm->get_hw_mac($model->getModelId(), 'model', 'id, global_custom_cfg_data, global_user_cfg_data') as $mac_list_item)
					{
						$global_custom_cfg_data = unserialize($mac_list_item['global_custom_cfg_data'] ?? '');

						if ((is_array($global_custom_cfg_data)) AND (!array_key_exists('data', $global_custom_cfg_data)))
						{
							outn(_("⚡ -- Old Data Detected! Migrating ... "));

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


							$update_hw_mac = array(
								'global_custom_cfg_data' => serialize(array('data' => $new_data, 'ari'  => $new_ari)),
							);
							$this->epm->set_hw_mac($mac_list_item['id'], $update_hw_mac, 'id');
							unset($update_hw_mac);


							// $sql  = sprintf("UPDATE %s SET global_custom_cfg_data = :cfg_data WHERE id = :id", "endpointman_mac_list");
							// $stmt = $this->db->prepare($sql);
							// $stmt->execute([
							// 	':cfg_data' => serialize(array('data' => $new_data, 'ari'  => $new_ari)),
							// 	':id'	    => $data['id']
							// ]);

							// out(_("Done!"));
							out(" ✔");
						}

						$global_user_cfg_data = unserialize($mac_list_item['global_user_cfg_data'] ?? '');
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
							outn(_("⚡ -- Old Data Detected! Migrating ..."));
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

							$update_hw_mac = array(
								'global_user_cfg_data' => serialize($new_data),
							);
							$this->epm->set_hw_mac($mac_list_item['id'], $update_hw_mac, 'id');
							unset($update_hw_mac);



							// $sql  = sprintf("UPDATE %s SET global_user_cfg_data = :cfg_data WHERE id = :id", "endpointman_mac_list");
							// $stmt = $this->db->prepare($sql);
							// $stmt->execute([
							// 	':cfg_data' => serialize($new_data),
							// 	':id'	    => $data['id'],
							// ]);

							// out(_("Done!"));
							out(" ✔");
						}
					}


					foreach ($this->epm->get_hw_template($model->getModelId(), 'model_id', 'id, global_custom_cfg_data') as $template_item)
					{
						$global_custom_cfg_data = unserialize($template_item['global_custom_cfg_data'] ?? '');

						if ((is_array($global_custom_cfg_data)) AND (!array_key_exists('data', $global_custom_cfg_data))) 
						{
							out(_("⚡ -- Old Data Detected! Migrating ..."));
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
							
							$update_hw_template = array(
								'global_custom_cfg_data' => serialize(array('data' => $new_data, 'ari'  => $new_ari)),
							);
							$this->epm->set_hw_template($template_item['id'], $update_hw_template, 'id');
							unset($update_hw_mac);

							// $sql  = sprintf("UPDATE %s SET global_custom_cfg_data = :cfg_data WHERE id = :id", "endpointman_template_list");
							// $stmt = $this->db->prepare($sql);
							// $stmt->execute([
							// 	':cfg_data' => serialize(array('data' => $new_data, 'ari'  => $new_ari)),
							// 	':id'	    => $data['id']
							// ]);

							// out(_("Done!"));
							out(" ✔");
						}
					}
					

					
					outn( sprintf(_("⚡ - Inserting/Updating Model '%s' ..."), $model->getModel()));
					$new_model = array(
						'max_lines'		=> $model->getMaxLines(),
						'model'			=> $model->getModel(),
						'template_list'	=> $model->getTemplateList(true),
					);
					$new_model_insert = array(
						'id' 		 => $model->getModelId(),
						'brand'		 => $model->getBrandID(),
						'product_id' => $model->getFamilyId(),
						'enabled'	 => 0,
						'hidden'	 => 0,
					);

					$this->epm->set_hw_model($model->getModelId(), $new_model, 'id', $new_model_insert);
					unset($new_model);
					unset($new_model_insert);
					out (" ✔");

					$errlog = array();
					if (! $this->sync_model($model, $errlog))
					{
						out(_("❌ System Error in Sync Model Function, Load Failure!"));
						out("❌ ".$errlog['sync_model']);
					}
					unset ($errlog);
				}
				//END Updating Model Lines................


				//Phone Models Move Here
				foreach ($this->epm->get_hw_model_list($family->getFamilyId(), true) as $model_item)
				{
					$model_id 	= $model_item['id']    ?? '';
					$model_name = $model_item['model'] ?? '';
					
					if (empty($model_name) || empty($model_id))
					{
						continue;
					}

					if (! $family->isModelExist($model_name))
					{
						outn(sprintf(_("Moving/Removing Model '%s' not present in JSON file ..."), $model_name));

						$this->epm->del_hw_model($model_id);

						
						//TODO: Mover Query a otro sitio
						$sql = sprintf('SELECT id FROM %s WHERE model LIKE :model_name', "endpointman_model_list");
						$stmt = $this->db->prepare($sql);
						$stmt->execute([
							':model_name' => $model_name
						]);
						$new_model_id = $stmt->rowCount() === 0 ? false : ($stmt->fetchColumn() ?? false);

						// If the model is not found, set the model to 0
						$update_model = array(
							'model' => $new_model_id ? $new_model_id: '0',
						);
						$this->epm->set_hw_mac($model_id, $update_model, 'model');
						unset($update_model);
						unset($new_model_id);

						out(_(" ✔"));
					}
				}
			}
			out(_("✅ All Done!"));
			//END Updating Family Lines

			outn(_("⚡ Updating OUI list in DB ..."));
			foreach ($brand->getOUI() as $oui)
			{
				$this->epm->set_hw_oui($oui, $brand->getBrandID());
			}
			out(" ✔");
		}
    }

	
	/**
	 * Removes a brand from the system.
	 *
	 * @param int $id The ID of the brand to remove.
	 * @param bool $force Whether to force the removal of the brand.
	 * @return bool Returns true if the brand is successfully removed, false otherwise.
	 */
    public function remove_brand($id = null, $force = false)
	{
		out(_("⚡ Uninstalla Brand ..."));

		if (!is_numeric($id))
		{
			out(_("❌ No ID Given!"));
			return false;
		}
		elseif ($this->configmod->get('use_repo') && !$force)
		{
			out(_("❌ Not allowed in repo mode!!"));
			return false;
        }
		elseif (! $this->epm->is_exist_hw_brand($id, 'id'))
		{
			out(sprintf(_("❌ Brand with id '%s' not found!"), $id));
			return false;
		}

        if (!$this->configmod->get('use_repo') && !$force)
		{
			$row = $this->epm->get_hw_brand($id, 'id', '*', true);
			if (empty($row['directory'] || empty($row['id'])))
			{
				out(_("❌ Brand Directory or ID Is Not Set!"));
				return false;
			}
			$brand = $this->epm->packages->getBrandByDirectory($row['directory']);
			if(empty($brand))
			{
				out(_("❌ Brand Object is Empty!"));	
				return false;
			}

            foreach ($this->epm->get_hw_product($id, 'brand', 'id, firmware_vers') as $product)
			{
				if (empty($product['firmware_vers'])) {
					continue;
				}
                $this->remove_firmware($product['id']);
			}
			$brand->uninstall();
		}

		$this->epm->del_hw_brand_any_list($id);
		
		out(_("✅ Uninstall Brand Success!"));
		return true;
    }


	/**
	 * Installs firmware for a specific product.
	 *
	 */
    public function install_firmware($product_id)
	{
    	out(_("⚡ Installa frimware..."));

		if (empty($product_id) || !is_numeric($product_id))
		{
			out(_("❌ No ID Given!"));
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
	 * @param int|null $id The ID of the product to remove the firmware from.
	 * @return bool Returns true if the firmware is successfully removed, false otherwise.
	 */
    public function remove_firmware($id = null)
	{
		out(_("⚡ Uninstalla frimware ..."));

		if (empty($id) || !is_numeric($id))
		{
			out(_("❌ No ID Given!"));
			return false;
		}
		elseif (! $this->epm->is_exist_hw_product($id, "id", true))
		{
			out(_("❌ Product is not exist in the database!"));
			return false;
		}

		$files  = null;
		$result = $this->epm->get_hw_product($id, 'id', 'id, firmware_files', true);
		if (!empty($result))
		{
			$files = $files['firmware_files'] ?? "";
		}
		if (empty($files))
		{
			out(_("💥Skipping, the brand does not have any firmware files."));
			return true;
		}

		$path_config = $this->configmod->get('config_location');
		if (empty($path_config))
		{
			out(_("⭕ Skipping, Config Location tftp is not set!"));
		}
		elseif (!file_exists($path_config))
		{
			out(_("⭕ Skipping, Location tftp does not exist!"));
		}
		else
		{
			foreach (explode(",", $files) as $file)
			{
				if (empty(trim($file)) || !is_string($file))
				{
					continue;
				}
				$file_path = $this->epm->brindPath($path_config, $file);
				if (file_exists($file_path) &&  is_file($file_path))
				{
					// Remove files from tftp directory
					if (!unlink($file_path))
					{
						out(sprintf(_("❌ Unable to remove firmware file '%'s!"), $file));
					}
				}
			}
		}

		$this->epm->set_hw_product($id, ['firmware_files' => '', 'firmware_vers' => ''], 'id');

		out(_("✅ Firmware Removed Successfully!"));
    }


	/**
	 * Checks if a firmware update is available for a given product ID.
	 *
	 * @param int|null $id The ID of the product to check.
	 * @return string Returns an empty string if the ID is not numeric, the product is not found, or the configuration drive is unknown.
	 * 			  Returns the firmware version if the firmware version in the database is less than the firmware version in the JSON file.
	 * 			  Returns an empty string otherwise.
	 */
    public function firmware_update_check($id = null)
	{
		if (empty($id) || !is_numeric($id))
		{
			return '';
		}

		$product_json = $this->epm->packages->getProductByProductID($id);
		$product_db   = $this->epm->get_hw_product($id, "id", "*", true);

		if (empty($product_json) || empty($product_db))
		{
			// Return false if the product is not found or the configuration drive is unknown.
			return '';
		}

		$firmware_ver_json = $product_json->getFirmwareVer();
		$firmware_ver_db   = $product_db['firmware_vers'] ?? '';

		if ($firmware_ver_db < $firmware_ver_json)
		{
			return $firmware_ver_json;
		}
		return '';
    }


	/**
	 * Checks the local firmware for a given ID.
	 *
	 * @param int|null $id The ID of the product to check.
	 * @return string Returns 'nothing' if the ID is not numeric, the product is not found, or the configuration drive is unknown.
	 * 			  Returns 'remove' if the firmware version in the database is empty.
	 * 			  Returns 'install' if the firmware version in the database is not empty.
	 * 			  Returns 'nothing' otherwise.
	 */
    public function firmware_local_check(?int $id = null)
	{
		if (empty($id) || ! is_numeric($id) )
		{
			return "nothing";
		}

		// Not exist in the database or the configuration drive is unknown.
		if (! $this->epm->is_exist_hw_product($id, 'id', true))
		{
			return "nothing";
		}

		$product_json = $this->epm->packages->getProductByProductID($id);
		if (empty($product_json) || empty($product_db))
		{
			// Is not found or the configuration drive is unknown.
			return 'nothing';
		}

		if (empty($product_json->getFirmwareVer()))
		{
			return "remove";
		}
		return "install";
    }










	// /**
    //  * Reads a file. Json decodes it and will report any errors back
    //  * @param string $file location of file
    //  * @return mixed false on error, array on success
    //  */
    // public function file2json($file)
	// {
	// 	$data_return = false;
    //     if (file_exists($file))
	// 	{
    //         $json = file_get_contents($file);
    //         $data = json_decode($json, TRUE);
			

	// 		switch (json_last_error())
	// 		{
	// 			case JSON_ERROR_NONE:
	// 				$data_return = $data;
	// 				break;

	// 			case JSON_ERROR_DEPTH:
	// 				$this->error['file2json'] = _('Maximum stack depth exceeded');
	// 				// throw new \Exception(_('Maximum stack depth exceeded'));
	// 				break;

	// 			case JSON_ERROR_STATE_MISMATCH:
	// 				$this->error['file2json'] = _('Underflow or the modes mismatch');
	// 				// throw new \Exception(_('Underflow or the modes mismatch'));
	// 				break;

	// 			case JSON_ERROR_CTRL_CHAR:
	// 				$this->error['file2json'] = _('Unexpected control character found');
	// 				// throw new \Exception(_('Unexpected control character found'));
	// 				break;

	// 			case JSON_ERROR_SYNTAX:
	// 				$this->error['file2json'] = _('Syntax error, malformed JSON');
	// 				// throw new \Exception(_('Syntax error, malformed JSON'));
	// 				break;

	// 			case JSON_ERROR_UTF8:
	// 				$this->error['file2json'] = _('Malformed UTF-8 characters, possibly incorrectly encoded');
	// 				// throw new \Exception(_('Malformed UTF-8 characters, possibly incorrectly encoded'));
	// 				break;

	// 			default:
	// 				$this->error['file2json'] = _('Unknown error');
	// 				// throw new \Exception(_('Unknown error'));
	// 				break;
	// 		}
    //     }
	// 	else
	// 	{
    //         $this->error['file2json'] = sprintf(_('Cant find file: %s'), $file);
    //     }
	// 	return $data_return;
    // }
}