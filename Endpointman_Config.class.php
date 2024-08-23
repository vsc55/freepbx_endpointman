<?php
/**
 * Endpoint Manager Object Module - Sec Config
 *
 * @author Javier Pastor
 * @license MPL / GPLv2 / LGPL
 * @package Provisioner
 */

namespace FreePBX\modules;

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
		$this->system 	 = $epm->system;

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


	/**
     * Get info models by product id selected.
     * @param int $id_product product ID
	 * @param bool $show_all True return all, False return hidden = 0
     * @return array
     */
	public function epm_config_hardware_get_list_models($id_product=NULL, $show_all = true, $byorder = "model")
	{
		if(! is_numeric($id_product)) { throw new \Exception( _("ID Producto not is number")." (".$id_product.")"); }
		if($show_all == true) 	{ $sql = 'SELECT * FROM endpointman_model_list WHERE product_id = '.$id_product.' ORDER BY '.$byorder.' ASC'; }
		else 					{ $sql = 'SELECT * FROM endpointman_model_list WHERE hidden = 0 AND product_id = '.$id_product.' ORDER BY '.$byorder.' ASC'; }
		$result = sql($sql, 'getAll', \PDO::FETCH_ASSOC);
		return $result;
	}

	/**
     * Get info product by brand id selected.
     * @param int $id_brand brand ID
	 * @param bool $show_all True return all, FAlse return hidde = 0
     * @return array
     */
	public function epm_config_hardware_get_list_product($id_brand=NULL, $show_all = true, $byorder = "long_name")
	{
		if(! is_numeric($id_brand)) { throw new \Exception(_("ID Brand not is numbre")." (".$id_brand.")"); }
		if ($show_all == true) 	{ $sql = 'SELECT * FROM endpointman_product_list WHERE brand = '.$id_brand.' ORDER BY '.$byorder.' ASC'; }
		else 					{ $sql = 'SELECT * FROM endpointman_product_list WHERE hidden = 0 AND brand = '.$id_brand.' ORDER BY '.$byorder.' ASC'; }
		$result = sql($sql, 'getAll', \PDO::FETCH_ASSOC);
		return $result;
	}

	/**
     * Get info all brands.
	 * @param bool $show_all True return all, False return hidde = 0
     * @return array
     */
	public function epm_config_hardware_get_list_brand($show_all = true, $byorder = "id") {
		if ($show_all == true) 	{ $sql = "SELECT * from endpointman_brand_list WHERE id > 0 ORDER BY " . $byorder . " ASC "; }
		else 					{ $sql = "SELECT * from endpointman_brand_list WHERE id > 0 AND hidden = 0 ORDER BY " . $byorder . " ASC "; }
		$result = sql($sql, 'getAll', \PDO::FETCH_ASSOC);
		return $result;
	}


	/**** FUNCIONES SEC MODULO "epm_config\editor" ****/
	/**
     * Get info all brdans, prodics, models.
     * @return array
     */
	 /*
	 SE DESACTIVA A VERS SI NO SE USA EN NINGUN SITIO.
	public function epm_config_editor_hardware_get_list_all ()
	{
		$row_out = array();
		$i = 0;
		foreach ($this->epm_config_hardware_get_list_brand(true, "name") as $row)
		{
			$row_out[$i] = $row;
			$row_out[$i]['count'] = $i;
			if ($row['installed'])
			{
				$j = 0;
				foreach ($this->epm_config_hardware_get_list_product($row['id'], true) as $row2)
				{
					$row_out[$i]['products'][$j] = $row2;
					$k = 0;
					foreach ($this->epm_config_hardware_get_list_models($row2['id'], true) as $row3) {
						$row_out[$i]['products'][$j]['models'][$k] = $row3;
						$k++;
					}
					$j++;
				}
			}
			$i++;
		}
		return $row_out;
	}
	*/
	/*** END SEC FUNCTIONS ***/






	/**** FUNCIONES SEC MODULO "epm_config\manager" ****/
	private function epm_config_manager_check_for_updates ()
	{
		out("<h3>"._("Update data...")."</h3>");
		$this->update_check(true);
		out (_("All Done!"));
	}

	private function epm_config_manager_brand()
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
			case "brand_install":
			case "brand_update":
				$this->download_brand($dget['id']);
				break;

			case "brand_uninstall":
				$this->remove_brand($dget['id']);
				break;

			default:
				out (_("Error: Command not found!")." [" . $dget['command'] . "]");
		}
		$this->update_check();
		unset ($dget);

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
		$row_out = array();

		$i = 0;
		$brand_list = $this->epm_config_hardware_get_list_brand(true, "name");
		foreach ($brand_list as $row)
		{
			//$row_out[$i] = $row;
			$row_out[$i]['id'] = $row['id'];
			$row_out[$i]['name'] = $row['name'];
			$row_out[$i]['directory'] = $row['directory'];
			$row_out[$i]['installed'] = $row['installed'];
			$row_out[$i]['hidden'] = $row['hidden'];
			$row_out[$i]['count'] = $i;
			$row_out[$i]['products'] = array();
			if ($row['hidden'] == 1)
			{
				$i++;
				continue;
			}

			$j = 0;
			$product_list = $this->epm_config_hardware_get_list_product($row['id'], true);
			foreach($product_list as $row2) {
				//$row_out[$i]['products'][$j] = $row2;
				$row_out[$i]['products'][$j]['id'] = $row2['id'];
				$row_out[$i]['products'][$j]['brand'] = $row2['brand'];
				$row_out[$i]['products'][$j]['long_name'] = $row2['long_name'];
				$row_out[$i]['products'][$j]['short_name'] = $row2['short_name'];
				$row_out[$i]['products'][$j]['hidden'] = $row2['hidden'];
				$row_out[$i]['products'][$j]['count'] = $j;
				$row_out[$i]['products'][$j]['models'] = array();
				if ($row2['hidden'] == 1)
				{
					$j++;
					continue;
				}

				$k = 0;
				$model_list = $this->epm_config_hardware_get_list_models($row2['id'], true);
				foreach($model_list as $row3)
				{
					//$row_out[$i]['products'][$j]['models'][$k] = $row3;
					$row_out[$i]['products'][$j]['models'][$k]['id'] = $row3['id'];
					$row_out[$i]['products'][$j]['models'][$k]['brand'] = $row3['brand'];
					$row_out[$i]['products'][$j]['models'][$k]['model'] = $row3['model'];
					$row_out[$i]['products'][$j]['models'][$k]['product_id'] = $row3['product_id'];
					$row_out[$i]['products'][$j]['models'][$k]['enabled'] = $row3['enabled'];
					$row_out[$i]['products'][$j]['models'][$k]['hidden'] = $row3['hidden'];
					$row_out[$i]['products'][$j]['models'][$k]['count'] = $k;
					$k++;
				}
				$j++;
			}
			$i++;
		}
		//echo "<textarea>" . print_r($row_out, true)  . "</textarea>";
		return $row_out;
	}


	//TODO: PENDIENTE ACTUALIZAR Y ELIMINAR DATOS NO NECESARIOS (TEMPLATES)
	//http://pbx.cerebelum.lan/admin/ajax.php?module=endpointman&module_sec=epm_config&module_tab=manager&command=list_all_brand
	public function epm_config_manager_hardware_get_list_all()
	{
		$row_out = array();
		$i = 0;
		$brand_list = $this->epm_config_hardware_get_list_brand(true, "name");
		//FIX: https://github.com/FreePBX-ContributedModules/endpointman/commit/2ad929d0b38f05c9da1b847426a4094c3314be3b

		foreach ($brand_list as $row)
		{
			$row_out[$i] = $row;
			$row_out[$i]['count'] = $i;
			$row_out[$i]['cfg_ver_datetime'] = $row['cfg_ver'];
			$row_out[$i]['cfg_ver_datetime_txt'] = date("c",$row['cfg_ver']);

			$row_mod = $this->brand_update_check($row['directory']);
			$row_out[$i]['update'] = $row_mod['update'];
			if(isset($row_mod['update_vers'])) {
				$row_out[$i]['update_vers'] = $row_mod['update_vers'];
				$row_out[$i]['update_vers_txt'] = date("c",$row_mod['update_vers']);
			}

			if (! isset($row_out[$i]['update'])) 			{ $row_out[$i]['update'] = ""; }
			if (! isset($row_out[$i]['update_vers'])) 		{ $row_out[$i]['update_vers'] = $row_out[$i]['cfg_ver_datetime']; }
			if (! isset($row_out[$i]['update_vers_txt'])) 	{ $row_out[$i]['update_vers_txt'] = $row_out[$i]['cfg_ver_datetime_txt']; }

			if ($row['hidden'] == 1)
			{
				$i++;
				continue;
			}


			$j = 0;
			$product_list = $this->epm_config_hardware_get_list_product($row['id'], true);
			foreach($product_list as $row2) {
				$row_out[$i]['products'][$j] = $row2;
				if((array_key_exists('firmware_vers', $row2)) AND ($row2['firmware_vers'] > 0)) {
					$temp = $this->firmware_update_check($row2['id']);
					$row_out[$i]['products'][$j]['update_fw'] = 1;
					$row_out[$i]['products'][$j]['update_vers_fw'] = $temp['data']['firmware_ver'];
				} else {
					$row_out[$i]['products'][$j]['update_fw'] = 0;
					$row_out[$i]['products'][$j]['update_vers_fw'] = "";
				}
				if (! isset($row_out[$i]['products'][$j]['update_fw'])) 		{ $row_out[$i]['products'][$j]['update_fw'] = 0; }
				if (! isset($row_out[$i]['products'][$j]['update_vers_fw'])) 	{ $row_out[$i]['products'][$j]['update_vers_fw'] = ""; }


				$row_out[$i]['products'][$j]['fw_type'] = $this->firmware_local_check($row2['id']);
				$row_out[$i]['products'][$j]['count'] = $j;
				if ($row2['hidden'] == 1)
				{
					$j++;
					continue;
				}

				$k = 0;
				$model_list = $this->epm_config_hardware_get_list_models($row2['id'], true);
				foreach($model_list as $row3)
				{
					$row_out[$i]['products'][$j]['models'][$k] = $row3;

					unset ($row_out[$i]['products'][$j]['models'][$k]['template_list']);
					unset ($row_out[$i]['products'][$j]['models'][$k]['template_data']);

					if($row_out[$i]['products'][$j]['models'][$k]['enabled']){
						$row_out[$i]['products'][$j]['models'][$k]['enabled_checked'] = 'checked';
					}
					$row_out[$i]['products'][$j]['models'][$k]['count'] = $k;
					$k++;
				}
				$j++;
			}


			$i++;
		}
		//echo "<textarea>".print_r($row_out,true)."</textarea>";
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
		if ($brand_name_find == NULL)
		{
			return $this->brand_update_check_all();
		}

		$sql = sprintf("SELECT * FROM %s WHERE directory = '%s'", "endpointman_brand_list", $brand_name_find);
		$row = sql($sql, 'getRow', \PDO::FETCH_ASSOC);

		$out = array();
		if  (! isset($row['directory']))
		{
			$out['update'] = -2;
		}
		else
		{
			$file_brand_data = $this->epm->buildPath($this->epm->PHONE_MODULES_PATH, 'endpoint', $row['directory'], 'brand_data.json');
			if (file_exists($file_brand_data))
			{
				$temp = null;
				try
				{
					$temp = $this->epm->file2json($file_brand_data);
				}
				catch (\Exception $e)
				{
					$this->error['file2json'] = $e->getMessage();
				}

				if (is_null($temp))
				{
					$out['update'] = -3;
				}
				else 
				{
					$temp = $temp['data']['brands'];

					$version = $temp['last_modified'];
					$last_mod = "";
					foreach ($temp['family_list'] as $list) {
						$last_mod = max($last_mod, $list['last_modified']);
					}
					$last_mod = max($last_mod, $version);
					$version = $last_mod;
	
					if ($row['cfg_ver'] < $version) {
						$out['update'] = 1;
						$out['update_vers'] = $version;
					} else {
						$out['update'] = NULL;
						$out['update_vers'] = $version;
					}
				}				
			}
			else
			{
				$out['update'] = -1;
			}
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
		$temp = null;
		$out  = array();

		$file_master = $this->epm->buildPath($this->epm->PHONE_MODULES_PATH, 'endpoint', 'master.json');

		try
		{
			$temp = $this->epm->file2json($file_master);
		}
		catch (\Exception $e)
		{
			$this->error['file2json'] = $e->getMessage();
		}

		if (!empty($temp))
		{
			// TODO: Code Obsolete???
			// $endpoint_package = $temp['data']['package'];
			// $endpoint_last_mod = $temp['data']['last_modified'];

			$version = array();
			$out 	 = $temp['data']['brands'];

			foreach ($out as $data)
			{
				$file_brand_data = $this->epm->buildPath($this->epm->PHONE_MODULES_PATH, 'endpoint', $data['directory'], 'brand_data.json');
				
				try
				{
					$temp = $this->epm->file2json($file_brand_data);
				}
				catch (\Exception $e)
				{
					$this->error['file2json'] = $e->getMessage();
					continue;
				}

				/**
				 * Check if the 'brands' key exists in the 'data' array and assign its value to the $temp variable.
				 * If the 'directory' key exists in the $temp array, assign its value to the $brand_name variable.
				 * If $temp or $brand_name is empty, continue to the next iteration.
				 */
				$temp 		= $temp['data']['brands'] ?? array();
				$brand_name = $temp['directory'] ?? '';
				if (empty($temp) || empty($brand_name))
				{
					continue;
				}

				$version[$brand_name] = $temp['last_modified'] ?? "-1";
				$last_mod = "";
				foreach ($temp['family_list'] ?? array() as $list)
				{
					$last_mod = max($last_mod, $list['last_modified'] ?? '0' );
				}
				$last_mod = max($last_mod, $version[$brand_name]);
				$version[$brand_name] = $last_mod;
			}

			$sql = sprintf('SELECT * FROM %s WHERE id > 0', "endpointman_brand_list");
			$stmt = $this->db->prepare($sql);
			$stmt->execute();
			$row = $stmt->fetchAll(\PDO::FETCH_ASSOC);

			foreach ($row as $ava_brands)
			{
				$key = $this->system->arraysearchrecursive($ava_brands['directory'], $out, 'directory');

				if ($key === FALSE)
				{
					$tmp 		   = $ava_brands;
					$tmp['update'] = -1;
					$out[] = $tmp;
				}
				else
				{
					$key 		= $key[0];
					$brand_name = $ava_brands['directory'];
					if (! isset($version[$brand_name]))
					{
						$version[$brand_name] = 0;
					}

					if ($ava_brands['cfg_ver'] < $version[$brand_name])
					{
						$out[$key]['update'] 	  = 1;
						$out[$key]['update_vers'] = $version[$brand_name];
					}
					else
					{
						$out[$key]['update'] = NULL;
					}
				}
			}
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
		$url_master     = $this->epm->buildUrl($this->epm->URL_UPDATE, "master.json");
		$url_status	    = $this->epm->buildUrl($this->epm->URL_UPDATE, "update_status");
		$local_endpoint = $this->epm->buildPath($this->epm->PHONE_MODULES_PATH, "endpoint");
		$temp_location  = $this->epm->buildPath($this->epm->PHONE_MODULES_PATH, "temp", "provisioner");
		$local_master   = $this->epm->buildPath($local_endpoint, "master.json");
		
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
        	if ($echomsg == true)
			{
        		$master_result = $this->system->download_file_with_progress_bar($url_master, $local_master);
        	}
			else
			{
        		$master_result = $this->system->download_file($url_master, $local_master);
        	}

            if (!$master_result || !file_exists($local_master))
			{
            	$error['brand_update_check_master'] = _("Error: Not able to connect to repository. Using local master file instead.");
            	if ($echomsg == true )
				{
            		out($error['brand_update_check_master']);
            	}
            }

			$temp 			  = null;
			$endpoint_package = null;
			try
			{
				$temp 			   = $this->epm->file2json($local_master);
				$endpoint_package  = $temp['data']['package'] ?? null;
				$endpoint_last_mod = $temp['data']['last_modified'] ?? null;
			}
			catch (\Exception $e)
			{
				$error['file2json_master'] = $e->getMessage();
				if ($echomsg == true )
				{
            		out($error['file2json_master']);
            	}
			}
			if (empty($temp))
			{

			}
			elseif (empty($endpoint_package))
			{

			}
			else
			{
				$sql  = sprintf("SELECT value FROM %s WHERE var_name LIKE :find", "endpointman_global_vars");
				$stmt = $this->db->prepare($sql);
				$stmt->execute([
					':find' => "endpoint_vers"
				]);
				$data = $stmt->rowCount() === 0 ? null : $stmt->fetch(\PDO::FETCH_ASSOC);

				//TODO: Comment download update_status becouse not exist in repostitory GitHub and Set 0 becouse not existe Pakage File in repositopry.
				try
				{
					$contents = file_get_contents($url_status);
					if ($contents === FALSE)
					{
						throw new \Exception(_("The stream could not be opened: the requested url was not found or there was a problem with the request."));
					}
				} catch (\Exception $e)
				{
					$error['check_status_server'] = $e->getMessage();
					if ($echomsg == true )
					{
						out($error['check_status_server']);
					}
					$contents = -1;
				}
				
				if ($contents == '0')
				{
					if (($data == "") OR ($data <= $endpoint_last_mod))
					{
						$url_package  = $this->epm->buildUrl($this->epm->URL_UPDATE, $endpoint_package);
						$path_package = $this->epm->buildPath($temp_location, $endpoint_package);

						//TODO: ERROR Pakage file not exist in repository GitHub.
						if ((!$master_result) OR (!$this->system->download_file($url_package, $path_package)))
						{
							$error['brand_update_check_json'] = _("Not able to connect to repository. Using local Provisioner.net Package");
							if ($echomsg == true )
							{
								out($error['brand_update_check_json']);
							}
						}
						else
						{
							exec(sprintf("%s -xvf %s -C %s", $this->configmod->get("tar_location"), $path_package, $temp_location));
							// exec("tar -xvf " . $temp_location . $endpoint_package . " -C " . $temp_location);

							//TODO: Automate this somehow...
							$files = [
								"setup.php",
								"autoload.php",
								$this->epm->buildPath("endpoint', 'base.php"),
								$this->epm->buildPath("endpoint', 'global_template_data.json"),
							];
							foreach ($files as $file)
							{
								$file_path_src  = $this->epm->buildPath($temp_location, $file);
								$file_path_dest = $this->epm->buildPath($this->epm->PHONE_MODULES_PATH, $file);

								if (file_exists($file_path_dest))
								{
									unlink($file_path_dest);
								}
								if (file_exists($file_path_src))
								{
									rename($file_path_src, $file_path_dest);
								}
								else
								{
									$error['brand_update_check_file'] = sprintf(_("Skip: File %s not found in package."), $file);
									if ($echomsg == true )
									{
										out($error['brand_update_check_file']);
									}
								}
							}
							$sql  = sprintf("UPDATE %s SET value = :endpoint_last_mod WHERE var_name = :find", "endpointman_global_vars");
							$stmt = $this->db->prepare($sql);
							$stmt->execute([
								':endpoint_last_mod'=> $endpoint_last_mod,
								':find' 			=> "endpoint_vers"
							]);
						}
					}

					$out = $temp['data']['brands'];
					//Assume that if we can't connect and find the master.json file then why should we try to find every other file.
					if ($master_result)
					{
						$sql  = sprintf('SELECT * FROM %s WHERE id > 0', "endpointman_brand_list");
						$stmt = $this->db->prepare($sql);
						$stmt->execute();
						$row = $stmt->fetchAll(\PDO::FETCH_ASSOC);

						foreach ($out as $data)
						{
							$sql  = sprintf("SELECT local FROM %s WHERE directory = :directory", "endpointman_brand_list");
							$stmt = $this->db->prepare($sql);
							$stmt->execute([
								':directory' => $data['directory']
							]);
							$local = $stmt->rowCount() === 0 ? false : $stmt->fetch(\PDO::FETCH_ASSOC);

							$url_brand_data   = $this->epm->buildUrl($this->epm->URL_UPDATE, $data['directory'], $data['directory'].".json");
							$local_brand_data = $this->epm->buildPath($local_endpoint, $data['directory'], 'brand_data.json');
							if (!$local)
							{
								if ($echomsg == true)
								{
									out(sprintf(_("Update Brand (%s):"), $data['name']));
									$result = $this->system->download_file_with_progress_bar($url_brand_data, $local_brand_data);
								}
								else
								{
									$result = $this->system->download_file($url_brand_data, $local_brand_data);
								}
								if (!$result)
								{
									$error['brand_update_check'] = sprintf(_("Not able to connect to repository. Using local brand [%s] file instead."), $data['name']);
									if ($echomsg == true )
									{
										out($error['brand_update_check']);
									}
									continue;
								}
							}

							if (! file_exists($local_brand_data))
							{
								$error['brand_update_check_local_file'] = sprintf(_("Error: No Local File for %s!<br>Learn how to manually upload packages here (it's easy!): <a href='%s' target='_blank'> Click Here! </a>"), $data['name'], "http://wiki.provisioner.net/index.php/Endpoint_manager_manual_upload");
								if ($echomsg == true )
								{
									out($error['brand_update_check_local_file']);
								}
								continue;
							}

							try
							{
								$temp = $this->epm->file2json($local_brand_data);
							}
							catch (\Exception $e)
							{
								$this->error['file2json_brand_data'] = $e->getMessage();
								if ($echomsg == true )
								{
									out($error['file2json_brand_data']);
								}
								continue;
							}
							$temp = $temp['data']['brands'] ?? array();
							if (empty($temp))
							{
								continue;
							}
							if (array_key_exists('oui_list', $temp) && !empty($temp['brand_id']))
							{
								foreach ($temp['oui_list'] as $oui)
								{
									$sql = sprintf("REPLACE INTO %s (`oui`, `brand`, `custom`) VALUES (:oui, :brand_id, '0')", "endpointman_oui_list");
									$sth = $this->db->prepare($sql);
									$sth->execute([
										':oui' 		=> $oui,
										':brand_id' => $temp['brand_id']
									]);
								}
							}

							$brand_name 		  = $temp['directory'] ?? '';
							$version[$brand_name] = $temp['last_modified'] ?? "-1";
							$last_mod 			  = "";
							foreach ($temp['family_list'] ?? array() as $list)
							{
								$last_mod = max($last_mod, $list['last_modified'] ?? '0');
							}
							$last_mod 			  = max($last_mod, $version[$brand_name]);
							$version[$brand_name] = $last_mod;

							if (!($this->system->arraysearchrecursive($brand_name, $row, 'directory')))
							{
								$sql  = sprintf("SELECT directory FROM %s where id = :brand_id", "endpointman_brand_list");
								$stmt = $this->db->prepare($sql);
								$stmt->execute([
									':brand_id' => $temp['brand_id']
								]);

								if ( $stmt->rowCount() !== 0)
								{
									$error['brand_update_id_exist_other_brand'] = sprintf(_("You can not add the mark (%s) as the ID (%d) already exists in the database!"), $temp['name'], $temp['brand_id']);
									if ($echomsg == true )
									{
										out($error['brand_update_id_exist_other_brand']);
									}
									continue;
								}
								
								$sql = sprintf("INSERT INTO %s (id, name, directory, cfg_ver) VALUES (:brand_id, :name, :directory, :version)", "endpointman_brand_list");
								$sth = $this->db->prepare($sql);
								$sth->execute([
									':brand_id'  => $temp['brand_id'],
									':name' 	 => $temp['name'],
									':directory' => $temp['directory'],
									':version' 	 => $version[$brand_name],
								]);
							}
							else
							{
								//in database already!
							}
						}

						foreach ($row as $ava_brands)
						{
							$key = $this->system->arraysearchrecursive($ava_brands['directory'], $out, 'directory');
							if ($key === FALSE)
							{
								$this->remove_brand($ava_brands['id']);
							}
							else
							{
								$key 		= $key[0];
								$brand_name = $ava_brands['directory'];
								//TODO: This seems old
								if (!empty($version) && !empty($version[$brand_name]) && $ava_brands['cfg_ver'] < $version[$brand_name])
								{ 
									$out[$key]['update']	  = 1;
									$out[$key]['update_vers'] = $version[$brand_name];
								}
								else
								{
									$out[$key]['update'] = NULL;
								}
							}
						}
					}
					else
					{
						$error['brand_update_check_master_file'] = sprintf(_("Error: Aborting Brand Downloads. Can't Get Master File, Assuming Timeout Issues!<br>Learn how to manually upload packages here (it's easy!): <a href='%s' target='_blank'>Click Here!</a>"), "http://wiki.provisioner.net/index.php/Endpoint_manager_manual_upload");
						if ($echomsg == true )
						{
							out($error['brand_update_check_master_file']);
						}
					}
					return $out;
				}
				else
				{
					if ($contents == -1)
					{
						$error['remote_server'] = _("Error: The Remote server did not return any status information, Please try again later");
					}
					else
					{
						$error['remote_server'] = _("Error: The Remote Server Is Currently Syncing With the Master Server, Please try again later");
					}
					if ($echomsg == true )
					{
						out($error['remote_server']);
					}
				}
			}
        }
		else
		{
            $o = getcwd();
            chdir(dirname($this->epm->PHONE_MODULES_PATH));
            $path = $this->epm->has_git();
            exec($path . ' git pull', $output);
            //exec($path . ' git checkout master', $output); //Why am I doing this?

            chdir($o);
            $temp 			  = null;
			$endpoint_package = null;
			try
			{
				$temp 			   = $this->epm->file2json($local_master);
				$endpoint_package  = $temp['data']['package'] ?? '';
				$endpoint_last_mod = $temp['data']['last_modified'] ?? '';
			}
			catch (\Exception $e)
			{
				$this->error['file2json'] = $e->getMessage();
			}
			if (empty($temp))
			{

			}
			elseif (empty($endpoint_package))
			{

			}
			else
			{
				$sql  = sprintf("UPDATE %s SET value = :endpoint_last_mod WHERE var_name = :find", "endpointman_global_vars");
				$stmt = $this->db->prepare($sql);
				$stmt->execute([
					':endpoint_last_mod' => $endpoint_last_mod,
					':find' 			 => "endpoint_vers"
				]);

				$sql  = sprintf('SELECT * FROM %s WHERE id > 0', "endpointman_brand_list");
				$stmt = $this->db->prepare($sql);
				$stmt->execute();
				$row = $stmt->fetchAll(\PDO::FETCH_ASSOC);

				$out = $temp['data']['brands'] ?? array();
				foreach ($out as $data)
				{
					$local_brand_data = $this->epm->buildPath($local_endpoint, $data['directory'], 'brand_data.json');
					try
					{
						$temp = $this->epm->file2json($local_brand_data);
					}
					catch (\Exception $e)
					{
						$this->error['file2json_brand'] = $e->getMessage();
						continue;
					}

					if (! key_exists('directory', $temp['data']['brands']))
					{
						continue;
					}

					//Pull in all variables
					$directory 		= $temp['data']['brands']['directory'] ?? '';
					$brand_name		= $temp['data']['brands']['name'] ?? '';
					$brand_id		= $temp['data']['brands']['brand_id'] ?? '';
					$brand_version 	= $temp['data']['brands']['last_modified'] ?? '';

					if (empty($directory) || empty($brand_name) || empty($brand_id))
					{
						continue;
					}


					$sql  = sprintf("SELECT id FROM %s WHERE id = :brand_id", "endpointman_brand_list");
					$stmt = $this->db->prepare($sql);
					$stmt->execute([
						':brand_id' => $brand_id
					]);
					if ($stmt->rowCount() === 0)
					{
						$sql  = sprintf("INSERT INTO %s (id, name, directory, cfg_ver, local, installed) VALUES (:brand_id, :brand_name, :directory, :brand_version, '1', '1')", "endpointman_brand_list");
						$stmt = $this->db->prepare($sql);
						$stmt->execute([
							':brand_id' 	 => $brand_id,
							':brand_name' 	 => $brand_name,
							':directory' 	 => $directory,
							':brand_version' => $brand_version,
						]);
					}
					else
					{
						$sql  = sprintf("UPDATE %s SET local = '1', name = :brand_name, cfg_ver = :brand_version, installed = 1, hidden = 0 WHERE id = :brand_id", "endpointman_brand_list");
						$stmt = $this->db->prepare($sql);
						$stmt->execute([
							':brand_id' 	 => $brand_id,
							':brand_name' 	 => $brand_name,
							':brand_version' => $brand_version,
						]);
					}

					$last_mod = "";
					foreach ($temp['data']['brands']['family_list'] ?? array() as $family_list)
					{
						$local_family_data = $this->epm->buildPath($local_endpoint, $directory, $family_list['directory'], "family_data.json");
						try
						{
							$family_line = $this->epm->file2json($local_family_data);
						}
						catch (\Exception $e)
						{
							$this->error['file2json_family_line'] = $e->getMessage();
							continue;
						}

						$brand_id_family_line 				  = $brand_id . $family_line['data']['id'];
						$last_mod 		   					  = max($last_mod, $family_list['last_modified'] ?? '');
						$family_line['data']['last_modified'] = $family_line['data']['last_modified'] ?? '';

						/* DONT DO THIS YET
						$require_firmware = NULL;
						if ((key_exists('require_firmware', $family_line['data'])) && ($remote) && ($family_line['data']['require_firmware'] == "TRUE")) {
						echo "Firmware Requirment Detected!..........<br/>";
						$this->install_firmware($family_line['data']['id']);
						}
						*
						*/


						$short_name = preg_replace("/\[(.*?)\]/si", "", $family_line['data']['name']);

						$sql  = sprintf("SELECT id FROM %s WHERE id= :brand_id_family_line", "endpointman_product_list");
						$stmt = $this->db->prepare($sql);
						$stmt->execute([
							':brand_id_family_line' => $brand_id_family_line
						]);

						if ($stmt->rowCount() === 0)
						{
							$sql  = sprintf("INSERT INTO %s (`id`, `brand`, `short_name`, `long_name`, `cfg_dir`, `cfg_ver`, `config_files`, `hidden`) VALUES (:brand_id_family_line, :brand_id, :short_name, :long_name, :directory, :last_modified, :config_files, '0')", "endpointman_product_list");
							$stmt = $this->db->prepare($sql);
							$stmt->execute([
								':brand_id_family_line' => $brand_id_family_line,
								':brand_id' 			=> $brand_id,
								':short_name' 			=> $short_name,
								':long_name' 			=> $family_line['data']['name'],
								':directory' 			=> $family_line['data']['directory'],
								':last_modified' 		=> $family_line['data']['last_modified'],
								':config_files' 		=> $family_line['data']['configuration_files'],
							]);
						}
						else
						{
							$sql  = sprintf("UPDATE %s SET short_name = :short_name, long_name = :long_name, cfg_ver = :cfg_ver, config_files= :config_files WHERE id = :brand_id_family_line", "endpointman_product_list");
							$stmt = $this->db->prepare($sql);
							$stmt->execute([
								':brand_id_family_line' => $brand_id_family_line,
								':short_name' 			=> $short_name,
								':long_name' 			=> $family_line['data']['name'],
								':cfg_ver' 				=> $family_line['data']['version'],
								':config_files' 		=> $family_line['data']['configuration_files'],
							]);
						}

						foreach ($family_line['data']['model_list'] ?? array() as $model_list)
						{
							$template_list 				= implode(",", $model_list['template_data']);
							$brand_id_family_line_model = $brand_id_family_line . $model_list['id'];

							$sql  = sprintf("SELECT id FROM %s WHERE id= :brand_id_family_line_model", "endpointman_model_list");
							$stmt = $this->db->prepare($sql);
							$stmt->execute([
								':brand_id_family_line_model' =>  $brand_id_family_line_model
							]);

							if ($stmt->rowCount() === 0)
							{
								$sql  = sprintf("INSERT INTO %s (`id`, `brand`, `model`, `max_lines`, `product_id`, `template_list`, `enabled`, `hidden`) VALUES (:brand_id_family_line_model, :brand_id, :model, :lines, :brand_id_family_line, :template_list, '0', '0')", "endpointman_model_list");
								$stmt = $this->db->prepare($sql);
								$stmt->execute([
									':brand_id_family_line_model' => $brand_id_family_line_model,
									':brand_id' 				  => $brand_id,
									':model' 					  => $model_list['model'],
									':lines' 					  => $model_list['lines'],
									':brand_id_family_line' 	  => $brand_id_family_line,
									':template_list' 			  => $template_list,
								]);
							}
							else
							{
								$sql  = sprintf("UPDATE %s SET max_lines = :lines, model = :model, template_list = :template_list WHERE id = :brand_id_family_line_model", "endpointman_model_list");
								$stmt = $this->db->prepare($sql);
								$stmt->execute([
									':lines' 					  => $model_list['lines'],
									':model' 					  => $model_list['model'],
									':template_list' 			  => $template_list,
									':brand_id_family_line_model' => $brand_id_family_line_model,
								]);
							}

							if (!$this->sync_model($brand_id . $family_line['data']['id'] . $model_list['id'], $errsync_modal))
							{
								$error['sync_module_error'] = sprintf(_("Error: System Error in Sync Model [%s] Function, Load Failure!"), $model_list['model']);
								if ($echomsg == true ) {
									out($error['sync_module_error']);
									foreach ($errsync_modal as $v) {
										out($v);
									}
								}
							}
							unset($errsync_modal);
						}

						//Phone Models Move Here

						$sql  = sprintf('SELECT * FROM %s WHERE product_id = :brand_id_family_line', "endpointman_model_list");
						$stmt = $this->db->prepare($sql);
						$stmt->execute([
							'brand_id_family_line' => $brand_id_family_line
						]);
						$products = $stmt->fetchAll(\PDO::FETCH_ASSOC);
						foreach ($products as $data)
						{
							if (!$this->system->arraysearchrecursive($data['model'], $family_line['data']['model_list'], 'model'))
							{
								if ($echomsg == true )
								{
									outn(sprintf(_("Moving/Removing Model '%s' not present in JSON file......"), $data['model']));
								}

								$sql  = sprintf("DELETE FROM %s WHERE id = :id", "endpointman_model_list");
								$stmt = $this->db->prepare($sql);
								$stmt->execute([
									':id' => $data['id']
								]);

								$sql  = sprintf("SELECT id FROM %s WHERE model LIKE :model_name", "endpointman_model_list");
								$stmt = $this->db->prepare($sql);
								$stmt->execute([
									':model_name' => $data['model']
								]);
								$new_model_id = $stmt->rowCount() === 0 ? false : ($stmt->fetchColumn() ?? false);

								if ($new_model_id)
								{
									$sql  = sprintf("UPDATE %s SET  model = :new_model_id WHERE  model = :model", "endpointman_mac_list");
									$stmt = $this->db->prepare($sql);
									$stmt->execute([
										':new_model_id' => $new_model_id,
										':model' 		=> $data['id'],
									]);
								}
								else
								{
									$sql  = sprintf("UPDATE %s SET  model = '0' WHERE  model = :model", "endpointman_mac_list");
									$stmt = $this->db->prepare($sql);
									$stmt->execute([
										':model' => $data['id'],
									]);
								}

								if ($echomsg == true )
								{
									out (_("Done!"));
								}
							}
						}
					}

					foreach ($temp['data']['brands']['oui_list'] ?? array() as $oui)
					{
						$sql  = sprintf("REPLACE INTO %s (`oui`, `brand`, `custom`) VALUES (:oui, :brand_id, '0')", "endpointman_oui_list");
						$stmt = $this->db->prepare($sql);
						$stmt->execute([
							':oui' 	 	=> $oui,
							':brand_id' => $brand_id,
						]);
					}
				}
			}
        }
    }



	 /**
     * Sync the XML files (incuding all template files) from the hard drive with the database
     * @param int $model Model ID
     * @return boolean True on sync completed. False on sync failed
     */
    function sync_model($model, &$error = array()) {
        if ((!empty($model)) OR ($model > 0)) {
            $sql = "SELECT * FROM  endpointman_model_list WHERE id='" . $model . "'";
            $model_row = sql($sql, 'getrow', \PDO::FETCH_ASSOC);

            $sql = "SELECT * FROM  endpointman_product_list WHERE id='" . $model_row['product_id'] . "'";
            $product_row = sql($sql, 'getRow', \PDO::FETCH_ASSOC);

            $sql = "SELECT * FROM  endpointman_brand_list WHERE id=" . $model_row['brand'];
            $brand_row = sql($sql, 'getRow', \PDO::FETCH_ASSOC);


            $path_brand_dir 		 = $this->epm->buildPath($this->epm->PHONE_MODULES_PATH, 'endpoint', $brand_row['directory']);
            $path_brand_dir_cfg 	 = $this->epm->buildPath($path_brand_dir, $product_row['cfg_dir']);
            $path_brand_dir_cfg_json = $this->epm->buildPath($path_brand_dir_cfg, 'family_data.json');

            if (!file_exists($path_brand_dir)){
            	$error['sync_model'] = sprintf(_("Brand Directory '%s' Doesn't Exist! (%s)"), $brand_row['directory'], $path_brand_dir);
                return(FALSE);
            }
            if (!file_exists($path_brand_dir_cfg)) {
            	$error['sync_model'] = sprintf(_("Product Directory '%s' Doesn't Exist! (%s)"), $product_row['cfg_dir'], $path_brand_dir_cfg);
                return(FALSE);
            }
            if (!file_exists($path_brand_dir_cfg_json)) {
                $error['sync_model'] = sprintf(_("File 'family_data.json' Doesn't exist in directory: %s"), $path_brand_dir_cfg);
                return(FALSE);
            }
            $family_line_json = $this->file2json($path_brand_dir_cfg_json);


            //TODO: Add local file checks to avoid slow reloading on PHP < 5.3
			$key = $this->system->arraysearchrecursive($model_row['model'], $family_line_json['data']['model_list'], 'model');
            if ($key === FALSE) {
                $error['sync_model'] = "Can't locate model in family JSON file";
                return(FALSE);
            } else {
                $template_list = implode(",", $family_line_json['data']['model_list'][$key[0]]['template_data']);
                $template_list_array = $family_line_json['data']['model_list'][$key[0]]['template_data'];
            }
            $maxlines = $family_line_json['data']['model_list'][$key[0]]['lines'];

            $sql = "UPDATE endpointman_model_list SET max_lines = '" . $maxlines . "', template_list = '" . $template_list . "' WHERE id = '" . $model . "'";
            sql($sql);

            $version = isset($family_line_json['data']['last_modified']) ? $family_line_json['data']['last_modified'] : '';
            $long_name = $family_line_json['data']['name'];
            $short_name = preg_replace("/\[(.*?)\]/si", "", $family_line_json['data']['name']);
            $configuration_files = $family_line_json['data']['configuration_files'];

            $sql = "UPDATE endpointman_product_list SET long_name = '" . str_replace("'", "''", $long_name) . "', short_name = '" . str_replace("'", "''", $short_name) . "' , cfg_ver = '" . $version . "' WHERE id = '" . $product_row['id'] . "'";
            sql($sql);

            $template_data_array = $this->merge_data($this->epm->buildPath($this->epm->PHONE_MODULES_PATH, 'endpoint', $brand_row['directory'], $product_row['cfg_dir']), $template_list_array, true, true);

            $sql = "UPDATE endpointman_model_list SET template_data = '" . serialize($template_data_array) . "' WHERE id = '" . $model . "'";
            sql($sql);
            return(TRUE);
        } else {
            return(FALSE);
        }
    }

	 /**
     * This will download the xml & brand package remotely
     * @param integer $id Brand ID
     */
    function download_brand($id) {
    	out(_("Install/Update Brand..."));
        if (!$this->configmod->get('use_repo'))
		{
			$temp_directory = $this->epm->PROVISIONER_PATH;
            // $temp_directory = $this->system->sys_get_temp_dir() . "/epm_temp/";

			if (!file_exists($temp_directory))
			{
				out(_("Creating EPM temp directory..."));
				if (! @mkdir($temp_directory, 0775, true))
				{
					out(sprintf(_("Error: Failed to create the directory '%s', please Check Permissions!"), $temp_directory));
					return false;
				}
			}

			outn(_("Downloading Brand JSON....."));

            // $row = sql('SELECT * FROM  endpointman_brand_list WHERE id =' . $id, 'getAll', \PDO::FETCH_ASSOC);
			$sql  = "SELECT * FROM  endpointman_brand_list WHERE id = :id";
			$stmt = $this->db->prepare($sql);
			$stmt->execute([':id' => $id]);

			if ($stmt->rowCount() === 0)
			{
				out(sprintf(_("Error: Brand with id '%s' not found!"), $id));
			}
			else
			{
				$row = $stmt->fetch(\PDO::FETCH_ASSOC);
			
				$url_brand_data   = $this->epm->buildUrl($this->epm->URL_UPDATE, $row['directory'], $row['directory'].".json");
				$local_brand_data = $this->epm->buildPath($this->epm->PHONE_MODULES_PATH, 'endpoint',  $row['directory'], "brand_data.json");

				$result = $this->system->download_file($url_brand_data, $local_brand_data);
				if ($result)
				{
					out(_("Done!"));

					$temp    = $this->file2json($local_brand_data);
					$package = $temp['data']['brands']['package'];

					out(_("Downloading Brand Package..."));

					$url_package  = $this->epm->buildUrl($this->epm->URL_UPDATE, $row['directory'], $package);
					$path_package = $this->epm->buildPath($temp_directory, $package);

					if ($this->system->download_file_with_progress_bar($url_package, $path_package))
					{
						if (file_exists($path_package))
						{
							$md5_xml = $temp['data']['brands']['md5sum'];
							$md5_pkg = md5_file($path_package);

							outn(_("Checking MD5sum of Package.... "));
							if ($md5_xml == $md5_pkg)
							{
								out(_("Done!"));

								outn(_("Extracting Tarball........ "));
								//TODO: PENDIENTE VALIDAR SI DA ERROR LA DESCOMPRESION
								exec( sprintf("%s -xvf %s -C %s", $this->configmod->get("tar_location"), $path_package, $temp_directory) );
								// exec("tar -xvf " . $path_package . " -C " . $temp_directory);
								out(_("Done!"));

								//Update File in the temp directory
								$path_package_json = $this->epm->buildPath($temp_directory, $row['directory'], "brand_data.json");
								copy($local_brand_data, $path_package_json);
								$this->update_brand($row['directory'], TRUE);
							}
							else
							{
								out(_("MD5 Did not match!"));
								out(sprintf(_("MD5 XML: %s"), $md5_xml));
								out(sprintf(_("MD5 PKG: %s"), $md5_pkg));
							}
						}
						else
						{
							out(_("Error: Can't Find Downloaded File!"));
						}
					}
					else
					{
						out(_("Error download Brand package!"));
					}
				}
				else
				{
					out(_("Error!"));
					out(_("Error Connecting to the Package Repository. Module not installed. Please Try again later."));
					out(_("You Can Also Manually Update The Repository By Downloading Files here: <a href='http://www.provisioner.net/releases3' target='_blank'> Release Repo </a>"));
					out(_("Then Use Manual Upload in Advanced Settings."));
				}
			}
        }
		else
		{
			out(_("Error: Installing brands is disabled while in repo mode!"));
        }
    }

    /**
     * This will install or updated a brand package (which is the same thing to this)
     * Still needs way to determine when models move...perhaps another function?
     */
    function update_brand($package, $remote=TRUE)
	{
    	out(sprintf(_("Update Brand %s ... "), $package));

		// $temp_directory = $this->system->sys_get_temp_dir() . "/epm_temp/";

		$temp_directory  = $this->epm->PROVISIONER_PATH;
		$temp_brand_json = $this->epm->buildPath($temp_directory, $package, "brand_data.json");

		//DEBUG
		out( sprintf(_("Processing %s..."), $temp_brand_json));

        if (file_exists($temp_brand_json))
		{
            $temp = $this->file2json($temp_brand_json);
            if (key_exists('directory', $temp['data']['brands']))
			{
				out(_("Appears to be a valid Provisioner.net JSON file.....Continuing"));
                //Pull in all variables
                $directory     = $temp['data']['brands']['directory'];
                $brand_name    = $temp['data']['brands']['name'];
                $brand_id      = $temp['data']['brands']['brand_id'];
                $brand_version = $temp['data']['brands']['last_modified'];

                //create directory structure and move files
                out(sprintf(_("Creating Directory Structure for Brand '%s' and Moving Files..."), $brand_name));

				$local_brand_location = $this->epm->buildPath($this->epm->PHONE_MODULES_PATH, 'endpoint', $directory);

                if (!file_exists($local_brand_location))
				{
                    mkdir($local_brand_location, 0775, true);
                }


				$path_iterator = $this->epm->buildPath($temp_directory, $directory);

                $dir_iterator = new \RecursiveDirectoryIterator($path_iterator);
                $iterator 	  = new \RecursiveIteratorIterator($dir_iterator, \RecursiveIteratorIterator::SELF_FIRST);
                foreach ($iterator as $file)
				{
                    if (is_dir($file))
					{
                        $dir      = str_replace($path_iterator, "", $file);
						$path_dir = $this->epm->buildPath($local_brand_location, $dir);

                        if (!file_exists($path_dir))
						{
                            mkdir($path_dir, 0775, TRUE);
//echo ".";
                        }
                    }
					else
					{
                        if ((basename($file) != "brand_data.json") OR (!$remote))
						{
                            $dir   	  = str_replace($path_iterator, "", $file);
							$path_dir = $this->epm->buildPath($local_brand_location, $dir);

                            $stats = rename($file, $path_dir);
                            if ($stats === FALSE)
							{
                            	out(sprintf(_("- Error Moving %s!"), basename($file)));
                            }
                            chmod($path_dir, 0775);
//echo ".";
                        }
                    }
                }
                out(_("All Done!"));

                $local = $remote ? 0 : 1;

                // $b_data = sql("SELECT id FROM endpointman_brand_list WHERE id = '" . $brand_id . "'", 'getOne');
				$sql  = "SELECT id FROM endpointman_brand_list where id = :brand_id";
				$stmt = $this->db->prepare($sql);
				$stmt->execute([':brand_id' => $brand_id]);

                if ($stmt->rowCount() === 0)
				{
					outn(sprintf(_("Inserting %s brand data ..."), $brand_name));
					// $sql = "INSERT INTO endpointman_brand_list (id, name, directory, cfg_ver, local, installed) VALUES ('" . $brand_id . "', '" . $brand_name . "', '" . $directory . "', '" . $brand_version . "', '" . $local . "', '1')";
                    // sql($sql);

					$sql  = "INSERT INTO endpointman_brand_list (id, name, directory, cfg_ver, local, installed) VALUES (:brand_id, :brand_name, :directory, :brand_version, :local, '1')";
					$stmt = $this->db->prepare($sql);
					$stmt->execute([
						':brand_id' 	 => $brand_id,
						':brand_name' 	 => $brand_name,
						':directory'     => $directory,
						':brand_version' => $brand_version,
						':local' 		 => $local,
					]);
                }
				else
				{
                	outn(sprintf(_("Updating %s brand data ..."), $brand_name));
                    // $sql = "UPDATE endpointman_brand_list SET local = '" . $local . "', name = '" . $brand_name . "', cfg_ver = '" . $brand_version . "', installed = 1, hidden = 0 WHERE id = " . $brand_id;
                    // sql($sql);

					$sql  = "UPDATE endpointman_brand_list SET local = :local, name = :brand_name, cfg_ver = :brand_version, installed = 1, hidden = 0 WHERE id = :brand_id";
					$stmt = $this->db->prepare($sql);
					$stmt->execute([
						':brand_id' 	 => $brand_id,
						':brand_name' 	 => $brand_name,
						':brand_version' => $brand_version,
						':local' 		 => $local,
					]);
                }
				out(_("Done!"));

				//TODO: Pending Update Query SQL to parser and execute
                $last_mod = "";
                foreach ($temp['data']['brands']['family_list'] as $family_list)
				{
					out(_("Updating Family Lines ..."));

                    $last_mod = max($last_mod, $family_list['last_modified']);

                    $family_line = $this->file2json($this->epm->buildPath($this->epm->PHONE_MODULES_PATH, 'endpoint', $directory, $family_list['directory'], 'family_data.json'));
                    $family_line['data']['last_modified'] = isset($family_line['data']['last_modified']) ? $family_line['data']['last_modified'] : '';

                    $require_firmware = NULL;
                    if ((key_exists('require_firmware', $family_line['data'])) && ($remote) && ($family_line['data']['require_firmware'] == "TRUE"))
					{
						out(_("Firmware Requirment Detected!.........."));
						$this->install_firmware($family_line['data']['id']);
                    }

                    $data = sql("SELECT id FROM endpointman_product_list WHERE id='" . $brand_id . $family_line['data']['id'] . "'", 'getOne');
                    $short_name = preg_replace("/\[(.*?)\]/si", "", $family_line['data']['name']);

					if ($data) {
						if ($this->configmod->get('debug')) echo "-Updating Family ".$short_name."<br/>";
                        $sql = "UPDATE endpointman_product_list SET short_name = '" . str_replace("'", "''", $short_name) . "', long_name = '" . str_replace("'", "''", $family_line['data']['name']) . "', cfg_ver = '" . $family_line['data']['version'] . "', config_files='" . $family_line['data']['configuration_files'] . "' WHERE id = '" . $brand_id . $family_line['data']['id'] . "'";
                    }
					else {
						if ($this->configmod->get('debug')) echo "-Inserting Family ".$short_name."<br/>";
                        $sql = sprintf("INSERT INTO endpointman_product_list (`id`, `brand`, `short_name`, `long_name`, `cfg_dir`, `cfg_ver`, `config_files`, `hidden`) VALUES ('%s', '%s', '%s', '%s', '%s', '%s','%s', '0')",
						$brand_id . $family_line['data']['id'], 
						$brand_id,
						str_replace("'", "''", $short_name),
						str_replace("'", "''", $family_line['data']['name']),
						$family_line['data']['directory'],
						$family_line['data']['last_modified'],
						$family_line['data']['configuration_files']
						);
                    }
					sql($sql);


					if (count($family_line['data']['model_list']) > 0) {
						out(_("-- Updating Model Lines ... "));
	                    foreach ($family_line['data']['model_list'] as $model_list) {
	                        $template_list = implode(",", $model_list['template_data']);

	                        $model_final_id = $brand_id . $family_line['data']['id'] . $model_list['id'];
	                        $sql = 'SELECT id, global_custom_cfg_data, global_user_cfg_data FROM endpointman_mac_list WHERE model = ' . $model_final_id;
	                        $old_data = NULL;
	                        $old_data = sql($sql, 'getAll', \PDO::FETCH_ASSOC);
	                        foreach ($old_data as $data) {
	                            $global_custom_cfg_data = unserialize($data['global_custom_cfg_data']);
	                            if ((is_array($global_custom_cfg_data)) AND (!array_key_exists('data', $global_custom_cfg_data))) {
outn(_("----Old Data Detected! Migrating ... "));
	                                $new_data = array();
	                                $new_ari = array();
	                                foreach ($global_custom_cfg_data as $key => $old_keys) {
	                                    if (array_key_exists('value', $old_keys)) {
	                                        $new_data[$key] = $old_keys['value'];
	                                    } else {
	                                        $breaks = explode("_", $key);
	                                        $new_data["loop|" . $key] = $old_keys[$breaks[2]];
	                                    }
	                                    if (array_key_exists('ari', $old_keys)) {
	                                        $new_ari[$key] = 1;
	                                    }
	                                }
	                                $final_data = array();
	                                $final_data['data'] = $new_data;
	                                $final_data['ari'] = $new_ari;
	                                $final_data = serialize($final_data);
	                                $sql = "UPDATE endpointman_mac_list SET  global_custom_cfg_data =  '" . $final_data . "' WHERE  id =" . $data['id'];
	                                sql($sql);
									out(_("Done!"));
	                            }

	                            $global_user_cfg_data = unserialize($data['global_user_cfg_data']);
	                            $old_check = FALSE;
	                            if (is_array($global_user_cfg_data)) {
	                                foreach ($global_user_cfg_data as $stuff) {
	                                    if (is_array($stuff)) {
	                                        if (array_key_exists('value', $stuff)) {
	                                            $old_check = TRUE;
	                                            break;
	                                        } else {
	                                            break;
	                                        }
	                                    } else {
	                                        break;
	                                    }
	                                }
	                            }
	                            if ((is_array($global_user_cfg_data)) AND ($old_check)) {
outn(_("Old Data Detected! Migrating ... "));
	                                $new_data = array();
	                                foreach ($global_user_cfg_data as $key => $old_keys) {
	                                    if (array_key_exists('value', $old_keys)) {
	                                        $exploded = explode("_", $key);
	                                        $counted = count($exploded);
	                                        $counted = $counted - 1;
	                                        if (is_numeric($exploded[$counted])) {
	                                            $key = "loop|" . $key;
	                                        }
	                                        $new_data[$key] = $old_keys['value'];
	                                    }
	                                }
	                                $final_data = serialize($new_data);
	                                $sql = "UPDATE endpointman_mac_list SET  global_user_cfg_data =  '" . $final_data . "' WHERE  id =" . $data['id'];
	                                sql($sql);
									out(_("Done!"));
	                            }
	                        }
	                        $old_data = NULL;
	                        $sql = 'SELECT id, global_custom_cfg_data FROM endpointman_template_list WHERE model_id = ' . $model_final_id;
	                        $old_data = sql($sql, 'getAll', \PDO::FETCH_ASSOC);
	                        foreach ($old_data as $data) {
	                            $global_custom_cfg_data = unserialize($data['global_custom_cfg_data']);
	                            if ((is_array($global_custom_cfg_data)) AND (!array_key_exists('data', $global_custom_cfg_data))) {
out(_("Old Data Detected! Migrating ... "));
	                                $new_data = array();
	                                $new_ari = array();
	                                foreach ($global_custom_cfg_data as $key => $old_keys) {
	                                    if (array_key_exists('value', $old_keys)) {
	                                        $new_data[$key] = $old_keys['value'];
	                                    } else {
	                                        $breaks = explode("_", $key);
	                                        $new_data["loop|" . $key] = $old_keys[$breaks[2]];
	                                    }
	                                    if (array_key_exists('ari', $old_keys)) {
	                                        $new_ari[$key] = 1;
	                                    }
	                                }
	                                $final_data = array();
	                                $final_data['data'] = $new_data;
	                                $final_data['ari'] = $new_ari;
	                                $final_data = serialize($final_data);
	                                $sql = "UPDATE endpointman_template_list SET  global_custom_cfg_data =  '" . $final_data . "' WHERE  id =" . $data['id'];
	                                sql($sql);
									out(_("Done!"));
	                            }
	                        }

	                        $m_data = sql("SELECT id FROM endpointman_model_list WHERE id='" . $brand_id . $family_line['data']['id'] . $model_list['id'] . "'", 'getOne');
	                        if ($m_data) {
if ($this->configmod->get('debug')) echo $this->epm->format_txt(_("---Updating Model %_NAMEMOD_%"), "", array("%_NAMEMOD_%" => $model_list['model']));
	                            $sql = "UPDATE endpointman_model_list SET max_lines = '" . $model_list['lines'] . "', model = '" . $model_list['model'] . "', template_list = '" . $template_list . "' WHERE id = '" . $brand_id . $family_line['data']['id'] . $model_list['id'] . "'";
	                        }
							else {
if ($this->configmod->get('debug')) echo $this->epm->format_txt(_("---Inserting Model %_NAMEMOD_%"), "", array("%_NAMEMOD_%" => $model_list['model']));
	                            $sql = "INSERT INTO endpointman_model_list (`id`, `brand`, `model`, `max_lines`, `product_id`, `template_list`, `enabled`, `hidden`) VALUES ('" . $brand_id . $family_line['data']['id'] . $model_list['id'] . "', '" . $brand_id . "', '" . $model_list['model'] . "', '" . $model_list['lines'] . "', '" . $brand_id . $family_line['data']['id'] . "', '" . $template_list . "', '0', '0')";
	                        }
	                        sql($sql);

	                        //echo "brand_id:".$brand_id. " - family_line:" . $family_line['data']['id'] . "- model_list:" . $model_list['id']."<br>";
	                        if (!$this->sync_model($brand_id . $family_line['data']['id'] . $model_list['id'], $errlog)) {
	                        	out(_("Error: System Error in Sync Model Function, Load Failure!"));
								out(_("Error: ").$errlog['sync_model']);
	                        }
	                        unset ($errlog);
	                    }
					}
                    //END Updating Model Lines................

                    //Phone Models Move Here
                    $family_id = $brand_id . $family_line['data']['id'];
                    $sql = "SELECT * FROM endpointman_model_list WHERE product_id = " . $family_id;
                    $products = sql($sql, 'getall', \PDO::FETCH_ASSOC);
                    foreach ($products as $data) {
                        if (!$this->system->arraysearchrecursive($data['model'], $family_line['data']['model_list'], 'model')) {
							outn(sprintf(_("Moving/Removing Model '%s' not present in JSON file ... "), $data['model']));
                            $model_name = $data['model'];
                            $sql = 'DELETE FROM endpointman_model_list WHERE id = ' . $data['id'];
                            sql($sql);
                            $sql = "SELECT id FROM endpointman_model_list WHERE model LIKE '" . $model_name . "'";
                            $new_model_id = sql($sql, 'getOne');
                            if ($new_model_id) {
                                $sql = "UPDATE  endpointman_mac_list SET  model =  '" . $new_model_id . "' WHERE  model = '" . $data['id'] . "'";
                            } else {
                                $sql = "UPDATE  endpointman_mac_list SET  model =  '0' WHERE  model = '" . $data['id'] . "'";
                            }
                            sql($sql);
                            out(_("Done!"));
                        }
                    }
                }
				out(_("All Done!"));
				//END Updating Family Lines

				outn(_("Updating OUI list in DB ... "));
				if ((isset($temp['data']['brands']['oui_list'])) AND (count($temp['data']['brands']['oui_list']) > 0))
				{
	                foreach ($temp['data']['brands']['oui_list'] as $oui) {
	                    $sql = "REPLACE INTO endpointman_oui_list (`oui`, `brand`, `custom`) VALUES ('" . $oui . "', '" . $brand_id . "', '0')";
	                    sql($sql);
	                }
				}
				out(_("Done!"));
            } else {
				outn(sprintf(_("Error: Invalid JSON Structure in %s/brand_data.json"), $this->epm->buildPath($temp_directory, $package)));
            }
        } else {
			out(_("Error: No 'brand_data.xml' file exists!"));
        }

		outn(_("Removing Temporary Files... "));

        $this->system->rmrf($this->epm->buildPath($temp_directory, $package));
        out(_("Done!"));
    }

	/**
     * Remove the brand
     * @param int $id Brand ID
     */
    function remove_brand($id=NULL, $remove_configs=FALSE, $force=FALSE) {
		out(_("Uninstalla Brand..."));

        if (!$this->configmod->get('use_repo')) {
            $sql = "SELECT id, firmware_vers FROM endpointman_product_list WHERE brand = '" . $id . "'";
            $products = sql($sql, 'getall', \PDO::FETCH_ASSOC);

            foreach ($products as $data) {
                if ($data['firmware_vers'] != "") {
                    $this->remove_firmware($data['id']);
                }
            }

			$sql = "SELECT directory FROM endpointman_brand_list WHERE id = '" . $id . "'";
            $brand_dir = sql($sql, 'getOne');
            $this->system->rmrf($this->epm->buildPath($this->epm->PHONE_MODULES_PATH, 'endpoint', $brand_dir));

            $sql = "DELETE FROM endpointman_model_list WHERE brand = '" . $id . "'";
            sql($sql);

            $sql = "DELETE FROM endpointman_product_list WHERE brand = '" . $id . "'";
            sql($sql);

            $sql = "DELETE FROM endpointman_oui_list WHERE brand = '" . $id . "'";
            sql($sql);

            $this->system->rmrf($this->epm->buildPath($this->epm->PHONE_MODULES_PATH, $brand_dir));
            $sql = "DELETE FROM endpointman_brand_list WHERE id = '" . $id . "'";
            sql($sql);

			out(_("All Done!"));
        }
		elseif ($force) {
			$sql = "SELECT directory FROM endpointman_brand_list WHERE id = '" . $id . "'";
            $brand_dir = sql($sql, 'getOne');

            $sql = "DELETE FROM endpointman_model_list WHERE brand = '" . $id . "'";
            sql($sql);

            $sql = "DELETE FROM endpointman_product_list WHERE brand = '" . $id . "'";
            sql($sql);

            $sql = "DELETE FROM endpointman_oui_list WHERE brand = '" . $id . "'";
            sql($sql);

            $sql = "DELETE FROM endpointman_brand_list WHERE id = '" . $id . "'";
            sql($sql);

			out(_("Done!"));
        }
		else {
			out(_("Error: Not allowed in repo mode!!"));
        }
    }

	/**
     * Install Firmware for the specified Product Line
     * @param <type> $product_id Product ID
     */
    function install_firmware($product_id) {
    	out(_("Installa frimware... "));

		//TOOD: Review howto create temp directory
        // $temp_directory = $this->epm->buildPath($this->system->sys_get_temp_dir(), "/epm_temp/");
		$temp_directory = $this->epm->PROVISIONER_PATH;
        $sql = 'SELECT endpointman_product_list.*, endpointman_brand_list.directory FROM endpointman_product_list, endpointman_brand_list WHERE endpointman_product_list.brand = endpointman_brand_list.id AND endpointman_product_list.id = ' . $product_id;
        $row = sql($sql, 'getRow', \PDO::FETCH_ASSOC);
        $json_data = $this->file2json($this->epm->buildPath($this->epm->PHONE_MODULES_PATH, 'endpoint', $row['directory'], $row['cfg_dir'], "family_data.json"));

        if ((! isset($json_data['data']['firmware_ver'])) OR (empty($json_data['data']['firmware_ver']))) {
        	out (_("Error: The version of the firmware package is blank!"));
        	return false;
        }

        if ((! isset($json_data['data']['firmware_pkg'])) OR (empty($json_data['data']['firmware_pkg'])) OR ($json_data['data']['firmware_pkg'] == "NULL")) {
        	out (_("Error: The package name of the firmware to be downloaded is Null or blank!"));
        	return false;
        }

        if ($json_data['data']['firmware_ver'] > $row['firmware_vers'])
		{
            if (!file_exists($temp_directory))
			{
                mkdir($temp_directory, 0775, true);
            }
            $md5_xml      	   = $json_data['data']['firmware_md5sum'];
            $firmware_pkg 	   = $json_data['data']['firmware_pkg'];
			$firmware_pkg_path = $this->epm->buildPath($temp_directory, $firmware_pkg);
			$firmware_pkg_url  = $this->epm->buildUrl($this->epm->URL_UPDATE, $row['directory'], $firmware_pkg);

            if (file_exists($firmware_pkg_path)) {
                $md5_pkg = md5_file($firmware_pkg_path);
                if ($md5_xml == $md5_pkg)
				{
					out(_("Skipping download, updated local version..."));
                }
				else
				{
					out(_("Downloading firmware..."));
                    if (! $this->system->download_file_with_progress_bar($firmware_pkg_url, $$firmware_pkg_path)) {
						out(_("Error download frimware package!"));
						return false;
					}
                    $md5_pkg = md5_file($firmware_pkg_path);
                }
            }
			else
			{
				out(_("Downloading firmware..."));
                if (! $this->system->download_file_with_progress_bar($firmware_pkg_url, $firmware_pkg_path))
				{
					out(_("Error download frimware package!"));
					return false;
				}
                $md5_pkg = md5_file($firmware_pkg_path);
            }

			outn(_("Checking MD5sum of Package... "));
            if ($md5_xml == $md5_pkg)
			{
				out(_("Matches!"));

				$path_firmware_dir = $this->epm->buildPath($temp_directory, $row['directory'], $row['cfg_dir'], "firmware");
                if (file_exists($path_firmware_dir))
				{
                    $this->system->rmrf($path_firmware_dir);
                }
                mkdir($path_firmware_dir, 0777, TRUE);

				out(_("Installing Firmware..."));
				//TODO: AÑADIR VALIDACION EXTRACCION CORRECTA
				exec( sprintf("%s -xvf %s -C %s",
						$this->configmod->get("tar_location"),
						$firmware_pkg_path,
						$this->epm->buildPath($temp_directory, $row['directory'], $row['cfg_dir'])
					)
				);
                // exec("tar -xvf " . $temp_directory . $firmware_pkg . " -C " . $temp_directory . $row['directory'] . "/" . $row['cfg_dir']);
                $i = 0;
                foreach (glob($this->epm->buildPath($path_firmware_dir, "*")) as $filename)
				{
                    $file 	  = basename($filename);
                    $list[$i] = $file;
                    if (!@copy($filename, $this->epm->buildPath($this->configmod->get('config_location'), $file)))
					{
                    	out(sprintf(_("- Failed To Copy %s!"), $file));
                        $copy_error = TRUE;
                    }
					elseif ($this->configmod->get('debug'))
					{
						out(sprintf(_("- Copied %s to %s."), $file, $this->configmod->get('config_location')));
                    }
                    $i++;
                }

                $this->system->rmrf($this->epm->buildPath($temp_directory, $row['directory']));
                $list = implode(",", $list);
                $sql = "UPDATE endpointman_product_list SET firmware_vers = '" . $json_data['data']['firmware_ver'] . "', firmware_files = '" . $list . "' WHERE id = " . $row['id'];
                sql($sql);

                if (isset($copy_error)) {
					out(_("Copy Error Detected! Aborting Install!"));
                    $this->remove_firmware($product_id);
					out(_("Info: Please Check Directory/Permissions!"));
                }
				else {
					out(_("Done!"));
                }
            }
			else {
				out(_("Firmware MD5 didn't match!"));
            }
        }
		else {
			out(_("Your Firmware is already up to date."));
        }
    }

	/**
     * Remove firmware from the Hard Drive
     * @param int $id Product ID
     */
    function remove_firmware($id) {
		outn(_("Uninstalla frimware... "));

        $sql = "SELECT firmware_files FROM  endpointman_product_list WHERE  id ='" . $id . "'";
        $files = sql($sql, 'getOne');

        $file_list = explode(",", $files);
        $i = 0;
        foreach ($file_list as $file) {
			if (trim($file) == "") { continue; }
            if (! file_exists($this->configmod->get('config_location') . $file)) { continue; }
			if (! is_file($this->configmod->get('config_location') . $file)) { continue; }
					unlink($this->configmod->get('config_location') . $file);
        }
        $sql = "UPDATE endpointman_product_list SET firmware_files = '', firmware_vers = '' WHERE id = '" . $id . "'";
        sql($sql);

		out(_("Done!"));
    }




		/**
     * Check for new firmware on the servers
     * @param int $id Product ID
     * @return bool True on yes False on no
     */
    function firmware_update_check($id=NULL) {
        $sql = "SELECT * FROM  endpointman_product_list WHERE  id ='" . $id . "'";
        $row = sql($sql, 'getRow', \PDO::FETCH_ASSOC);

        $sql = "SELECT directory FROM  endpointman_brand_list WHERE id ='" . $row['brand'] . "'";
        $brand_directory = sql($sql, 'getOne');

        //config drive unknown!
        if ($row['cfg_dir'] == "") {
            return FALSE;
        } else {
            $temp = $this->file2json($this->epm->buildPath($this->epm->PHONE_MODULES_PATH, 'endpoint', $brand_directory, $row['cfg_dir'], "family_data.json"));
			if (is_array($temp) && ((array_key_exists('data', $temp)) AND (!is_array($temp['data']['firmware_ver'])))) {
                if ($row['firmware_vers'] < $temp['data']['firmware_ver']) {
                    return $temp;
                } else {
                    return FALSE;
                }
            } else {
                return FALSE;
            }
        }
    }

	/**
     * Check to see the status of the firmware locally (installed or not)
     * @param int $id
     * @return string
     */
    function firmware_local_check($id=NULL) {
        $sql = "SELECT * FROM  endpointman_product_list WHERE hidden = 0 AND id ='" . $id . "'";
        $res = sql($sql, 'getAll', \PDO::FETCH_ASSOC);

        if (count(array($res)) > 0) {
            $row = sql($sql, 'getRow', \PDO::FETCH_ASSOC);

            $sql = "SELECT directory FROM  endpointman_brand_list WHERE hidden = 0 AND id ='" . $row['brand'] . "'";
            $brand_directory = sql($sql, 'getOne');

            //config drive unknown!
            if ($row['cfg_dir'] == "") {
				return("nothing");
            } else {
                $temp = $this->file2json($this->epm->buildPath($this->epm->PHONE_MODULES_PATH, 'endpoint', $brand_directory, $row['cfg_dir'], "family_data.json"));
                if ( (isset($temp['data']['firmware_ver'])) AND (! empty ($temp['data']['firmware_ver'])) ) {
                    if ($row['firmware_vers'] == "") {
                        return("install");
                    } else {
                        return("remove");
                    }
                } else {
                    return("nothing");
                }
            }
        } else {
            return("nothing");
        }
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
    		$full_path = $path . $files_data;

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