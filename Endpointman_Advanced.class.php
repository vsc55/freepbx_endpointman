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

class Endpointman_Advanced
{
	public $epm;
	public $freepbx;
	public $db;
	public $config;
	public $epm_config;

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

	public function ajaxRequest($req, &$setting, array $data)
	{
		$allowRequest = array(
			"oui",							// Tab OUI Manager
			"oui_brands",					// Tab OUI Manager
			"oui_add",						// Tab OUI Manager
			"oui_remove",					// Tab OUI Manager
			"poce_tree", 					// Tab POCE
			"poce_select_file",				// Tab POCE
			"poce_save_file",				// Tab POCE
			"poce_save_as_file",			// Tab POCE
			"poce_delete_config_custom",	// Tab POCE
			"list_files_brands_export",		// Tab Manual Upload
			"saveconfig"					// Tab Settings
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
				'opt_invalid' 	  => _("Invalid Option!"),
			),
			'poce' => array(
			 	'load_data_ok' 	  => _("File Content Obtained Successfully"),
			),
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
							$ret = array();
							foreach ($this->epm->packagesdb->getBrands(true, 'name') as $brand)
							{
								$brand_id 	= $brand->getId();
								$brand_name = $brand->getName();
								foreach($brand->getOUI_All() as $oui)
								{
									$ret[] = array(
										'brand_id' => $brand_id,
										'id' 	   => $oui['id'], 
										'oui' 	   => $oui['oui'],
										'brand'    => $brand_name, 
										'custom'   => $oui['custom']
									);
								}
							}
							return $ret;
							break;
		
						case "oui_brands":
							$retarr = $this->epm_advanced_oui_brands($data, true, []);
							break;
							
						case "oui_add":
							$retarr = $this->epm_advanced_oui_add($data);
							break;
		
						case "oui_remove":
							$retarr = $this->epm_advanced_oui_remove($data);
							break;
		
						default:
							$command_allow = false;
					}
				break;
				
				case 'poce':
					switch ($command)
					{
						case 'poce_tree':
							// Unset txt['pose'] to prevent jstree from generating an error when loading the main tree
							unset($txt['poce']);
							$retarr = $this->epm_advanced_poce_tree($data);
							break;

						// case "poce_list_brands":
						// 	$retarr = $this->epm_advanced_poce_list_brands();
						// 	break;

						// case "poce_select":
						// 	$retarr = $this->epm_advanced_poce_select($data);
						// 	break;

						case "poce_select_file":
							$retarr = $this->epm_advanced_poce_select_file($data);
							break;

						case "poce_save_file":
						case "poce_save_as_file":
							$retarr = $this->epm_advanced_poce_save_file($data);
							break;

							
						case "poce_share":
							$retarr = $this->epm_advanced_poce_share($data);
							break;

						case "poce_delete_config_custom":
							$retarr = $this->epm_advanced_poce_delete_config_custom($data);
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

	public function doConfigPageInit(array $data)
	{
		// Force flush all output buffers, need for AJAX
		if (!empty($data) && is_array($data))
		{
			$mod_tab = $data['module_tab'] ?? '';
			$command = $data['command']    ?? '';
			$request = $data['request']    ?? array();

			$endprocess = false;
			switch ($mod_tab)
			{
				case "manual_upload":
					switch ($command)
					{
						case "export_brands_availables":
							$endprocess = true;
							$this->activeFlush();
							$this->epm_advanced_manual_upload_export_brans_available();
							break;

						case "export_brands_availables_file":
							$endprocess = true;
							$this->activeFlush();
							$this->epm_advanced_manual_upload_export_brans_available_file();
							//TODO: Igual tenemos que hacer exit para que no añadia el <br /><hr><br />
							break;

						case "upload_brand":
							$endprocess = true;
							$this->activeFlush();
							$this->epm_advanced_manual_upload_brand();
							break;

						case "upload_provisioner":
							$endprocess = true;
							$this->activeFlush();
							$this->epm_advanced_manual_upload_provisioner();
							break;
					}
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

	// Force flush all output buffers, need for AJAX
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

	public function showPage(array &$data)
	{
		$tabs = array(
			'settings' => array(
				"name" => _("Settings"),
				"page" => '/views/epm_advanced_settings.page.php'
			),
			'oui_manager' => array(
				"name" => _("OUI Manager"),
				"page" => '/views/epm_advanced_oui_manager.page.php'
			),
			'poce' => array(
				"name" => _("Product Configuration Editor"),
				"page" => '/views/epm_advanced_poce.page.php'
			),
			'manual_upload' => array(
				"name" => _("Package Import/Export"),
				"page" => '/views/epm_advanced_manual_upload.page.php'
			),
		);

		$data['subpage'] = $data['request']['subpage'] ?? '';
		if (! in_array($data['subpage'], array_keys($tabs)))
		{
			$data['subpage'] = 'settings';
		}

		foreach($tabs as $key => &$page)
		{
			$data_tab = array();
			switch($key)
			{
				case "settings":
					if (($this->epm->getConfig("server_type") == 'file') AND ($this->epm_advanced_config_loc_is_writable()))
					{
						$this->tftp_check();
					}
					
					if ($this->epm->getConfig("use_repo") == "1")
					{
						if ($this->epm->has_git())
						{
							if (!file_exists($this->system->buildPath($this->epm->PHONE_MODULES_PATH, '.git'))) {
								$o = getcwd();
								chdir(dirname($this->epm->PHONE_MODULES_PATH));
								$this->epm->system->rmrf($this->epm->PHONE_MODULES_PATH);
								$path = $this->epm->has_git();
								exec($path . ' clone https://github.com/provisioner/Provisioner.git _ep_phone_modules', $output);
								chdir($o);
							}
						}
						else
						{
							echo  _("Git not installed!");
						}
					}
					else
					{
						if (file_exists($this->epm->system->buildPath($this->epm->PHONE_MODULES_PATH, '.git')))
						{
							$this->epm->system->rmrf($this->epm->PHONE_MODULES_PATH);

							$sql = "SELECT * FROM  `".self::TABLES['epm_brands_list']."` WHERE  `installed` =1";
							$result = & sql($sql, 'getAll', \PDO::FETCH_ASSOC);
							foreach ($result as $row)
							{
								$id_product = $row['id'] ?? null;
								$this->epm_config->remove_brand($id_product, true);
							}
						}
					}
					
					$url_provisioning = $this->epm->system->buildUrl(sprintf("%s://%s", $this->epm->getConfig("server_type"), $this->epm->getConfig("srvip")), "provisioning","p.php");

					$data_tab['config']['data'] = array(
						'setting_provision' => array(
							'label' => _("Provisioner Settings"),
							'items' => array(
								'srvip' => array(
									'label' 	  => _("IP address of phone server"),
									'type' 		  => 'text',
									'value' 	  => $this->epm->getConfig("srvip"),
									'placeholder' => _("IP Server PBX..."),
									'help' 		  => _("The IP Address of the Server PBX that will be used to provision the phones."),
									'button' 	  => array(
										'id' 	  => 'autodetect',
										'label'   => _("Use Me!"),
										'icon'    => 'fa-search',
										'onclick' => sprintf("epm_advanced_tab_setting_input_value_change_bt('#srvip', sValue = '%s', bSaveChange = true);", $_SERVER["SERVER_ADDR"]),
									),
								),
								'intsrvip' => array(
									'label' 	  => _("Internal IP address of phone server"),
									'type' 		  => 'text',
									'value' 	  => $this->epm->getConfig("intsrvip"),
									'placeholder' => _("Internal IP Server PBX..."),
									'help' 		  => _("The Internal IP Address of the Server PBX that will be used to provision the phones."),
									'button' 	  => array(
										'id' 	  => 'autodetect',
										'label'   => _("Use Me!"),
										'icon'    => 'fa-search',
										'onclick' => sprintf("epm_advanced_tab_setting_input_value_change_bt('#intsrvip', sValue = '%s', bSaveChange = true);", $_SERVER["SERVER_ADDR"]),
									),
								),
								'cfg_type' => array(
									'label' 	  => _("Configuration Type"),
									'type' 		  => 'select',
									'value' 	  => $this->epm->getConfig("server_type"),
									'help' 		  => _("The type of server that will be used to provision the phones (TFTP/FTP, HTTP, HTTPS)."),
									'select_check'=> function ($option, $value) {
										return (strtolower($value) == strtolower($option['value']));
									},
									'options'	  => array(
										'file'  => array(
											'text' => _("File (TFTP/FTP)"),
											'icon'  => 'fa-upload',
											'value' => 'file',
										),
										'http'  => array(
											'text' => _("HTTP"),
											'icon'  => 'fa-upload',
											'value' => 'http',
										),
										'https' => array(
											'text' => _("HTTPS"),
											'icon'  => 'fa-upload',
											'value' => 'https',
										),
									),
									'alert' => array(
										'cfg_type_alert' => array(
											'msg'   => sprintf(_("<strong>Updated!</strong> - Point your phones to: %s"), sprintf('<a href="%1$s" class="alert-link" target="_blank">%1$s</a>', $url_provisioning)),
											'types' => array('http', 'https'),
										)
									)
								),
								'config_loc' => array(
									'label' 	  => _("Global Final Config & Firmware Directory"),
									'type' 		  => 'text',
									'value' 	  => $this->epm->getConfig("config_loc"),
									'placeholder' => _("Configuration Location..."),
									'help' 		  => _("Path location root TFTP server."),
								),
								'adminpass' => array(
									'label' 	  => _("Phone Admin Password"),
									'type' 		  => 'text',
									'class' 	  => 'confidential',	//'password-meter confidential',
									'value' 	  => $this->epm->getConfig("adminpass"),
									'placeholder' => _("Admin Password..."),
									'help' 		  => _("Enter a admin password for your phones. Must be 6 characters and only nummeric is recommendet!"),
								),
								'userpass' => array(
									'label' 	  => _("Phone User Password"),
									'type' 		  => 'text',
									'class' 	  => 'confidential',	//'password-meter confidential',
									'value' 	  => $this->epm->getConfig("userpass"),
									'placeholder' => _("User Password..."),
									'help' 		  => _("Enter a user password for your phones. Must be 6 characters and only nummeric is recommendet!"),
								),
							)
						),
						'setting_time' => array(
							'label' => _("Time Settings"),
							'items' => array(
								'tz' => array(
									'label'				 => _("Time Zone"),
									'type'				 => 'select',
									'value'				 => $this->epm->getConfig("tz"),
									'help'				 => _("Time Zone configuration terminasl. Like England/London"),
									'options'			 => $this->epm->listTZ($this->epm->getConfig("tz")),
									'search'			 => true,
									'search_placeholder' => _("Search"),
									'size'				 => 10,
									'icon_option'		 => 'fa-clock-o',
									'select_check'		 => function ($option, $value) {
										return ($option['selected'] == 1);
									},
									'button'			 => array(
										'id' 	  => 'tzphp',
										'label'   =>_("Time Zone PBX"),
										'icon'    => 'fa-clock-o',
										'onclick' => sprintf("epm_advanced_tab_setting_input_value_change_bt('#tz', sValue = '%s', bSaveChange = true);", $this->config->get('PHPTIMEZONE')),
									),
								),
								'ntp_server' => array(
									'label' 	  => _("Time Server (NTP Server)"),
									'type' 		  => 'text',
									'value' 	  => $this->epm->getConfig("ntp"),
									'placeholder' => _("NTP Server..."),
									'help' 		  => _("The NTP Server that will be used to provision the phones."),
									'button' 	  => array(
										'id' 	  => 'autodetectntp',
										'label'   => _("Use Me!"),
										'icon'    => 'fa-search',
										'onclick' => sprintf("epm_advanced_tab_setting_input_value_change_bt('#ntp_server', sValue = '%s', bSaveChange = true);", $_SERVER["SERVER_ADDR"]),
									),
								),
							)
						),
						'setting_local_paths' => array(
							'label' => _("Local Paths"),
							'items' => array(
								'nmap_loc' => array(
									'label' 	  => _("NMAP Executable Path"),
									'type' 		  => 'text',
									'value' 	  => $this->epm->getConfig("nmap_location"),
									'placeholder' => _("Nmap Location..."),
									'help' 		  => _("The location of the Nmap binary."),
								),
								'arp_loc' => array(
									'label' 	  => _("Arp Executable Path"),
									'type' 		  => 'text',
									'value' 	  => $this->epm->getConfig("arp_location"),
									'placeholder' => _("Arp Location..."),
									'help' 		  => _("The location of the Arp binary."),
								),
								'asterisk_loc' => array(
									'label' 	  => _("Asterisk Executable Path"),
									'type' 		  => 'text',
									'value' 	  => $this->epm->getConfig("asterisk_location"),
									'placeholder' => _("Asterisk Location..."),
									'help' 		  => _("The location of the Asterisk binary."),
								),
								'tar_loc' => array(
									'label' 	  => _("Tar Executable Path"),
									'type' 		  => 'text',
									'value' 	  => $this->epm->getConfig("tar_location"),
									'placeholder' => _("Tar Location..."),
									'help' 		  => _("The location of the Tar binary."),
								),
								'netstat_loc' => array(
									'label' 	  => _("Netstat Executable Path"),
									'type' 		  => 'text',
									'value' 	  => $this->epm->getConfig("netstat_location"),
									'placeholder' => _("Netstat Location..."),
									'help' 		  => _("The location of the Netstat binary."),
								),
								'whoami_loc' => array(
									'label' 	  => _("Whoami Executable Path"),
									'type' 		  => 'text',
									'value' 	  => $this->epm->getConfig("whoami_location"),
									'placeholder' => _("Whoami Location..."),
									'help' 		  => _("The location of the Whoami binary."),
								),
								'nohup_loc' => array(
									'label' 	  => _("Nohup Executable Path"),
									'type' 		  => 'text',
									'value' 	  => $this->epm->getConfig("nohup_location"),
									'placeholder' => _("Nohup Location..."),
									'help' 		  => _("The location of the Nohup binary."),
								),
								'groups_loc' => array(
									'label' 	  => _("Groups Executable Path"),
									'type' 		  => 'text',
									'value' 	  => $this->epm->getConfig("groups_location"),
									'placeholder' => _("Groups Location..."),
									'help' 		  => _("The location of the Groups binary."),
								),
							)
						),
						'setting_web_directories' => array(
							'label' => _("Web Directories"),
							'items' => array(
								'package_server' => array(
									'label' 	  => _("Package Server"),
									'type' 		  => 'text',
									'value' 	  => $this->epm->getConfig("update_server"),
									'placeholder' => _("Server Packages..."),
									'help' 		  => _("URL download files and packages the configuration terminals."),
									'button' 	  => array(
										'id' 	  => 'default_package_server',
										'label'   => _("Default Mirror FreePBX"),
										'icon'    => 'fa-undo',
										'onclick' => sprintf("epm_advanced_tab_setting_input_value_change_bt('#package_server', sValue = '%s', bSaveChange = true);", Endpointman::URL_PROVISIONER),
									),
								),
							)
						),
						'setting_other' => array(
							'label' => _("Other Settings"),
							'items' => array(
								'disable_endpoint_warning' => array(
									'label' 	  => _("Disable Endpoint Warning"),
									'type' 		  => 'radioset',
									'help' 		  => _("Enable this setting if you dont want to get a warning message anymore if you have the Commercial Endpoint Manager installed together with OSS Endpoint Manager."),
									'value' 	  => $this->epm->getConfig("disable_endpoint_warning"),
									'options'	  => array(
										'No' => array(
											'label' => _("No"),
											'value' => '0',
											'icon'  => 'fa-times',
										),
										'Yes' => array(
											'label' => _("Yes"),
											'value' => '1',
											'icon'  => 'fa-check',
										)
									),
								),
							)
						),
						'setting_experimental' => array(
							'label' => _("Experimental Settings"),
							'items' => array(
								'enable_ari' => array(
									'label' 	  => _("Enable FreePBX ARI Module"),
									'type' 		  => 'radioset',
									'help' 		  => sprintf(_('Enable FreePBX ARI Module %s.'), '<a href="http://wiki.provisioner.net/index.php/Endpoint_manager_manual_ari" target="_blank"><i class="fa fa-external-link" aria-hidden="true"></i></a>'),
									'value' 	  => $this->epm->getConfig("enable_ari"),
									'options'	  => array(
										'No' => array(
											'label' => _("No"),
											'value' => '0',
											'icon'  => 'fa-times',
										),
										'Yes' => array(
											'label' => _("Yes"),
											'value' => '1',
											'icon'  => 'fa-check',
										)
									),
								),
								'enable_debug' => array(
									'label' 	  => _("Debug"),
									'type' 		  => 'radioset',
									'help' 		  => _("Enable this setting if you want to see debug messages."),
									'value' 	  => $this->epm->getConfig("debug"),
									'disabled'	  => true,
									'options'	  => array(
										'No' => array(
											'label' => _("No"),
											'value' => '0',
											'icon'  => 'fa-times',
										),
										'Yes' => array(
											'label' => _("Yes"),
											'value' => '1',
											'icon'  => 'fa-check',
										)
									),
								),
								'disable_help' => array(
									'label' 	  => _("Disable Tooltips"),
									'type' 		  => 'radioset',
									'help' 		  => _("Disable Tooltip popups"),
									'value' 	  => $this->epm->getConfig("disable_help"),
									'options'	  => array(
										'No' => array(
											'label' => _("No"),
											'value' => '0',
											'icon'  => 'fa-times',
										),
										'Yes' => array(
											'label' => _("Yes"),
											'value' => '1',
											'icon'  => 'fa-check',
										)
									),
								),
								'allow_dupext' => array(
									'label' 	  => _("Allow Duplicate Extensions"),
									'type' 		  => 'radioset',
									'help' 		  => _("Assign the same extension to multiple phones (Note: This is not supported by Asterisk)"),
									'value' 	  => $this->epm->getConfig("show_all_registrations"),
									'options'	  => array(
										'No' => array(
											'label' => _("No"),
											'value' => '0',
											'icon'  => 'fa-times',
										),
										'Yes' => array(
											'label' => _("Yes"),
											'value' => '1',
											'icon'  => 'fa-check',
										)
									),
								),
								'allow_hdfiles' => array(
									'label' 	  => _("Allow Saving Over Default Configuration Files"),
									'type' 		  => 'radioset',
									'help' 		  => _("When editing the configuration files allows one to save over the global template default instead of saving directly to the database. These types of changes can and will be overwritten when updating the brand packages from the configuration/installation page."),
									'value' 	  => $this->epm->getConfig("allow_hdfiles"),
									'options'	  => array(
										'No' => array(
											'label' => _("No"),
											'value' => '0',
											'icon'  => 'fa-times',
										),
										'Yes' => array(
											'label' => _("Yes"),
											'value' => '1',
											'icon'  => 'fa-check',
										)
									),
								),
								'tftp_check' => array(
									'label' 	  => _("Disable TFTP Server Check"),
									'type' 		  => 'radioset',
									'help' 		  => _("Disable checking for a valid, working TFTP server which can sometimes cause Apache to crash."),
									'value' 	  => $this->epm->getConfig("tftp_check"),
									'options'	  => array(
										'No' => array(
											'label' => _("No"),
											'value' => '0',
											'icon'  => 'fa-times',
										),
										'Yes' => array(
											'label' => _("Yes"),
											'value' => '1',
											'icon'  => 'fa-check',
										)
									),
								),
								'backup_check' => array(
									'label' 	  => _("Disable Configuration File Backups"),
									'type' 		  => 'radioset',
									'help' 		  => _("Disable backing up the tftboot directory on every phone rebuild or save"),
									'value' 	  => $this->epm->getConfig("backup_check"),
									'options'	  => array(
										'No' => array(
											'label' => _("No"),
											'value' => '0',
											'icon'  => 'fa-times',
										),
										'Yes' => array(
											'label' => _("Yes"),
											'value' => '1',
											'icon'  => 'fa-check',
										)
									),
								),
							)
						)
					);
				break;

				case "manual_upload":
					$provisioner_ver 						= $this->epm->getConfig("endpoint_vers");
					$data_tab['config']['provisioner_ver']  = sprintf(_("%s at %s"), date("d-M-Y", $provisioner_ver) , date("g:ia", $provisioner_ver));
					$data_tab['config']['brands_available'] = $this->epm->brands_available("", false);
				break;

				case "oui_manager":
					$data_tab['config']['url_grid'] = sprintf("ajax.php?module=endpointman&amp;module_sec=epm_advanced&amp;module_tab=%s&amp;command=oui", $key);
				break;

				case "poce":
				break;
			}
			$data_tab = array_merge($data, $data_tab);

			$page['content'] = load_view($this->epm->system->buildPath(__DIR__, $page['page']), $data_tab);
		}
		$data['tabs'] = $tabs;
		unset($tabs);
	}





	/**** FUNCIONES SEC MODULO "epm_advanced\settings" ****/
	public function epm_advanced_config_loc_is_writable()
	{
		$config_loc = $this->epm->getConfig("config_loc");
		return (empty($config_loc) || !file_exists($config_loc) || !is_dir($config_loc) || !is_writable($config_loc)) ? false : true;
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
					$this->epm->setConfig('config_loc', $value);
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
	public function epm_advanced_poce_tree(array $data)
	{
		$request = $data['request'] 	?? array();
		$tree_id = $request['tree_id'] 	?? '#';
		$nodes 	 = [];
		$err_msg = null;

		if ($tree_id == "#")
		{
			// Get only brands with hidden = 0
			$brands   = $this->epm->packagesdb->getBrands(false);
			$products = [];
			foreach ($brands as $brand)
			{
				// Get only products with hidden = 0
				foreach ($brand->getProducts(false) as $product)
				{
					// Check if exists models enabled and not hidden
					if ($product->countModels(false, true) > 0)
					{
						$products[] = $product;
					}
				}
			}

			// Sort products by name
			usort($products, function($a, $b)
			{
				return strcmp($a->getName(), $b->getName());
			});

			// Prepare the data to nodes
			foreach ($products as $product)
			{
				$nodes[] = [
					'id' 		=> $product->getId(),
					'text' 		=> substr($product->getName(), 0, 40).(strlen($product->getName()) > 40 ? "..." : ""),
					'children' 	=> true,
					'icon' 		=> 'fa fa-cube',
					'a_attr' 	=> [
						'title' => $product->getName(),
					],
					'data' 		=> [
						'id' 	=> $product->getId(),
						'text'  => $product->getName(),
					]
				];
			}
		}
		elseif (is_numeric($tree_id))
		{
			$id_file 	 = $tree_id . '_file';
			$id_template = $tree_id . '_template';
			$id_custom 	 = $tree_id . '_custom';

			$nodes[] = [
				'id' 		=> $id_file,
				'text' 		=> _("Files Config"),
				'children' 	=> true,
				'icon' 		=> 'fa fa-cogs',
				'data' 		=> [
					'type'  => 'raw',
				]
			];
			$nodes[] = [
				'id' 		=> $id_template,
				'text' 		=> _("Templates"),
				'children' 	=> true,
				'icon' 		=> 'fa fa-cogs',
				'data' 		=> [
					'type'  => 'xml',
				]
			];
			$nodes[] = [
				'id' 		=> $id_custom,
				'text' 		=> _("Custom Files"),
				'children' 	=> true,
				'icon' 		=> 'fa fa-cogs',
				'data' 		=> [
					'type'  => 'raw',
				]
			];
		}
		else
		{
			$tree_args = explode("_", $tree_id);
			$tree_id   = $tree_args[0];
			$tree_type = $tree_args[1];

			$path_setup = $this->epm->system->buildPath(__DIR__, 'install', 'setup.php');

			if (empty($tree_id))
			{
				$err_msg = _("No send ID Product!");
			}
			elseif (! is_numeric($tree_id))
			{
				$err_msg = _("Ivalid ID Product!");
			}
			elseif ($tree_id < 0)
			{
				$err_msg = _("Invalid ID Product (less than 0)!");
			}
			elseif (!file_exists($path_setup))
			{
				$err_msg = _("File setup.php not found!");
			}
			else
			{
				$product = $this->epm->packagesdb->getProductByID($tree_id);
				if (empty($product) || $product->isDestory())
				{
					$err_msg = _("Product not located in the database!");
				}
				else
				{
					require_once($path_setup);
		
					$brand_raw   = $product->getBrand()->getDirectory();
					$product_raw = $product->getDirectory();
		
					$class 		  = sprintf("endpoint_%s_%s_phone", $brand_raw, $product_raw);
					$base_class   = sprintf("endpoint_%s_base", $brand_raw);
					$master_class = "endpoint_base";
		
		
					//TODO: Pending to test in FreePBX 17 Debian (remove is not necessary)
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
					if (!class_exists($class))
					{
						$err_msg = sprintf(_("Class '%s' not found!"), $class);
					}
					else
					{
						$phone_config = new $class();
						//end quick fix

						$id_file 	 = $tree_id . '_file';
						$id_template = $tree_id . '_template';
						$id_custom 	 = $tree_id . '_custom';

						switch	($tree_type)
						{
							case "file":
								foreach ($product->getConfigurationFiles() as $config_file)
								{
									$nodes[] = [
										'id' 	 	=> sprintf('file_%s_%s', $product->getId() , $config_file),
										'parent' 	=> $id_file,
										'text' 	 	=> $config_file,
										'icon' 	 	=> 'fa fa-file-text-o',
										'children'  => false,
										'data' 	 	=> [
											// 'id' 	=> $product->getId(),
											// 'type'  => 'raw',
											// 'src'  	=> 'file',
											'func'	=> 'epm_advanced_tab_poce_edit_file',
											'param' => [
												'product' => $product->getId(),
												'type' 	  => $tree_type,
												// 'type_file' => 'raw',
												'name_file' => $config_file,
												'id_file' => $product->getId(),
											],
										]
									];
								}
								break;

							case "template":
								//TODO: remove
								// $nodes[] = [
								// 	'id' 		=> 'template_data_custom.xml',
								// 	'parent' 	=> $id_template,
								// 	'text' 		=> 'template_data_custom.xml',
								// 	'icon' 		=> 'fa fa-file-text-o',
								// 	'children' 	=> false,
								// 	'data' 		=> [
								// 		'id' 	=> -1,
								// 		'type'  => 'xml',
								// 	]
								// ];
								foreach ($product->getModels(false, true) as $model)
								{
									$nodes[] = [
										'id' 		=> sprintf('template_%s_%s', $product->getId() , $model->getId()),
										'parent' 	=> $id_template,
										'text' 		=> sprintf("template_data_%s_custom.xml", $model->getModel()),
										'icon' 		=> 'fa fa-file-text-o',
										'children' 	=> false,
										'data' 		=> [
											// 'id' 	 => $model->getId(),
											// 'type'   => 'xml',
											// 'src'  	 => 'tfile',
											'func'  => 'epm_advanced_tab_poce_edit_file',
											'param' => [
												'product' 	=> $product->getId(),
												'type' 	  	=> $tree_type,
												// 'type_file' => 'xml',
												'name_file' => sprintf("template_data_%s_custom.xml", $model->getModel()),
												'id_file' 	=> $model->getId(),
											],
										]
									];
								}
								break;

							case "custom":
								foreach ($product->getConfigurationsCustom() as $config_custom)
								{
									$nodes[] = [
										'id' 		=> sprintf('custom_%s_%s', $product->getId(), $config_custom['id']),
										'parent'	=> $id_custom,
										'text' 		=> $config_custom['name'],
										'icon' 		=> 'fa fa-file-text-o',
										'children' 	=> false,
										'data' 		=> [
											// 'id'    => $config_custom['id'],
											// 'ref'   => $config_custom['original_name'],
											// 'type'  => 'raw',
											// 'src'   => 'sql',
											'func'  => 'epm_advanced_tab_poce_edit_file',
											'param' => [
												'product' 	=> $product->getId(),
												'type' 	  	=> $tree_type,
												// 'type_file' => 'raw',
												'name_file' => $config_custom['original_name'],
												'id_file'   => $config_custom['id'],
											],
										]
									];
								}
								break;
						}

						if (count($nodes) == 0)
						{
							$nodes[] = [
								'id' 		=>  sprintf('%s_%s_empty', $tree_type,  $product->getId()),
								'text' 		=> _("There are no files!"),
								'children' 	=> false,
								'icon' 		=> 'fa fa-exclamation-triangle',
								'data' 		=> [
									'id' => null,
								],
								'state' 	=> [
									'disabled' => true,
								],
							];
						}
					}
				}
			}
		}

		if (! is_null($err_msg))
		{
			$nodes[] = [
				'id' 		=> 'empty',
				'text' 		=> $err_msg,
				'children' 	=> false,
				'icon' 		=> 'fa fa-exclamation-triangle',
				'state' 	=> [
					'disabled' => true,
				],
			];
		}
		if (count($nodes) == 0)
		{

			if ($tree_id == "#")
			{
				$nodes = [
					'id' 		=> 'empty',
					'text' 		=> _("There are no activated products!"),
					'children' 	=> false,
					'icon' 		=> 'fa fa-exclamation-triangle',
					'state' 	=> [
						'disabled' => true,
					],
				];
			}
			else
			{
				$nodes = [
					'id' 		=> 'empty',
					'text' 		=> _("There are no files!"),
					'children' 	=> false,
					'icon' 		=> 'fa fa-exclamation-triangle',
					'state' 	=> [
						'disabled' => true,
					],
				];
			}
		}

		return $nodes;
	}

	public function epm_advanced_poce_list_brands()
	{
		// Get only brands with hidden = 0
		$brands   = $this->epm->packagesdb->getBrands(false);
		$products = [];
		foreach ($brands as $brand)
		{
			// Get only products with hidden = 0
			foreach ($brand->getProducts(false) as $product)
			{
				// Check if exists models enabled and not hidden
				if ($product->countModels(false, true) == 0)
				{
					continue;
				}
				
				$products[] = $product;
			}
		}

		// Sort products by name
		usort($products, function($a, $b)
		{
			return strcmp($a->getName(), $b->getName());
		});

		// Prepare the data to return
		$products_return = [];
		foreach ($products as $product)
		{
			$new_product = [
				'id' 		=> $product->getId(),
				'name' 		=> $product->getName(),
				'name_mini' => substr($product->getName(), 0, 40).(strlen($product->getName()) > 40 ? "..." : ""),
			];
			$products_return[] = $new_product;
		}
		// Reindex the array
		$products_return = array_values($products_return);

		return array("status" => true, "message" => _("OK!"), "ldatos" => $products_return);
	}

	// public function epm_advanced_poce_select(array $data)
	// {
	// 	$request 		= $data['request'] 			 ?? array();
	// 	$product_select = $request['product_select'] ?? '';
	// 	// $path_setup 	= $this->epm->system->buildPath($this->epm->PHONE_MODULES_PATH, 'setup.php');
	// 	$path_setup = $this->epm->system->buildPath(__DIR__, 'install', 'setup.php');


	// 	if (empty($product_select))
	// 	{
	// 		return array("status" => false, "message" => _("No send Product Select!"));
	// 	}
	// 	elseif (! is_numeric($product_select))
	// 	{
	// 		return array("status" => false, "message" => _("Product Select send is not number!"));
	// 	}
	// 	elseif ($product_select < 0)
	// 	{
	// 		return array("status" => false, "message" => _("Product Select send is number not valid!"));
	// 	}
	// 	elseif (!file_exists($path_setup))
	// 	{
	// 		return array("status" => false, "message" => _("File setup.php not found!"));
	// 	}
	// 	else
	// 	{
	// 		$product = $this->epm->packagesdb->getProductByID($product_select);
	// 		if (empty($product) || $product->isDestory())
	// 		{
	// 			return array("status" => false, "message" => _("Product not found!"));
	// 		}

	// 		require_once($path_setup);

	// 		$brand_raw   = $product->getBrand()->getDirectory();
	// 		$product_raw = $product->getDirectory();

	// 		$class 		  = sprintf("endpoint_%s_%s_phone", $brand_raw, $product_raw);
	// 		$base_class   = sprintf("endpoint_%s_base", $brand_raw);
	// 		$master_class = "endpoint_base";


	// 		//TODO: Pending to test in FreePBX 17 Debian (remove is not necessary)
	// 		/*********************************************************************************
	// 		 *** Quick Fix for FreePBX Distro
	// 		 *** I seriously want to figure out why ONLY the FreePBX Distro can't do autoloads.
	// 		 **********************************************************************************/
	// 		if (!class_exists($master_class))
	// 		{
	// 			\ProvisionerConfig::endpointsAutoload($master_class);
	// 		}
	// 		if (!class_exists($base_class))
	// 		{
	// 			\ProvisionerConfig::endpointsAutoload($base_class);
	// 		}
	// 		if (!class_exists($class))
	// 		{
	// 			\ProvisionerConfig::endpointsAutoload($class);
	// 		}
	// 		if (!class_exists($class))
	// 		{
	// 			return array("status" => false, "message" => sprintf(_("Class '%s' not found!"), $class));
	// 		}
	// 		$phone_config = new $class();
	// 		//end quick fix

	// 		$tree_files = [];

	// 		$tree_files[] = [
	// 			'id' => 'files',
	// 			'parent' => '#',
	// 			'text' => _("Files"),
	// 		];

	// 		$config_files = [];
	// 		foreach ($product->getConfigurationFiles() as $config_file)
	// 		{
	// 			$config_files[] = [
	// 				'value' => $product->getId(),
	// 				'text'  => $config_file
	// 			];
	// 			$tree_files[] = [
	// 				'id' => 'file_' . $product->getId(),
	// 				'parent' => 'files',
	// 				'text' => $config_file,
	// 			];
	// 		}

	// 		$template_files = [];

	// 		$tree_files[] = [
	// 			'id' => 'templates',
	// 			'parent' => '#',
	// 			'text' => _("Templates"),
	// 		];

	// 		//TODO: remove
	// 		$template_files[] = array(
	// 			'value' => 'template_data_custom.xml',
	// 			'text'  => 'template_data_custom.xml'
	// 		);
	// 		$tree_files[] = [
	// 			'id' => 'template_data_custom.xml',
	// 			'parent' => 'templates',
	// 			'text' => 'template_data_custom.xml',
	// 		];

	// 		foreach ($product->getModels(false, true) as $model)
	// 		{
	// 			$template_files[] = [
	// 				'value' => $model->getId(),
	// 				'text'  => sprintf("template_data_%s_custom.xml", $model->getModel())
	// 			];

	// 			$tree_files[] = [
	// 				'id' => "template_" . $model->getId(),
	// 				'parent' => 'templates',
	// 				'text' => sprintf("template_data_%s_custom.xml", $model->getModel()),
	// 			];
	// 		}

	// 		$tree_files[] = [
	// 			'id' => 'sql',
	// 			'parent' => '#',
	// 			'text' => _("Custom"),
	// 		];

	// 		$config_customs = [];
	// 		foreach ($product->getConfigurationsCustom() as $config_custom)
	// 		{
	// 			$config_customs[] = [
	// 				'value' => $config_custom['id'],
	// 				'text'	=> $config_custom['name'],
	// 				'ref' 	=> $config_custom['original_name']
	// 			];

	// 			$tree_files[] = [
	// 				'id' => "custom_".$config_custom['id'],
	// 				'parent' => 'sql',
	// 				'text' => $config_custom['name'],
	// 			];
	// 		}

	// 		$retarr = array("status" => true, "message" => _("OK"),
	// 						"product_select" 	  => $product->getId(),
	// 						"product_select_info" => $product->exportInfo(),
	// 						"file_list" 		  => $config_files,
	// 						"template_file_list"  => $template_files,
	// 						"sql_file_list" 	  => $config_customs,
	// 						'tree_files' 		  => $tree_files,
	// 					);
	// 	}
	// 	return $retarr;
	// }

	public function epm_advanced_poce_select_file(array $data)
	{
		$request 		  = $data['request'] ?? [];
		$request_args 	  = array("product_select", "file_id", "file_name", "type_file");
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
		$file_name 		= $request['file_name'];
		$file_id   		= $request['file_id'];
		$type_file 		= $request['type_file'];

		if ($product_select < 0)
		{
			return array("status" => false, "message" => _("Product Select send is number not valid!"));
		}

		$product = $this->epm->packagesdb->getProductByID($product_select);
		switch($type_file)
		{
			case "custom":
				$config_custom 		= $product->getConfigurationCustom($file_id);
				$sendidt 			= $config_custom['id'];
				$save_as_name_value = $config_custom['name'];
				$original_name 		= $config_custom['original_name'];
				$filename 			= $config_custom['name'];
				$location 			= "SQL: ". $config_custom['name'];
				$config_data 		= $this->display_htmlspecialchars($config_custom['data']);

				unset($config_custom);
				break;

			case "file":
				$config_files = $product->getConfigurationFiles($file_name);
				if (!in_array($file_name, $config_files))
				{
					return array("status" => false, "message" => sprintf(_("File '%s' not found!"), $file_name));
				}
				$product_json = $this->epm->packages->getProductByProductID($product_select);
				try
				{
					$contents = $product_json->getConfigurationFile($file_name, false);
				}
				catch(\Exception $e)
				{
					return array("status" => false, "message" => $e->getMessage());
				}

				// $contents = "prueba
				// algo";
				$sendidt 			= $file_id;
				$save_as_name_value = $file_name;
				$original_name 		= $file_name;
				$filename 			= $file_name;
				$location 			= $product_json->getConfigurationFilePath($file_name);
				// $config_data 		= $this->display_htmlspecialchars($contents);
				$config_data 		= $contents;

				$config_data = str_replace('\n', "\n\r", $config_data);

				unset($config_files);
				break;

			case "template":
				if ($file_id == "template_data_custom.xml")
				{
					$sendidt		= "";
					$original_name	= $file_name;
					$config_data	= "";
				}
				else
				{
					$model 			  = $product->getModel($file_id);
					$sendidt 		  = $model->getId();
					$original_name 	  = $file_name;
					// $config_data	  = $this->epm->generate_xml_from_array ($model->getTemplateData(), 'node');
					// // $config_data_json = json_encode($model->getTemplateData(), JSON_PRETTY_PRINT);
					// $config_data_json = $model->getTemplateData();
					$config_data = $model->getTemplateData();
					
				}

				$save_as_name_value = $file_name;
				$filename 			= $file_name;
				$location 			= $file_name;
				break;

			default:
				return array("status" => false, "message" => sprintf(_("Type File invalid: %s"), $type_file));
		}

		$retarr = array("status" 			 => true,
						"message" 			 => _("OK"),
						"type" 				 => $type_file,
						"sendidt" 			 => $sendidt,
						"product_select" 	 => $product_select,
						"save_as_name_value" => $save_as_name_value,
						"original_name" 	 => $original_name,
						"filename" 			 => $filename,
						"location" 			 => $location,
						"config_data" 		 => $config_data ?? '',
						// "config_data_json"	 => $config_data_json ?? '',
					);
		return $retarr;
	}

	public function epm_advanced_poce_delete_config_custom(array $data)
	{
		$request 		  = $data['request'] ?? [];
		$request_args 	  = array("product_select", "type_file", "sql_select");
		$request_args_int = array("sql_select");
		$args_check 	  = $this->epm->system->check_request_args($request, $request_args, $request_args_int);
		$retarr  		  = null;
		$err_msg 		  = null;
		switch(true)
		{
			case (empty($args_check)):
			case ($args_check === false):
				$err_msg = _("Error in the process of checking the request arguments!");

			case ($args_check === true):
				break;

			case (is_string($args_check)):
			default:
				$err_msg = $args_check;
		}
		if (! empty($err_msg))
		{
			$retarr =  array("status" => false, "message" => $err_msg);
		}
		else
		{
			$product_select = $request['product_select'];
			$type_file 		= $request['type_file'];
			$sql_select   	= $request['sql_select'];

			$product = $this->epm->packagesdb->getProductByID($product_select);
			if (empty($product) || $product->isDestory())
			{
				$retarr = array("status" => false, "message" => _("Product not found!"));
			}
			else
			{
				switch ($type_file)
				{
					case "custom":
						try
						{
							if ($product->delConfigurationCustom($sql_select))
							{
								$retarr = array("status" => true, "message" => _("File delete ok!"));
							}
							else
							{
								$retarr = array("status" => false, "message" => _("File not delete!"));
							}
						}
						catch(\Exception $e)
						{
							$retarr =  array("status" => false, "message" => $e->getMessage());
						}
						break;

					default:
						$retarr = array("status" => false, "message" => sprintf(_("Type File [%s] not valid!"), $type_file));
				}
			}
		}
		return $retarr;
	}

	public function epm_advanced_poce_save_file(array $data)
	{
		$request 		  = $data['request'] 	?? [];
		$params 		  = $request['params']  ?? [];
		$request_args 	  = array(
			"type_file", 		// Type of file: file, template, custom
			"product_select", 	// ID Product
			"config_data",		// Configuration data
			"filename_new",		// New name of file to save
			"filename_now",		// Now name of file is editing
			"filename_src"		// Original name of file
		);
		$args_check = $this->epm->system->check_request_args($params, $request_args);
		$err_msg 	= null;
		switch(true)
		{
			case (empty($args_check)):
			case ($args_check === false):
				$err_msg = _("Error in the process of checking the request arguments!");

			case ($args_check === true):
				break;

			case (is_string($args_check)):
			default:
				$err_msg = $args_check;
		}
		if (! empty($err_msg))
		{
			return array(
				"status"  => false,
				"message" => $err_msg
			);
		}

		$product_select = $params['product_select'];
		$product_db 	= $this->epm->packagesdb->getProductByID($product_select);

		if (!$product_db->isExistID())
		{
			return array(
				"status"  => false,
				"message" => sprintf(_("Product '%s' not found!"), $product_select)
			);
		}
		

		// $iddb 		= $params['iddb'];
		$type_file 	  = $params['type_file'];
		$filename_new = $params['filename_new'];
		$filename_now = $params['filename_now'];
		$filename_src = $params['filename_src'];
		$config_data  = $params['config_data'];

		// Is $overwrite is true, then the file is saved with the same name
		// Is $overwrite is false, then the file is saved with a new name
		$overwrite 	 = empty($filename_new);
		$error_check = null;

		switch(true)
		{
			case $overwrite:
				// Is true, not is necessary check
				break;

			case empty($filename_new):
				$error_check = _("The name of the file to save is empty!");
				break;

			case $filename_new == $filename_src:
			case $filename_new == $filename_now:
				$error_check = _("The name of the file to save must be different from the original name!");
				break;
			
			case !in_array($type_file, array('file', 'template', 'custom')):
				$error_check = sprintf(_("Type File '%s' not valid!"), $type_file);
				break;
		}
		if (! is_null($error_check))
		{
			return array(
				"status"  => false,
				"message" => $error_check
			);
		}

		// Set Default values
		$tree_reload   	= false; // false > no reload tree
		$tree_node_find	= null;  // null  > no find node in tree
		try
		{
			switch($type_file)
			{
				case 'file':
					if ($overwrite)
					{
						$product_json = $this->epm->packages->getProductByProductID($product_select);
						$config_files = $product_db->getConfigurationFiles($filename_now);
						if (!in_array($filename_now, $config_files))
						{
							return array(
								"status"  => false,
								"message" => sprintf(_("File '%s' not found!"), $filename_now)
							);
						}
						$product_json->setConfigurationFile($filename_now, $config_data, true, false);
					}
					else
					{
						$newid 			= $product_db->setConfigurationCustom($filename_src, $filename_new, $config_data, false, false);
						$tree_reload   	= true;
						$tree_node_find = sprintf("custom_%s_%s", $product_select, $newid);
					}
				break;

				case 'template':
					//TODO: Pending save template
					/*
					$db = $this->db;
					$sql = 'INSERT INTO endpointman_custom_configs (name, original_name, product_id, data) VALUES (?,?,?,?)';
					$q = $db->prepare($sql);
					@ -790,7 +790,7 @@ class Endpointman_Advanced
					$retarr['type_file'] = "sql";
					$retarr['location'] = "SQL: ". $dget['save_as_name'];
					$retarr['sendidt'] = $newidinsert;
					*/
				break;

				case 'custom':
					if ($overwrite)
					{
						$filename_new = $filename_now;
					}

					$newid			= $product_db->setConfigurationCustom($filename_src, $filename_new, $config_data, $overwrite, false);
					$tree_reload	= !$overwrite;
					$tree_node_find = $overwrite ? null : sprintf("custom_%s_%s", $product_select, $newid);
				break;
			}
		}
		catch(\Exception $e)
		{
			return array(
				"status"  => false,
				"message" => sprintf(_("Error Saving, Type '%s': %s"), $type_file, $e->getMessage())
			);
		}
		return [
			"status"		 => true,
			"message"		 => _("Saved data successfully!"),
			"tree_reload"	 => $tree_reload,
			"tree_node_find" => $tree_node_find,
		];
	}

	function epm_advanced_poce_share(array $data)
	{
		$request 		  = $data['request'] 	?? [];
		$params 		  = $request['params']  ?? [];
		$request_args 	  = array(
			"type_file", 		// Type of file: file, template, custom
			"product_select", 	// ID Product
			"iddb",				// ID File in DB
			"filename_src",		// Original name of file
			"filename_now",		// Now name of file is editing
		);
		// $request_args_int = array("sendid");
		// $args_check 	  = $this->epm->system->check_request_args($request, $request_args, $request_args_int);
		$args_check 	  = $this->epm->system->check_request_args($params, $request_args);
		$retarr  		  = null;
		$err_msg 		  = null;
		switch(true)
		{
			case (empty($args_check)):
			case ($args_check === false):
				$err_msg = _("Error in the process of checking the request arguments!");

			case ($args_check === true):
				break;

			case (is_string($args_check)):
			default:
				$err_msg = $args_check;
		}
		if (! empty($err_msg))
		{
			return array(
				"status"  => false,
				"message" => $err_msg
			);
		}

		$product_select = $params['product_select'];
		$type_file 		= $params['type_file'];
		$iddb 			= $params['iddb'];
		$filename_src 	= $params['filename_src'];
		$filename_now 	= $params['filename_now'];

		if (!in_array(strtolower($type_file), array('file', 'custom')))
		{
			return array(
				"status"  => false,
				"message" => sprintf(_("Type File '%s' not valid!"), $type_file)
			);
		}

		$product_db   = $this->epm->packagesdb->getProductByID($product_select);
		$product_json = $this->epm->packages->getProductByProductID($product_select);
		if (empty($product_db) || $product_db->isDestory() || empty($product_json) || $product_json->isDestory())
		{
			return array(
				"status"  => false,
				"message" => _("Product not found!")
			);
		}

		switch($type_file)
		{
			case "file":
				$brand_raw	   = $product_json->getBrand()->getDirectory();
				$product_raw   = $product_json->getDirectory();
				$content_raw   = $product_json->getConfigurationFile($filename_src, false);
			break;

			case "custom":
				$brand_raw 	  = $product_db->getBrand()->getDirectory();
				$product_raw  = $product_db->getDirectory();
				$content_raw  = $product_db->getConfigurationCustom($iddb);
			break;
		}

		$return_submit = $this->epm_advanced_poce_submit_config($brand_raw, $product_raw, $filename_src, $content_raw);
		$retarr = array(
			"status"  => true,
			"message" => "Sent! Thanks :-)",
			'submit'  => $return_submit,
		);
		return $retarr;
	}

    /**
     * Used to send sample configurations to provisioner.net
     * NOTE: The user has to explicitly click a link that states they are sending the configuration to the project
     * We don't take configs on our own accord!!
     * @param <type> $brand Brand Directory
     * @param <type> $product Product Directory
     * @param <type> $orig_name The file's original name we are sending
     * @param <type> $data The config file's data
	*/
	private function epm_advanced_poce_submit_config($brand, $product, $orig_name, $data)
	{
		$posturl = 'http://www.provisioner.net/submit_config.php';

		$file_name_with_full_path = $this->epm->system->buildPath(sys_get_temp_dir(), 'data.txt');

		$fp = fopen( $file_name_with_full_path, 'w');
		fwrite($fp, $data);
		fclose($fp);
		

		$postvars = array(
			'brand' 		=> $brand,
			'product' 		=> $product,
			'origname' 		=> htmlentities(addslashes($orig_name)),
			'file_contents' => '@' . $file_name_with_full_path
		);

		$ch = curl_init($posturl);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postvars);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);  // DO NOT RETURN HTTP HEADERS
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  // RETURN THE CONTENTS OF THE CALL, probably not needed
		$Rec_Data = curl_exec($ch);

		ob_start();
		header("Content-Type: text/html");
		$Final_Out = ob_get_clean();
		curl_close($ch);
		unlink($file_name_with_full_path);

		return($Final_Out);
	}





	/**** FUNCIONES SEC MODULO "epm_advanced\oui_manager" ****/
	/**
	 * Get the list of OUIs
	 * 
	 * @param bool $include_one_select True to include the first option in the list with the text "Select Brand:" and false to not include it
	 * @param array $select Array with the IDs of the selected brands
	 * @return array Array with the status, message, brands, count, select and count_select.
	 * 
	 * 
	 */
	private function epm_advanced_oui_brands(array $data, bool $include_one_select = true, array $select = [])
	{
		$brands = [];
		if ($include_one_select)
		{
			$brands[] = array(
				'id'   		=> '',
				'name' 		=> _("Select Brand:"),
				'is_select' => empty($select),
			);
		}
		foreach($this->epm->packagesdb->getBrands(true, 'name', 'ASC') as $brand)
		{
			$brands[] = array(
				'id'   		=> $brand->getId(),
				'name' 		=> $brand->getName(),
				'is_select' => in_array($brand->getId(), $select),
			);
		}
		return array(
			"status"  		=> true,
			"message" 		=> "OK",
			"brands"  		=> $brands,
			"count"   		=> count($brands),
			'select'  		=> $select,
			'count_select'  => count($select),
		);
	}
		
	private function epm_advanced_oui_remove(array $data)
	{
		$request 		  = $data['request'] ?? [];
		$request_args 	  = array("oui_remove");
		$args_check 	  = $this->epm->system->check_request_args($request, $request_args);

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

		$oui_id	 = trim($request['oui_remove']);
		$oui	 = null;
		$all_oui = $this->epm->packagesdb->getOUIAll(true);
		$err_msg = null;


		if (empty($oui_id))
		{
			$err_msg = _("OUI is empty!");
		}
		else if (!is_numeric($oui_id))
		{
			$err_msg =  _("OUI must be a number!");
		}
		foreach($all_oui as $oui_key => $oui_data)
		{
			if ($oui_data['id'] == $oui_id)
			{
				if ($oui_data['custom'] != '1') 
				{
					$err_msg = _("OUI is not custom!");
					break;
				}
				$oui = $oui_data;
				break;
			}
		}
		if (empty($err_msg) && empty($oui))
		{
			$err_msg = _("OUI not found!");
		}

		if (!empty($err_msg))
		{
			return array("status" => false, "message" => $err_msg);
		}

		$brand = $this->epm->packagesdb->getBrandByID($oui['brand_id']);
		if (! $brand->deleteOUIByID($oui_id))
		{
		 	return array("status" => false, "message" => sprintf(_("Error deleting OUI '%s' from brand '%s'!"), $oui['oui'], $brand->getName()));
		}
		return array("status" => true, "message" => sprintf(_("OUI '%s' deleted from brand '%s' successfully!"), $oui['oui'], $brand->getName()));
	}

	private function epm_advanced_oui_add(array $data)
	{
		$request 		  = $data['request'] ?? [];
		$request_args 	  = array("new_oui_number", "new_oui_brand");
		$args_check 	  = $this->epm->system->check_request_args($request, $request_args);

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

		$brand 	  = null;
		$brand_id = trim($request['new_oui_brand']);
		$oui	  = strtoupper(preg_replace("/[^a-zA-Z0-9]/", "", trim($request['new_oui_number'])));
		$all_oui  = $this->epm->packagesdb->getOUIAll(true);

		$err_msg = null;
		if (empty($brand_id))
		{
			$err_msg = _("Brand is empty!");
		}
		else if ($brand_id < 0)
		{
			$err_msg = _("Brand send is number not valid!");
		}
		else if ($this->epm->packagesdb->isBrandExist($brand_id) == false)
		{
			$err_msg = sprintf(_("Brand '%s' not found!"), $brand_id);
		}
		else if (empty($oui))
		{
			$err_msg = _("OUI is empty!");
		}
		else if (strlen($oui) < 6)
		{
			$err_msg =  _("OUI must be at least 6 characters!");
		}
		else if (! preg_match('/^[a-fA-F0-9]+$/', $oui))
		{
			$err_msg =  _("OUI must be hexadecimal!");
		}
		else if (in_array($oui, array_keys($all_oui)))
		{
			$oui_exist = $all_oui[$oui];
			if ($oui_exist['brand_id'] == $brand_id)
			{
				$err_msg = sprintf(_("OUI '%s' already exists!"), $oui);
			}
			else
			{
				$err_msg = sprintf(_("OUI '%s' already exists for brand '%s'!"), $oui, $oui_exist['brand']);
			}
		}
		if (!empty($err_msg))
		{
			return array("status" => false, "message" => $err_msg);
		}

		$brand = $this->epm->packagesdb->getBrandByID($brand_id);
		if (! $brand->setOUI($oui, true))
		{
			return array("status" => false, "message" => sprintf(_("Error adding OUI '%s' to brand '%s'!"), $oui, $brand->getName()));
		}
		return array("status" => true, "message" => sprintf(_("OUI '%s' added to brand '%s' successfully!"), $oui, $brand->getName()));
	}















	//TODO: PENDIENTE REVISAR
	

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




	public function tftp_check(bool $noException = true)
	{
        //create a simple block here incase people have strange issues going on as we will kill http
        //by running this if the server isn't really running!

		$tfp_check = $this->epm->getConfig('tftp_check');
        if ($tfp_check != 1)
		{
			$this->epm->setConfig('tftp_check', 1);
			$config_location 	  = $this->epm->getConfig('config_location');
			$config_location_test = $this->epm->system->buildPath($config_location, 'TEST');
			$netstat_path 		  = $this->epm->getConfig('netstat_location');
            
            $return_shell = shell_exec(sprintf("%s -luan --numeric-ports", $netstat_path));
            if (preg_match('/:69\s/i', $return_shell))
			{
                $rand = md5(rand(10, 2000));
                if (file_put_contents($config_location_test, $rand))
				{
					$test_file = ($this->epm->system->tftp_fetch('127.0.0.1', 'TEST') != $rand);
                    if (file_exists($config_location_test))
					{
						unlink($config_location_test);
					}
					if (!$test_file)
					{
						$msg_error = _('Local TFTP Server is not correctly configured!');
						if ($noException) { return false; }
						throw new \Exception($msg_error);
					}
					return true;
                }
				else
				{
					$msg_error = sprintf(_("Unable to write to '%s'!"), $config_location);
					if ($noException) { return false; }
					throw new \Exception($msg_error);
                }
            }
			else
			{
                // $dis = false;
                if (file_exists('/etc/xinetd.d/tftp'))
				{
                    $contents = file_get_contents('/etc/xinetd.d/tftp');
                    if (preg_match('/disable.*=.*yes/i', $contents))
					{
						$msg_error = _('Disabled is set to "yes" in /etc/xinetd.d/tftp. Please fix <br />Then restart your TFTP service');
						if ($noException) { return false; }
						throw new \Exception($msg_error);
                    }
                }
                // if (!$dis)
				// {
					$msg_error = _('TFTP Server is not running. <br />See here for instructions on how to install one: <a href="http://wiki.provisioner.net/index.php/Tftp" target="_blank">http://wiki.provisioner.net/index.php/Tftp</a>');
					if ($noException) { return false; }
					throw new \Exception($msg_error);
                // }
            }
			$this->epm->setConfig('tftp_check', 0);
        }
		else
		{
			$msg_error = _('TFTP Server check failed on last past. Skipping');
			if ($noException) { return false; }
			throw new \Exception($msg_error);
        }
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
}