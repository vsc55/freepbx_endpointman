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
use FreePBX\modules\Endpointman\Provisioner\ProvisionerFamilyDB;
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


	/**
	 * Check if the user has access to the module
	 */
	public function ajaxRequest($req, &$setting, array $data)
	{
		$allowCommand = array(
			"saveconfig",
			"list_all_brand",
			"list_brand_model_hide"
		);
		if (in_array($req, $allowCommand))
		{
			$setting['authenticate'] = true;
			$setting['allowremote']  = false;
			return true;
		}
		return false;
	}

	/**
	 * Handle AJAX requests
	 * 
	 * @param array $data Data to be processed
	 * 		- command: Command to be executed
	 * 		- request: Request data
	 * @return array Data to be returned
	 * 		- status: Status of the request
	 * 		- message: Message to be returned
	 * 		- datlist: Data list to be returned only for return list (list_all_brand, list_brand_model_hide)
	 * 		- txt: Text to be returned
	 */
    public function ajaxHandler(array $data)
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

		if (empty($data) || !is_array($data))
		{
			$retarr = array(
				"status" => false,
				"message" => _("Empty data received or data is not foromatted correctly!")
			);
		}
		else
		{
			$command = $data['command'] ?? '';
			$request = $data['request'] ?? array();

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
		}
		$retarr['txt'] = $txt;
		return $retarr;
	}

	/**
	 * Initialize the configuration page
	 * 
	 * @param array $data Data to be processed
	 * 		- command: Command to be executed is string
	 * 		- request: Request data is array
	 * @return void
	 */
	public function doConfigPageInit(array $data)
	{
		// Force flush all output buffers, need for AJAX
		if (!empty($data) && is_array($data))
		{
			$command = $data['command'] ?? '';
			$request = $data['request'] ?? array();

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
	}

	private function activeFlush()
	{
		ob_implicit_flush(true);
		while (ob_get_level() > 0)
		{
			ob_end_flush();
		}
	}

	public function myShowPage(array &$pagedata, array $data) { }


	public function getRightNav(array $data) { return ""; }

	public function getActionBar(array $data) { return ""; }

	
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
				if (empty($id) || !is_numeric($id))
				{
					out(sprintf(_("❌ Install Firmware Error, ID '%s' is invalid!"), $id));
					return false;
				}
				$product_db = $this->epm->packagesdb->getProductByID($id);
				$this->install_firmware($product_db);
				break;

			case "fw_uninstall":
				if (empty($id) || !is_numeric($id))
				{
					out(sprintf(_("❌ Remove Firmware Error, ID '%s' is invalid!"), $id));
					return false;
				}
				$product_db = $this->epm->packagesdb->getProductByID($id);
				$this->remove_firmware($product_db);
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
					$model = $this->epm->packagesdb->getModelByID($id);
					if (! $model->setEnabled($value))
					{
						return array("status" => false, "message" => _("Error in the process of enabling the model!"));
					}

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
					$brand = $this->epm->packagesdb->getBrandByID($id);
					if (! $brand->setHidden($value))
					{
						return array("status" => false, "message" => _("Error in the process of hiding the brand!"));
					}
					unset($brand);
					break;

				case "producto":
					$product = $this->epm->packagesdb->getProductByID($id);
					if (! $product->setHidden($value))
					{
						return array("status" => false, "message" => _("Error in the process of hiding the product!"));
					}
					break;

				case "modelo":
					$model = $this->epm->packagesdb->getModelByID($id);
					if (! $model->setHidden($value))
					{
						return array("status" => false, "message" => _("Error in the process of hiding the model!"));
					}
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
		foreach($this->epm->packagesdb->getBrands() as $brand)
		{
			// if ($brand->isHidden()) { continue; }
			$brand_item = [
				'id' 		=> $brand->getID(),
				'name' 		=> $brand->getName(),
				'directory' => $brand->getDirectory(),
				'installed' => $brand->getInstalled(),
				'hidden' 	=> $brand->getHidden(),
				'count' 	=> count($row_out) + 1, // Add +1 to not start at 0
				'products' 	=> []
			];
			foreach($brand->getProducts() as $product)
			{
				// if ($product->isHidden()) { continue; }
				$product_item = [
					'id' 		 => $product->getID(),
					'brand'		 => $product->getBrandId(),
					'long_name'  => $product->getName(),
					'short_name' => $product->getShortName(),
					'hidden'	 => $product->getHidden(),
					'count'		 => count($brand_item['products']) + 1, // Add +1 to not start at 0
					'models'	 => []
				];

				foreach($product->getModels() as $model)
				{
					$model_item = [
						'id' 		 => $model->getID(),
						'brand' 	 => $model->getFamily()->getBrandId(),
						'model' 	 => $model->getModel(),
						'product_id' => $model->getFamilyId(),
						'enabled' 	 => $model->getEnabled(),
						'hidden' 	 => $model->getHidden(),
						'count' 	 => count($product_item['models']) + 1, // Add +1 to not start at 0
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

		foreach($this->epm->packagesdb->getBrands(true, 'name') as $brand)
		{
			if ($brand->getHidden()) { continue; }
			$row_mod = $this->brand_update_check($brand->getDirectory());
			$brand_item = [
				'id' 					=> $brand->getID(),
				'name' 					=> $brand->getName(),
				'directory' 			=> $brand->getDirectory(),
				'installed' 			=> $brand->getInstalled(),
				'local' 				=> $brand->getLocal(),
				'hidden' 				=> $brand->getHidden(),
				'count'					=> count($row_out) + 1, // Add +1 to not start at 0
				'cfg_ver_datetime' 		=> $brand->getLastModified(),
				'cfg_ver_datetime_txt' 	=> date("c", $brand->getLastModified()),
				'update_vers'			=> $row_mod['update_vers']		?? $brand->getLastModified(),
				'update_vers_txt'		=> $row_mod['update_vers_txt']	?? date("c", $brand->getLastModified()),
				'update'				=> $row_mod['update']			?? false,
				'products'				=> []
			];

			foreach($brand->getProducts() as $product)
			{
				if ($product->getHidden()) { continue; }
				$product_item = [
					'id' 				=> $product->getID(),
					'brand'				=> $product->getBrandId(),
					'long_name' 		=> $product->getName(),
					'short_name' 		=> $product->getShortName(),
					'cfg_dir' 			=> $product->getDirectory(),
					'cfg_ver' 			=> $product->getLastModified(),
					'hidden' 			=> $product->getHidden(),
					'firmware_vers' 	=> $product->getFirmwareVer(),
					'firmware_files' 	=> $product->getFirmwareFiles(),
					'count' 			=> count($brand_item['products']) + 1, // Add +1 to not start at 0
					'update_fw' 		=> $product->getFirmwareVer() ? true : false,
					'update_vers_fw' 	=> $product->getFirmwareVer() ? $this->firmware_update_check($product->getID()) : "",
					'fw_type' 			=> $this->firmware_local_check($product->getID()),
					'models' 			=> []
				];
				foreach($product->getModels() as $model)
				{
					if ($model->getHidden()) { continue; }
					$model_item = [
						'id' 				=> $model->getID(),
						'brand' 			=> $model->getFamily()->getBrandId(),
						'model' 			=> $model->getModel(),
						'max_lines' 		=> $model->getMaxLines(),
						'product_id' 		=> $model->getFamilyId(),
						'enabled' 			=> $model->getEnabled(),
						'hidden' 			=> $model->getHidden(),
						'count' 			=> count($product_item['models']) + 1, // Add +1 to not start at 0
						'enabled_checked' 	=> $model->getEnabled() ? 'checked' : '',
					];

					$product_item['models'][] = $model_item;
				}
				$brand_item['products'][] = $product_item;
			}
			$row_out[] = $brand_item;
		}
		return $row_out;
		
		// $row_out 	= [];
		// $brand_list = $this->epm->get_hw_brand_list(true, "name");
		// //FIX: https://github.com/FreePBX-ContributedModules/endpointman/commit/2ad929d0b38f05c9da1b847426a4094c3314be3b
	
		// foreach ($brand_list as $i => $brand)
		// {
		// 	if ($brand['hidden'] == 1)
		// 	{
		// 		continue;
		// 	}

		// 	$brand['count'] 			   = count($row_out);
		// 	$brand['cfg_ver_datetime']	   = $brand['cfg_ver'];
		// 	$brand['cfg_ver_datetime_txt'] = date("c", $brand['cfg_ver']);
	
		// 	$row_mod = $this->brand_update_check($brand['directory']);
			
		// 	$brand['update_vers']	  = $row_mod['update_vers']		?? $brand['cfg_ver_datetime'];
		// 	$brand['update_vers_txt'] = $row_mod['update_vers_txt'] ?? $brand['cfg_ver_datetime_txt'];
		// 	$brand['update'] 		  = $row_mod['update']			?? false;
		// 	$brand['products'] 		  = [];

		// 	$product_list = $this->epm->get_hw_product_list($brand['id'], true);
		// 	foreach ($product_list as $j => $product)
		// 	{
		// 		if ($product['hidden'] == 1)
		// 		{
		// 			continue;
		// 		}

		// 		$product['count'] 		  = count($brand['products']);
		// 		$product['firmware_vers'] = $product['firmware_vers'] ?? 0;

		// 		if ($product['firmware_vers'] > 0)
		// 		{
		// 			$product['update_fw'] = 1;
		// 			$product['update_vers_fw'] = $this->firmware_update_check($product['id']);
		// 		}
		// 		else
		// 		{
		// 			$product['update_fw'] = 0;
		// 			$product['update_vers_fw'] = "";
		// 		}
	
		// 		$product['fw_type'] = $this->firmware_local_check($product['id']);
		// 		$product['models']  = [];
					
		// 		$model_list = $this->epm->get_hw_model_list($product['id'], true);
		// 		foreach ($model_list as $k => $model)
		// 		{
		// 			$model['count'] 		  = count($product['models']);
		// 			$model['enabled_checked'] = $model['enabled'] ? 'checked' : '';
	
		// 			unset($model['template_list'], $model['template_data']);
	
		// 			$product['models'][] = $model;
		// 		}
		// 		$brand['products'][] = $product;
		// 	}
		// 	$row_out[] = $brand;
		// }
	
		// return $row_out;
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


        if (!$this->epm->getConfig('use_repo'))
		{
			$url_status	= $this->system->buildUrl($this->epm->URL_UPDATE, "update_status");
			dbug($url_status);

			$out("⚡ Checking status server...", false);
			try
			{
				if (($contents = file_get_contents($url_status)) === false)
				{
					$out(" ❌");
					$outputError('check_status_server', _("❌ The stream could not be opened: the requested url was not found or there was a problem with the request."));
					$contents = -2;
				}
			}
			catch (\Exception $e)
			{
				$out(" ❌");
				$outputError('check_status_server', "❌ ".$e->getMessage());
				$contents = -1;
			}
			
			if ($contents != '0')
			{
				if (in_array($contents, [-1, -2]))
				{
					$outputError('remote_server', _("❌ The Remote server did not return any status information, Please try again later!"));
				}
				else
				{
					$out ("❌");
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
					

					$brand_db = $this->epm->packagesdb->getBrandByID($brand->getBrandID());
					if ($brand_db->isExistID())
					{
						$outputError('brand_update_id_exist', sprintf(_("✅ Brand '%s' already exists in the database."), $brand->getName()));
					}
					else
					{
						try
						{
							$data_new = array(
								'id'		=> $brand->getBrandID(),
								'name'		=> $brand->getName(),
								'directory'	=> $brand->getDirectory(),
								'cfg_ver'	=> $brand->getLastModifiedMax(),
							);
							$brand_db->create($data_new, false, false);
							$outputError('brand_update_check_add_brand', sprintf(_("✅ Brand '%s' added to the database."), $brand->getName()));
							unset($data_new);
						}
						catch (\Exception $e)
						{
							$outputError('brand_update_check_add_brand', sprintf(_("❌ Unable to add brand '%s', error: %s"), $brand->getName(), $e->getMessage()));
							continue;
						}
					}

					// Get the maximum last modified date from the family list and the brand
					$version[$brand_rawname] = $brand->getLastModifiedMax();

					// Update the OUIs for the brand
					$out( sprintf(_("⚡ Update OUIs for brand '%s' ◾◾◾"), $brand->getName()), false);
					if ($brand->countOUI() > 0 && $brand->isSetBrandID())
					{
						foreach ($brand->getOUI() as $oui)
						{
							if (empty($oui)) { continue; }

							$out("◾", false);
							$brand_db->setOUI($oui);
						}
					}
					$out(_(" ✔"));
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
				
				$brand_db = $this->epm->packagesdb->getBrandByID($brand->getBrandID());
				if ($brand_db->isExistID())
				{
					$brand_db->setLocal(true);
					$brand_db->setInstalled(true);
					$brand_db->setLastModified($brand->getLastModified());
				}
				else
				{
					try
					{
						$data_new = array(
							'id'		=> $brand->getBrandID(),
							'name'		=> $brand->getName(),
							'directory'	=> $brand->getDirectory(),
							'cfg_ver'	=> $brand->getLastModified(),
							'local' 	=> true,
							'installed' => true,
						);
						$brand_db->create($data_new, false, false);
						$outputError('brand_update_check_add_brand', sprintf(_("✅ Brand '%s' added to the database."), $brand->getName()));
						unset($data_new);
					}
					catch (\Exception $e)
					{
						$outputError('brand_update_check_add_brand', sprintf(_("❌ Unable to add brand '%s', error: %s"), $brand->getName(), $e->getMessage()));
						continue;
					}
				}
				
				// Update the OUIs for the brand
				if ($brand->countOUI() > 0 && $brand->isSetBrandID())
				{
					foreach ($brand->getOUI() as $oui)
					{
						if (empty($oui)) { continue; }
						$brand_db->setOUI($oui);
					}
				}

				$last_mod = "";
				$brand_familys = $brand->getFamilyList();

				foreach ($brand_familys as &$family)
				{
					$product_db 		   = $brand_db->getProduct($family->getFamilyID());
					$last_mod 			   = max($last_mod, $family->getLastModified());
					$family_dir 		   = $family->getDirectory();
					$family_file_json_path = $this->system->buildPath($local_endpoint, $brand_dir, $family_dir, "family_data.json");	// $local_family_data
					
					//If is necessary return more info in the exception (set the second parameter to false)
					if (! $family->importJSON($family_file_json_path, true))
					{
						$outputError('family_update_check_json', sprintf(_("Error: Unable to import JSON file for family %s"), $family->getShortName()));
						continue;
					}

					if ($product_db->isExistID())
					{
						$product_db->setName($family->getName());
						$product_db->setShortName($family->getShortName());
						$product_db->setLastModified($family->getLastModified());
						$product_db->setConfigFiles($family->getConfigurationFiles());
					}
					else
					{
						try
						{
							$data_new = array(
								'id'			=> $family->getFamilyID(),
								'brand'			=> $brand->getBrandID(),
								'short_name'	=> $family->getShortName(),
								'long_name'		=> $family->getName(),
								'cfg_dir'		=> $family->getDirectory(),
								'cfg_ver'		=> $family->getLastModified(),
								'config_files'	=> $family->getConfigurationFiles(),
							);
							$product_db->create($data_new, false, false);
							$outputError('family_update_check_add_product', sprintf(_("✅ Product '%s' added to the database."), $family->getShortName()));
							unset($data_new);
						}
						catch (\Exception $e)
						{
							$outputError('family_update_check_add_product', sprintf(_("❌ Unable to add product '%s', error: %s"), $family->getShortName(), $e->getMessage()));
							continue;
						}
					}

					$models = $family->getModelList();
					foreach ($models as &$model)
					{
						$model_id = $model->getModelId();

						$model_db = $product_db->getModel($model_id);
						if ($model_db->isExistID())
						{
							$model_db->setModel($model->getModel());
							$model_db->setMaxLines($model->getMaxLines());
							$model_db->set_TemplateList($model->getConfigurationFiles());
							try
							{
								$model_db->setTemplateData($model->importTemplates(12, null, false));
							}
							catch (\Exception $e)
							{
								$outputError('model_update_check_template_data', sprintf(_("❌ Unable to update model '%s', error: %s"), $model->getModel(), $e->getMessage()));
							}
						}
						else
						{
							try
							{
								$data_model = array(
									'id'			=> $model->getModelId(),
									'brand' 		=> $brand->getBrandID(),
									'product_id' 	=> $family->getFamilyID(),
									'enabled' 		=> false,
									'hidden' 		=> false,
									'model' 		=> $model->getModel(),
									'max_lines' 	=> $model->getMaxLines(),
									'template_list' => $model->getConfigurationFiles(),
									'template_data' => $model->importTemplates(12, null, false),
								);
								$model_db->create($data_model, false, false);
							}
							catch (\Exception $e)
							{
								$outputError('model_update_check_add_model', sprintf(_("❌ Unable to add model 11 '%s', error: %s"), $model->getModel(), $e->getMessage()));
								continue;
							}
						}
					}

					//Phone Models Move Here
					// foreach ($this->epm->get_hw_model_list($family_id, true) as $model)
					foreach ($product_db->getModelList() as &$model)
					{
						$model_id   = $model->getID();
						$model_name = $model->getModel();

						
						if (! $family->isModelExist($model_name))
						{
							if ($echomsg == true )
							{
								outn(sprintf(_("Moving/Removing Model '%s' not present in JSON file......"), $model_name));
							}

							// Remove Brand Product Model
							if (! $model->delete())
							{
								if ($echomsg == true ) { out(_("Error!")); }
								$outputError('delete_model', sprintf(_("Error: System Error in Delete Model [%s] Function, Load Failure!"), $model_name));
							}

							// Sync MAC Brand By Model
							// TODO: Move sync_mac_brand_by_model to ProvisionerModelDB class
							if (! $this->epm->sync_mac_brand_by_model($model_name, $model_id))
							{
								if ($echomsg == true ) { out(_("Error!")); }
								$outputError('sync_mac_brand_by_model', sprintf(_("Error: System Error in Sync MAC Brand By Model [%s] Function, Load Failure!"), $model_name));
							}
							if ($echomsg == true )
							{
								out (_("Done!"));
							}
						}
					}

					/* DONT DO THIS YET
					if ($family->isFirmwareRequired())
					{
						out(_("Firmware Requirment Detected, Initiating Firmware Installation..."));
						$this->install_firmware($product_db);
					}
					*/
				}
			}
        }
    }


	/**
	 * Downloads and installs/updates a brand.
	 *
	 * @param string $id The ID of the brand to download.
	 * @return bool Returns true if the brand was successfully installed/updated, false otherwise.
	 */
    public function download_brand($id)
	{
		if (!is_numeric($id) || $id < 1)
		{
			out(sprintf(_("❌ Invalid Brand ID '%s'!"), $id));
			return false;
		}
		elseif ($this->epm->getConfig('use_repo'))
		{
			out(_("❌ Installing brands is disabled while in repo mode!"));
			return false;
		}

		$brand_db = $this->epm->packagesdb->getBrandByID($id);
		if (! $brand_db->isExistID())
		{
			out(sprintf(_("❌ Brand with id '%s' not found in the database!"), $id));
			return false;
		}

		out(sprintf(_("⚡ Install/Update Brand '%s' ..."), $brand_db->getName()));
		if (empty($brand_db->getDirectory()))
		{
			out(_("❌ Brand Directory Is Not Set!"));
			return false;
		}

		$brand = $this->epm->packages->getBrandByDirectory($brand_db->getDirectory());
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

			$brand_db = $this->epm->packagesdb->getBrandByID($brand->getBrandID());
			if ($brand_db->isExistID())
			{
				outn( sprintf(_("⚡ Updating Brand '%s' ..."), $brand->getName()));
				$brand_db->setLocal($remote);
				$brand_db->setInstalled(true);
				$brand_db->setLastModified($brand->getLastModified());
			}
			else
			{
				outn( sprintf(_("⚡ Inserting Brand '%s' ..."), $brand->getName()));
				try
				{
					$data_new = array(
						'id'		=> $brand->getBrandID(),
						'name'		=> $brand->getName(),
						'directory'	=> $brand->getDirectory(),
						'cfg_ver'	=> $brand->getLastModified(),
						'local' 	=> $remote,
						'installed' => true,
					);
					$brand_db->create($data_new, false, false);
					unset($data_new);
				}
				catch (\Exception $e)
				{
					out(" ❌");
					out(sprintf(_("❌ Unable to add brand '%s', error: %s"), $brand->getName(), $e->getMessage()));
					return false;
				}
			}
			out(" ✔");

			outn(_("⚡ Updating OUI list in DB ..."));
			foreach ($brand->getOUI() as $oui)
			{
				if(empty($oui)) { continue; }
				$brand_db->setOUI($oui);
			}
			out(" ✔");

			foreach ($brand->getFamilyList() as &$family)
			{
				$product_db = $brand_db->getProduct($family->getFamilyId());

				out(_("⚡ Updating Family Lines ..."));
				if ($product_db->isExistID())
				{
					outn( sprintf(_("⚡ - Updating Family '%s'..."), $family->getShortName()));
					$product_db->setName($family->getName());
					$product_db->setShortName($family->getShortName());
					$product_db->setLastModified($family->getLastModified());
					$product_db->setConfigFiles($family->getConfigurationFiles());
				}
				else
				{
					outn( sprintf(_("⚡ - Inserting Family '%s'..."), $family->getShortName()));
					try
					{
						$data_new = array(
							'id'			=> $family->getFamilyId(),
							'brand'			=> $family->getBrandID(),
							'short_name'	=> $family->getShortName(),
							'long_name'		=> $family->getName(),
							'cfg_dir'		=> $family->getDirectory(),
							'cfg_ver'		=> $family->getLastModified(),
							'config_files'	=> $family->getConfigurationFiles(),
							'hidden'		=> 0,
						);
						$product_db->create($data_new, false, false);
						unset($data_new);
					}
					catch (\Exception $e)
					{
						out(" ❌");
						out(sprintf(_("❌ Unable to add product '%s', error: %s"), $family->getShortName(), $e->getMessage()));
						continue;
					}
				}
				out(" ✔");


				if ($family->countModels() > 0)
				{
					out(_("⚡ - Updating Model Lines ... "));
				}
				foreach ($family->getModelList() as &$model)
				{
					//TODO: Pending the migrate of the class ProvisionerModelDB
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

					$model_db = $product_db->getModel($model->getModelId());
					if ($model_db->isExistID())
					{
						outn( sprintf(_("⚡ - Updating Model '%s' ..."), $model->getModel()));
						$model_db->setModel($model->getModel());
						$model_db->setMaxLines($model->getMaxLines());
						$model_db->setTemplateList($model->getTemplateList());
						try
						{
							$model_db->setTemplateData($model->importTemplates(12, null, false));
							out (" ✔");
						}
						catch (\Exception $e)
						{
							out(" ❌");
							out(sprintf(_("❌ Unable to update model '%s', error: %s"), $model->getModel(), $e->getMessage()));
						}
					}
					else
					{
						outn( sprintf(_("⚡ - Inserting Model '%s' ..."), $model->getModel()));
						try
						{
							$data_model = array(
								'id' 		 	=> $model->getModelId(),
								'brand'		 	=> $model->getBrandID(),
								'model'		 	=> $model->getModel(),
								'max_lines'		=> $model->getMaxLines(),
								'template_list'	=> $model->getTemplateList(),
								'template_data' => $model->importTemplates(12, null, false),
								'product_id' 	=> $model->getFamilyId(),
								'enabled'	 	=> false,
								'hidden'		=> false,
							);
							$model_db->create($data_model, false, false);
							unset($data_model);
							out (" ✔");
						}
						catch (\Exception $e)
						{
							out(" ❌");
							out(sprintf(_("❌ Unable to add model '%s', error: %s"), $model->getModel(), $e->getMessage()));
							continue;
						}
					}
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

				/* DONT DO THIS YET
				if ($family->isFirmwareRequired() && $remote)
				{
					out(_("Firmware Requirment Detected, Initiating Firmware Installation..."));
					$this->install_firmware($product_db);
				}
				*/
			}
			out(_("✅ All Done!"));
			//END Updating Family Lines
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
		if (!is_numeric($id) || $id < 1)
		{
			out(sprintf(_("❌ Uninstall Brand Error, Brand ID '%s' is invalid!"), $id));
			return false;
		}
		elseif ($this->epm->getConfig('use_repo') && !$force)
		{
			out(_("❌ Uninstalling brands is disabled while in repo mode!"));
			return false;
        }

		$brand_db = $this->epm->packagesdb->getBrandByID($id);
		$brand 	  = $this->epm->packages->getBrandByDirectory($brand_db->getDirectory());
		if (! $brand_db->isExistID())
		{
			out(sprintf(_("❌ Uninstall Brand Error, Brand ID '%s' not found in the database!"), $id));
			return false;
		}
		else if(empty($brand))
		{
			out(_("❌ Failed to get data from the json file!"));
			return false;
		}

		out(sprintf(_("⚡ Uninstalla Brand '%s' ..."), $brand_db->getName()));
        if (!$this->epm->getConfig('use_repo') && !$force)
		{
			if (empty($brand_db->getDirectory()))
			{
				out(_("❌ Brand Directory Is Not Set!"));
				return false;
			}
		
			foreach ($brand_db->getProducts() as &$product)
			{
				if (!$product->isSetFirmwareVer())
				{
					continue;
				}
				$this->remove_firmware($product);
			}
			$brand->uninstall();
		}

		try
		{
			$brand_db->delete();
		}
		catch (\Exception $e)
		{
			out(sprintf(_("❌ %s"), $e->getMessage()));
			return false;
		}

		out(_("✅ Uninstall Brand Success!"));
		return true;
    }


	//TODO: Pending to testing
	/**
	 * Installs firmware for a specific product.
	 *
	 * @param ProvisionerFamilyDB|null $product_db The product object to install the firmware for.
	 * @return bool Returns true if the firmware is successfully installed, false otherwise.
	 */
    public function install_firmware(?ProvisionerFamilyDB $product_db = null)
	{
		if (empty($product_db))
		{
			out(_("❌ Install Firmware Error, Product Object is empty!"));
			return false;
		}
		else if (! $product_db->isExistID())
		{
			out(_("❌ Install Firmware Error, Product ID not found in the database!"));
			return false;
		}

		$product_json = $this->epm->packages->getProductByProductID($product_db->getID());
		if (empty($product_json))
		{
			out(_("❌ Install Firmware Error, Product JSON is empty!"));
			return false;
		}

		out(sprintf(_("⚡ Installa frimware for Product '%s' ..."), $product_db->getName()));

		$firmware_ver = $product_json->getFirmwareVer();
		$firmware_pkg = $product_json->getFirmwarePkg();
		$tftp_path	  = $this->epm->getConfig('config_location');

        if (empty($firmware_ver))
		{
			out(_("❌ The firmware version is Null or blank!"));
        	return false;
        }
        else if ((empty($firmware_pkg)) OR ($firmware_pkg == "NULL"))
		{
			out(_("❌ The firmware package is Null or blank!"));
        	return false;
        }

        if ($firmware_ver > $product_db->getFirmwareVer())
		{
			out(sprintf(_("⚡ New Firmware Version Detected '%s'!"), $firmware_ver));
			if ($product_json->isMD5SumFirmwarePkgValid())
			{
				out(_("✅ Firmware file is already downloaded, skipping download!"));
			}
			else
			{
				out(_("⚡ Downloading firmware..."));
				try
				{
					if (! $product_json->downloadFirmwarePkg(true, false))
					{
						out(_("❌ Error Downloading Firmware!"));
						return false;
					}
				}
				catch (\Exception $e)
				{
					out(" ❌" . $e->getMessage());
					return false;
				}
				outn(_("⚡ Checking MD5sum of Package thas was downloaded ..."));
				if (! $product_json->isMD5SumFirmwarePkgValid())
				{
					out(" ❌");
					out(_("❌ Firmware MD5 for the package '%s' is invalid!"), $firmware_pkg);
					return false;
				}
				out(" ✔");
			}

			out(_("⚡ Installing Firmware..."));
			$copy_ok = true;
			$firmware_files = array();
			try
			{
				$copy_ok = $product_json->installFirmwarePkg($tftp_path, false, $firmware_files);
			}
			catch (\Exception $e)
			{
				$copy_ok = false;
				out(" ❌" . $e->getMessage());
			}
			finally
			{
				// Important: Update the firmware version in the database is necessary for the process to remove the firmware
				$product_db->setFirmwareVer($firmware_ver);
				$product_db->setFirmwareFiles(array_keys($firmware_files));
			}

			foreach ($firmware_files as $file => $status)
			{
				if ($status === false)
				{
					out(sprintf(_("❌ Failed To Copy %s!"), $file));
					$copy_ok = false;
				}
				else
				{
					if ($this->epm->getConfig('debug'))
					{
						out(sprintf(_("👁‍🗨 Copied '%s' to '%s'!"), $file, $tftp_path));
					}
				}
			}

			if (! $copy_ok)
			{
				out(_("❌ Copy Error Detected! Aborting Install!"));
				$this->remove_firmware($product_db);
				out(_("👁‍🗨Info: Please Check Directory/Permissions!"));

			}
			else
			{
				out(_("✅ Firmware Installed Successfully!"));
			}
        }
		else
		{
			out(_("✅ Your Firmware is already up to date!"));
        }
		return true;
    }

	//TODO: Pending to testing
	/**
	 * Removes firmware files associated with a specific ID.
	 *
	 * @param ProvisionerFamilyDB|null $product_db The product object to remove the firmware for.
	 * @return bool Returns true if the firmware is successfully removed, false otherwise.
	 */
    public function remove_firmware(?ProvisionerFamilyDB $product_db = null)
	{
		if (empty($product_db))
		{
			out(_("❌ Remove Firmware Error, Product Object is empty!"));
			return false;
		}
		if (! $product_db->isExistID())
		{
			out(_("❌ Remove Firmware Error, Product ID not found in the database!"));
			return false;
		}

		out(sprintf(_("⚡ Uninstall Firmware for Product '%s' ..."), $product_db->getName()));
		if ($product_db->countFirmwareFiles() == 0)
		{
			out(_("💥Skipping, the brand does not have any firmware files."));
			return true;
		}

		$tftp_path = $this->epm->getConfig('config_location');
		if (empty($tftp_path))
		{
			out(_("⭕ Skipping, Config Location tftp is not set!"));
		}
		elseif (!file_exists($tftp_path))
		{
			out(_("⭕ Skipping, Location tftp does not exist!"));
		}
		else
		{
			foreach ($product_db->getFirmwareFiles() as $file)
			{
				if (empty(trim($file)) || !is_string($file))
				{
					continue;
				}
				$file_path = $this->epm->brindPath($tftp_path, $file);
				if (file_exists($file_path) &&  is_file($file_path))
				{
					if (! is_writable($file_path))
					{
						out(sprintf(_("❌ Unable to remove firmware file '%s' due to permissions!"), $file));
						continue;
					}

					// Remove files from tftp directory
					if (!unlink($file_path))
					{
						out(sprintf(_("❌ Unable to remove firmware file '%'s!"), $file));
					}
				}
			}
		}

		$product_db->setFirmwareVer('');
		$product_db->setFirmwareFiles(null);

		out(_("✅ Firmware Removed Successfully!"));
		return true;
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
		$product_db   = $this->epm->packagesdb->getProductByID($id);

		if (empty($product_json) || !$product_db->isExistID())
		{
			// Return false if the product is not found or the configuration drive is unknown.
			return '';
		}

		$fw_ver_json = $product_json->getFirmwareVer();
		if ($product_db->getFirmwareVer() < $fw_ver_json)
		{
			return $fw_ver_json;
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

		$product_db   = $this->epm->packagesdb->getProductByID($id);
		$product_json = $this->epm->packages->getProductByProductID($id);

		if (! $product_db->isExistID() || empty($product_json))
		{
			// Is not found or the configuration drive is unknown.
			return "nothing";
		}

		// The product not firmware available to install.
		if (empty($product_json->getFirmwareVer()))
		{
			return 'nothing';
		}

		// The firmware is already installed, accion allowed is remove.
		if ($product_db->isSetFirmwareVer())
		{
			return "remove";
		}

		// The firmware is not installed yet, accion allowed is install.
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