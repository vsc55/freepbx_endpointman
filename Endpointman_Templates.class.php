<?php
/**
 * Endpoint Manager Object Module - Sec Templates
 *
 * @author Javier Pastor
 * @license MPL / GPLv2 / LGPL
 * @package Provisioner
 */

namespace FreePBX\modules;

class Endpointman_Templates
{
	public $epm;
	public $freepbx;
	public $db;
	public $config;
	public $epm_config;
	public $eda;

	public function __construct($epm)
	{
		$this->epm 		  = $epm;
		$this->freepbx 	  = $epm->freepbx;
		$this->db 	   	  = $epm->freepbx->Database;
		$this->config  	  = $epm->freepbx->Config;
		$this->epm_config = $epm->epm_config;
		$this->eda 		  = $epm->eda;
	}

	public function ajaxRequest($req, &$setting, array $data)
	{
		$allowRequest = array(
			"list_current_template",
			"add_template",
			"del_template",
			'add_products_list',
			"model_clone",

			"custom_config_get_gloabl",
			"custom_config_update_gloabl",
			"custom_config_reset_gloabl",
			"list_files_edit"
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
		$retarr = "";
		$txt 	= [];

		if (empty($data) || !is_array($data))
		{
			$retarr = array(
				"status"  => false,
				"message" => _("Empty data received or data is not foromatted correctly!")
			);
		}
		else
		{
			$command_allow  = true;
			$command 		= $data['command'] 	  ?? '';
			$module_tab		= $data['module_tab'] ?? '';

			switch($module_tab)
			{
				case "manager":
					switch ($command)
					{
						case "list_current_template":
							$retarr = $this->epm_templates_list_current_templates();
						break;
							
						case "model_clone":
							$retarr = $this->epm_templates_model_clone($data);
						break;
							
						case "add_template":
							$retarr = $this->epm_templates_add_template($data);
						break;
							
						case "del_template":
							$retarr = $this->epm_templates_del_template($data);
						break;
						
						case 'add_products_list':
							$retarr = $this->epm_templates_add_products_list();
						break;
						
						case 'add_template_list_models':
						break;
	
						default:
							$command_allow = false;
					}
				break;

				case "edit":
					switch ($command)
					{
						case "custom_config_get_gloabl":
							$retarr = $this->epm_template_custom_config_get_global();
						break;
						
						case "custom_config_update_gloabl":
							$retarr = $this->epm_template_custom_config_update_global();
						break;
						
						case "custom_config_reset_gloabl":
							$retarr = $this->epm_template_custom_config_reset_global();
						break;
							
						case "list_files_edit":
						/*
							$return = array();
							$return[] = array('value' => 'va11', 'txt' => 'txt1', 'select' => "OFF");
							$return[] = array('value' => 'va12', 'txt' => 'txt2', 'select' => "ON");
							$return[] = array('value' => 'va13', 'txt' => 'txt3', 'select' => "OFF");
						*/
							return $this->edit_template_display_files($_REQUEST['idsel'],$_REQUEST['custom'], $_REQUEST['namefile']);
						break;
							
						default:
							$command_allow = false;
					}
				break;

				default:
					$retarr = array(
						"status"  => false,
						"message" => sprintf(_("Tab '%s' not valid!"), $module_tab)
					);
			}

			if (! $command_allow)
			{
				$retarr = array(
					"status"  => false,
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
	
	public function doConfigPageInit($module_tab = "", $command = "") { }
	
	public function myShowPage(array &$pagedata, array $data)
	{
		if(empty($pagedata))
		{
			$pagedata['manager'] = array(
				"name" => _("Current Templates"),
				"page" => '/views/epm_templates_manager.page.php'
			);
			$pagedata['edit'] = array(
				"name" => _("Template Editor"),
				"page" => '/views/epm_templates_editor.page.php'
			);
		}
	
	}

	public function getRightNav($request, $params = array())
	{
		$data_return = "";
		if(isset($request['subpage']) && $request['subpage'] == "edit")
		{
			$data_return = load_view(__DIR__."/views/epm_templates/editor.views.rnav.php", $params);
		}
		return $data_return;
	}
	
	public function getActionBar($request)
	{
		$buttons = array();
        switch(strtolower($request['subpage']))
		{
            case 'edit':
                $buttons = array(
					'delete' => array(
                        'name' 	 => 'delete',
                        'id' 	 => 'delete',
                        'value'  => _('Delete'),
                        'hidden' => ''
                    ),
					'save' => array(
                        'name' 	 => 'submit',
                        'id' 	 => 'save',
                        'value'  => _('Save'),
                        'hidden' => ''
                    )
                );
				
				if(empty($request['idsel']) && empty($request['custom']))
				{
					$buttons = "";
				}
            	break;
        }
        return $buttons;
	}
	
	public function showPage(array &$data)
	{
		// $data['showpage'] = $this->myShowPage();
		$tabs = array(
			'manager' => array(
				"name" => _("Current Templates"),
				"page" => '/views/epm_templates_manager.page.php'
			),
			'edit' => array(
				"name" => _("Template Editor"),
				"page" => '/views/epm_templates_editor.page.php'
			),
		);

		$data['subpage'] = $data['request']['subpage'] ?? '';
		if (! in_array($data['subpage'], array_keys($tabs)))
		{
			$data['subpage'] = 'manager';
		}

		$data['command']  = $data['request']['command'] ?? '';
		$data['id']  	  = $data['request']['id'] ?? '';
		$data['custom']	  = $data['request']['custom'] ?? '';

		$data['product_list']  = sql("SELECT * FROM endpointman_product_list WHERE id > 0", 'getAll', \PDO::FETCH_ASSOC);
		$data['mac_list']      = sql("SELECT * FROM endpointman_mac_list", 'getAll', \PDO::FETCH_ASSOC);



		$data['main']['warning']['no_modules_install']			= (empty($data['product_list']) && empty($data['mac_list']));
		$data['main']['warning']['update_from_ver_previous_2']	= empty($data['product_list']);

		

		foreach($tabs as $key => &$page)
		{
			if ($data['subpage'] != $key)
			{
				continue;
			}

			$data_tab = array();
			switch($key)
			{
				case 'manager':
					$data_tab['config']['url_grid'] = "ajax.php?module=endpointman&amp;module_sec=epm_templates&amp;module_tab=manager&amp;command=list_current_template";
				break;

				case 'edit':
					$data_tab['custom']		  = $data['request']['custom'] ?? '';
					$data_tab['id_template']  = $data['request']['idsel'] ?? null;
					$data_tab['missing_data'] = $data_tab['custom'] == "" || empty($data_tab['id_template']);

					// $data_tab['edit_template_display'] = $this->epm_templates->edit_template_display($data_tab['idsel'], $data['custom']);
				break;
			}
			$data_tab = array_merge($data, $data_tab);

			$page['content'] = load_view($this->epm->system->buildPath(__DIR__, $page['page']), $data_tab);
		}
		$data['tabs'] = $tabs;
		unset($tabs);
		
	}
	
	
	
	
	public function epm_template_custom_config_get_global()
	{
		if (! isset($_REQUEST['custom'])) {
			$retarr = array("status" => false, "message" => _("No send Custom Value!"));
		}
		elseif (! isset($_REQUEST['tid'])) {
			$retarr = array("status" => false, "message" => _("No send TID!"));
		}
		elseif (! is_numeric($_REQUEST['tid'])) {
			$retarr = array("status" => false, "message" => _("TID is not number!"));
		}
		else 
		{
			$dget['custom'] = $_REQUEST['custom'];
			$dget['tid'] = $_REQUEST['tid'];
			
			if($dget['custom'] == 0) {
				//This is a group template
		        $sql = 'SELECT global_settings_override FROM endpointman_template_list WHERE id = '.$dget['tid'];
			} else {
				//This is an individual template
		        $sql = 'SELECT global_settings_override FROM endpointman_mac_list WHERE id = '.$dget['tid'];;
			}
			$settings = sql($sql, 'getOne');

			if ((isset($settings)) and (strlen($settings) > 0)) {
				$settings = unserialize($settings);
				//$settings['tz'] = FreePBX::Endpointman()->listTZ(FreePBX::Endpointman()->epm->getConfig("tz"));
			} 
			else {
				$settings['srvip'] 			 = ""; //$this->epm->getConfig("srvip");
				$settings['ntp'] 			 = ""; //$this->epm->getConfig("ntp");
				$settings['config_location'] = ""; //$this->epm->getConfig("config_location");
				$settings['tz'] 		 	 = $this->epm->getConfig("tz");
				$settings['server_type'] 	 = $this->epm->getConfig("server_type");
			}
    		
			$retarr = array("status" => true, "settings" => $settings, "message" => _("Global Config Read OK!"));
			unset($dget);
		}
		return $retarr;
	}
	
	
	public function epm_template_custom_config_update_global ()
	{
		if (! isset($_REQUEST['custom'])) {
			$retarr = array("status" => false, "message" => _("No send Custom Value!"));
		}
		elseif (! isset($_REQUEST['tid'])) {
			$retarr = array("status" => false, "message" => _("No send TID!"));
		}
		elseif (! is_numeric($_REQUEST['tid'])) {
			$retarr = array("status" => false, "message" => _("TID is not number!"));
		}
		else 
		{
			$dget['custom'] = $_REQUEST['custom'];
			$dget['tid'] = $_REQUEST['tid'];
			
			
			$_REQUEST['srvip'] = trim($_REQUEST['srvip']);  #trim whitespace from IP address
			$_REQUEST['config_loc'] = trim($_REQUEST['config_loc']);  #trim whitespace from Config Location
	
			$settings_warning = "";
			if (strlen($_REQUEST['config_loc']) > 0) {
				//No trailing slash. Help the user out and add one :-)
				if($_REQUEST['config_loc'][strlen($_REQUEST['config_loc'])-1] != "/") {
					$_REQUEST['config_loc'] = $_REQUEST['config_loc'] ."/";
				}
				
				if((isset($_REQUEST['config_loc'])) AND ($_REQUEST['config_loc'] != "")) {
					if((file_exists($_REQUEST['config_loc'])) AND (is_dir($_REQUEST['config_loc']))) {
						if(is_writable($_REQUEST['config_loc'])) {
							$_REQUEST['config_loc'] = $_REQUEST['config_loc'];
						} else {
							$settings_warning = _("Directory Not Writable!");
							$_REQUEST['config_loc'] = $this->epm->getConfig('config_location');
						}
					} else {
						$settings_warning = _("Not a Vaild Directory");
						$_REQUEST['config_loc'] = $this->epm->getConfig('config_location');
					}
				} else {
					$settings_warning = _("No Configuration Location Defined!");
					$_REQUEST['config_loc'] = $this->epm->getConfig('config_location');
				}
			}
			
			$settings['config_location'] = $_REQUEST['config_loc'];
			$settings['server_type'] 	 = $_REQUEST['server_type'] ?? "";	//REVISAR NO ESTABA ANTES
			$settings['srvip'] 			 = $_REQUEST['srvip'] ?? "";
			$settings['ntp'] 			 = $_REQUEST['ntp_server'] ?? "";
			$settings['tz'] 			 = $_REQUEST['tz'] ?? "";
			$settings_ser 				 = serialize($settings);
			unset($settings);
			
			if($dget['custom'] == 0) {
				//This is a group template
				$sql = "UPDATE endpointman_template_list SET global_settings_override = '".addslashes($settings_ser)."' WHERE id = ".$dget['tid'];
			} else {
				//This is an individual template
				$sql = "UPDATE endpointman_mac_list SET global_settings_override = '".addslashes($settings_ser)."' WHERE id = ".$dget['tid'];
			}
			unset($settings_ser);
			sql($sql);
			
			if (strlen($settings_warning) > 0) { $settings_warning = " ".$settings_warning; }
			$retarr = array("status" => true, "message" => _("Updated!").$settings_warning);
			unset($dget);
		}
		return $retarr;
	}
	
	
	public function epm_template_custom_config_reset_global()
	{
		if (! isset($_REQUEST['custom'])) {
			$retarr = array("status" => false, "message" => _("No send Custom Value!"));
		}
		elseif (! isset($_REQUEST['tid'])) {
			$retarr = array("status" => false, "message" => _("No send TID!"));
		}
		elseif (! is_numeric($_REQUEST['tid'])) {
			$retarr = array("status" => false, "message" => _("TID is not number!"));
		}
		else 
		{
			$dget['custom'] = $_REQUEST['custom'];
			$dget['tid'] = $_REQUEST['tid'];
			
			if($dget['custom'] == 0) {
				//This is a group template
				$sql = "UPDATE endpointman_template_list SET global_settings_override = NULL WHERE id = ".$dget['tid'];
			} else {
				//This is an individual template
				$sql = "UPDATE endpointman_mac_list SET global_settings_override = NULL WHERE id = ".$dget['tid'];
			}
			sql($sql);
			
			$retarr = array("status" => true, "message" => _("Globals Reset to Default!"));
			unset($dget);
		}
		return $retarr;
	}
	
	
	
	
	/**** FUNCIONES SEC MODULO "epm_template\manager" ****/
	public function epm_templates_model_clone (array $data) 
	{
		$request 		  = $data['request'] ?? [];
		$request_args 	  = array("product_select");
		$request_args_int = array("product_select");
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

		$product_select = $request['product_select'];

		if ($product_select < 1)
		{
			$retarr = array("status" => false, "message" => _("Product ID is not valid, the value is less than 1!"));
		}
		else
		{
			$out = [];

			$out[] = array(
				'id'   		=> '',
				'name' 		=> _("None"),
				'is_select' => true,
			);
			$sql = sprintf("SELECT endpointman_model_list.id, endpointman_model_list.model as model FROM endpointman_model_list, endpointman_product_list WHERE endpointman_product_list.id = endpointman_model_list.product_id AND endpointman_model_list.enabled = 1 AND endpointman_model_list.hidden = 0 AND product_id = '%s'", $product_select);
			$result = sql($sql,'getAll', \PDO::FETCH_ASSOC);
			foreach($result as $row)
			{
				$out[] = array(
					'id' 		=> $row['id'],
					'name'  	=> $row['model'],
					'is_select' => false,
				);
			}
			$retarr = array(
				"status"  => true,
				"message" => _("Generate list Ok!"),
				"options" => $out,
				"count"   => count($out),
			);
		}
		return $retarr;
	}

	public function epm_templates_add_template (array $data)
	{
		$request 		  = $data['request'] ?? [];
		$data_new 		  = $request['new_template'] ?? [];
		$request_args 	  = array("name", "product", "model");
		$request_args_int = array("product", "model");
		$args_check 	  = $this->epm->system->check_request_args($data_new, $request_args, $request_args_int);

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

		$template_name  = $data_new['name'];
		$product_select = $data_new['product'];
		$model_select   = $data_new['model'];

		if ($product_select < 1)
		{
			$retarr = array("status" => false, "message" => _("Product ID is not valid, the value is less than 1!"));
		}
		else if ($model_select < 1)
		{
			$retarr = array("status" => false, "message" => _("Model ID is not valid, the value is less than 1!"));
		}

		$sql = "INSERT INTO endpointman_template_list (product_id, name, model_id) VALUES (?, ?, ?)";
		$q = $this->db->prepare($sql);
		$q->execute(array($product_select, addslashes($template_name), $model_select));
		$newid = $this->db->lastInsertId();
		//$this->edit_template_display($newid,0);
		
		$retarr = array(
			"status"		 => true,
			"message"		 => _("New Template Created Successfully!"),
			"redirect"		 => sprintf("config.php?display=%s&subpage=edit&custom=0&idsel=%s", $request['module_sec'], $newid),
			"redirect_delay" => 500,
			"newid"			=> $newid
		);
		return $retarr;
	}

	public function epm_templates_del_template(array $data)
	{
		$request 		  = $data['request'] ?? [];
		$request_args 	  = array("id_template");
		$request_args_int = array("id_template");
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

		$id_template = $request['id_template'];

		if ($id_template < 1)
		{
			return array("status" => false, "message" => _("Template ID is not valid, the value is less than 1!"));
		}

		$sql = sprintf("DELETE FROM endpointman_template_list WHERE id = %s", $id_template);
		sql($sql);
		$sql = sprintf("UPDATE endpointman_mac_list SET template_id = 0 WHERE template_id = %s", $id_template);
		sql($sql);
			
		return array(
			"status"  => true,
			"message" => _("Delete Template Successfully!")
		);
	}
	
	public function epm_templates_list_current_templates ()
	{
	
		$sql = 'SELECT 
					endpointman_template_list.*,
					endpointman_product_list.short_name as model_class,
					endpointman_model_list.model as model_clone,
					endpointman_model_list.enabled
						FROM
							endpointman_template_list,
							endpointman_model_list,
							endpointman_product_list
								WHERE
									endpointman_model_list.hidden = 0 AND
									endpointman_template_list.model_id = endpointman_model_list.id AND
									endpointman_template_list.product_id = endpointman_product_list.id
				';

		$template_list = sql($sql, 'getAll', \PDO::FETCH_ASSOC);

		$row_out = array();
		foreach($template_list as $row)
		{
			$row['custom'] = 0;

			// TODO: Check if this is needed
			// if(!$row['enabled']) {
			// 	$row['model_clone'] = $row['model_clone'];
			// }
			$row_out[] = $row;
		}

		$sql = 'SELECT
					endpointman_mac_list.mac,
					endpointman_mac_list.id,
					endpointman_mac_list.model,
					endpointman_model_list.model as model_clone,
					endpointman_product_list.short_name as model_class
						FROM
							endpointman_mac_list,
							endpointman_model_list,
							endpointman_product_list
								WHERE
									endpointman_product_list.id = endpointman_model_list.product_id AND
									endpointman_mac_list.global_custom_cfg_data IS NOT NULL AND
									endpointman_model_list.id = endpointman_mac_list.model AND
									endpointman_mac_list.template_id = 0
				';

		$template_list = sql($sql, 'getAll', \PDO::FETCH_ASSOC);
		foreach($template_list as $row)
		{
			$sql		 = sprintf('SELECT description, line FROM endpointman_line_list WHERE mac_id = %s ORDER BY line ASC', $row['id']);
			$line_list	 = sql($sql, 'getAll', \PDO::FETCH_ASSOC);

			$descriptions = array_map(function($line_row) { return $line_row['description']; }, $line_list);
			$description  = implode(', ', $descriptions);

			$row['custom'] 		= 1;
			$row['name'] 		= $row['mac'];
			$row['description'] = $description;
			$row_out[]			= $row;
		}
		
		/*
		//$sql = 'SELECT endpointman_oui_list.id, endpointman_oui_list.oui , endpointman_brand_list.name, endpointman_oui_list.custom FROM endpointman_oui_list , endpointman_brand_list WHERE endpointman_oui_list.brand = endpointman_brand_list.id ORDER BY endpointman_oui_list.oui ASC';
		$sql = 'SELECT T1.id, T1.oui, T2.name, T1.custom FROM endpointman_oui_list as T1 , endpointman_brand_list as T2 WHERE T1.brand = T2.id ORDER BY T1.oui ASC';
		$data = sql($sql, 'getAll', \PDO::FETCH_ASSOC);
		$ret = array();
		foreach ($data as $item) {
			$ret[] = array('id' => $item['id'], 'oui' => $item['oui'], 'brand' => $item['name'], 'custom' => $item['custom']);
		}
		*/
		return $row_out;
	}
	
	public function epm_templates_add_products_list()
	{
		$products = [];
		$products[] = array(
			'id'   		=> '',
			'name' 		=> _("None"),
			'is_select' => true,
		);

		foreach($this->epm->packagesdb->getProductsByModelsEnabled(false, false) as $row)
		{
			$products[] = array(
				'id'   		=> $row->getId(),
				'name' 		=> $row->getShortName(),
				'is_select' => false,
			);
		}
		
		return array(
			"status"  => true,
			"message" => "OK",
			"options" => $products,
			"count"   => count($products)
		);
	}
	
	
	
	
	



	
	
	function edit_template_display_files($id, $custom, $namefile = "")
	{
    	if ($custom == 0) {
    		$sql = "SELECT model_id FROM endpointman_template_list WHERE id=" . $id;
    	} else {
    		$sql = "SELECT model FROM endpointman_mac_list WHERE id=" . $id;
    	}
    	$model_id = sql($sql, 'getOne');
    	if (!$this->epm_config->sync_model($model_id)) {
    		die("unable to sync local template files - TYPE:" . $custom);
    	}
		
    	$dReturn = array();
		if ($custom == 0) {
			$sql = "SELECT endpointman_model_list.max_lines, endpointman_model_list.model as model_name, endpointman_template_list.global_custom_cfg_data,  endpointman_product_list.config_files, endpointman_product_list.short_name, endpointman_product_list.id as product_id, endpointman_model_list.template_data, endpointman_model_list.id as model_id, endpointman_template_list.* FROM endpointman_product_list, endpointman_model_list, endpointman_template_list WHERE endpointman_product_list.id = endpointman_template_list.product_id AND endpointman_template_list.model_id = endpointman_model_list.id AND endpointman_template_list.id = " . $id;
		} else {
			$sql = "SELECT endpointman_model_list.max_lines, endpointman_model_list.model as model_name, endpointman_mac_list.global_custom_cfg_data, endpointman_product_list.config_files, endpointman_mac_list.*, endpointman_line_list.*, endpointman_model_list.id as model_id, endpointman_model_list.template_data, endpointman_product_list.id as product_id, endpointman_product_list.short_name, endpointman_product_list.cfg_dir, endpointman_brand_list.directory FROM endpointman_brand_list, endpointman_mac_list, endpointman_model_list, endpointman_product_list, endpointman_line_list WHERE endpointman_mac_list.id=" . $id . " AND endpointman_mac_list.id = endpointman_line_list.mac_id AND endpointman_mac_list.model = endpointman_model_list.id AND endpointman_model_list.brand = endpointman_brand_list.id AND endpointman_model_list.product_id = endpointman_product_list.id";
		}
		$row = sql($sql, 'getRow', \PDO::FETCH_ASSOC);
		
		
		if ($row['config_files_override'] == "") {
			$config_files_saved = "";
		} else {
			$config_files_saved = unserialize($row['config_files_override']);
		}
		$config_files_list = explode(",", $row['config_files']);
		asort($config_files_list);
		
		$i = 0;
		$b = 0;
		$alt_configs = array();
		$only_configs = array();
		foreach ($config_files_list as $files) 
		{
			if ($namefile != $files)  { continue; }
			
			$only_configs[$b]['id'] = $b;
			$only_configs[$b]['id_d'] = $id;
			$only_configs[$b]['id_p'] = $row['product_id'];
			$only_configs[$b]['name'] = $files;
			$only_configs[$b]['select'] = "ON";
			
			$sql = "SELECT * FROM  endpointman_custom_configs WHERE product_id = '" . $row['product_id'] . "' AND original_name = '" . $files . "'";
			$alt_configs_list = sql($sql, 'getAll', \PDO::FETCH_ASSOC );
			
			if ( count(array($alt_configs_list)) > 0) 
			{
				$files = str_replace(".", "_", $files);
				foreach ($alt_configs_list as $ccf) 
				{
					$cf_key = $files;
					if ((isset($config_files_saved[$cf_key])) AND (is_array($config_files_saved)) AND ($config_files_saved[$cf_key] == $ccf['id'])) {
						$alt_configs[$i]['select'] = 'ON';
						$only_configs[$b]['select'] = "OFF";
					}
					else {
						$alt_configs[$i]['select'] = 'OFF';
					}
					$alt_configs[$i]['id'] = $ccf['id'];
					$alt_configs[$i]['id_p'] = $row['product_id'];
					$alt_configs[$i]['name'] = $ccf['name'];
					$alt_configs[$i]['name_original'] = $files;
					
					$i++;
				}
			}
		}
		
		$dReturn['only_configs'] = $only_configs;
		$dReturn['alt_configs'] = $alt_configs;
		
    	return $dReturn;		
	}
	
	function edit_template_display_files_list($id, $custom)
	{
    	if ($custom == 0) {
    		$sql = "SELECT model_id FROM endpointman_template_list WHERE id=" . $id;
    	} else {
    		$sql = "SELECT model FROM endpointman_mac_list WHERE id=" . $id;
    	}
    	$model_id = sql($sql, 'getOne');


		//TODO: He comentado esto ya que sync-model ya no existe!!!!!!!
    	// if (!$this->epm_config->sync_model($model_id)) {
    	// 	die("unable to sync local template files - TYPE:" . $custom);
    	// }
		

		if ($custom == 0)
		{
			$sql = sprintf("SELECT endpointman_model_list.max_lines, endpointman_model_list.model as model_name, endpointman_template_list.global_custom_cfg_data,  endpointman_product_list.config_files, endpointman_product_list.short_name, endpointman_product_list.id as product_id, endpointman_model_list.template_data, endpointman_model_list.id as model_id, endpointman_template_list.* FROM endpointman_product_list, endpointman_model_list, endpointman_template_list WHERE endpointman_product_list.id = endpointman_template_list.product_id AND endpointman_template_list.model_id = endpointman_model_list.id AND endpointman_template_list.id = %s", $id);
		}
		else
		{
			$sql = sprintf("SELECT endpointman_model_list.max_lines, endpointman_model_list.model as model_name, endpointman_mac_list.global_custom_cfg_data, endpointman_product_list.config_files, endpointman_mac_list.*, endpointman_line_list.*, endpointman_model_list.id as model_id, endpointman_model_list.template_data, endpointman_product_list.id as product_id, endpointman_product_list.short_name, endpointman_product_list.cfg_dir, endpointman_brand_list.directory FROM endpointman_brand_list, endpointman_mac_list, endpointman_model_list, endpointman_product_list, endpointman_line_list WHERE endpointman_mac_list.id = %s AND endpointman_mac_list.id = endpointman_line_list.mac_id AND endpointman_mac_list.model = endpointman_model_list.id AND endpointman_model_list.brand = endpointman_brand_list.id AND endpointman_model_list.product_id = endpointman_product_list.id", $id);
		}

		$row = sql($sql, 'getRow', \PDO::FETCH_ASSOC);
		$config_files_list = explode(",", $row['config_files']);
		asort($config_files_list);
		
		$i = 0;
		$b = 0;
		$dReturn = array();
		foreach ($config_files_list as $files) 
		{
			$dReturn[$b]['id'] = $b;
			$dReturn[$b]['id_d'] = $id;
			$dReturn[$b]['id_p'] = $row['product_id'];
			$dReturn[$b]['name'] = $files;
			$b++;
		}
		unset($config_files_list);

    	return $dReturn;		
	}
	
	
	
	
	
	/**
     * Custom Means specific to that MAC
     * id is either the mac ID (not address) or the template ID
     * @param integer $id
     * @param integer $custom
     */
    function edit_template_display($id, $custom) {
    	//endpointman_flush_buffers();
    	
    	$alt_configs = NULL;
    
    	if ($custom == 0)
		{
    		$sql = sprintf("SELECT model_id FROM endpointman_template_list WHERE id = %s", $id);
    	}
		else
		{
    		$sql = sprintf("SELECT model FROM endpointman_mac_list WHERE id = %s", $id);
    	}
    	$model_id = sql($sql, 'getOne');
    
		//TODO: He comentado esto ya que sync-model ya no existe!!!!!!!
    	//Make sure the model data from the local confg files are stored in the database and vice-versa. Serious errors will occur if the database is not in sync with the local file
    	// if (!$this->epm_config->sync_model($model_id))
		// {
    	// 	die("unable to sync local template files - TYPE:" . $custom);
    	// }
   
    	$dReturn = array();

    	
		//Determine if we are dealing with a general template or a specific [for that phone only] template (custom =0 means general)
		if ($custom == 0) {
			$sql = "SELECT endpointman_model_list.max_lines, endpointman_model_list.model as model_name, endpointman_template_list.global_custom_cfg_data,  endpointman_product_list.config_files, endpointman_product_list.short_name, endpointman_product_list.id as product_id, endpointman_model_list.template_data, endpointman_model_list.id as model_id, endpointman_template_list.* FROM endpointman_product_list, endpointman_model_list, endpointman_template_list WHERE endpointman_product_list.id = endpointman_template_list.product_id AND endpointman_template_list.model_id = endpointman_model_list.id AND endpointman_template_list.id = " . $id;
		} else {
			$sql = "SELECT endpointman_model_list.max_lines, endpointman_model_list.model as model_name, endpointman_mac_list.global_custom_cfg_data, endpointman_product_list.config_files, endpointman_mac_list.*, endpointman_line_list.*, endpointman_model_list.id as model_id, endpointman_model_list.template_data, endpointman_product_list.id as product_id, endpointman_product_list.short_name, endpointman_product_list.cfg_dir, endpointman_brand_list.directory FROM endpointman_brand_list, endpointman_mac_list, endpointman_model_list, endpointman_product_list, endpointman_line_list WHERE endpointman_mac_list.id=" . $id . " AND endpointman_mac_list.id = endpointman_line_list.mac_id AND endpointman_mac_list.model = endpointman_model_list.id AND endpointman_model_list.brand = endpointman_brand_list.id AND endpointman_model_list.product_id = endpointman_product_list.id";
		}
		$row = sql($sql, 'getRow', \PDO::FETCH_ASSOC);
		
		
		$dReturn['template_editor_display'] = 1;
		
		//Let the template system know if we are working with a general template or a specific [for that phone only] template
		$dReturn['custom'] = $custom;
    	 if ($custom) 
    	 {
			$dReturn['ext'] = $row['ext'];
    	 } 
    	 else 
    	 {
    	 	$dReturn['template_name'] = $row['name'];
    	 }
		$dReturn['product'] = $row['short_name'];
		$dReturn['model'] = $row['model_name'];

		if ($ma = $this->models_available($row['model_id'], NULL, $row['product_id'])) {
			$dReturn['models_ava'] = $ma;
		}

		if (isset($_REQUEST['maxlines'])) {
			$areas = $this->areaAvailable($row['model_id'], $_REQUEST['maxlines']);
		} else {
			$areas = $this->areaAvailable($row['model_id'], 1);
		}
		$dReturn['area_ava'] = $areas;
    	
		//Start the display of the html file in the product folder
		if ($row['config_files_override'] == "") {
			$config_files_saved = "";
		} else {
			$config_files_saved = unserialize($row['config_files_override']);
		}
		$config_files_list = explode(",", $row['config_files']);
		asort($config_files_list);
		
		$alt = 0;
		$i = 0;
		$b = 0;
		$only_configs = array();
		foreach ($config_files_list as $files) {
			$sql = "SELECT * FROM  endpointman_custom_configs WHERE product_id = '" . $row['product_id'] . "' AND original_name = '" . $files . "'";
			$alt_configs_list_count = sql($sql, 'getAll', \PDO::FETCH_ASSOC );
			if (! empty($alt_configs_list_count)) {
				$alt_configs_list = sql($sql, 'getAll', \PDO::FETCH_ASSOC);
				$alt_configs[$i]['name'] = $files;
				$alt_configs[$i]['id_p'] = $row['product_id'];
				$files = str_replace(".", "_", $files);
				$h = 0;
				foreach ($alt_configs_list as $ccf) {
					$alt_configs[$i]['list'][$h]['id'] = $ccf['id'];
					$cf_key = $files;
					if ((isset($config_files_saved[$cf_key])) AND (is_array($config_files_saved)) AND ($config_files_saved[$cf_key] == $ccf['id'])) {
						$alt_configs[$i]['list'][$h]['selected'] = 'selected';
					}
					$alt_configs[$i]['list'][$h]['id'] = $h;
					$alt_configs[$i]['list'][$h]['name'] = $ccf['name'];
					$h++;
				}
				$alt = 1;
			} 
			else {
				$only_configs[$b]['id'] = $b;
				$only_configs[$b]['id_d'] = $id;
				$only_configs[$b]['id_p'] = $row['product_id'];
				$only_configs[$b]['name'] = $files;
				$b++;
			}
			$i++;
		}
		
		$dReturn['only_configs'] = $only_configs;
		$dReturn['alt_configs'] = $alt_configs;
		$dReturn['alt'] = $alt;
		
		if (!isset($_REQUEST['maxlines'])) {
			$maxlines = 1;
		} else {
			$maxlines = $_REQUEST['maxlines'];
		}
		if ($row['template_data'] != "") {
			$out = $this->generate_gui_html($row['template_data'], $row['global_custom_cfg_data'], TRUE, NULL, $maxlines);
		} else {
			$out = "No Template Data has been defined for this Product<br />";
		}
		
		$dReturn['template_editor'] = $out;
		$dReturn['hidden_id'] = $row['id'];
		$dReturn['hidden_custom'] = $custom;

    	return $dReturn;
    }
	
	
	
	
	 /**
     *
     * @param integer $model model ID
     * @param integer $brand brand ID
     * @param integer $product product ID
     * @return array
     */
    function models_available($model=NULL, $brand=NULL, $product=NULL) {
    
    	if ((!isset($oui)) && (!isset($brand)) && (!isset($model))) {
    		$result1 = $this->eda->all_models();
    	} elseif ((isset($brand)) && ($brand != 0)) {
    		$result1 = $this->eda->all_models_by_brand($brand);
    	} elseif ((isset($product)) && ($product != 0)) {
    		$result1 = $this->eda->all_models_by_product($product);
    	} else {
    		$result1 = $this->eda->all_models();
    	}
    
    	$i = 1;
    	foreach ($result1 as $row) {
    		if ($row['id'] == $model) {
    			$temp[$i]['value'] = $row['id'];
    			$temp[$i]['text'] = $row['model'];
    			$temp[$i]['selected'] = 'selected';
    		} else {
    			$temp[$i]['value'] = $row['id'];
    			$temp[$i]['text'] = $row['model'];
    			$temp[$i]['selected'] = 0;
    		}
    		$i++;
    	}
    
    	if (!isset($temp)) {
			//TODO: Esto seguro que peta, pero si no lo hace hay que ver que hace!!!! configmod es obsoleto ahora es epm->isConfigExist
    		if (! $this->configmod->isExiste('new')) {
    			$this->error['modelsAvailable'] = "You need to enable at least ONE model";
    		}
    		return(FALSE);
    	} else {
    		return($temp);
    	}
    }
	
	
	
	function areaAvailable($model, $area=NULL) {
    	$sql = "SELECT max_lines FROM endpointman_model_list WHERE id = '" . $model . "'";
    	$count = sql($sql, 'getOne');
    
    	for ($z = 0; $z < $count; $z++) {
    		$result[$z]['id'] = $z + 1;
    		$result[$z]['model'] = $z + 1;
    	}
    
    	$i = 1;
    	foreach ($result as $row) {
    		if ($row['id'] == $area) {
    			$temp[$i]['value'] = $row['id'];
    			$temp[$i]['text'] = $row['model'];
    			$temp[$i]['selected'] = 'selected';
    		} else {
    			$temp[$i]['value'] = $row['id'];
    			$temp[$i]['text'] = $row['model'];
    			$temp[$i]['selected'] = 0;
    		}
    		$i++;
    	}
    
    	return($temp);
    }
	
	/**
     * Generates the Visual Display for the end user
     * @param <type> $cfg_data
     * @param <type> $custom_cfg_data
     * @param <type> $admin
     * @param <type> $user_cfg_data
     * @return <type>
     */
    function generate_gui_html($cfg_data, $custom_cfg_data=NULL, $admin=FALSE, $user_cfg_data=NULL, $max_lines=3, $ext=NULL) {
    	//take the data out of the database and turn it back into an array for use
    	$cfg_data = unserialize($cfg_data);
    	$template_type = 'GENERAL';
    	//Check to see if there is a custom template for this phone already listed in the endpointman_mac_list database
    	if (!empty($custom_cfg_data)) {
    		$custom_cfg_data = unserialize($custom_cfg_data);
    		if (array_key_exists('data', $custom_cfg_data)) {
    			if (array_key_exists('ari', $custom_cfg_data)) {
    				$extra_data = $custom_cfg_data['ari'];
    			} else {
    				$template_type = 'GLOBAL';
    				$extra_data = $custom_cfg_data['freepbx'];
    			}
    			$custom_cfg_data = $custom_cfg_data['data'];
    		} else {
    			$extra_data = array();
    		}
    	} else {
    		$custom_cfg_data = array();
    		$extra_data = array();
    	}
    	if (isset($user_cfg_data)) {
    		$user_cfg_data = unserialize($user_cfg_data);
    	}
    
    	$template_variables_array = array();
    	$group_count = 0;
    	$variables_count = 0;
    
    	foreach ($cfg_data['data'] as $cats_name => $cats) {
    		if ($admin) {
    			$group_count++;
    			$template_variables_array[$group_count]['title'] = $cats_name;
    		} else {
    			//Group all ARI stuff into one tab
    			$template_variables_array[$group_count]['title'] = "Your Phone Settings";
    		}
    		foreach ($cats as $subcat_name => $subcats) {
    			foreach ($subcats as $item_var => $config_options) {
    				if (preg_match('/(.*)\|(.*)/i', $item_var, $matches)) {
    					$type = $matches[1];
    					$variable = $matches[2];
    				} else {
    					die('no matches!');
    				}
    				if ($admin) {
    					//Administration View Only
    					switch ($type) {
    						case "lineloop":
    							//line|1|display_name
    							foreach ($config_options as $var_name => $var_items) {
    								$lcount = isset($var_items['line_count']) ? $var_items['line_count'] : $lcount;
    								$key = "line|" . $lcount . "|" . $var_name;
    								$items[$variables_count] = $items;
    								$template_variables_array[$group_count]['data'][$variables_count] = $this->generate_form_data($variables_count, $var_items, $key, $custom_cfg_data, $admin, $user_cfg_data, $extra_data, $template_type);
    								$template_variables_array[$group_count]['data'][$variables_count]['looping'] = TRUE;
    								$variables_count++;
    							}
    
    							if ($lcount <= $max_lines) {
    								$template_variables_array[$group_count]['title'] = "Line Options for Line " . $lcount;
    								$group_count++;
    							} else {
    								unset($template_variables_array[$group_count]);
    							}
    
    							continue 2;
    						case "loop":
    							foreach ($config_options as $var_name => $var_items) {
    								//loop|remotephonebook_url_0
    								$tv = explode('_', $variable);
    								$key = "loop|" . $tv[0] . "_" . $var_name . (isset($var_items['loop_count']) ? "_" . $var_items['loop_count'] : '');
    								$items[$variables_count] = $var_items;
    								$template_variables_array[$group_count]['data'][$variables_count] = $this->generate_form_data($variables_count, $var_items, $key, $custom_cfg_data, $admin, $user_cfg_data, $extra_data, $template_type);
    								$template_variables_array[$group_count]['data'][$variables_count]['looping'] = TRUE;
    								$variables_count++;
    							}
    							continue 2;
    					}
    				} else {
    					//ARI View Only
    					switch ($type) {
    						case "loop_line_options":
    							//$a is the line number
    							$sql = "SELECT line FROM endpointman_line_list WHERE  ext = " . $ext;
    							$a = $this->eda->sql($sql, 'getOne');
    							//TODO: fix this area
    							$template_variables_array[$group_count]['data'][$variables_count]['type'] = "break";
    							$variables_count++;
    							continue 2;
    						case "loop":
    							foreach ($config_options as $var_name => $var_items) {
    								$tv = explode('_', $variable);
    								$key = "loop|" . $tv[0] . "_" . $var_name . "_" . $var_items['loop_count'];
    								if (isset($extra_data[$key])) {
    									$items[$variables_count] = $var_items;
    									$template_variables_array[$group_count]['data'][$variables_count] = $this->generate_form_data($variables_count, $var_items, $key, $custom_cfg_data, $admin, $user_cfg_data, $extra_data, $template_type);
    									$template_variables_array[$group_count]['data'][$variables_count]['looping'] = TRUE;
    									$variables_count++;
    								}
    							}
    							continue 2;
    					}
    				}
    				//Both Views
    				switch ($config_options['type']) {
    					case "break":
    						$template_variables_array[$group_count]['data'][$variables_count] = $this->generate_form_data($variables_count, $config_options, $key, $custom_cfg_data, $admin, $user_cfg_data, $extra_data, $template_type);
    						$variables_count++;
    						break;
    					default:
    						if (array_key_exists('variable', $config_options)) {
    							$key = str_replace('$', '', $config_options['variable']);
    							//TODO: Move this into the sync function
    							//Checks to see if values are defined in the database, if not then we assume this is a new option and we need a default value here!
    							if (!isset($custom_cfg_data[$key])) {
    								//xml2array will take values that have no data and turn them into arrays, we want to avoid the word 'array' as a default value, so we blank it out here if we are an array
    								if ((array_key_exists('default_value', $config_options)) AND (is_array($config_options['default_value']))) {
    									$custom_cfg_data[$key] = "";
    								} elseif ((array_key_exists('default_value', $config_options)) AND (!is_array($config_options['default_value']))) {
    									$custom_cfg_data[$key] = $config_options['default_value'];
    								}
    							}
    							if ((!$admin) AND (isset($extra_data[$key]))) {
    								$custom_cfg_data[$key] = $user_cfg_data[$key];
    								$template_variables_array[$group_count]['data'][$variables_count] = $this->generate_form_data($variables_count, $config_options, $key, $custom_cfg_data, $admin, $user_cfg_data, $extra_data, $template_type);
    								$variables_count++;
    							} elseif ($admin) {
    								$template_variables_array[$group_count]['data'][$variables_count] = $this->generate_form_data($variables_count, $config_options, $key, $custom_cfg_data, $admin, $user_cfg_data, $extra_data, $template_type);
    								$variables_count++;
    							}
    						}
    						break;
    				}
    				continue;
    			}
    		}
    	}
    
    	return($template_variables_array);
    }	

	
	 /**
     * Generate an array that will get parsed as HTML from an array of values from XML
     * @param int $i
     * @param array $cfg_data
     * @param string $key
     * @param array $custom_cfg_data
     * @return array
     */
    function generate_form_data($i, $cfg_data, $key=NULL, $custom_cfg_data=NULL, $admin=FALSE, $user_cfg_data=NULL, $extra_data=NULL, $template_type='GENERAL') {
    	switch ($cfg_data['type']) {
    		case "input":
    			if ((!$admin) && (isset($user_cfg_data[$key]))) {
    				$custom_cfg_data[$key] = $user_cfg_data[$key];
    			}
    			$template_variables_array['type'] = "input";
    			if (isset($cfg_data['max_chars'])) {
    				$template_variables_array['max_chars'] = $cfg_data['max_chars'];
    			}
    			$template_variables_array['key'] = $key;
    			$template_variables_array['value'] = isset($custom_cfg_data[$key]) ? $custom_cfg_data[$key] : $cfg_data['default_value'];
    			$template_variables_array['description'] = $cfg_data['description'];
    			break;
    			
    		case "radio":
    			if ((!$admin) && (isset($user_cfg_data[$key]))) {
    				$custom_cfg_data[$key] = $user_cfg_data[$key];
    			}
    			$num = isset($custom_cfg_data[$key]) ? $custom_cfg_data[$key] : $cfg_data['default_value'];
    			$template_variables_array['type'] = "radio";
    			$template_variables_array['key'] = $key;
    			$template_variables_array['description'] = $cfg_data['description'];
    			$z = 0;
    			while ($z < count($cfg_data['data'])) {
    				$template_variables_array['data'][$z]['key'] = $key;
    				$template_variables_array['data'][$z]['value'] = $cfg_data['data'][$z]['value'];
    				$template_variables_array['data'][$z]['description'] = $cfg_data['data'][$z]['text'];
    				if ($cfg_data['data'][$z]['value'] == $num) {
    					$template_variables_array['data'][$z]['checked'] = 'checked';
    				}
    				$z++;
    			}
    			break;
    			
    		case "list":
    			if ((!$admin) && (isset($user_cfg_data[$key]))) {
    				$custom_cfg_data[$key] = $user_cfg_data[$key];
    			}
    			$num = isset($custom_cfg_data[$key]) ? $custom_cfg_data[$key] : $cfg_data['default_value'];
    			$template_variables_array['type'] = "list";
    			$template_variables_array['key'] = $key;
    			$template_variables_array['description'] = $cfg_data['description'];
    			$z = 0;
    			while ($z < count($cfg_data['data'])) {
    				$template_variables_array['data'][$z]['value'] = $cfg_data['data'][$z]['value'];
    				$template_variables_array['data'][$z]['description'] = $cfg_data['data'][$z]['text'];
    				if (isset($cfg_data['data'][$z]['disable'])) {
    					$cfg_data['data'][$z]['disable'] = str_replace('{$count}', $z, $cfg_data['data'][$z]['disable']);
    					$template_variables_array['data'][$z]['disables'] = explode(",", $cfg_data['data'][$z]['disable']);
    				}
    				if (isset($cfg_data['data'][$z]['enable'])) {
    					$cfg_data['data'][$z]['enable'] = str_replace('{$count}', $z, $cfg_data['data'][$z]['enable']);
    					$template_variables_array['data'][$z]['enables'] = explode(",", $cfg_data['data'][$z]['enable']);
    				}
    				if ($cfg_data['data'][$z]['value'] == $num) {
    					$template_variables_array['data'][$z]['selected'] = 'selected';
    				}
    				$z++;
    			}
    			break;
    			
    		case "checkbox":
    			if ((!$admin) && (isset($user_cfg_data[$key]))) {
    				$custom_cfg_data[$key] = $user_cfg_data[$key];
    			}
    			$num = isset($custom_cfg_data[$key]) ? $custom_cfg_data[$key] : $cfg_data['default_value'];
    			$template_variables_array['type'] = "checkbox";
    			$template_variables_array['key'] = $key;
    			$template_variables_array['description'] = $cfg_data['description'];
    			$template_variables_array['checked'] = $custom_cfg_data[$key] ? TRUE : NULL;
    			$template_variables_array['value'] = $key;
    			break;
    			
    		case "group";
    			$template_variables_array['type'] = "group";
    			$template_variables_array['description'] = $cfg_data['description'];
    			break;
    			
    		case "header";
    			$template_variables_array['type'] = "header";
    			$template_variables_array['description'] = $cfg_data['description'];
    			break;
    			
    		case "textarea":
    			if ((!$admin) && (isset($user_cfg_data[$key]))) {
    				$custom_cfg_data[$key] = $user_cfg_data[$key];
    			}
    			$template_variables_array['type'] = "textarea";
    			if (isset($cfg_data['rows'])) {
    				$template_variables_array['rows'] = $cfg_data['rows'];
    			}
    			if (isset($cfg_data['cols'])) {
    				$template_variables_array['cols'] = $cfg_data['cols'];
    			}
    			$template_variables_array['key'] = $key;
    			$template_variables_array['value'] = isset($custom_cfg_data[$key]) ? $custom_cfg_data[$key] : $cfg_data['default_value'];
    			$template_variables_array['description'] = $cfg_data['description'];
    			break;
    			
    		case "break":
    			if ($admin) {
    				$template_variables_array['type'] = "break";
    			} else {
    				$template_variables_array['type'] = "NA";
    			}
    			break;
    			
    		default:
    			$template_variables_array['type'] = "NA";
    			break;
    	}
    
    	if (isset($cfg_data['tooltip'])) {
    		$template_variables_array['tooltip'] = htmlentities($cfg_data['tooltip']);
    	}
    
    	if (($this->epm->getConfig('enable_ari')) AND ($admin) AND ($cfg_data['type'] != "break") AND ($cfg_data['type'] != "group") AND ($template_type == 'GENERAL')) {
    
    		$template_variables_array['aried'] = 1;
    		$template_variables_array['ari']['key'] = $key;
    
    		if (isset($extra_data[$key])) {
    			$template_variables_array['ari']['checked'] = "checked";
    		}
    	}
    
    	if ($template_type == 'GLOBAL') {
    		$template_variables_array['freepbxed'] = 1;
    		$template_variables_array['freepbx']['key'] = $key;
    		if (empty($extra_data)) {
    			$template_variables_array['freepbx']['checked'] = TRUE;
    		} elseif (isset($extra_data[$key])) {
    			$template_variables_array['freepbx']['checked'] = TRUE;
    		}
    	}
    	return($template_variables_array);
    }
	
}