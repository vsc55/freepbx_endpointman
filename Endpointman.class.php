<?php
/**
 * Endpoint Manager Object Module
 *
 * @author Andrew Nagy
 * @author Javier Pastor
 * @license MPL / GPLv2 / LGPL
 * @package Provisioner
 */

namespace FreePBX\modules;
use FreePBX_Helpers;
use BMO;
use PDO;
use Exception;

require_once('lib/epm_system.class.php');
require_once('lib/epm_data_abstraction.class.php');
require_once('lib/epm_packages.class.php');
require_once('lib/epm_packages_db.class.php');
//require_once("lib/RainTPL.class.php");

require_once('Endpointman_Config.class.php');
require_once('Endpointman_Advanced.class.php');
require_once('Endpointman_Templates.class.php');
require_once('Endpointman_Devices.class.php');

#[\AllowDynamicProperties]
class Endpointman extends FreePBX_Helpers implements BMO {

	public $epm_config 	 	 = null;
	public $epm_advanced 	 = null;
	public $epm_templates 	 = null;
	public $epm_devices 	 = null;
	public $epm_oss 		 = null;
	public $epm_placeholders = null;

	public $freepbx		= null;
	public $db			= null; //Database from FreePBX
	public $config		= null;
	public $system		= null;
	public $eda			= null; //endpoint data abstraction layer
	public $astman		= null;
	public $packages	= null;
	public $packagesdb	= null;
	
	private $endpoint = null;

	// public $tpl; //Template System Object (RAIN TPL)

    public $error   = array(); //error construct
    public $message = array(); //message construct

	public $URL_UPDATE;

    public $MODULES_PATH;
	public $MODULE_PATH;

	public $PHONE_MODULES_PATH;
	public $PROVISIONER_PATH;
	public $EXPORT_PATH;
	public $TEMP_PATH;

	public $PROVISIONER_BASE; // Obsolete now used PHONE_MODULES_PATH

	// const URL_PROVISIONER = "http://mirror.freepbx.org/provisioner/v3/";
	const URL_PROVISIONER = "https://raw.githubusercontent.com/billsimon/provisioner/packaging/";
	
	const TABLES = array(
		'devices' 		 	=> 'devices',
		'epm_line_list'  	=> 'endpointman_line_list',
		'epm_model_list' 	=> 'endpointman_model_list',
		'epm_template_list' => 'endpointman_template_list',
		'epm_product_list'  => 'endpointman_product_list',
		'epm_mac_list' 		=> 'endpointman_mac_list',
		'epm_brands_list'	=> 'endpointman_brand_list',
		'epm_oui_list'		=> 'endpointman_oui_list',
		'epm_global_vars'   => 'endpointman_global_vars',
	);

	
	private $pagedata;

	// final public const ASTERISK_SECTION = 'app-queueprio';


	public function __construct($freepbx = null)
	{
		if ($freepbx == null) {
			throw new \Exception(_("Not given a FreePBX Object"));
		}
		parent::__construct($freepbx);

		$this->freepbx 	 = $freepbx;
		$this->db 		 = $freepbx->Database;
		$this->config 	 = $freepbx->Config;
		$this->astman 	 = $freepbx->astman;
		$this->system 	 = new Endpointman\epm_system();

		$this->setConfig('disable_epm', false);

		if (!$this->isConfigExist('tz') || empty($this->getConfig('tz')))
		{
			// Set TimeZone by config FREEPBX is not set
			$this->setConfig('tz', $this->config->get('PHPTIMEZONE'));
		}
		date_default_timezone_set($this->getConfig('tz'));
		
		$this->eda 		 = new epm_data_abstraction($this);

		
        //Generate empty array
        $this->error   = array();
        $this->message = array();
		$this->URL_UPDATE   = $this->getConfig('update_server');
        $this->MODULES_PATH = $this->system->buildPath($this->config->get('AMPWEBROOT'), 'admin', 'modules');
		$this->MODULE_PATH 	= $this->system->buildPath($this->MODULES_PATH, "endpointman");

		// Define the location of phone modules, keeping it outside of the module directory so that when 
		// the user updates endpointmanager they don't lose all of their phones
		// TODO: Migrate _ep_phone_modules to spool or other location
		$this->PHONE_MODULES_PATH = $this->system->buildPath($this->MODULES_PATH, '_ep_phone_modules');

		$this->TEMP_PATH 		  = $this->system->buildPath($this->PHONE_MODULES_PATH, "temp");
		$this->PROVISIONER_PATH   = $this->system->buildPath($this->TEMP_PATH, "provisioner");
		$this->EXPORT_PATH		  = $this->system->buildPath($this->TEMP_PATH, "export");

        if (! file_exists($this->MODULE_PATH))
		{
            die(sprintf(_("Can't Load Local Endpoint Manager Directory (%s)!"), __CLASS__));
        }

		// Check if directories needed are created
		$this->checkPathsFiles();

		if (!file_exists($this->PHONE_MODULES_PATH))
		{
			die(_('Endpoint Manager can not create the modules folder!'));
		}

        //Define error reporting
        if (($this->getConfig('debug')) AND (!isset($_REQUEST['quietmode'])))
		{
            error_reporting(E_ALL);
            ini_set('display_errors', 1);
        }
		else
		{
            ini_set('display_errors', 0);
        }

        //Check if config location is writable and/or exists!
        if ($this->isConfigExist('config_location'))
		{
			$config_location = $this->getConfig('config_location');
            if (is_dir($config_location))
			{
                if (!is_writeable($config_location))
				{
                    $user  = exec('whoami');
                    $group = exec("groups");
                    $this->error['config_location'] = sprintf(_("Configuration Directory is not writable!<br />
                            Please change the location: <a href='%1\$s'>Here</a><br />
                            Or run this command on SSH: <br />
							'chown -hR root:%2\$s %3\$s'<br />
							'chmod g+w %3\$s'"), 'config.php?display=epm_advanced', $group, $config_location);
					$this->setConfig('disable_epm', true);
                }
            }
			else
			{
                $this->error['config_location'] = sprintf(_("Configuration Directory is not a directory or does not exist! Please change the location here: <a href='%s'>Here</a>"), "config.php?display=epm_advanced");
				$this->setConfig('disable_epm', true);
            }
        }

        //$this->tpl = new RainTPL(MODULE_PATH . '/_old/templates/freepbx', MODULE_PATH . '/_old/templates/freepbx/compiled', '/admin/assets/endpointman/images');
		//$this->tpl = new RainTPL('/admin/assets/endpointman/images');

		$this->packages			= new Endpointman\Packages($this);
		$this->packagesdb		= new Endpointman\PackagesDB($this);
		$this->epm_config 		= new Endpointman_Config($this);
		$this->epm_advanced 	= new Endpointman_Advanced($this);
		$this->epm_templates 	= new Endpointman_Templates($this);
		$this->epm_devices 		= new Endpointman_Devices($this);
		$this->epm_oss 		    = new Endpointman_Devices($this);
		$this->epm_placeholders = new Endpointman_Devices($this);
	}

	private function checkPathsFiles()
	{
		$return_data = true;
		$operations = [
			[
				'path'			=> $this->system->buildPath($this->PHONE_MODULES_PATH),
				'permissions'	=> 0755,
				'type'			=> 'dir',
				'action'		=> 'check',
				'action_error'	=> 'break'
			],
			// [
			// 	'path'	 => $this->system->buildPath($this->PHONE_MODULES_PATH, "setup.php"),
			// 	'action' => 'del'
			// ],
			[
				'path' 		  => $this->system->buildPath($this->PHONE_MODULES_PATH, "endpoint"),
				'permissions' => 0755,
				'type' 		  => 'dir',
				'action' 	  => 'check'
			],
			[
				'path' 		  => $this->TEMP_PATH,
				'permissions' => 0755,
				'type' 		  => 'dir',
				'action' 	  => 'check'
			],
			[
				'path' 		  => $this->PROVISIONER_PATH,
				'permissions' => 0755,
				'type' 		  => 'dir',
				'action' 	  => 'check'
			],
			[
				'path' 		  => $this->EXPORT_PATH,
				'permissions' => 0755,
				'type' 		  => 'dir',
				'action' 	  => 'check'
			],
		];
		foreach ($operations as $operation)
		{
			$path 			= $operation['path'] 				?? '';
			$permissions	= $operation['permissions'] 		?? 0755;
			$recursive		= $operation['recursive']			?? true;
			$action			= strtolower($operation['action'])	?? 'check';
			$action_error	= $operation['action_error'] 		?? '';
			$action_return 	= true;

			if (empty($path))
			{
				continue;
			}

			switch(strtolower($action))
			{
				case 'check':
					if (!file_exists($path))
					{
						if ($operation['type'] === 'dir')
						{
							$action_return = mkdir($path, $permissions, $recursive);
						}
						else
						{
							$action_return = touch($path);
							if ($action_return)
							{
								$action_return = chmod($path, $permissions);
							}
						}
					}
					else
					{
						// Get only the permission bits
						$currentPermissions = fileperms($path) & 0777;
						if ($currentPermissions !== $permissions)
						{
							$action_return = chmod($path, $permissions);
						}
					}
					break;

				case 'del':
					if (file_exists($path))
					{
						if (is_dir($path))
						{
							$action_return = rmdir($path);
						}
						else
						{
							$action_return = unlink($path);
						}
					}
					
					break;
			}

			if ($action_return === false)
			{
				$return_data = false;
				if ($action_error === 'break')
				{
					break;
				}
			}
		}

		return $return_data;
	}

	public function chownFreepbx()
	{
		$files = array();
		$files[] = array(
			'type'  => 'dir',
			'path'  => $this->PHONE_MODULES_PATH,
			'perms' => 0755
		);
		$files[] = array(
			'type'  => 'file',
			'path'  => $this->system->buildPath($this->PHONE_MODULES_PATH, "setup.php"),
			'perms' => 0755
		);
		$files[] = array(
			'type'  => 'dir',
			'path'  => '/tftpboot',
			'perms' => 0755
		);
		return $files;
	}

	/**
	 * Allow or deny access to ajax requests
	 * 
	 * @param string $req The request command ($_REQUEST['command'])
	 * @param array $setting The setting array to be passed by reference
	 * 						$setting['authenticate'] = true/false (default true), if true, the request will be authenticated
	 * 						$setting['allowremote']  = true/false (default false), if true, the request will be allowed from remote
	 * @return bool True if the request is allowed, false otherwise
	 */
	public function ajaxRequest($req, &$setting)
	{
		// ** Allow remote consultation with Postman **
		// ********************************************
		$setting['authenticate'] = false;
		$setting['allowremote']  = true;
		return true;
		// ********************************************

		$request 	= freepbxGetSanitizedRequest();

		$data = array(
			'epm' 		 => $this,
			'request' 	 => $request,
			'module_sec' => strtolower(trim($request['module_sec'] ?? '')),
		);

		switch($data['module_sec'])
		{
			case "epm_devices":
				$return_status = $this->epm_devices->ajaxRequest($req, $setting);
				break;

			case "epm_oss":
				$return_status = $this->epm_oss->ajaxRequest($req, $setting);
				break;

			case "epm_placeholders":
				$return_status = $this->epm_placeholders->ajaxRequest($req, $setting);
				break;

			case "epm_config":
				$return_status = $this->epm_config->ajaxRequest($req, $setting, $data);
				break;

			case "epm_advanced":
				$return_status = $this->epm_advanced->ajaxRequest($req, $setting, $data);
				break;

			case "epm_templates":
				$return_status = $this->epm_templates->ajaxRequest($req, $setting);
				break;
			default:
				$return_status = false;
		}
        return $return_status;
    }

	/**
	 * When making an ajax query to the module, this function is in charge of processing it and returning with the results
	 * 
	 * @return array The data to be returned to the client
	 * 				$data_return['status'] = true/false
	 * 				$data_return['message'] = string
	 * 
	 */
    public function ajaxHandler()
	{
		$request = freepbxGetSanitizedRequest();
		$data = array(
			'epm' 		 => $this,
			'request' 	 => $request,
			'module_sec' => strtolower(trim($request['module_sec'] ?? '')),
			'module_tab' => strtolower(trim($request['module_tab'] ?? '')),
			'command' 	 => strtolower(trim($request['command']    ?? '')),
		);

		if (empty($data['command']))
		{
			$data_return = array( "status" => false, "message" => _("No command was sent!") );
		}
		else
		{
			switch ($data['module_sec'])
			{
				case "epm_devices":
					$data_return = $this->epm_devices->ajaxHandler($data['module_tab'], $data['command']);
					break;

				case "epm_oss":
					$data_return = $this->epm_oss->ajaxHandler($data['module_tab'], $data['command']);
					break;

				case "epm_placeholders":
					$data_return = $this->epm_placeholders->ajaxHandler($data['module_tab'], $data['command']);
					break;

				case "epm_templates":
					$data_return = $this->epm_templates->ajaxHandler($data['module_tab'], $data['command']);
					break;

				case "epm_config":
					$data_return = $this->epm_config->ajaxHandler($data);
					break;

				case "epm_advanced":
					$data_return = $this->epm_advanced->ajaxHandler($data);
					break;

				case "epm_ajax":
					$id = $request['id'] ?? null;
					
					//Although id == "0" is redundant since emty encompasses it, it is left for better code compression.
					if(empty($id) or $id == "0")
					{
						$data_return = array(
							0 => array(
								"optionValue"  => "",
								"optionDisplay" => ""
							)
						);
					}
					else
					{
						$macid = $request['macid'] ?? null;
						$mac   = $request['mac'] ?? null;

						switch($data['command'])
						{
							case "model":
								$sql = sprintf("SELECT * FROM %s WHERE enabled = 1 AND brand = %s", self::TABLES['epm_model_list'], $id);
								break;
							
							case "template":
								$sql = sprintf("SELECT id, name as model FROM  %s WHERE  product_id = '%s'", self::TABLES['epm_template_list'], $id);
								break;

							case "mtemplate":
								$sql = sprintf("SELECT id, name as model FROM  %s WHERE  model_id = '%s'", self::TABLES['epm_template_list'], $id);
								break;

							case "template2":
								$sql = sprintf(
									"SELECT DISTINCT etl.id, etl.name as model FROM %s as etl, %s as eml, %s as epl
									WHERE etl.product_id = eml.product_id AND eml.product_id = epl.id AND eml.id = '%s'",
									self::TABLES['epm_template_list'], self::TABLES['epm_model_list'], self::TABLES['epm_product_list'], $id
								);
								break;

							case "model_clone":
								$sql = sprintf(
									"SELECT eml.id, eml.model as model FROM %s as eml, %s as epl
									WHERE epl.id = eml.product_id AND eml.enabled = 1 AND eml.hidden = 0 AND product_id = '%s'",
									self::TABLES['epm_model_list'], self::TABLES['epm_product_list'], $id
								);
								break;

							case "lines":
								if(!empty($macid))
								{
									$sql = sprintf(
										"SELECT eml.max_lines FROM %s as eml, %s as ell, %s as emacl
										WHERE emacl.id = ell.mac_id AND eml.id = emacl.model AND ell.luid = %s",
										self::TABLES['epm_model_list'], self::TABLES['epm_line_list'], self::TABLES['epm_mac_list'], $macid
									);
								}
								elseif(!empty($mac))
								{
									$sql   = sprintf("SELECT id FROM %s WHERE mac = '%s'", self::TABLES['epm_mac_list'], $this->epm_advanced->mac_check_clean($mac));
									$macid = $this->eda->sql($sql, 'getOne');
									if($macid)
									{
										$mac = $macid;
										$sql = sprintf(
											"SELECT eml.max_lines FROM %s as eml, %s as ell, %s as emacl
											WHERE emacl.id = ell.mac_id AND eml.id = emacl.model AND emacl.id = %s",
											self::TABLES['epm_model_list'], self::TABLES['epm_line_list'], self::TABLES['epm_mac_list'], $macid
										);
									}
									else
									{
										$mac = null;
										$sql = sprintf("SELECT max_lines FROM %s WHERE id = '%s'", self::TABLES['epm_model_list'], $id);
									}
								}
								else
								{
									$sql = sprintf("SELECT max_lines FROM %s WHERE id = '%s'", self::TABLES['epm_model_list'], $id);
								}
								break;	
						}

						switch($data['command'])
						{
							case "template":
							case "template2":
							case "mtemplate":
								$out[0]['optionValue'] = 0;
								$out[0]['optionDisplay'] = _("Custom...");
								$i=1;
								break;

							case "model":
								$out[0]['optionValue'] = 0;
								$out[0]['optionDisplay'] = "";
								$i=1;
								break;

							default:
								$i=0;
						}

						
					
						$result = array();
						if(($data['command'] == "lines") && (!empty($mac)) && (!empty($macid)))
						{
							$count = $this->eda->sql($sql, 'getOne');
							for($z=0; $z<$count; $z++)
							{
								$result[$z] = array(
									'id' => $z + 1,
									'model' => $z + 1,
								);
							}
						}
						elseif(!empty($macid))
						{
							$result = $this->linesAvailable($macid);
						}
						elseif(!empty($mac))
						{
							$result = $this->linesAvailable(NULL, $mac);
						}
						else
						{
							$result = $this->eda->sql($sql, 'getAll', \PDO::FETCH_ASSOC);
						}

						$data_return = array();
						foreach($result as $row)
						{
							if((!empty($macid)) OR (!empty($mac)))
							{
								$data_return[$i] = array(
									'optionValue'   => $row['value'],
									'optionDisplay' => $row['text'],
								);
							}
							else
							{
								$data_return[$i] = array(
									'optionValue'   => $row['id'],
									'optionDisplay' => $row['model'],
								);
							}
							$i++;
						}
					}
					break;

				default:
					$data_return = array("status" => false, "message" => _("Invalid section module '%s'!", $data['module_sec']));
			}
		}
		return $data_return;
    }






	public static function myDialplanHooks()
	{
		return true;
	}

	public function doDialplanHook(&$ext, $engine, $priority)
	{
		global $core_conf;

		if ($engine != "asterisk") { return; }

		// if (isset($core_conf) && is_a($core_conf, "core_conf") && (method_exists($core_conf, 'addSipNotify')))
		if (isset($core_conf) && $core_conf instanceof core_conf && method_exists($core_conf, 'addSipNotify')) {
			$sipNotifications = [
				'polycom-check-cfg' 	 => ['Event' => 'check-sync', 'Content-Length' => '0'],
				'polycom-reboot' 		 => ['Event' => 'check-sync', 'Content-Length' => '0'],
				'sipura-check-cfg' 		 => ['Event' => 'resync', 'Content-Length' => '0'],
				'grandstream-check-cfg'  => ['Event' => 'sys-control'],
				'cisco-check-cfg' 		 => ['Event' => 'check-sync', 'Content-Length' => '0'],
				'reboot-snom' 			 => ['Event' => 'reboot', 'Content-Length' => '0'],
				'aastra-check-cfg' 		 => ['Event' => 'check-sync', 'Content-Length' => '0'],
				'linksys-cold-restart' 	 => ['Event' => 'reboot_now', 'Content-Length' => '0'],
				'linksys-warm-restart' 	 => ['Event' => 'restart_now', 'Content-Length' => '0'],
				'spa-reboot' 			 => ['Event' => 'reboot', 'Content-Length' => '0'],
				'reboot-yealink' 		 => ['Event' => 'check-sync;reboot=true', 'Content-Length' => '0'],
				'reboot-gigaset' 		 => ['Event' => 'check-sync;reboot=true', 'Content-Length' => '0'],
				'panasonic-check-cfg' 	 => ['Event' => 'check-sync', 'Content-Length' => '0'],
				'snom-check-cfg' 		 => ['Event' => 'check-sync', 'Content-Length' => '0'],
			];
		
			foreach ($sipNotifications as $name => $params)
			{
				$core_conf->addSipNotify($name, $params);
			}
		}
	}	

	public static function myGuiHooks()
	{
		return array("core");
	}

	// Called when generating the page
	public function doGuiHook(&$cc)
	{
		$request = freepbxGetSanitizedRequest();
		$display = $request['display'] 	 ?? '';

		if ($display != "extensions" && $display != "devices")
		{
			return;
		}


		$action     = $request['action'] 	 	?? null;
		$extdisplay = $request['extdisplay'] 	?? null;
		$tech		= $request['tech_hardware'] ?? null;

		if (!empty($extdisplay))
		{
			$sql = sprintf("SELECT tech FROM %s WHERE id = %s", self::TABLES['devices'], $extdisplay);
			// $tech = $this->endpoint->eda->sql($sql, 'getOne');
			$tech = $this->eda->sql($sql, 'getOne');
		}

		$extension_address = $this->astman->database_get("SIP", "Registry/".$extdisplay);
		$extension_address = explode(":", $extension_address);
		// echo $extension_address['0'];

		if (in_array($tech, array('sip', 'pjsip', 'sip_generic')))
		{
			// Don't display this stuff it it's on a 'This xtn has been deleted' page.
			if ($action != 'del')
			{
				$section = _('End Point Manager');

				$js = "
					$.ajaxSetup({ cache: false });
					$.ajax({
						url: window.FreePBX.ajaxurl,
						type: 'POST',
						data: {
							module: 'endpointman',
							module_sec: 'epm_ajax',
							module_tab: '',
							command: 'model',
							id: value
						},
						dataType: 'json',
						error: function(xhr, ajaxOptions, thrownError) {
							fpbxToast('"._('ERROR AJAX:')." ' + thrownError,'ERROR (' + xhr.status + ')!','error');
							return false;
						},
						success: function(j) {
							var options = '';
							for (var i = 0; i < j.length; i++) {
								options += sprintf('<option value=\"%s\">%s</option>', j[i].optionValue, j[i].optionDisplay);
							}
							$('#epm_model').html(options);
							$('#epm_model option:first').attr('selected', 'selected');
							$('#epm_temps').html('<option></option>');
							$('#epm_temps option:first').attr('selected', 'selected');
							$('#epm_line').html('<option></option>');
							$('#epm_line option:first').attr('selected', 'selected');
						}
					});
				";
				$cc->addjsfunc('brand_change(value)', $js);
				unset($js);

				// $sql = sprintf("SELECT mac_id, luid, line FROM %s WHERE ext = '%s'", self::TABLES['epm_line_list'], $extdisplay);
				// $line_info = $this->endpoint->eda->sql($sql, 'getRow', \PDO::FETCH_ASSOC);
				
				$sql = sprintf("SELECT * FROM %s WHERE ext = '%s'", self::TABLES['epm_line_list'], $extdisplay);
				$line_info = $this->eda->sql($sql, 'getRow', \PDO::FETCH_ASSOC);
				if ($line_info)
				{
					$js = "
						$.ajaxSetup({ cache: false });
						$.ajax({
							url: window.FreePBX.ajaxurl,
							type: 'POST',
							data: {
								module: 'endpointman',
								module_sec: 'epm_ajax',
								module_tab: '',
								command: 'template2',
								id: value
							},
							dataType: 'json',
							error: function(xhr, ajaxOptions, thrownError) {
								fpbxToast('"._('ERROR AJAX:')." ' + thrownError,'ERROR (' + xhr.status + ')!','error');
								return false;
							},
							success: function(j) {
								var options = '';
								for (var i = 0; i < j.length; i++) {
									options += sprintf('<option value=\"%s\">%s</option>', j[i].optionValue, j[i].optionDisplay);
								}
								$('#epm_temps').html(options);
								$('#epm_temps option:first').attr('selected', 'selected');
							}
						});

						$.ajaxSetup({ cache: false });
						$.ajax({
							url: window.FreePBX.ajaxurl,
							type: 'POST',
							data: {
								module: 'endpointman',
								module_sec: 'epm_ajax',
								module_tab: '',
								macid: macid,
								command: 'lines',
								id: value
							},
							dataType: 'json',
							error: function(xhr, ajaxOptions, thrownError) {
								fpbxToast('"._('ERROR AJAX:')." ' + thrownError,'ERROR (' + xhr.status + ')!','error');
								return false;
							},
							success: function(j) {
								var options = '';
								for (var i = 0; i < j.length; i++) {
									options += sprintf('<option value=\"%s\">%s</option>', j[i].optionValue, j[i].optionDisplay);
								}
								$('#epm_line').html(options);
								$('#epm_line option:first').attr('selected', 'selected');
							}
						});
					";
					$cc->addjsfunc('model_change(value,macid)', $js);
					unset($js);

					$info = $this->endpoint->get_phone_info($line_info['mac_id']);

					$brand_list = $this->brands_available($info['brand_id'], true);
					if (!empty($info['brand_id']))
					{
						$model_list 	= $this->endpoint->models_available(NULL, $info['brand_id']);
						$line_list		= $this->linesAvailable($line_info['luid']);
						$template_list 	= $this->endpoint->display_templates($info['product_id']);
					}
					else
					{
						$model_list 	= array();
						$line_list 		= array();
						$template_list  = array();
					}

					$checked = false;
					$cc->addguielem($section, new \gui_checkbox('epm_delete', $checked, _('Delete'), _('Delete this Extension from Endpoint Manager')), 9);
					$cc->addguielem($section, new \guitext('epm_account_phone', sprintf('<a href="%s" target="_blank" id ="%s">%s</a>', sprintf("http://%s", $extension_address[0]), "epm_account_phone", _('Go to phone web interface')) ));
					$cc->addguielem($section, new \gui_textbox('epm_mac', $info['mac'], _('MAC Address'), _('The MAC Address of the Phone Assigned to this Extension/Device. <br />(Leave Blank to Remove from Endpoint Manager)'), '', _('Please enter a valid MAC Address'), true, 17, false), 9);
					$cc->addguielem($section, new \gui_selectbox('epm_brand', $brand_list, $info['brand_id'], _('Brand'), _('The Brand of this Phone.'), false, 'frm_' . $display . '_brand_change(this.options[this.selectedIndex].value)', false), 9);
					$cc->addguielem($section, new \gui_selectbox('epm_model', $model_list, $info['model_id'], _('Model'), _('The Model of this Phone.'), false, 'frm_' . $display . '_model_change(this.options[this.selectedIndex].value,\'' . $line_info['luid'] . '\')', false), 9);
					$cc->addguielem($section, new \gui_selectbox('epm_line', $line_list, $line_info['line'], _('Line'), _('The Line of this Extension/Device.'), false, '', false), 9);
					$cc->addguielem($section, new \gui_selectbox('epm_temps', $template_list, $info['template_id'], _('Template', 'The Template of this Phone.'), false, '', false), 9);
					$cc->addguielem($section, new \gui_checkbox('epm_reboot', $checked, _('Reboot'), _('Reboot this Phone on Submit')), 9);
				}
				else
				{

					$js = "
						$.ajaxSetup({ cache: false });
						$.ajax({
							url: window.FreePBX.ajaxurl,
							type: 'POST',
							data: {
								module: 'endpointman',
								module_sec: 'epm_ajax',
								module_tab: '',
								command: 'template2',
								id: value
							},
							dataType: 'json',
							error: function(xhr, ajaxOptions, thrownError) {
								fpbxToast('"._('ERROR AJAX:')." ' + thrownError,'ERROR (' + xhr.status + ')!','error');
								return false;
							},
							success: function(j) {
								var options = '';
								for (var i = 0; i < j.length; i++) {
									options += sprintf('<option value=\"%s\">%s</option>', j[i].optionValue, j[i].optionDisplay);
								}
								$('#epm_temps').html(options);
								$('#epm_temps option:first').attr('selected', 'selected');
							}
						});

						$.ajaxSetup({ cache: false });
						$.ajax({
							url: window.FreePBX.ajaxurl,
							type: 'POST',
							data: {
								module: 'endpointman',
								module_sec: 'epm_ajax',
								module_tab: '',
								mac: mac,
								command: 'lines',
								id: value
							},
							dataType: 'json',
							error: function(xhr, ajaxOptions, thrownError) {
								fpbxToast('"._('ERROR AJAX:')." ' + thrownError,'ERROR (' + xhr.status + ')!','error');
								return false;
							},
							success: function(j) {
								var options = '';
								for (var i = 0; i < j.length; i++) {
									options += sprintf('<option value=\"%s\">%s</option>', j[i].optionValue, j[i].optionDisplay);
								}
								$('#epm_line').html(options);
								$('#epm_line option:first').attr('selected', 'selected');
							}
						});
					";
					$cc->addjsfunc('model_change(value,mac)', $js);
					unset($js);

					$brand_list 	= $this->brands_available(NULL, true);
					$model_list 	= array();
					$line_list 		= array();
					$template_list 	= array();

					$cc->addguielem($section, new \gui_textbox('epm_mac', "", _('MAC Address'), _('The MAC Address of the Phone Assigned to this Extension/Device. <br />(Leave Blank to Remove from Endpoint Manager)'), '', _('Please enter a valid MAC Address'), true, 17, false), 9);
					$cc->addguielem($section, new \gui_selectbox('epm_brand', $brand_list, "", _('Brand'), _('The Brand of this Phone.'), false, 'frm_' . $display . '_brand_change(this.options[this.selectedIndex].value)', false), 9);
					$cc->addguielem($section, new \gui_selectbox('epm_model', $model_list, "", _('Model'), _('The Model of this Phone.'), false, 'frm_' . $display . '_model_change(this.options[this.selectedIndex].value,document.getElementById(\'epm_mac\').value)', false), 9);
					$cc->addguielem($section, new \gui_selectbox('epm_line', $line_list, "", _('Line'), _('The Line of this Extension/Device.'), false, '', false), 9);
					$cc->addguielem($section, new \gui_selectbox('epm_temps', $template_list, "", _('Template'), _('The Template of this Phone.'), false, '', false), 9);
					$cc->addguielem($section, new \guitext('epm_note', _('Note: This might reboot the phone if it\'s already registered to Asterisk')));
				}
			}
		}
		return;
	}



	/**
	 * Get the list of pages that the module will be displayed on the GUI
	 */
	public static function myConfigPageInits()
	{
		return array("extensions", "devices");
	}

	/**
	 * Get Inital Display
	 * @param {string} $display The Page name
	 */
	public function doConfigPageInit($display)
	{
		$request = freepbxGetSanitizedRequest();

		//TODO: Pendiente revisar y eliminar moule_tab.
		$data = array(
			'epm' 		 => $this,
			'request' 	 => $request,
			'module_sec' => $display,
			'module_tab' => strtolower(trim($request['module_tab'] ?? $request['subpage'] ?? '')),
			'command' 	 => strtolower(trim($request['command'] ?? '')),
		);

		// //TODO: Pendiente revisar y eliminar moule_tab.
		// $module_tab = isset($request['module_tab'])? trim($request['module_tab']) : '';
		// if ($module_tab == "") {
		// 	$module_tab = isset($request['subpage'])? trim($request['subpage']) : '';
		// }
		// $command = isset($request['command'])? trim($request['command']) : '';

		$extdisplay = '';
		$step2 		= false;
		switch ($display)
		{
			case "epm_devices":
				$this->epm_devices->doConfigPageInit($data['module_tab'], $data['command']);
				break;

			case "epm_oss":
				$this->epm_oss->doConfigPageInit($data['module_tab'], $data['command']);
				break;

			case "epm_placeholders":
				$this->epm_placeholders->doConfigPageInit($data['module_tab'], $data['command']);
				break;

			case "epm_templates":
				$this->epm_templates->doConfigPageInit($data['module_tab'], $data['command']);
				break;

			case "epm_config":
				$this->epm_config->doConfigPageInit($data);
				break;

			case "epm_advanced":
				$this->epm_advanced->doConfigPageInit($data['module_tab'], $data['command']);
				break;
			
			case "extensions":
				$extdisplay = $request['extension'] ?? $request['extdisplay'] ?? null;
				$step2 = true;
				break;
	
			case "devices":
				$extdisplay = $request['deviceid'] ?? $request['extdisplay'] ?? null;
				$step2 = true;
				break;

			default:
				// die(_("Invalid section module!"));
				return true;
		}

		if (!$step2)
		{
			return true;
		}


		$type       = '';
		$tech       = '';

		if (isset($extdisplay) && !empty($extdisplay))
		{
			$sql = "SELECT tech FROM devices WHERE id = :id";
			$stmt = $this->db->prepare($sql);
			$stmt->execute([':id' => $extdisplay]);
			if ($stmt->rowCount() === 0)
			{
				$tech = "sip";
				$type = 'new';
			}
			else
			{
				$tech = $stmt->fetch(\PDO::FETCH_ASSOC);
				if(in_array($tech, ['sip', 'pjsip']))
				{
					$type = 'edit';
				}
			}
		}
		elseif(isset($request['tech_hardware']) OR isset($request['tech']))
		{
			$tech = $request['tech_hardware'] ?? $request['tech'];
			if (in_array($tech, ['sip_generic', 'sip', 'pjsip']))
			{
				$tech = "sip";
				$type = 'new';
			}
		}

		if ((($tech == 'sip') OR ($tech == 'pjsip')) AND (!empty($type)))
		{
			$action = $request['action'] ?? null;
			$delete = $request['epm_delete'] ?? null;


			$path_funciones_inf = $this->system->buildPath($this->MODULE_PATH, 'includes/functions.inc');

			if (file_exists($path_funciones_inf))
			{
				require_once($path_funciones_inf);

				$this->endpoint = new \endpointmanager($this);
				ini_set('display_errors', 0);

				switch($action)
				{
					case "del":
						$sql = sprintf("SELECT mac_id, luid FROM %s WHERE ext = %s", self::TABLES['epm_line_list'], $extdisplay);
						// $macid = $this->endpoint->eda->sql($sql, 'getRow', \PDO::FETCH_ASSOC);
						$macid = $this->eda->sql($sql, 'getRow', \PDO::FETCH_ASSOC);
						if ($macid) {
							$this->endpoint->delete_line($macid['luid'], TRUE);
						}
						break;

					case "add":
					case "edit":
						if (isset($delete))
						{
							$sql = sprintf("SELECT mac_id, luid FROM %s WHERE ext = %s", self::TABLES['epm_line_list'], $extdisplay);
							// $macid = $this->endpoint->eda->sql($sql, 'getRow', \PDO::FETCH_ASSOC);
							$macid = $this->eda->sql($sql, 'getRow', \PDO::FETCH_ASSOC);
							if ($macid) {
								$this->endpoint->delete_line($macid['luid'], TRUE);
							}
						}
	
						$mac = isset($request['epm_mac']) ? $request['epm_mac'] : null;
						if (!empty($mac))
						{
							//Mac is set
							$brand = isset($request['epm_brand']) ? $request['epm_brand'] : null;
							$model = isset($request['epm_model']) ? $request['epm_model'] : null;
							$line  = isset($request['epm_line']) ? $request['epm_line'] : null;
							$temp  = isset($request['epm_temps']) ? $request['epm_temps'] : null;
							if (isset($request['name']))
							{
								$name = isset($request['name']) ? $request['name'] : null;
							}
							else
							{
								$name = isset($request['description']) ? $request['description'] : null;
							}
							if (isset($request['deviceid']))
							{
								if ($request['devicetype'] == "fixed")
								{
									//SQL to get the Description of the  extension from the extension table
									$sql = sprintf("SELECT name FROM %s WHERE extension = '%s'", "users", $request['deviceuser']);
									// $name_o = $this->endpoint->eda->sql($sql, 'getOne');
									$name_o = $this->eda->sql($sql, 'getOne');
									if($name_o) {
										$name = $name_o;
									}
								}
							}
	
							$reboot = isset($request['epm_reboot']) ? $request['epm_reboot'] : null;
	
							if ($this->epm_advanced->mac_check_clean($mac))
							{
								$sql = sprintf("SELECT id FROM %s WHERE mac = '%s'", "endpointman_mac_list", $this->epm_advanced->mac_check_clean($mac));
								// $macid = $this->endpoint->eda->sql($sql, 'getOne');
								$macid = $this->eda->sql($sql, 'getOne');
								if ($macid)
								{
									//In Database already
	
									$sql = sprintf('SELECT * FROM %s WHERE ext = %s AND mac_id = %s', self::TABLES['epm_line_list'], $extdisplay, $macid);
									// $lines_list = & $this->endpoint->eda->sql($sql, 'getRow', \PDO::FETCH_ASSOC);
									$lines_list = & $this->eda->sql($sql, 'getRow', \PDO::FETCH_ASSOC);
	
									if (($lines_list) AND (isset($model)) AND (isset($line)) AND (!isset($delete)) AND (isset($temp)))
									{
										//Modifying line already in the database
										$this->endpoint->update_device($macid, $model, $temp, $lines_list['luid'], $name, $lines_list['line']);
	
										$row = $this->endpoint->get_phone_info($macid);
										if (isset($reboot))
										{
											$this->endpoint->prepare_configs($row);
										}
										else
										{
											$this->endpoint->prepare_configs($row, FALSE);
										}
									}
									elseif ((isset($model)) AND (!isset($delete)) AND (isset($line)) AND (isset($temp)))
									{
										//Add line to the database
	
										if (empty($line))
										{
											$this->endpoint->add_line($macid, NULL, $extdisplay, $name);
										}
										else
										{
											$this->endpoint->add_line($macid, $line, $extdisplay, $name);
										}
	
										$this->endpoint->update_device($macid, $model, $temp, NULL, NULL, NULL, FALSE);
	
										$row = $this->endpoint->get_phone_info($macid);
										if (isset($reboot))
										{
											$this->endpoint->prepare_configs($row);
										}
										else
										{
											$this->endpoint->prepare_configs($row, FALSE);
										}
									}
								}
								elseif (!isset($delete))
								{
									//Add Extension/Phone to database
									$mac_id = $this->endpoint->add_device($mac, $model, $extdisplay, $temp, NULL, $name);
	
									if ($mac_id)
									{
										debug('Write files?');
										$row = $this->endpoint->get_phone_info($mac_id);
										$this->endpoint->prepare_configs($row);
									}
								}
							}
						}
						break;
				}

				// global $currentcomponent;
				// Add the 'process' function - this gets called when the page is loaded, to hook into
				// displaying stuff on the page.
				// $currentcomponent->addguifunc('endpointman_configpageload');
			}
			else
			{
				//System can't find the include file.
			}
    	}


	}


	
	



	public function doGeneralPost()
	{
		if (!isset($_REQUEST['Submit'])) 	{ return; }
		if (!isset($_REQUEST['display'])) 	{ return; }

		needreload();
	}

	public function myShowPage()
	{
		$pagedata = array();
		$request  = freepbxGetSanitizedRequest();
		$data     = array(
			'epm' 	  => $this,
			'request' => $request,
			'display' => $request['display'] ?? '',
		);
		
		switch ($data['display'])
		{
			case "epm_devices":
				$this->epm_devices->myShowPage($pagedata);
				break;

			case "epm_oss":
				return $this->epm_oss->myShowPage($pagedata);
				break;

			case "epm_placeholders":
				return $this->epm_placeholders->myShowPage($pagedata);
				break;

			case "epm_templates":
				$this->epm_templates->myShowPage($pagedata);
				return $pagedata;
				break;

			case "epm_config":
				$this->epm_config->myShowPage($pagedata, $data);
				break;

			// case "epm_advanced":
			// 	$this->epm_advanced->myShowPage($pagedata);
			// 	break;

			case "":
			default:
				return $pagedata;
		}

		if(! empty($pagedata))
		{
			foreach($pagedata as &$page)
			{
				if (empty($page['page'] ?? ''))
				{
					continue;
				}
				$page['content'] = load_view($this->system->buildPath(__DIR__, $page['page']), $data);
			}
			return $pagedata;
		}
	}

	public function showPage($page, $params = array())
	{
		$request = freepbxGetSanitizedRequest();
		$data = array(
			"epm"		    => $this,
			'request'	    => $request,
			'page' 		    => $page ?? '',
			'endpoint_warn' => "",
		);

		if ($this->isActiveModule('endpoint'))
		{
			if ($this->getConfig("disable_endpoint_warning") !== "1")
			{
				$data['endpoint_warn'] = load_view(__DIR__."/views/page.epm_warning.php", $data);
			}
		}

		$data = array_merge($data, $params);
		switch ($page) 
		{
			case 'main.oos':
				$data_return = load_view(__DIR__."/views/page.main.oos.php", $data);
			break;
			
			case "main.placeholders":
				$data_return = load_view(__DIR__."/views/page.main.placeholders.php", $data);
			break;

			case "main.config":
				$data_return = load_view(__DIR__."/views/page.main.config.php", $data);
			break;

			case "main.templates":
				if ((! isset($_REQUEST['subpage'])) || ($_REQUEST['subpage'] == "")) {
					$_REQUEST['subpage'] = "manager";
					$data['request']['subpage'] = "manager";
				}
				$data['subpage']  = $_REQUEST['subpage'] ?? 'manager';
				$data['command']  = $_REQUEST['command'] ?? '';
				$data['id']  	  = $_REQUEST['id'] ?? '';
				$data['custom']	  = $_REQUEST['custom'] ?? '';

				$data['product_list']  = sql("SELECT * FROM endpointman_product_list WHERE id > 0", 'getAll', \PDO::FETCH_ASSOC);
				$data['mac_list']      = sql("SELECT * FROM endpointman_mac_list", 'getAll', \PDO::FETCH_ASSOC);

				// $data['showpage'] = $this->myShowPage();
				$tabs = array(
					'manager' => array(
						"name" => _("Current Templates"),
						"page" => '/views/epm_templates_manager.page.php'
					),
					'editor' => array(
						"name" => _("Template Editor"),
						"page" => '/views/epm_templates_editor.page.php'
					),
				);
				foreach($tabs as $key => &$page)
				{
					$data_tab = array();
					switch($key)
					{
						case 'manager':
							if ($data['subpage'] != "manager")
							{
								$page['content'] = _("Invalid page!");
								continue 2;
							}		
							$data_tab['template_list'] = sql("SELECT DISTINCT endpointman_product_list.* FROM endpointman_product_list, endpointman_model_list WHERE endpointman_product_list.id = endpointman_model_list.product_id AND endpointman_model_list.hidden = 0 AND endpointman_model_list.enabled = 1 AND endpointman_product_list.hidden != 1 AND endpointman_product_list.cfg_dir !=  ''", 'getAll', \PDO::FETCH_ASSOC);
							break;

						case 'editor':
							if ($data['subpage'] != "editor")
							{
								$page['content'] = _("Invalid page!");
								continue 2;
							}

							$data_tab['idsel'] = $_REQUEST['idsel'] ?? null;
							// $data_tab['edit_template_display'] = $this->epm_templates->edit_template_display($data_tab['idsel'], $data['custom']);
							break;
					}
					$data_tab = array_merge($data, $data_tab);

					// ob_start();
					// include($page['page']);
					// $page['content'] = ob_get_contents();
					// ob_end_clean();
					$page['content'] = load_view(__DIR__ . '/' . $page['page'], $data_tab);
				}
				$data['tabs'] = $tabs;
				unset($tabs);
				

				$data_return = load_view(__DIR__."/views/page.main.templates.php", $data);
			break;

			case "main.advanced":
				$data['subpage'] = $request['subpage'] ?? 'settings';

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
					'iedl' => array(
						"name" => _("Import/Export My Devices List"),
						"page" => '/views/epm_advanced_iedl.page.php'
					),
					'manual_upload' => array(
						"name" => _("Package Import/Export"),
						"page" => '/views/epm_advanced_manual_upload.page.php'
					),
				);
				foreach($tabs as $key => &$page)
				{
					$data_tab = array();
					switch($key)
					{
						case "settings":
							if (($this->getConfig("server_type") == 'file') AND ($this->epm_advanced->epm_advanced_config_loc_is_writable()))
							{
								$this->tftp_check();
							}
							
							if ($this->getConfig("use_repo") == "1")
							{
								if ($this->has_git())
								{
									if (!file_exists($this->system->buildPath($this->PHONE_MODULES_PATH, '.git'))) {
										$o = getcwd();
										chdir(dirname($this->PHONE_MODULES_PATH));
										$this->rmrf($this->PHONE_MODULES_PATH);
										$path = $this->has_git();
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
								if (file_exists($this->system->buildPath($this->PHONE_MODULES_PATH, '.git')))
								{
									$this->rmrf($this->PHONE_MODULES_PATH);

									$sql = "SELECT * FROM  `".self::TABLES['epm_brands_list']."` WHERE  `installed` =1";
									$result = & sql($sql, 'getAll', \PDO::FETCH_ASSOC);
									foreach ($result as $row)
									{
										$id_product = $row['id'] ?? null;
										$this->epm_config->remove_brand($id_product, true);
									}
								}
							}
							
							$url_provisioning = $this->system->buildUrl(sprintf("%s://%s", $this->getConfig("server_type"), $this->getConfig("srvip")), "provisioning","p.php");

							$data_tab['config']['data'] = array(
								'setting_provision' => array(
									'label' => _("Provisioner Settings"),
									'items' => array(
										'srvip' => array(
											'label' 	  => _("IP address of phone server"),
											'type' 		  => 'text',
											'value' 	  => $this->getConfig("srvip"),
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
											'value' 	  => $this->getConfig("intsrvip"),
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
											'value' 	  => $this->getConfig("server_type"),
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
											'value' 	  => $this->getConfig("config_location"),
											'placeholder' => _("Configuration Location..."),
											'help' 		  => _("Path location root TFTP server."),
										),
										'adminpass' => array(
											'label' 	  => _("Phone Admin Password"),
											'type' 		  => 'text',
											'class' 	  => 'confidential',	//'password-meter confidential',
											'value' 	  => $this->getConfig("adminpass"),
											'placeholder' => _("Admin Password..."),
											'help' 		  => _("Enter a admin password for your phones. Must be 6 characters and only nummeric is recommendet!"),
										),
										'userpass' => array(
											'label' 	  => _("Phone User Password"),
											'type' 		  => 'text',
											'class' 	  => 'confidential',	//'password-meter confidential',
											'value' 	  => $this->getConfig("userpass"),
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
											'value'				 => $this->getConfig("tz"),
											'help'				 => _("Time Zone configuration terminasl. Like England/London"),
											'options'			 => $this->listTZ($this->getConfig("tz")),
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
											'value' 	  => $this->getConfig("ntp"),
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
											'value' 	  => $this->getConfig("nmap_location"),
											'placeholder' => _("Nmap Location..."),
											'help' 		  => _("The location of the Nmap binary."),
										),
										'arp_loc' => array(
											'label' 	  => _("Arp Executable Path"),
											'type' 		  => 'text',
											'value' 	  => $this->getConfig("arp_location"),
											'placeholder' => _("Arp Location..."),
											'help' 		  => _("The location of the Arp binary."),
										),
										'asterisk_loc' => array(
											'label' 	  => _("Asterisk Executable Path"),
											'type' 		  => 'text',
											'value' 	  => $this->getConfig("asterisk_location"),
											'placeholder' => _("Asterisk Location..."),
											'help' 		  => _("The location of the Asterisk binary."),
										),
										'tar_loc' => array(
											'label' 	  => _("Tar Executable Path"),
											'type' 		  => 'text',
											'value' 	  => $this->getConfig("tar_location"),
											'placeholder' => _("Tar Location..."),
											'help' 		  => _("The location of the Tar binary."),
										),
										'netstat_loc' => array(
											'label' 	  => _("Netstat Executable Path"),
											'type' 		  => 'text',
											'value' 	  => $this->getConfig("netstat_location"),
											'placeholder' => _("Netstat Location..."),
											'help' 		  => _("The location of the Netstat binary."),
										),
										'whoami_loc' => array(
											'label' 	  => _("Whoami Executable Path"),
											'type' 		  => 'text',
											'value' 	  => $this->getConfig("whoami_location"),
											'placeholder' => _("Whoami Location..."),
											'help' 		  => _("The location of the Whoami binary."),
										),
										'nohup_loc' => array(
											'label' 	  => _("Nohup Executable Path"),
											'type' 		  => 'text',
											'value' 	  => $this->getConfig("nohup_location"),
											'placeholder' => _("Nohup Location..."),
											'help' 		  => _("The location of the Nohup binary."),
										),
										'groups_loc' => array(
											'label' 	  => _("Groups Executable Path"),
											'type' 		  => 'text',
											'value' 	  => $this->getConfig("groups_location"),
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
											'value' 	  => $this->getConfig("update_server"),
											'placeholder' => _("Server Packages..."),
											'help' 		  => _("URL download files and packages the configuration terminals."),
											'button' 	  => array(
												'id' 	  => 'default_package_server',
												'label'   => _("Default Mirror FreePBX"),
												'icon'    => 'fa-undo',
												'onclick' => sprintf("epm_advanced_tab_setting_input_value_change_bt('#package_server', sValue = '%s', bSaveChange = true);", self::URL_PROVISIONER),
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
											'value' 	  => $this->getConfig("disable_endpoint_warning"),
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
											'value' 	  => $this->getConfig("enable_ari"),
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
											'value' 	  => $this->getConfig("debug"),
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
											'value' 	  => $this->getConfig("disable_help"),
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
											'value' 	  => $this->getConfig("show_all_registrations"),
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
											'value' 	  => $this->getConfig("allow_hdfiles"),
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
											'value' 	  => $this->getConfig("tftp_check"),
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
											'value' 	  => $this->getConfig("backup_check"),
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
							$provisioner_ver 						= $this->getConfig("endpoint_vers");
							$data_tab['config']['provisioner_ver']  = sprintf(_("%s at %s"), date("d-M-Y", $provisioner_ver) , date("g:ia", $provisioner_ver));
							$data_tab['config']['brands_available'] = $this->brands_available("", false);
							break;

						case "iedl":
							$data_tab['config']['url_export'] = "config.php?display=epm_advanced&subpage=iedl&command=export";
							break;

						case "oui_manager":
							// $data_tab['config']['brands']   = sql('SELECT * from '. self::TABLES['epm_brands_list'] .' WHERE id > 0 ORDER BY name ASC', 'getAll', \PDO::FETCH_ASSOC);

							// Send the brands list to the view to be used in the select box for the win new OUI is added
							$data_tab['config']['brands']   = $this->get_hw_brand_list(true, 'name', 'ASC');
							$data_tab['config']['url_grid'] = "ajax.php?module=endpointman&amp;module_sec=epm_advanced&amp;module_tab=oui_manager&amp;command=oui";
							break;

						case "poce":
							break;
					}
					$data_tab = array_merge($data, $data_tab);

					// ob_start();
					// include($page['page']);
					// $page['content'] = ob_get_contents();
					// ob_end_clean();
					$page['content'] = load_view(__DIR__ . '/' . $page['page'], $data_tab);
				}
				$data['tabs'] = $tabs;
				unset($tabs);

				$data_return = load_view(__DIR__."/views/page.main.advanced.php", $data);
				break;

			default:
				$data_return = sprintf(_("Page Not Found (%s)!!!!"), $page);
		}
		return $data_return;
	}

	public function getActiveModules()
	{
		global $active_modules;
		return $active_modules;
	}

	public function isActiveModule($name)
	{
		$modules = $this->getActiveModules();
		return !empty($modules[$name]['rawname']);
	}

	//http://wiki.freepbx.org/display/FOP/Adding+Floating+Right+Nav+to+Your+Module
	public function getRightNav($request, $params = array())
	{
		$data = array(
			"epm" 	  => $this,
			"request" => $request,
			"display" => strtolower(trim($request['display'] ?? '')),
		);
		$data = array_merge($data, $params);

		switch($data['display'])
		{
			case 'epm_oss':
			case 'epm_devices':
			case 'epm_placeholders':
			case 'epm_config':
			case 'epm_advanced':
			case 'epm_templates':
				$data_return = load_view(__DIR__.'/views/rnav.php', $data);
				break;

			default:
				$data_return = "";
		}
		return $data_return;
	}

	//http://wiki.freepbx.org/pages/viewpage.action?pageId=29753755
	public function getActionBar($request)
	{
		$data = array(
			"epm" 	  => $this,
			"request" => $request,
			"display" => strtolower(trim($request['display'] ?? '')),
		);

		switch($data['display'])
		{
			case "epm_devices":
				$data_return = $this->epm_devices->getActionBar($request);
				break;

			case "epm_oss":
				$data_return = $this->epm_oss->getActionBar($request);
				break;

			case "epm_placeholders":
				$data_return = $this->epm_placeholders->getActionBar($request);
				break;

			case "epm_config":
				$data_return = $this->epm_config->getActionBar($data);
				break;

			case "epm_advanced":
				$data_return = $this->epm_advanced->getActionBar($request);
				break;

			case "epm_templates":
				$data_return = $this->epm_templates->getActionBar($request);
				break;

			default:
				$data_return = "";
		}
		return $data_return;
	}

	public function install()
	{
		out(_("⚡ Endpoint Manager Installer ..."));
		outn(_("⚡ Createing Structure directories..."));
		$this->checkPathsFiles();
		out(" ✔");

		outn(_('⚡ Creating symlink to web provisioner...'));
		$provisioning_lnk = $this->system->buildPath($this->config->get('AMPWEBROOT'), "provisioning");
		$provisioning_src = $this->system->buildPath($this->MODULE_PATH, "provisioning");
		if (file_exists($provisioning_lnk))
		{
			if (is_link($provisioning_lnk))
			{
				$pathNowFile = readlink($provisioning_lnk);
				if ($pathNowFile === $provisioning_src)
				{
					out(_(" Skipped (already exists) ✔"));
				}
				else
				{
					out(" ❌");
					out(sprintf("<strong>%s</strong", sprintf(_("The file '%s' already exists and not pointing to the correct location, location is '%s'!")), $provisioning_lnk, $pathNowFile));
				}
			}
			else
			{
				out(" ❌");
				out(sprintf("❌ <strong>%s</strong", sprintf(_("The file '%s' already exists and is not a symbolic link!")), $provisioning_lnk));
			}
		}
		else
		{
			if (! is_writable($this->config->get('AMPWEBROOT')))
			{
				out(" ❌");
				out(sprintf("❌ <strong>%s</strong", sprintf(_("Your permissions are wrong on '%s', web provisioning link not created!")), $this->config->get('AMPWEBROOT')));
			
			}
			else if (!symlink($provisioning_src, $provisioning_lnk))
			{
				out(" ❌");
				out(sprintf("❌ <strong>%s</strong", sprintf(_("Failed to create symbolic link '%s' to '%s'!")), $provisioning_lnk, $provisioning_src));
			}
			else
			{
				out(" ✔");
			}
		}

		// if (!file_exists($this->PHONE_MODULES_PATH . "/setup.php"))
		// {
		// 	outn(_("Moving Auto Provisioner Class..."));
    	// 	copy($this->MODULE_PATH . "/install/setup.php", $this->PHONE_MODULES_PATH . "/setup.php");
		// 	out(_("OK"));
		// }

		$modinfo 	   = module_getinfo('endpointman');
		$epmxmlversion = $modinfo['endpointman']['version'];
		$epmdbversion  = !empty($modinfo['endpointman']['dbversion']) ? $modinfo['endpointman']['dbversion'] : null;
		
		outn(_("⚡ Inserting Config Global and Updating..."));
		$dataDefault = [
			'srvip' 					=> '',
			'tz' 						=> '',
			'gmtoff' 					=> '',
			'gmthr' 					=> '',
			'config_location' 			=> '/tftpboot/',
			'update_server' 			=> self::URL_PROVISIONER,
			'version' 					=> '',
			'enable_ari' 				=> '0',
			'debug' 					=> '0',
			'arp_location' 				=> $this->system->find_exec("arp", false, false),
			'nmap_location' 			=> $this->system->find_exec("nmap", false, false),
			'asterisk_location' 		=> $this->system->find_exec("asterisk", false, false),
			'tar_location' 				=> $this->system->find_exec("tar", false, false),
			'netstat_location' 			=> $this->system->find_exec("netstat", false, false),
			'whoami_location' 			=> $this->system->find_exec("whoami", false, false),
			'nohup_location' 			=> $this->system->find_exec("nohup", false, false),
			'groups_location' 			=> $this->system->find_exec("groups", false, false),
			'language' 					=> '',
			'check_updates' 			=> '0',
			'disable_htaccess' 			=> '',
			'endpoint_vers' 			=> '0',
			'disable_help' 				=> '0',
			'show_all_registrations' 	=> '0',
			'ntp' 						=> '',
			'server_type' 				=> 'file',
			'allow_hdfiles' 			=> '0',
			'tftp_check' 				=> '0',
			'nmap_search' 				=> '',
			'backup_check' 				=> '0',
			'use_repo' 					=> '0',
			'adminpass' 				=> '123',
			'userpass' 					=> '111',
			'intsrvip' 					=> '',
			'disable_endpoint_warning' 	=> '0',
		];
		foreach ($dataDefault as $key => $val)
		{
			// Get the config if it exists (database old or new system kvstore), if not create it with the default value.
			//TODO: Pending remove old table 'endpointman_global_vars'
			$config = $this->getConfig($key, $val);
			switch ($key)
			{
				case "update_server":
					if ($epmdbversion < "17.0.0.0")
					{
						$config = self::URL_PROVISIONER;
					}
					break;

				case "version":
					$config = $epmxmlversion;
					break;
			}
			$this->setConfig($key, $config);
		}
		out(_(" ✔"));
		out(" ");
	}

	public function uninstall()
	{
		outn(_("⚡ Removing Structure Directories..."));
		if(file_exists($this->PHONE_MODULES_PATH))
		{
			$this->system->rmrf($this->PHONE_MODULES_PATH);
		}
		out(_(" ✔"));

		outn(_("⚡ Removing Symlink to web provisioner..."));
		$provisioning_lnk = $this->system->buildPath($this->config->get('AMPWEBROOT'), "provisioning");
		$provisioning_src = $this->system->buildPath($this->MODULE_PATH, "provisioning");
		if(file_exists($provisioning_lnk))
		{
			if (is_link($provisioning_lnk))
			{
				$provisioning_now_src = readlink($provisioning_lnk);
				if ($provisioning_now_src === $provisioning_src)
				{
					if (! unlink($provisioning_lnk))
					{
						out(_("❌"));
						out(sprintf("❌ <strong>%s</strong>", sprintf(_("Failed to remove symbolic link '%s' to '%s'!")), $provisioning_lnk, $provisioning_src));
					}
					else
					{
						out(" ✔");
					}
				}
				else
				{
					out(_("❌"));
					out(sprintf("❌ <strong>%s</strong>", sprintf(_("The file '%s' already exists and not pointing to the correct location, location is '%s, can't remove!")), $provisioning_lnk, $provisioning_now_src));
				}
			}
			else
			{
				out(_("❌"));
				out(sprintf("❌ <strong>%s</strong>", sprintf(_("The file '%s' already exists and is not a symbolic link, can't remove!")), $provisioning_lnk));
			}
		}
		else
		{
			out(" ✔");
		}
		out(" ");

		// if(!is_link($this->config->get('AMPWEBROOT').'/admin/assets/endpointman'))
		// {
		// 	$this->system->rmrf($this->config->get('AMPWEBROOT').'/admin/assets/endpointman');
		// }
		return true;
	}

	public function backup() { }
    public function restore($backup) { }

	public function setDatabase($pdo)
	{
		$this->db = $pdo;
		return $this;
	}
	
	public function resetDatabase()
	{
		$this->db = $this->FreePBX->Database;
		return $this;
	}

	private function epm_config_manual_install($install_type = "", $package ="")
	{
		if ($install_type == "") {
			throw new \Exception("Not send install_type!");
		}

		switch($install_type) {
			case "export_brand":

				break;

			case "upload_master_xml":


				$file_xml_tmp  = $this->system->buildPath($this->TEMP_PATH, 'master.xml');
				$file_xml_dest = $this->system->buildPath($this->PHONE_MODULES_PATH, 'master.xml');

				if (file_exists($file_xml_tmp)) {
					$handle = fopen($file_xml_tmp, "rb");
					$contents = stream_get_contents($handle);
					fclose($handle);
					@$a = simplexml_load_string($contents);
					if($a===FALSE) {
						echo "Not a valid xml file";
						break;
					} else {
						rename($file_xml_tmp, $file_xml_dest);
						echo "Move Successful<br />";
						$this->update_check();
						echo "Updating Brands<br />";
					}
				} else {
				}
				break;

			case "upload_provisioner":

				break;

			case "upload_brand":

				break;
		}
	}



	/**
	 * Checks if a value exists in a multidimensional array recursively.
	 *
	 * @param mixed $needle The value to search for.
	 * @param array $haystack The array to search in.
	 * @return bool Returns true if the value is found, false otherwise.
	 */
	public static function in_array_recursive($needle, $haystack)
	{
        $it = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($haystack));
        foreach ($it AS $element)
		{
            if ($element == $needle)
			{
                return TRUE;
            }
        }
        return FALSE;
    }

	

	/**
	 * Formats the given text with the specified CSS class and replaces any specified text.
	 *
	 * @param string $texto The text to be formatted.
	 * @param string $css_class The CSS class to be applied to the formatted text.
	 * @param array $remplace_txt An array of text replacements to be made in the formatted text.
	 * @return string The formatted text wrapped in a paragraph tag with the specified CSS class.
	 */
	function format_txt($texto = "", $css_class = "", $remplace_txt = array())
	{
		if (count($remplace_txt) > 0)
		{
			foreach ($remplace_txt as $clave => $valor)
			{
				$texto = str_replace($clave, $valor, $texto);
			}
		}
		return sprintf('<p class="%s">%s</p>', $css_class, $texto);
	}

	/**
	 * Generates an XML string from an array or object.
	 *
	 * This function recursively converts an array or object into an XML string.
	 * It uses the provided node name to create XML tags for each element.
	 * Numeric keys are replaced with the provided node name.
	 * The generated XML string is indented using tabs.
	 *
	 * @param array|object $array The array or object to convert to XML.
	 * @param string $node_name The name of the XML node.
	 * @param int $tab The current indentation level (default: -1).
	 * @return string The generated XML string.
	 */
	function generate_xml_from_array ($array, $node_name, &$tab = -1)
	{
		$tab++;
		$xml ="";
		if (is_array($array) || is_object($array))
		{
			foreach ($array as $key=>$value)
			{
				if (is_numeric($key))
				{
					$key = $node_name;
				}
				$xml .= sprintf("%s<%s>\n", str_repeat("	", $tab), $key);
				$xml .= $this->generate_xml_from_array($value, $node_name, $tab);
				$xml .= sprintf("%s</%s>\n", str_repeat("	", $tab), $key);

			}
		}
		else
		{
			$xml = sprintf("%s%s\n", str_repeat("	", $tab), htmlspecialchars($array, ENT_QUOTES));
		}
		$tab--;
		return $xml;
	}


	// TODO: Move to system class, only retrocompatibility
    public function file2json($file)
	{
		return $this->system->file2json($file);
    }


	









	/**
	 * Retrieves the value of a configuration key.
	 *
	 * @param string $key The configuration key to retrieve.
	 * @param mixed $default The default value to return if the key is not found.
	 * @param string $section The configuration section where the key is stored. Defaults to `globalSettings`.
	 * @return mixed The value of the configuration key, or the default value if not found.
	 *
	 * @deprecated This method currently supports retrocompatibility with data saved in the database, but it will be removed in the future.
	 */
	public function getConfig($key = null, $default = false, $section = "globalSettings")
	{
		// dbug("getConfig: $key, Default: $default");
		$data = $default;
		if (empty($section))
		{
			$section = "globalSettings";
		}
		if (! empty($key))
		{
			// TODO: Retrocompatibility data saved in the database 
			$where = array(
				'var_name' => array( 'value' => 'endpoint_vers', 'operator' => "LIKE")
			);
			$old_value =  $this->get_database_data(self::TABLES['epm_global_vars'], $where, 'value');
			// TODO: Retrocompatibility data saved in the database

			if ( $this->isConfigExist($key, $section))
			{
				$data = parent::getConfig($key, $section);
			}
			elseif (! empty($old_value))
			{
				$data = $old_value;
			}
			else
			{
				$data = $default;
			}
		}
		return $data;
	}

	/**
	 * Check if a configuration key exists.
	 * 
	 * @param string $key The configuration key to retrieve.
	 * @param string $section The configuration section where the key is stored. Defaults to `globalSettings`.
	 * @return array Returns an true if the configuration key exists, false otherwise.
	 */
	public function isConfigExist(string $key = '', ?string $section = "globalSettings")
	{
		if (empty($key))
		{
			return false;
		}
		if (empty($section))
		{
			$section = "globalSettings";
		}
		return in_array($key, $this->getAllKeys($section));
	}

	/**
	 * Sets the value of a configuration key.
	 *
	 * @param string $key The configuration key to set.
	 * @param mixed $value The value to set for the configuration key.
	 * @param string $section The configuration section where the key will be stored. Defaults to `globalSettings`.
	 * @return bool Returns true if the configuration key was set, false otherwise.
	 *
	 * @deprecated This method currently supports retrocompatibility with data saved in the database, but it will be removed in the future.
	 */
	public function setConfig($key = '', $value = false, $section = "globalSettings")
	{
		if (empty($key))
		{
			return false;
		}
		if (empty($section))
		{
			$section = "globalSettings";
		}
		return parent::setConfig($key, $value, $section);
	}







	/**
	 * set_database_data
	 *
	 * Inserts or updates data in a specified database table based on the presence of a record matching a given condition.
	 *
	 * This function performs an `INSERT` or `UPDATE` operation on a database table depending on whether a record
	 * already exists that matches the specified `where` condition with the given `find` value.
	 *
	 * @param string $table The name of the database table where the operation will be performed. This is a required parameter.
	 * @param mixed $find The value to be searched for in the specified column (`$where`). If a record with this value exists, an `UPDATE` operation is performed; otherwise, an `INSERT` operation is executed.
	 * @param array $data The associative array of data to be inserted or updated. Keys represent the column names, and values represent the corresponding data. This array is required and must not be empty.
	 * @param string $where The column name used for searching the record in the table. Defaults to `id`. This is a required parameter.
	 * @param array $data_insert Optional. An additional associative array of data to be merged with `$data` for the `INSERT` operation. If not provided, only `$data` is used for insertion.
	 * @param array $data_update Optional. An additional associative array of data to be merged with `$data` for the `UPDATE` operation. If not provided, only `$data` is used for updating.
	 * @param bool $debug Optional. If set to `true`, the function will return the generated SQL query instead of executing it. Defaults to `false`.
	 *
	 * @return mixed Returns the ID of the newly inserted record if an `INSERT` operation is performed, or `true` if an `UPDATE` operation is performed successfully.
	 *               Returns `false` if any of the required parameters (`$data`, `$where`, `$table`) are missing or invalid.
	 *
	 * @throws PDOException If the database operation fails, an exception is thrown by the PDO layer.
	 *
	 * @version 1.0.0
 	 * @author Javier Pastor
 	 *
	 * @example
	 * // Example usage for updating an existing record
	 * $table = 'users';
	 * $find = 5; // Assuming this is the ID of the user
	 * $data = ['username' => 'new_username'];
	 * $data_update = ['email' => 'new_email@example.com'];
	 * $result = $this->set_database_data($table, $find, $data, 'id', [], $data_update);
	 * // Updates the 'username' and 'email' for the user with ID 5.
	 *
	 * @example
	 * // Example usage for inserting a new record
	 * $table = 'users';
	 * $data = ['username' => 'new_user', 'email' => 'new_user@example.com'];
	 * $data_insert = ['created_at' => date('Y-m-d H:i:s')];
	 * $result = $this->set_database_data($table, null, $data, 'id', $data_insert, []);
	 * // Inserts a new user with the provided data.
	 */
	public function set_database_data($table = null, $find = null, $data = array(), $where = "id", $data_insert = array(), $data_update = array(), $debug = false)
	{
		if (empty($table) || !is_string($table) || !preg_match('/^[a-zA-Z0-9_]+$/', $table))
		{
			if ($debug)
			{
				dbug("Invalid table name: $table");
			}
			return false;
		}
		if (!is_array($data) || empty($data) || empty($where))
		{
			if ($debug)
			{
				dbug("Invalid data or where condition!");
			}
			return false;
		}

		$isExistKeyFind = function () use ($table, $where, $find, $data) {

			// TODO: REMOVE DEBUG!!!!
			// dbug('0000000000000000000000000000000000000');
			// dbug(array(
			// 	'table' => array(
			// 		'type' => gettype($table),
			// 		'data' => $table
			// 	),
			// 	'where' => array(
			// 		'type' => gettype($where),
			// 		'data' => $where
			// 	),
			// 	'find' => array(
			// 		'type' => gettype($find),
			// 		'data' => $find
			// 	),
			// 	'data' => array(
			// 		'type' => gettype($data),
			// 		'data' => $data
			// 	)
			// ));


			$sql  = sprintf("SELECT COUNT(*) as total FROM %s WHERE %s = :find", $table, $where);
			$stmt = $this->db->prepare($sql);
			$stmt->execute(
				[':find' => $find]
			);
			$result = $stmt->fetch(\PDO::FETCH_ASSOC);
			return ($result['total'] ?? 0) > 0;
		};

		// Check if the record exists in the database, not used $this->count_database_data since it generate loop infinite.
		$isUpdate  = !empty($find) && $isExistKeyFind();
		$params    = [];
		if ($isUpdate)
		{
			// Generate a random key to avoid conflicts with the data array
			$key_where 							= sprintf("%s_%s", "where_filter_value", rand(1000, 9999));
			$params[sprintf(":%s", $key_where)] = $find;

			// Combine the data arrays and generate the SQL query for updating the data in the database
			$data 	   = array_merge($data, $data_update);
			$setPart   = [];
			foreach ($data as $column => $value)
			{
				$setPart[] = sprintf("%s = :%s", $column, $column);
			}
			$setPart = implode(", ", $setPart);
			$sql 	 = sprintf("UPDATE %s SET %s WHERE %s = :%s", $table, $setPart, $where, $key_where);
		}
		else
		{
			// Combine the data arrays and generate the SQL query for inserting the data into the database
			$data 		  = array_merge($data, $data_insert);
			$columns 	  = implode(", ", array_keys($data));
			$placeholders = implode(", ", array_map(function($column) { return sprintf(":%s",$column); }, array_keys($data)));
			$sql 		  = sprintf("INSERT INTO %s (%s) VALUES (%s)", $table, $columns, $placeholders);
		}
		if ($debug)
		{
			$data_debug = array(
				'table' 	  => $table,
				'find' 		  => $find,
				'data' 		  => $data,
				'where' 	  => $where,
				'isUpdate' 	  => $isUpdate ? 'TRUE' : 'FALSE',
				'sql' 		  => $sql,
				'data' 		  => $data,
				'data_insert' => $data_insert,
				'data_update' => $data_update,
			);
			dbug($data_debug);
		}
		$stmt = $this->db->prepare($sql);

		// Bind the parameters to the query and execute it
		foreach ($data as $key => $value)
		{
			$params[sprintf(":%s", $key)] = $value;
		}
		$stmt->execute($params);
		return $isUpdate ? true : $this->db->lastInsertId();
	}
	

	/**
	 * Retrieves data from a database table based on the specified conditions.
	 *
	 * This function retrieves data from a database table based on the specified conditions.
	 * The data is returned as an associative array of rows, where each row is an associative array of columns.
	 *
	 * @param string $table The name of the database table to retrieve data from. This is a required parameter.
	 * @param array $where An associative array of conditions to filter the data. The keys represent the column names, and the values are arrays with the following keys:
	 *                     - `operator`: The comparison operator to use in the condition (e.g., '=', '>', '<', 'LIKE', 'IN', 'NOT IN').
	 *                     - `value`: The value to compare against in the condition.
	 * @param string $select The columns to select from the table. Defaults to `*` (all columns).
	 * @param string $order_by The column to use for sorting the results. Defaults to `null` (no sorting).
	 * @param string $order_dir The direction to use for sorting the results. Defaults to `null` (no sorting).
	 * @param bool $return_bool Whether to return a boolean value instead of an empty array when no data is found. Defaults to `false`.
	 * @param bool $return_stml Whether to return the PDO statement object instead of the data array. Defaults to `false`.
	 * @param bool $debug Whether to output the generated SQL query and parameters for debugging purposes. Defaults to `false`.
	 * @return array The data retrieved from the database as an associative array of rows, where each row is an associative array of columns.
	 *
	 * @throws PDOException If the database operation fails, an exception is thrown by the PDO layer.
	 *
	 * @version 1.0.0
 	 * @author Javier Pastor
 	 *
	 * @example
	 * // Example usage for retrieving data from a database table
	 * $table = 'users';
	 * $where = [
	 *     'id' => ['operator' => '>', 'value' => 0],
	 *     'status' => ['operator' => '=', 'value' => 'active']
	 * ];
	 * $select = 'id, username, email';
	 * $order_by = 'created_at';
	 * $order_dir = 'DESC';
	 * $result = $this->get_database_data($table, $where, $select, $order_by, $order_dir);
	 * // Retrieves the 'id', 'username', and 'email' columns from the 'users' table where 'id' is greater than 0 and 'status' is 'active', ordered by 'created_at' in descending order.
	 *
	 * @example 
	 * $where = array(
	 *		'brand' => array( 'operator' => '=', 'value' => $id)
	 * );
	 * if (!$show_all)
	 * {
 	 * 		$where['hidden'] = array('operator' => '=', 'value' => "0");
	 * }
	 * return $this->get_database_data(self::TABLES['epm_product_list'], $where, '*', $order_by, $order_dir);
	 * 
	 */
	
	public function get_database_data($table, $where = array(), $select = null, $order_by = null, $order_dir = null, $return_bool = false, $return_stml = false, $debug = false)
	{
		if (empty($select))
		{
			$select = "*";
		}
		if (empty($table) || !is_string($table) || !preg_match('/^[a-zA-Z0-9_]+$/', $table) || !is_string($select) || !preg_match('/^[a-zA-Z0-9_.*,\\s()]+$/', $select))
		{
			if ($return_bool)
			{
				return false;
			}
			return [];
		}

		$params = [];
		$sql = sprintf('SELECT %s FROM %s', $select, $table);

		if (!empty($where) && is_array($where))
		{
			$where_sql = [];
			foreach ($where as $key => $value)
			{
				$operator  = strtoupper($value['operator'] ?? "");
				$where_val = $value['value'] ?? null;

				if (empty($where_val) ||empty($operator) || !in_array($operator, ['=', '>', '<', '>=', '<=', '!=', 'LIKE', 'IN', 'NOT IN']))
				{
					continue;
				}
				$where_sql[] = sprintf('%1$s %2$s :%1$s', $key, $operator);
				$params[sprintf(":%s", $key)] = $where_val;
			}
			if (count($where_sql) > 0)
			{
				$sql .= sprintf(' WHERE %s', implode(" AND ", $where_sql));
			}
		}

		if (!empty($order_by))
		{
			// Sanitize the order by column name
			$order_by  = preg_replace('/[^a-zA-Z0-9_]/', '', $order_by);
			$order_dir = strtoupper($order_dir ?? 'ASC') == 'DESC' ? 'DESC' : 'ASC';
			$sql 	  .= sprintf(' ORDER BY %s %s', $order_by, $order_dir);
		}
		
		if ($debug)
		{
			dbug($sql);
			dbug($params);
		}

		$stmt = $this->db->prepare($sql);
		$stmt->execute($params);
	
		if ($return_stml)
		{
			return $stmt;
		}
		return $stmt->fetchAll(\PDO::FETCH_ASSOC);
	}

	/**
	 * Counts the number of database records in a specified table.
	 *
	 * @param string $table The name of the table to count records from.
	 * @param mixed $find The value to search for in the specified column.
	 * @param string $filter The column name to filter the search by.
	 * @param array $where An associative array of conditions to filter the data. The keys represent the column names, and the values are arrays with the following keys:
	 * @return bool Returns true if there are records matching the search criteria, false otherwise.
	 * /example
	 * // Example usage for counting records in a database table
	 * $table = 'users';
	 * $find = 5; // Assuming this is the ID of the user
	 * $result = $this->count_database_data($table, $find, 'id');
	 * // Counts the number of records in the 'users' table where 'id' is equal to 5.
	 */
	public function count_database_data($table, $find = null, $filter = null, $where = array())
	{
		$return_data = false;
		if (!is_array($where))
		{
			$where = [];
		}
		if (!empty($find) && !empty($filter))
		{
			$where[$filter] = array( 'operator' => '=', 'value' => $find);
		}
		$result = $this->get_database_data($table, $where, 'COUNT(*) as total');
		$return_data = ($result[0]['total'] ?? 0) > 0;
		return $return_data;
	}






	public function del_hw_brand_any_list(?int $id = null)
	{
		if (empty($id) || !is_numeric($id))
		{
			return false;
		}
		$tables = [
			self::TABLES['epm_model_list']	 => 'brand',
			self::TABLES['epm_product_list'] => 'brand',
			self::TABLES['epm_oui_list']	 => 'brand',
			self::TABLES['epm_brands_list']	 => 'id'
		];
		foreach ($tables as $table => $where)
		{
			$sql = sprintf('DELETE FROM %s WHERE %s = :id', $table, $where);
			$stmt = $this->db->prepare($sql);
			$stmt->execute([
				':id' => $id
			]);
		}
		return true;
	}

	/**
	 * Sets the database data for a brand.
	 *
	 * @param mixed $find The value to search for in the database.
	 * @param array $data An array of data to be set for the brand.
	 * @param string $where The column name to use for the search.
	 * @param array $data_insert An array of data to be inserted into the database.
	 * @param array $data_update An array of data to be updated in the database.
	 * @return mixed The result of the set_database_data method.
	 */
	public function set_hw_brand($find = null, $data = array(), $where = "id", $data_insert = array(), $data_update = array())
	{
		// Set true for debug mode to show the generated SQL query
		$debug 				  = false;

		$data 				  = is_array($data)			? $data 		: [];
		$data_insert  		  = is_array($data_insert)	? $data_insert	: [];
		$data_update  		  = is_array($data_update)	? $data_update	: [];
		$data_default 		  = [];
		$data_insert_defaults = [];
		$data_update_defaults = [
			// 'local' 	=> $data['local'] 	  ?? 1,
			// 'installed' => $data['installed'] ?? 1,
			// 'hidden' 	=> $data['hidden'] 	  ?? 0,
		];
		$data 		 = array_merge($data_default, $data);
		$data_insert = array_merge($data_insert_defaults, $data_insert);
		$data_update = array_merge($data_update_defaults, $data_update);

		return $this->set_database_data(self::TABLES['epm_brands_list'], $find, $data, $where, $data_insert, $data_update, $debug);
	}

	/**
	 * Retrieves the brand data from the database.
	 *
	 * @param mixed $find The value to search for in the database.
	 * @param string $where The column name to use for the search.
	 * @param string $select The columns to select from the database.
	 * @param string $order_by The column to use for sorting the results.
	 * @param string $order_dir The direction to use for sorting the results.
	 * @return array The result of the get_database_data method.
	 */
	public function get_hw_brand_list($show_all = false, $order_by = null, $order_dir = null)
	{
		$where = array(
			'id' => array( 'operator' => '>', 'value' => "0")
		);
		if (!$show_all)
		{
			$where['hidden'] = array('operator' => '=', 'value' => "0");
		}
		return $this->get_database_data(self::TABLES['epm_brands_list'], $where, '*', $order_by, $order_dir);
	}
	

	/**
	 * Retrieves the brand data from the database.
	 *
	 * @param mixed $id The ID of the brand to retrieve. The value is ignored if the `$where` parameter is an array.
	 * @param string|array $where The column name to use for the search (default: "directory") or an array of conditions.
	 * @param string $select The columns to select from the database (default: "*").
	 * @param bool $getOne Whether to return only the first result or all results (default: false).
	 * @return array The result of the get_database_data method.
	 * @example
	 * // Example usage for retrieving brand data from the database
	 * $id = 1;
	 * $where = 'id';
	 * $select = 'id, name, directory';
	 * $result = $this->get_hw_brand($id, $where, $select, true);
	 * // Retrieves the 'id', 'name', and 'directory' columns for the brand with ID 1 and returns only the first result.
	 * @example
	 * $id = 1;
	 * $where = array('name' => array( 'operator' => 'LIKE', 'value' => "cisco"));
	 * $select = 'id, name, directory';
	 * $result = $this->get_hw_brand($id, $where, $select, false);
	 * // Retrieves the 'id', 'name', and 'directory' columns for the brand with name like "cisco" and returns all results.
	 */
	public function get_hw_brand($id, $where = "directory", ?string $select = "*", ?bool $getOne = false)
	{
		$debug = false;
		if (empty($id) && !is_array($where)) { return []; }
		if (empty($where))					 { $where  = "directory"; }
		if (empty($select))					 { $select = "*"; }
		if (is_array($where))				 { $where_query = $where; }
		else
		{
			$where_query = array(
				$where => array( 'operator' => '=', 'value' => $id)
			);
		}

		$return_db = $this->get_database_data(self::TABLES['epm_brands_list'], $where_query, $select, null, null, false, false, $debug);
		
		if ($getOne && !empty($return_db))
		{
			$return_db = $return_db[0] ?? [];
		}
		return $return_db;
	}

	


	/**
	 * Check if a hardware brand exists.
	 *
	 * @param string|null $id The hardware brand to check.
	 * @param string $where The column to search for the hardware brand (default: "directory").
	 * @param bool $find_all Whether to find all brands or only the visible ones (default: false).
	 * @return bool Returns true if the brand exists, false otherwise.
	 */
	public function is_exist_hw_brand($id = null, $where = "directory", $find_all = false)
	{
		$return_data = false;
		if (empty($where))
		{
			$where = "directory";
		}
		if (!empty($id))
		{
			$final_where = array(
				$where => array( 'operator' => '=', 'value' => $id)
			);
			if (!$find_all)
			{
				$final_where['hidden'] = array( 'operator' => '=', 'value' => "0");
			}
			$count = $this->count_database_data(self::TABLES['epm_brands_list'], null, null, $final_where);
			$return_data = $count > 0;
		}
		return $return_data;
	}

	public function is_local_hw_brand($brand = null, $findby = "directory")
	{
		$return_data = null;
		if (!empty($brand) && !empty($findby))
		{
			$sql  = sprintf("SELECT local FROM %s WHERE %s = :findby", self::TABLES['epm_brands_list'], $findby);
			$stmt = $this->db->prepare($sql);
			$stmt->execute([
				':findby' => $brand
			]);
			$return_data = $stmt->rowCount() === 0 ? false : ($stmt->fetchColumn() === "1");
		}
		return $return_data;
	}




	/**
	 * Check if a hardware product exists in the database.
	 *
	 * @param int|null $id The ID of the hardware product.
	 * @param string $where The column to search for the hardware product. (default: "id")
	 * @param bool $find_all Whether to find all products or only the visible ones.
	 * @return bool Returns true if the hardware product exists, false otherwise.
	 */
	public function is_exist_hw_product($id = null, $where = "id", $find_all = false)
	{
		$return_data = false;
		if (empty($where))
		{
			$where = "id";
		}
		if (!empty($id))
		{
			$final_where = array(
				$where => array( 'operator' => '=', 'value' => $id)
			);
			if (!$find_all)
			{
				$final_where['hidden'] = array( 'operator' => '=', 'value' => "0");
			}
			$count = $this->count_database_data(self::TABLES['epm_product_list'], null, null, $final_where);
			$return_data = $count > 0;
		}
		return $return_data;
	}

	/**
	 * Sets the brand and product information in the database.
	 *
	 * @param mixed $find The value to search for in the "where" column.
	 * @param array $data An array containing the brand and product data.
	 * @param string $where The column name to use in the WHERE clause (Only INSERT).
	 * @param array $data_insert An optional array of additional data to insert.
	 * @param array $data_update An optional array of additional data to update.
	 * @return bool|int Returns true if the update was successful, or the inserted row ID if a new record was inserted. Returns false if the data is invalid or the "where" column is empty.
	 */
	public function set_hw_product($find = null, $data = array(), $where = "id", $data_insert = array(), $data_update = array())
	{
		// Set true for debug mode to show the generated SQL query
		$debug 				  = false;

		$data 				  = is_array($data)			? $data 		: [];
		$data_insert  		  = is_array($data_insert)	? $data_insert	: [];
		$data_update  		  = is_array($data_update)	? $data_update	: [];
		$data_default 		  = [];
		$data_insert_defaults = [
			// 'hidden' => $data['hidden'] ?? 0,
		];
		$data_update_defaults = [];
		$data 		 = array_merge($data_default, $data);
		$data_insert = array_merge($data_insert_defaults, $data_insert);
		$data_update = array_merge($data_update_defaults, $data_update);
		
		return $this->set_database_data(self::TABLES['epm_product_list'], $find, $data, $where, $data_insert, $data_update, $debug);
	}

	/**
	 * Retrieves the hardware product list based on the provided ID.
	 *
	 * @param int $id The ID of the brand.
	 * @param bool $show_all (Optional) Whether to show all products or not. Default is false.
	 * @param string|null $order_by (Optional) The column to order the results by. Default is null.
	 * @param string|null $order_dir (Optional) The direction to order the results in. Default is null.
	 * @return array The hardware product list.
	 */
	public function get_hw_product_list($id, $show_all = false, $order_by = null, $order_dir = null)
	{
		if (empty($id))
		{
			return array();
		}
		$where = array(
			'brand' => array( 'operator' => '=', 'value' => $id)
		);
		if (!$show_all)
		{
			$where['hidden'] = array('operator' => '=', 'value' => "0");
		}
		return $this->get_database_data(self::TABLES['epm_product_list'], $where, '*', $order_by, $order_dir);
	}

	public function get_hw_product($id, $where = "id", ?string $select = "*", ?bool $getOne = false)
	{
		$debug = false;
		if (empty($id) && !is_array($where)) { return []; }
		if (empty($where))					 { $where  = "id"; }
		if (empty($select))					 { $select = "*"; }
		if (is_array($where))				 { $where_query = $where; }
		else
		{
			$where_query = array(
				$where => array( 'operator' => '=', 'value' => $id)
			);
		}

		$return_db = $this->get_database_data(self::TABLES['epm_product_list'], $where_query, $select, null, null, false, false, $debug);

		if ($getOne && !empty($return_db))
		{
			$return_db = $return_db[0] ?? [];
		}
		return $return_db;
	}



	
	/**
	 * Sets the hardware model data in the database.
	 *
	 * @param mixed $find The value to search for in the database.
	 * @param array $data An array of data to be set.
	 * @param string $where The column to search for the value.
	 * @param array $data_insert An array of data to be inserted.
	 * @param array $data_update An array of data to be updated.
	 * @return mixed The result of setting the database data.
	 */
	public function set_hw_model($find = null, $data = array(), $where = "id", $data_insert = array(), $data_update = array())
	{
		// Set true for debug mode to show the generated SQL query
		$debug 				  = false;

		$data 				  = is_array($data)			? $data 		: [];
		$data_insert  		  = is_array($data_insert)	? $data_insert	: [];
		$data_update  		  = is_array($data_update)	? $data_update	: [];
		$data_default 		  = [];
		$data_insert_defaults = [
			// 'hidden' => $data['hidden'] ?? 0,
		];
		$data_update_defaults = [];
		$data 		 = array_merge($data_default, $data);
		$data_insert = array_merge($data_insert_defaults, $data_insert);
		$data_update = array_merge($data_update_defaults, $data_update);
		
		return $this->set_database_data(self::TABLES['epm_model_list'], $find, $data, $where, $data_insert, $data_update, $debug);
	}

	/**
	 * Retrieves the hardware model list based on the given ID.
	 *
	 * @param int $id The ID of the product.
	 * @param bool $show_all (Optional) Whether to show all models or not. Default is false.
	 * @param string|null $order_by (Optional) The column to order the results by. Default is null.
	 * @param string|null $order_dir (Optional) The direction to order the results in. Default is null.
	 * @return array The array of hardware models matching the given ID and conditions.
	 */
	public function get_hw_model_list($id, $show_all = false, $order_by = null, $order_dir = null)
	{
		if (empty($id))
		{
			return array();
		}
		$where = array(
			'product_id' => array( 'operator' => '=', 'value' => $id)
		);
		if (!$show_all)
		{
			$where['hidden'] = array('operator' => '=', 'value' => "0");
		}
		return $this->get_database_data(self::TABLES['epm_model_list'], $where, '*', $order_by, $order_dir);
	}

	/**
	 * Checks if a hardware model exists in the database.
	 *
	 * @param int|null $id The ID of the hardware model to check. Defaults to null.
	 * @param string $where The column to search for the hardware model. (Default: "id")
	 * @param bool $find_all Determines whether to find all matching hardware models or just one. Defaults to false.
	 *
	 * @return bool Returns true if the hardware model exists, false otherwise.
	 */
	public function is_exist_hw_model($id = null, $where = "id", $find_all = false)
	{
		$return_data = false;
		if (empty($where))
		{
			$where = "id";
		}
		if (!empty($id))
		{
			$final_where = array(
				$where => array( 'operator' => '=', 'value' => $id)
			);
			if (!$find_all)
			{
				$final_where['hidden'] = array( 'operator' => '=', 'value' => "0");
			}
			$count = $this->count_database_data(self::TABLES['epm_model_list'], null, null, $final_where);
			$return_data = $count > 0;
		}
		return $return_data;
	}

	/**
	 * Deletes a hardware model from the database.
	 *
	 * @param int|null $id The ID of the hardware model to delete. Defaults to null.
	 * @return bool Returns true if the hardware model was deleted, false otherwise.
	 */
	public function del_hw_model($id = null)
	{
		if (!$this->is_exist_hw_model($id))
		{
			return false;
		}

		$sql  = sprintf("DELETE FROM %s WHERE id = :id", self::TABLES['epm_model_list']);
		$stmt = $this->db->prepare($sql);
		$stmt->execute([
			':id' => $id
		]);
		return true;
	}






	/**
	 * Synchronizes the MAC brand by model.
	 *
	 * @param string $model The model name.
	 * @param int $model_id The model ID.
	 * @return bool Returns true if the synchronization is successful, false otherwise.
	 */
	public function sync_mac_brand_by_model($model, $model_id)
	{
		if (empty($model) || empty($model_id))
		{
			return false;
		}

		$sql  = sprintf("SELECT id FROM %s WHERE model LIKE :model", self::TABLES['epm_model_list']);
		$stmt = $this->db->prepare($sql);
		$stmt->execute([
			':model' => $model,
		]);
		$new_model_id = $stmt->rowCount() === 0 ? false : ($stmt->fetchColumn() ?? false);
	
		// if ($new_model_id)
		// {
		// 	$sql  = sprintf("UPDATE %s SET  model = :new_model_id WHERE  model = :model", "endpointman_mac_list");
		// 	$stmt = $this->db->prepare($sql);
		// 	$stmt->execute([
		// 		':new_model_id' => $new_model_id,
		// 		':model' 		=> $model_id,
		// 	]);
		// }
		// else
		// {
		// 	$sql  = sprintf("UPDATE %s SET  model = '0' WHERE model = :model", self::TABLES["epm_mac_list"]);
		// 	$stmt = $this->db->prepare($sql);
		// 	$stmt->execute([
		// 		':model' => $model_id,
		// 	]);
		// }

		$data_oid = array(
			':model' => $new_model_id ? $new_model_id : 0
		);
		$this->set_database_data(self::TABLES['epm_mac_list'], $model_id, $data_oid, 'model');
	}

 
	public function get_hw_mac($id = null, $where = 'id', $select = "*")
	{
		$where_query = [];
		if (empty($select))
		{
			$select = "*";
		}
		if (! empty($id))
		{
			if (empty($where))
			{
				$where = 'id';
			}
			$where_query = array(
				$where => array( 'operator' => '=', 'value' => $id)
			);
		}
		return $this->get_database_data(self::TABLES['epm_mac_list'], $where_query, $select);
		
		// $sql = sprintf('SELECT id, global_custom_cfg_data, global_user_cfg_data FROM %s WHERE model = :brand_id_family_line_model', "endpointman_mac_list");
		// $stmt = $this->db->prepare($sql);
		// $stmt->execute([
		// 	':brand_id_family_line_model' => $brand_id_family_line_model
		// ]);
		// $old_data = $stmt->fetchAll(\PDO::FETCH_ASSOC);
	}

	public function set_hw_mac($find = null, $data = array(), $where = "id", $data_insert = array(), $data_update = array())
	{
		$data 				  = is_array($data)			? $data 		: [];
		$data_insert  		  = is_array($data_insert)	? $data_insert	: [];
		$data_update  		  = is_array($data_update)	? $data_update	: [];
		$data_default 		  = [];
		$data_insert_defaults = [
			// 'hidden' => $data['hidden'] ?? 0,
		];
		$data_update_defaults = [];
		$data 		 = array_merge($data_default, $data);
		$data_insert = array_merge($data_insert_defaults, $data_insert);
		$data_update = array_merge($data_update_defaults, $data_update);
		
		return $this->set_database_data(self::TABLES['epm_mac_list'], $find, $data, $where, $data_insert, $data_update);
	}








	public function get_hw_template($id = null, $where = 'id', $select = "*")
	{
		$where_query = [];
		if (empty($select))
		{
			$select = "*";
		}
		if (! empty($id))
		{
			if (empty($where))
			{
				$where = 'id';
			}
			$where_query = array(
				$where => array( 'operator' => '=', 'value' => $id)
			);	
		}
		
		return $this->get_database_data(self::TABLES['epm_template_list'], $where_query, $select);
		
		// $sql = sprintf('SELECT id, global_custom_cfg_data FROM %s WHERE model_id = :brand_id_family_line_model', "endpointman_template_list");
		// $stmt = $this->db->prepare($sql);
		// $stmt->execute([
		// 	':brand_id_family_line_model' => $brand_id_family_line_model
		// ]);
		// $old_data = $stmt->fetchAll(\PDO::FETCH_ASSOC);
	}

	public function set_hw_template($find = null, $data = array(), $where = "id", $data_insert = array(), $data_update = array())
	{
		$data 				  = is_array($data)			? $data 		: [];
		$data_insert  		  = is_array($data_insert)	? $data_insert	: [];
		$data_update  		  = is_array($data_update)	? $data_update	: [];
		$data_default 		  = [];
		$data_insert_defaults = [
			// 'hidden' => $data['hidden'] ?? 0,
		];
		$data_update_defaults = [];
		$data 		 = array_merge($data_default, $data);
		$data_insert = array_merge($data_insert_defaults, $data_insert);
		$data_update = array_merge($data_update_defaults, $data_update);
		
		return $this->set_database_data(self::TABLES['epm_mac_list'], $find, $data, $where, $data_insert, $data_update);
	}







	public function set_hw_oui($oui = null, $brand_id = null, $custom = 0)
	{
		if(empty($oui) || empty($brand_id))
		{
			return false;
		}

		$sql = sprintf("REPLACE INTO %s (`oui`, `brand`, `custom`) VALUES (:oui, :brand_id, :custom)", self::TABLES['epm_oui_list']);
		$sth = $this->db->prepare($sql);
		$sth->execute([
			':oui' 		=> $oui,
			':brand_id' => $brand_id,
			':custom' 	=> $custom,
		]);
		return true;
	}





	/*****************************************
	****** CODIGO ANTIGUO -- REVISADO ********
	*****************************************/


	/**
     * Returns list of Brands that are installed and not hidden and that have at least one model enabled under them
     * @param integer $selected ID Number of the brand that is supposed to be selected in a drop-down list box
     * @return array Number array used to generate a select box
     */
    function brands_available($selected = NULL, $show_blank=TRUE)
	{
        $data = $this->eda->all_active_brands();
		$temp = [];
        if ($show_blank) {
            $temp[0]['value'] = "";
            $temp[0]['text'] = "";
            $i = 1;
        } else {
            $i = 0;
        }
        foreach ($data as $row) {
            $temp[$i]['value'] = $row['id'];
            $temp[$i]['text'] = $row['name'];
            if ($row['id'] == $selected) {
                $temp[$i]['selected'] = TRUE;
            } else {
                $temp[$i]['selected'] = NULL;
            }
            $i++;
        }
        return($temp);
    }

	function listTZ($selected) {
        $data = \DateTimeZone::listIdentifiers();
        $i = 0;
        foreach ($data as $key => $row) {
            $temp[$i]['value'] = $row;
            $temp[$i]['text'] = $row;
            if (strtoupper ($temp[$i]['value']) == strtoupper($selected)) {
                $temp[$i]['selected'] = 1;
            } else {
                $temp[$i]['selected'] = 0;
            }
            $i++;
        }

        return($temp);
    }

	/**
	 * Checks if Git is installed on the system.
	 *
	 * @return string|false The path to Git executable if Git is installed, false otherwise.
	 */
	public function has_git()
	{
        exec('which git', $output);
        $git = file_exists($line = trim(current($output))) ? $line : 'git';
        unset($output);

        exec($git . ' --version', $output);
        preg_match('#^(git version)#', current($output), $matches);

        return !empty($matches[0]) ? $git : false;
        echo !empty($matches[0]) ? 'installed' : 'nope';
    }

	function tftp_check() {
        //create a simple block here incase people have strange issues going on as we will kill http
        //by running this if the server isn't really running!
        $sql = 'SELECT value FROM endpointman_global_vars WHERE var_name = \'tftp_check\'';
        if (sql($sql, 'getOne') != 1) {
            $sql = 'UPDATE endpointman_global_vars SET value = \'1\' WHERE var_name = \'tftp_check\'';
            sql($sql);
            $subject = shell_exec("netstat -luan --numeric-ports");
            if (preg_match('/:69\s/i', $subject))
			{
                $rand = md5(rand(10, 2000));
                if (file_put_contents($this->getConfig('config_location') . 'TEST', $rand))
				{
                    if ($this->system->tftp_fetch('127.0.0.1', 'TEST') != $rand) {
                        $this->error['tftp_check'] = _('Local TFTP Server is not correctly configured');
echo $this->error['tftp_check'];
                    }
                    unlink($this->getConfig('config_location') . 'TEST');
                }
				else
				{
                    $this->error['tftp_check'] = sprintf(_('Unable to write to %s'), $this->getConfig('config_location'));
echo $this->error['tftp_check'];
                }
            } else {
                $dis = FALSE;
                if (file_exists('/etc/xinetd.d/tftp')) {
                    $contents = file_get_contents('/etc/xinetd.d/tftp');
                    if (preg_match('/disable.*=.*yes/i', $contents)) {
                        $this->error['tftp_check'] = _('Disabled is set to "yes" in /etc/xinetd.d/tftp. Please fix <br />Then restart your TFTP service');
echo $this->error['tftp_check'];
                        $dis = TRUE;
                    }
                }
                if (!$dis)
				{
                    $this->error['tftp_check'] = _('TFTP Server is not running. <br />See here for instructions on how to install one: <a href="http://wiki.provisioner.net/index.php/Tftp" target="_blank">http://wiki.provisioner.net/index.php/Tftp</a>');
echo $this->error['tftp_check'];
                }
            }
            $sql = 'UPDATE endpointman_global_vars SET value = \'0\' WHERE var_name = \'tftp_check\'';
            sql($sql);
        }
		else
		{
            $this->error['tftp_check'] = _('TFTP Server check failed on last past. Skipping');
echo $this->error['tftp_check'];
        }
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
    function submit_config($brand, $product, $orig_name, $data) {
    	$posturl = 'http://www.provisioner.net/submit_config.php';

		$file_name_with_full_path = $this->system->buildPath($this->MODULE_PATH, 'data.txt');

    	$fp = fopen( $file_name_with_full_path, 'w');
    	fwrite($fp, $data);
    	fclose($fp);
    	

    	$postvars = array('brand' => $brand, 'product' => $product, 'origname' => htmlentities(addslashes($orig_name)), 'file_contents' => '@' . $file_name_with_full_path);

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





	private function linesAvailable($lineid=NULL, $macid=NULL)
	{
        if (isset($lineid))
		{
            $sql = sprintf("SELECT max_lines FROM %s as eml WHERE id = (SELECT emacl.model FROM %s as emacl, %s as ell WHERE ell.luid = %s AND ell.mac_id = emacl.id)", self::TABLES['epm_model_list'], self::TABLES['epm_mac_list'], self::TABLES['epm_line_list'], $lineid);

            $sql_l = sprintf("SELECT line, mac_id FROM `%s` WHERE luid = %s", self::TABLES['epm_line_list'], $lineid);
            $line = $this->eda->sql($sql_l, 'getRow', \PDO::FETCH_ASSOC);

            $sql_lu = sprintf("SELECT line FROM %s WHERE mac_id = %s", self::TABLES['epm_line_list'], $line['mac_id']);
        }
		elseif (isset($macid))
		{
            $sql = sprintf("SELECT max_lines FROM %s WHERE id = (SELECT model FROM %s WHERE id =%s)", self::TABLES['epm_model_list'], self::TABLES['epm_mac_list'], $macid);
            $sql_lu = sprintf("SELECT line FROM %s WHERE mac_id = %s", self::TABLES['epm_line_list'], $macid);

            $line['line'] = 0;
        }

        $max_lines  = $this->eda->sql($sql, 'getOne');
        $lines_used = $this->eda->sql($sql_lu, 'getAll');

        for ($i = 1; $i <= $max_lines; $i++)
		{
            if ($i == $line['line'])
			{
                $temp[$i]['value'] = $i;
                $temp[$i]['text'] = $i;
                $temp[$i]['selected'] = "selected";
            }
			else
			{
                if (! self::in_array_recursive($i, $lines_used))
				{
                    $temp[$i]['value'] = $i;
                    $temp[$i]['text']  = $i;
                }
            }
        }
        if (isset($temp))
		{
            return($temp);
        }
		else
		{
            return FALSE;
        }
    }





























































    /**


    function add_device($mac, $model, $ext, $template=NULL, $line=NULL, $displayname=NULL) {
    	$mac = $this->mac_check_clean($mac);
    	if ($mac) {
    		if (empty($model)) {
//$this->error['add_device'] =
			out(_("You Must Select A Model From the Drop Down") . "!");
    			return(FALSE);
    		} elseif (empty($ext)) {
//$this->error['add_device'] =
			out(_("You Must Select an Extension/Device From the Drop Down") . "!");
    			return(FALSE);
    		} else {
    			if ($this->epm_config->sync_model($model)) {
    				$sql = "SELECT id,template_id FROM endpointman_mac_list WHERE mac = '" . $mac . "'";
    				$dup = sql($sql, 'getRow', \PDO::FETCH_ASSOC);

    				if ($dup) {
    					if (!isset($template)) {
    						$template = $dup['template_id'];
    					}

    					$sql = "UPDATE endpointman_mac_list SET model = " . $model . ", template_id =  " . $template . " WHERE id = " . $dup['id'];
    					sql($sql);
						$return = $this->add_line($dup['id'], $line, $ext);
    					if ($return) {
    						return($return);
    					} else {
    						return(FALSE);
    					}
    				} else {
    					if (!isset($template)) {
    						$template = 0;
    					}

    					$sql = "SELECT mac_id FROM " . self::TABLES['epm_line_list'] . " WHERE ext = " . $ext;
    					$used = sql($sql, 'getOne');

					if (($used) AND (! $this->getConfig('show_all_registrations'))) {
//$this->error['add_device'] =
						out(_("You can't assign the same user to multiple devices") . "!");
    						return(FALSE);
    					}

    					if (!isset($displayname)) {
    						$sql = 'SELECT description FROM devices WHERE id = ' . $ext;
    						$name = sql($sql, 'getOne');
							$name = "123";
							$displayname = "123";
    					} else {
    						$name = $displayname;
    					}

    					$sql = 'SELECT endpointman_product_list. * , endpointman_model_list.template_data, endpointman_brand_list.directory FROM endpointman_model_list, endpointman_brand_list, endpointman_product_list WHERE endpointman_model_list.id =  \'' . $model . '\' AND endpointman_model_list.brand = endpointman_brand_list.id AND endpointman_model_list.product_id = endpointman_product_list.id';
    					$row = sql($sql, 'getRow', \PDO::FETCH_ASSOC);

    					$sql = "INSERT INTO `endpointman_mac_list` (`mac`, `model`, `template_id`) VALUES ('" . $mac . "', '" . $model . "', '" . $template . "')";
    					sql($sql);

    					$sql = 'SELECT last_insert_id()';
    					$ext_id = sql($sql, 'getOne');

    					if (empty($line)) {
    						$line = 1;
    					}

    					$sql = "INSERT INTO `". self::TABLES['epm_line_list'] ."` (`mac_id`, `ext`, `line`, `description`) VALUES ('" . $ext_id . "', '" . $ext . "', '" . $line . "', '" . addslashes($name) . "')";
    					sql($sql);

//$this->message['add_device'][] =
					out(_("Added ") . $name . _(" to line ") . $line);
    					return($ext_id);
    				}
    			} else {
//$this->error['Sync_Model'] =
				out(_("Invalid Model Selected, Can't Sync System") . "!");
    				return(FALSE);
    			}
    		}
    	} else {
//$this->error['add_device'] =
		out(_("Invalid MAC Address") . "!");
    		return(FALSE);
    	}
    }


    function add_line($mac_id, $line=NULL, $ext=NULL, $displayname=NULL) {
    	if ((!isset($line)) AND (!isset($ext))) {
    		if ($this->linesAvailable(NULL, $mac_id)) {
    			if ($this->eda->all_unused_registrations()) {
    				$sql = 'SELECT * FROM '.self::TABLES['epm_line_list'].' WHERE mac_id = ' . $mac_id;
    				$lines_list = sql($sql, 'getAll', \PDO::FETCH_ASSOC);

    				foreach ($lines_list as $row) {
    					$sql = "SELECT description FROM devices WHERE id = " . $row['ext'];
    					$name = sql($sql, 'getOne');

    					$sql = "UPDATE ".self::TABLES['epm_line_list']." SET line = '" . $row['line'] . "', ext = '" . $row['ext'] . "', description = '" . $this->eda->escapeSimple($name) . "' WHERE luid =  " . $row['luid'];
    					sql($sql);
    				}

    				$reg = array_values($this->display_registration_list());
    				$lines = array_values($this->linesAvailable(NULL, $mac_id));

    				$sql = "SELECT description FROM devices WHERE id = " . $reg[0]['value'];
    				$name = sql($sql, 'getOne');

    				$sql = "INSERT INTO `".self::TABLES['epm_line_list']."` (`mac_id`, `ext`, `line`, `description`) VALUES ('" . $mac_id . "', '" . $reg[0]['value'] . "', '" . $lines[0]['value'] . "', '" . addslashes($name) . "')";
    				sql($sql);

//$this->message['add_line'] =
				out(_("Added '<i>") . $name . _("</i>' to line '<i>") . $lines[0]['value'] . _("</i>' on device '<i>") . $reg[0]['value'] . _("</i>' <br/> Configuration Files will not be Generated until you click Save!"));
    				return($mac_id);
    			} else {
//$this->error['add_line'] =
				out(_("No Devices/Extensions Left to Add") . "!");
    				return(FALSE);
    			}
    		} else {
//$this->error['add_line'] =
			out(_("No Lines Left to Add") . "!");
    			return(FALSE);
    		}
    	} elseif ((!isset($line)) AND (isset($ext))) {
    		if ($this->linesAvailable(NULL, $mac_id)) {
    			if ($this->eda->all_unused_registrations()) {
    				$lines = array_values($this->linesAvailable(NULL, $mac_id));

    				$sql = "INSERT INTO `endpointman_line_list` (`mac_id`, `ext`, `line`, `description`) VALUES ('" . $mac_id . "', '" . $ext . "', '" . $lines[0]['value'] . "', '" . addslashes($displayname) . "')";
    				sql($sql);

//$this->message['add_line'] =
				out(_("Added '<i>") . $name . _("</i>' to line '<i>") . $lines[0]['value'] . _("</i>' on device '<i>") . $reg[0]['value'] . _("</i>' <br/> Configuration Files will not be Generated until you click Save!"));
    				return($mac_id);
    			} else {
//$this->error['add_line'] =
				out(_("No Devices/Extensions Left to Add") . "!");
    				return(FALSE);
    			}
    		} else {
//$this->error['add_line'] =
			out(_("No Lines Left to Add") . "!");
    			return(FALSE);
    		}
    	} elseif ((isset($line)) AND (isset($ext))) {
    		$sql = "SELECT luid FROM endpointman_line_list WHERE line = '" . $line . "' AND mac_id = " . $mac_id;
    		$luid = sql($sql, 'getOne');
    		if ($luid) {
//$this->error['add_line'] =
			out(_("This line has already been assigned!"));
    			return(FALSE);
    		} else {
    			if (!isset($displayname)) {
    				$sql = 'SELECT description FROM devices WHERE id = ' . $ext;
    				$name = sql($sql, 'getOne');
    			} else {
    				$name = $displayname;
    			}

    			$sql = "INSERT INTO `endpointman_line_list` (`mac_id`, `ext`, `line`, `description`) VALUES ('" . $mac_id . "', '" . $ext . "', '" . $line . "', '" . addslashes($name) . "')";
    			sql($sql);
//$this->message['add_line'] =
			out(_("Added ") . $name . _(" to line ") . $line . "<br/>");
    			return($mac_id);
    		}
    	}
    }


    




     * Display all unused registrations from whatever manager we are using!
     * @return <type>
     */
	     /**
    function display_registration_list($line_id=NULL) {

    	if (isset($line_id)) {
    		$result = $this->eda->all_unused_registrations();
    		$line_data = $this->eda->get_line_information($line_id);
    	} else {
    		$result = $this->eda->all_unused_registrations();
    		$line_data = NULL;
    	}

    	$i = 1;
    	$temp = array();
    	foreach ($result as $row) {
    		$temp[$i]['value'] = $row['id'];
    		$temp[$i]['text'] = $row['id'] . " --- " . $row['description'];
    		$i++;
    	}

    	if (isset($line_data)) {
    		$temp[$i]['value'] = $line_data['ext'];
    		$temp[$i]['text'] = $line_data['ext'] . " --- " . $line_data['description'];
    		$temp[$i]['selected'] = "selected";
    	}

    	return($temp);
    }



     * Send this function an ID from the mac devices list table and you'll get all the information we have on that particular phone
     * @param integer $mac_id ID number reference from the MySQL database referencing the table endpointman_mac_list
     * @return array
     * @example
     * Final Output will look something similar to this
     *  Array
     *       (
     *            [config_files_override] =>
     *            [global_user_cfg_data] => N;
     *            [model_id] => 213
     *            [brand_id] => 2
     *            [name] => Grandstream
     *            [directory] => grandstream
     *            [model] => GXP2000
     *            [mac] => 000B820D0050
     *            [template_id] => 0
     *            [global_custom_cfg_data] => Serialized Data (Changed Template Values)
     *            [long_name] => GXP Enterprise IP series [280,1200,2000,2010,2020]
     *            [product_id] => 21
     *            [cfg_dir] => gxp
     *            [cfg_ver] => 1.5
     *            [template_data] => Serialized Data (The default Template Values)
     *            [enabled] => 1
     *            [line] => Array
     *                (
     *                    [1] => Array
     *                        (
     *                            [luid] => 2
     *                            [mac_id] => 2
     *                            [line] => 1
     *                            [ext] => 1000
     *                            [description] => Description
     *                            [custom_cfg_data] =>
     *                            [user_cfg_data] =>
     *                            [secret] => secret
     *                            [id] => 1000
     *                            [tech] => sip
     *                            [dial] => SIP/1000
     *                            [devicetype] => fixed
     *                            [user] => 1000
     *                            [emergency_cid] =>
     *                        )
     *                )
     *         )

    function get_phone_info($mac_id=NULL) {
    	//You could screw up a phone if the mac_id is blank
    	if (!isset($mac_id)) {
//$this->error['get_phone_info'] =
		out(_("Mac ID is not set"));
    		return(FALSE);
    	}
    	$sql = "SELECT id FROM endpointman_mac_list WHERE model > 0 AND id =" . $mac_id;

    	//$res = sql($sql);
		$res = sql($sql, 'getAll', \PDO::FETCH_ASSOC);
    	if (count(array($res))) {
    		//Returns Brand Name, Brand Directory, Model Name, Mac Address, Extension (FreePBX), Custom Configuration Template, Custom Configuration Data, Product Name, Product ID, Product Configuration Directory, Product Configuration Version, Product XML name,
    		$sql = "SELECT endpointman_mac_list.specific_settings, endpointman_mac_list.config_files_override, endpointman_mac_list.global_user_cfg_data, endpointman_model_list.id as model_id, endpointman_brand_list.id as brand_id, endpointman_brand_list.name, endpointman_brand_list.directory, endpointman_model_list.model, endpointman_mac_list.mac, endpointman_mac_list.template_id, endpointman_mac_list.global_custom_cfg_data, endpointman_product_list.long_name, endpointman_product_list.id as product_id, endpointman_product_list.cfg_dir, endpointman_product_list.cfg_ver, endpointman_model_list.template_data, endpointman_model_list.enabled, endpointman_mac_list.global_settings_override FROM endpointman_line_list, endpointman_mac_list, endpointman_model_list, endpointman_brand_list, endpointman_product_list WHERE endpointman_mac_list.model = endpointman_model_list.id AND endpointman_brand_list.id = endpointman_model_list.brand AND endpointman_product_list.id = endpointman_model_list.product_id AND endpointman_mac_list.id = endpointman_line_list.mac_id AND endpointman_mac_list.id = " . $mac_id;
    		$phone_info = sql($sql, 'getRow', \PDO::FETCH_ASSOC);

    		if (!$phone_info) {
//$this->error['get_phone_info'] =
			out(_("Error with SQL Statement"));
    		}

    		//If there is a template associated with this phone then pull that information and put it into the array
    		if ($phone_info['template_id'] > 0) {
    			$sql = "SELECT name, global_custom_cfg_data, config_files_override, global_settings_override FROM endpointman_template_list WHERE id = " . $phone_info['template_id'];
    			$phone_info['template_data_info'] = sql($sql, 'getRow', \PDO::FETCH_ASSOC);
    		}

    		$sql = "SELECT endpointman_line_list.*, sip.data as secret, devices.*, endpointman_line_list.description AS epm_description FROM endpointman_line_list, sip, devices WHERE endpointman_line_list.ext = devices.id AND endpointman_line_list.ext = sip.id AND sip.keyword = 'secret' AND mac_id = " . $mac_id . " ORDER BY endpointman_line_list.line ASC";
    		$lines_info = sql($sql, 'getAll', \PDO::FETCH_ASSOC);
    		foreach ($lines_info as $line) {
    			$phone_info['line'][$line['line']] = $line;
    			$phone_info['line'][$line['line']]['description'] = $line['epm_description'];
    			$phone_info['line'][$line['line']]['user_extension'] = $line['user'];
    		}
    	} else {
    		$sql = "SELECT id, mac FROM endpointman_mac_list WHERE id =" . $mac_id;
    		//Phone is unknown, we need to display this to the end user so that they can make corrections
    		$row = sql($sql, 'getRow', \PDO::FETCH_ASSOC);

			$brand = $this->get_brand_from_mac($row['mac']);
    		if ($brand) {
    			$phone_info['brand_id'] = $brand['id'];
    			$phone_info['name'] = $brand['name'];
    		} else {
    			$phone_info['brand_id'] = 0;
    			$phone_info['name'] = 'Unknown';
    		}

    		$phone_info['id'] = $mac_id;
    		$phone_info['model_id'] = 0;
    		$phone_info['product_id'] = 0;
    		$phone_info['custom_cfg_template'] = 0;
    		$phone_info['mac'] = $row['mac'];
    		$sql = "SELECT endpointman_line_list.*, sip.data as secret, devices.* FROM endpointman_line_list, sip, devices WHERE endpointman_line_list.ext = devices.id AND endpointman_line_list.ext = sip.id AND sip.keyword = 'secret' AND mac_id = " . $mac_id;
    		$lines_info = sql($sql, 'getAll', \PDO::FETCH_ASSOC);
    		foreach ($lines_info as $line) {
    			$phone_info['line'][$line['line']] = $line;
    		}
    	}
		$phone_info = "test";
    	return $phone_info;
    }
*/
    /**
     * Get the brand from any mac sent to this function
     * @param string $mac
     * @return array

    function get_brand_from_mac($mac) {
    	//Check for valid mac address first
		if (!$this->mac_check_clean($mac)) {
    		return(FALSE);
    	}

    	//Get the OUI only
    	$oui = substr($this->mac_check_clean($mac), 0, 6);
    	//Find the matching brand model to the oui
    	$oui_sql = "SELECT endpointman_brand_list.name, endpointman_brand_list.id FROM endpointman_oui_list, endpointman_brand_list WHERE oui LIKE '%" . $oui . "%' AND endpointman_brand_list.id = endpointman_oui_list.brand AND endpointman_brand_list.installed = 1 LIMIT 1";
    	$brand = sql($oui_sql, 'getRow', \PDO::FETCH_ASSOC);

    	$res = sql($oui_sql);
    	$brand_count = count(array($res));

    	if (!$brand_count) {
    		//oui doesn't have a matching mysql reference, probably a PC/router/wap/printer of some sort.
    		$phone_info['id'] = 0;
    		$phone_info['name'] = _("Unknown");
    	} else {
    		$phone_info['id'] = $brand['id'];
    		$phone_info['name'] = $brand['name'];
    	}

    	return($phone_info);
    }

*/

    /**
     * Prepare and then send the data that Provisioner expects, then take what provisioner gives us and do what it says
     * @param array $phone_info Everything from get_phone_info
     * @param bool  $reboot Reboot the Phone after write
     * @param bool  $write  Write out Directory structure.

    function prepare_configs($phone_info, $reboot=TRUE, $write=TRUE)
    {
    	$this->PROVISIONER_BASE = $this->PHONE_MODULES_PATH;
        define('PROVISIONER_BASE', $this->PROVISIONER_BASE);
    	if (file_exists($this->PHONE_MODULES_PATH . '/autoload.php')) {
    		if (!class_exists('ProvisionerConfig')) {
    			require($this->PHONE_MODULES_PATH . '/autoload.php');
    		}

    		//Load Provisioner
    		$class = "endpoint_" . $phone_info['directory'] . "_" . $phone_info['cfg_dir'] . '_phone';
    		$base_class = "endpoint_" . $phone_info['directory'] . '_base';
    		$master_class = "endpoint_base";
    		if (!class_exists($master_class)) {
    			ProvisionerConfig::endpointsAutoload($master_class);
    		}
    		if (!class_exists($base_class)) {
    			ProvisionerConfig::endpointsAutoload($base_class);
    		}
    		if (!class_exists($class)) {
    			ProvisionerConfig::endpointsAutoload($class);
    		}

    		if (class_exists($class)) {
				$provisioner_lib = new $class();

    			//Determine if global settings have been overridden
    			if ($phone_info['template_id'] > 0) {
    				if (isset($phone_info['template_data_info']['global_settings_override'])) {
    					$settings = unserialize($phone_info['template_data_info']['global_settings_override']);
    				} else {
    					$settings['srvip'] = $this->getConfig('srvip');
    					$settings['ntp'] = $this->getConfig('ntp');
    					$settings['config_location'] = $this->getConfig('config_location');
    					$settings['tz'] = $this->getConfig('tz');
    				}
    			} else {
    				if (isset($phone_info['global_settings_override'])) {
    					$settings = unserialize($phone_info['global_settings_override']);
    				} else {
    					$settings['srvip'] = $this->getConfig('srvip');
    					$settings['ntp'] = $this->getConfig('ntp');
    					$settings['config_location'] = $this->getConfig('config_location');
    					$settings['tz'] = $this->getConfig('tz');
    				}
    			}



    			//Tell the system who we are and were to find the data.
    			$provisioner_lib->root_dir = $this->PHONE_MODULES_PATH;
    			$provisioner_lib->engine = 'asterisk';
    			$provisioner_lib->engine_location = $this->getConfig('asterisk_location','asterisk');
    			$provisioner_lib->system = 'unix';

    			//have to because of versions less than php5.3
    			$provisioner_lib->brand_name = $phone_info['directory'];
    			$provisioner_lib->family_line = $phone_info['cfg_dir'];



    			//Phone Model (Please reference family_data.xml in the family directory for a list of recognized models)
    			//This has to match word for word. I really need to fix this....
    			$provisioner_lib->model = $phone_info['model'];

    			//Timezone
    			try {
                                $provisioner_lib->DateTimeZone = new \DateTimeZone($settings['tz']);
    			} catch (Exception $e) {
$this->error['parse_configs'] = 'Error Returned From Timezone Library: ' . $e->getMessage();
    				return(FALSE);
    			}

    			$temp = "";
    			$template_data = unserialize($phone_info['template_data']);
    			$global_user_cfg_data = unserialize($phone_info['global_user_cfg_data']);
    			if ($phone_info['template_id'] > 0) {
    				$global_custom_cfg_data = unserialize($phone_info['template_data_info']['global_custom_cfg_data']);
    				//Provide alternate Configuration file instead of the one from the hard drive
    				if (!empty($phone_info['template_data_info']['config_files_override'])) {
    					$temp = unserialize($phone_info['template_data_info']['config_files_override']);
    					foreach ($temp as $list) {
    						$sql = "SELECT original_name,data FROM endpointman_custom_configs WHERE id = " . $list;
    						//$res = sql($sql);
							$res = sql($sql, 'getAll', \PDO::FETCH_ASSOC);
    						if (count(array($res))) {
    							$data = sql($sql, 'getRow', \PDO::FETCH_ASSOC);
    							$provisioner_lib->config_files_override[$data['original_name']] = $data['data'];
    						}
    					}
    				}
    			} else {
    				$global_custom_cfg_data = unserialize($phone_info['global_custom_cfg_data']);
    				//Provide alternate Configuration file instead of the one from the hard drive
    				if (!empty($phone_info['config_files_override'])) {
    					$temp = unserialize($phone_info['config_files_override']);
    					foreach ($temp as $list) {
    						$sql = "SELECT original_name,data FROM endpointman_custom_configs WHERE id = " . $list;
    						//$res = sql($sql);
							$res = sql($sql, 'getAll', \PDO::FETCH_ASSOC);
    						if (count(array($res))) {
    							$data = sql($sql, 'getRow', \PDO::FETCH_ASSOC);
    							$provisioner_lib->config_files_override[$data['original_name']] = $data['data'];
    						}
    					}
    				}
    			}

    			if (!empty($global_custom_cfg_data)) {
    				if (array_key_exists('data', $global_custom_cfg_data)) {
    					$global_custom_cfg_ari = $global_custom_cfg_data['ari'];
    					$global_custom_cfg_data = $global_custom_cfg_data['data'];
    				} else {
    					$global_custom_cfg_data = array();
    					$global_custom_cfg_ari = array();
    				}
    			}

    			$new_template_data = array();
    			$line_ops = array();
    			if (is_array($global_custom_cfg_data)) {
    				foreach ($global_custom_cfg_data as $key => $data) {
    					//TODO: clean up with reg-exp
    					$full_key = $key;
    					$key = explode('|', $key);
    					$count = count($key);
    					switch ($count) {
    						case 1:
    							if (($this->getConfig('enable_ari') == 1) AND (isset($global_custom_cfg_ari[$full_key])) AND (isset($global_user_cfg_data[$full_key]))) {
    								$new_template_data[$full_key] = $global_user_cfg_data[$full_key];
    							} else {
    								$new_template_data[$full_key] = $global_custom_cfg_data[$full_key];
    							}
    							break;
    						case 2:
    							$breaks = explode('_', $key[1]);
    							if (($this->getConfig('enable_ari') == 1) AND (isset($global_custom_cfg_ari[$full_key])) AND (isset($global_user_cfg_data[$full_key]))) {
    								$new_template_data['loops'][$breaks[0]][$breaks[2]][$breaks[1]] = $global_user_cfg_data[$full_key];
    							} else {
    								$new_template_data['loops'][$breaks[0]][$breaks[2]][$breaks[1]] = $global_custom_cfg_data[$full_key];
    							}
    							break;
    						case 3:
    							if (($this->getConfig('enable_ari') == 1) AND (isset($global_custom_cfg_ari[$full_key])) AND (isset($global_user_cfg_data[$full_key]))) {
    								$line_ops[$key[1]][$key[2]] = $global_user_cfg_data[$full_key];
    							} else {
    								$line_ops[$key[1]][$key[2]] = $global_custom_cfg_data[$full_key];
    							}
    							break;
    					}
    				}
    			}

    			if (!$write) {
    				$new_template_data['provision']['type'] = 'dynamic';
    				$new_template_data['provision']['protocol'] = 'http';
    				$new_template_data['provision']['path'] =  rtrim($settings['srvip'] . dirname($_SERVER['REQUEST_URI']) . '/', '/');
    				$new_template_data['provision']['encryption'] = FALSE;
    			} else {
    				$new_template_data['provision']['type'] = 'file';
    				$new_template_data['provision']['protocol'] = 'tftp';
    				$new_template_data['provision']['path'] = $settings['srvip'];
    				$new_template_data['provision']['encryption'] = FALSE;
    			}

    			$new_template_data['ntp'] = $settings['ntp'];

    			//Overwrite all specific settings variables now
    			if (!empty($phone_info['specific_settings'])) {
    				$specific_settings = unserialize($phone_info['specific_settings']);
    				$specific_settings = is_array($specific_settings) ? $specific_settings : array();
    			} else {
    				$specific_settings = array();
    			}

    			//Set Variables according to the template_data files included. We can include different template.xml files within family_data.xml also one can create
    			//template_data_custom.xml which will get included or template_data_<model_name>_custom.xml which will also get included
    			//line 'global' will set variables that aren't line dependant


    			$provisioner_lib->settings = $new_template_data;

    			//Loop through Lines!
    			$li = 0;
    			foreach ($phone_info['line'] as $line) {
    				$line_options = is_array($line_ops[$line['line']]) ? $line_ops[$line['line']] : array();
    				$line_statics = array('line' => $line['line'], 'username' => $line['ext'], 'authname' => $line['ext'], 'secret' => $line['secret'], 'displayname' => $line['description'], 'server_host' => $this->getConfig('srvip'), 'server_port' => '5060', 'user_extension' => $line['user_extension']);
    				$provisioner_lib->settings['line'][$li] = array_merge($line_options, $line_statics);
    				$li++;
    			}

    			if (array_key_exists('data', $specific_settings)) {
    				foreach ($specific_settings['data'] as $key => $data) {
    					$default_exp = preg_split("/\|/i", $key);
    					if (isset($default_exp[2])) {
    						//lineloop
    						$var = $default_exp[2];
    						$line = $default_exp[1];
    						$loc = $this->system->arraysearchrecursive($line, $provisioner_lib->settings['line'], 'line');
    						if ($loc !== FALSE) {
    							$k = $loc[0];
    							$provisioner_lib->settings['line'][$k][$var] = $data;
    						} else {
    							//Adding a new line-ish type options
    							if (isset($specific_settings['data']['line|' . $line . '|line_enabled'])) {
    								$lastkey = array_pop(array_keys($provisioner_lib->settings['line']));
    								$lastkey++;
    								$provisioner_lib->settings['line'][$lastkey]['line'] = $line;
    								$provisioner_lib->settings['line'][$lastkey][$var] = $data;
    							}
    						}
    					} else {
    						switch ($key) {
    							case "connection_type":
    								$provisioner_lib->settings['network'][$key] = $data;
    								break;
    							case "ip4_address":
    								$provisioner_lib->settings['network']['ipv4'] = $data;
    								break;
    							case "ip6_address":
    								$provisioner_lib->settings['network']['ipv6'] = $data;
    								break;
    							case "subnet_mask":
    								$provisioner_lib->settings['network']['subnet'] = $data;
    								break;
    							case "gateway_address":
    								$provisioner_lib->settings['network']['gateway'] = $data;
    								break;
    							case "primary_dns":
    								$provisioner_lib->settings['network'][$key] = $data;
    								break;
    							default:
    								$provisioner_lib->settings[$key] = $data;
    								break;
    						}
    					}
    				}
    			}

    			$provisioner_lib->settings['mac'] = $phone_info['mac'];
    			$provisioner_lib->mac = $phone_info['mac'];

    			//Setting a line variable here...these aren't defined in the template_data.xml file yet. however they will still be parsed
    			//and if they have defaults assigned in a future template_data.xml or in the config file using pipes (|) those will be used, pipes take precedence
    			$provisioner_lib->processor_info = "EndPoint Manager Version " . $this->getConfig('version');

    			// Because every brand is an extension (eventually) of endpoint, you know this function will exist regardless of who it is
    			//Start timer
    			$time_start = microtime(true);

    			$provisioner_lib->debug = TRUE;

    			try {
    				$returned_data = $provisioner_lib->generate_all_files();
    			} catch (Exception $e) {
$this->error['prepare_configs'] = 'Error Returned From Provisioner Library: ' . $e->getMessage();
    				return(FALSE);
    			}
    			//print_r($provisioner_lib->debug_return);
    			//End timer
    			$time_end = microtime(true);
    			$time = $time_end - $time_start;
    			if ($time > 360) {
$this->error['generate_time'] = "It took an awfully long time to generate configs...(" . round($time, 2) . " seconds)";
    			}
    			if ($write) {
    				$this->write_configs($provisioner_lib, $reboot, $settings['config_location'], $phone_info, $returned_data);
    			} else {
    				return ($returned_data);
    			}
    			return(TRUE);
    		} else {
$this->error['parse_configs'] = "Can't Load \"" . $class . "\" Class!";
    			return(FALSE);
    		}
    	} else {
$this->error['parse_configs'] = "Can't Load the Autoloader!";
    		return(FALSE);
    	}
    }

    function write_configs($provisioner_lib, $reboot, $write_path, $phone_info, $returned_data) {

    	//Create Directory Structure (If needed)
    	if (isset($provisioner_lib->directory_structure)) {
    		foreach ($provisioner_lib->directory_structure as $data) {
    			if (file_exists($this->PHONE_MODULES_PATH . "/endpoint/" . $phone_info['directory'] . "/" . $phone_info['cfg_dir'] . "/" . $data)) {
    				$dir_iterator = new \RecursiveDirectoryIterator($this->PHONE_MODULES_PATH . "/endpoint/" . $phone_info['directory'] . "/" . $phone_info['cfg_dir'] . "/" . $data . "/");
    				$iterator = new \RecursiveIteratorIterator($dir_iterator, \RecursiveIteratorIterator::SELF_FIRST);
    				// could use CHILD_FIRST if you so wish
    				foreach ($iterator as $file) {
    					$dir = $write_path . str_replace($this->PHONE_MODULES_PATH . "/endpoint/" . $phone_info['directory'] . "/" . $phone_info['cfg_dir'] . "/", "", dirname($file));
    					if (!file_exists($dir)) {
    						if (!@mkdir($dir, 0775, TRUE)) {
$this->error['parse_configs'] = "Could Not Create Directory: " . $data;
    							return(FALSE);
    						}
    					}
    				}
    			} else {
    				$dir = $write_path . $data;
    				if (!file_exists($dir)) {
    					if (!@mkdir($dir, 0775)) {
$this->error['parse_configs'] = "Could Not Create Directory: " . $data;
    						return(FALSE);
    					}
    				}
    			}
    		}
    	}

    	//Copy Files (If needed)
    	if (isset($provisioner_lib->copy_files)) {
    		foreach ($provisioner_lib->copy_files as $data) {
    			if (file_exists($this->PHONE_MODULES_PATH . "/endpoint/" . $phone_info['directory'] . "/" . $phone_info['cfg_dir'] . "/" . $data)) {
    				$file = $write_path . $data;
    				$orig = $this->PHONE_MODULES_PATH . "/endpoint/" . $phone_info['directory'] . "/" . $phone_info['cfg_dir'] . "/" . $data;
    				if (!file_exists($file)) {
    					if (!@copy($orig, $file)) {
$this->error['parse_configs'] = "Could Not Create File: " . $data;
    						return(FALSE);
    					}
    				} else {
    					if (file_exists($this->PHONE_MODULES_PATH . "/endpoint/" . $phone_info['directory'] . "/" . $phone_info['cfg_dir'] . "/" . $data)) {
    						if (!file_exists(dirname($write_path . $data))) {
    							!@mkdir(dirname($write_path . $data), 0775);
    						}
    						copy($this->PHONE_MODULES_PATH . "/endpoint/" . $phone_info['directory'] . "/" . $phone_info['cfg_dir'] . "/" . $data, $write_path . $data);
    						chmod($write_path . $data, 0775);
    					}
    				}
    			}
    		}
    	}

    	foreach ($returned_data as $file => $data) {
    		if (((file_exists($write_path . $file)) AND (is_writable($write_path . $file)) AND (!in_array($file, $provisioner_lib->protected_files))) OR (!file_exists($write_path . $file))) {
    			//Move old file to backup
    			if (!$this->eda->global_cfg['backup_check']) {
    				if (!file_exists($write_path . 'config_bkup')) {
    					if (!@mkdir($write_path . 'config_bkup', 0775)) {
$this->error['parse_configs'] = "Could Not Create Backup Directory";
    						return(FALSE);
    					}
    				}
    				if (file_exists($write_path . $file)) {
    					copy($write_path . $file, $write_path . 'config_bkup/' . $file . '.' . time());
    				}
    			}
    			file_put_contents($write_path . $file, $data);
    			chmod($write_path . $file, 0775);
    			if (!file_exists($write_path . $file)) {
$this->error['parse_configs'] = "File (" . $file . ") not written to hard drive!";
    				return(FALSE);
    			}
    		} elseif (!in_array($file, $provisioner_lib->protected_files)) {
$this->error['parse_configs'] = "File not written to hard drive!";
    			return(FALSE);
    		}
    	}

    	if ($reboot) {
    		$provisioner_lib->reboot();
    	}
    }
*/



























































































	/*********************************************
	****** CODIGO ANTIGUO -- SIN REVISADO ********
	*********************************************/









/*

    function download_json($location, $directory=NULL) {
        $temp_directory = $this->sys_get_temp_dir() . "/epm_temp/";
        if (!isset($directory)) {
            $destination_file = $this->PHONE_MODULES_PATH . '/endpoint/master.json';
            $directory = "master";
        } else {
            if (!file_exists($this->PHONE_MODULES_PATH . '/' . $directory)) {
                mkdir($this->PHONE_MODULES_PATH . '/' . $directory, 0775, TRUE);
            }
            $destination_file = $this->PHONE_MODULES_PATH . '/' . $directory . '/brand_data.json';
        }
        $temp_file = $temp_directory . $directory . '.json';
        file_exists(dirname($temp_file)) ? '' : mkdir(dirname($temp_file));

        if ($this->system->download_file($location, $temp_file)) {
            $handle = fopen($temp_file, "rb");
            $contents = fread($handle, filesize($temp_file));
            fclose($handle);

            $a = $this->validate_json($contents);
            if ($a === FALSE) {
                //Error with the internet....ABORRRTTTT THEEEEE DOWNLOAAAAADDDDDDDD! SCOTTYYYY!;
                unlink($temp_file);
                return(FALSE);
            } else {
                rename($temp_file, $destination_file);
                chmod($destination_file, 0775);
                return(TRUE);
            }
        } else {
            return(FALSE);
        }
    }


*/


    /**
    * Send process to run in background
    * @version 2.11
    * @param string $command the command to run
    * @param integer $Priority the Priority of the command to run
    * @return int $PID process id
    * @package epm_system

    function run_in_background($Command, $Priority = 0) {
        return($Priority ? shell_exec("nohup nice -n $Priority $Command 2> /dev/null & echo $!") : shell_exec("nohup $Command > /dev/null 2> /dev/null & echo $!"));
    }

    /**
    * Check if process is running in background
    * @version 2.11
    * @param string $PID proccess ID
    * @return bool true or false
    * @package epm_system

    function is_process_running($PID) {
        exec("ps $PID", $ProcessState);
        return(count($ProcessState) >= 2);
    }



    /**
    * Uses which to find executables that asterisk can run/use
    * @version 2.11
    * @param string $exec Executable to find
    * @package epm_system


    function find_exec($exec) {
        $o = exec('which '.$exec);
        if($o) {
            if(file_exists($o) && is_executable($o)) {
                return($o);
            } else {
                return('');
            }
        } else {
            return('');
        }
    }

    */
    /**
     * Only used once in all of Endpoint Manager to determine if a table exists
     * @param string $table Table to look for
     * @return bool

    function table_exists($table) {
        $sql = "SHOW TABLES FROM " . $this->config->get('AMPDBNAME');
        $result = $this->eda->sql($sql, 'getAll');
        foreach ($result as $row) {
            if ($row[0] == $table) {
                return TRUE;
            }
        }
        return FALSE;
    }
     */




    /**
     * Check for valid netmast to avoid security issues
     * @param string $mask the complete netmask, eg [1.1.1.1/24]
     * @return boolean True if valid, False if not
     * @version 2.11

    function validate_netmask($mask) {
        return preg_match("/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})\/(\d{1,2})$/", $mask) ? TRUE : FALSE;
    }

    /**
     * Discover New Device/Hardware
     * nmap will actually discover 'unseen' devices that the VoIP server hasn't heard from
     * If the user just wishes to use the local arp cache they can tell the function to not use nmap
     * This results in a speed increase from 60 seconds to less than one second.
     *
     * This is the original function that started it all
     * http://www.pbxinaflash.com/community/index.php?threads/end-point-configuration-manager-module-for-freepbx-part-1.4514/page-4#post-37671
     *
     * @version 2.11
     * @param mixed $netmask The netmask, eg [1.1.1.1/24]
     * @param boolean $use_nmap True use nmap, false don't use it
     * @return array List of devices found on the network

    function discover_new($netmask, $use_nmap=TRUE) {
        if (($use_nmap) AND (file_exists($this->eda->global_cfg['nmap_location'])) AND ($this->validate_netmask($netmask))) {
            shell_exec($this->eda->global_cfg['nmap_location'] . ' -v -sP ' . $netmask);
        } elseif (!$this->validate_netmask($netmask)) {
            $this->error['discover_new'] = "Invalid Netmask";
            return(FALSE);
        } elseif (!file_exists($this->eda->global_cfg['nmap_location'])) {
            $this->error['discover_new'] = "Could Not Find NMAP, Using ARP Only";
            //return(FALSE);
        }

        //Get arp list
        $arp_list = shell_exec($this->eda->global_cfg['arp_location'] . " -an");

        //Throw arp list into an array, break by new lines
        $arp_array = explode("\n", $arp_list);

        //Find all references to active computers by searching out mac addresses.
        $temp = array_values(array_unique(preg_grep("/[0-9a-f][0-9a-f][:-]" .
                                "[0-9a-f][0-9a-f][:-]" .
                                "[0-9a-f][0-9a-f][:-]" .
                                "[0-9a-f][0-9a-f][:-]" .
                                "[0-9a-f][0-9a-f][:-]" .
                                "[0-9a-f][0-9a-f]/i", $arp_array)));

        //Go through each row of valid arp entries and pull out the information and add it into a nice array!
        $z = 0;
        foreach ($temp as $key => &$value) {

            //Pull out the IP address from row. It's always the first entry in the row and it can only be a max of 15 characters with the delimiters
            preg_match_all("/\((.*?)\)/", $value, $matches);
            $ip = $matches[1];
            $ip = $ip[0];

            //Pull out the mac address by looking for the delimiter
            $mac = substr($value, (strpos($value, ":") - 2), 17);

            //Get rid of the delimiter
            $mac_strip = strtoupper(str_replace(":", "", $mac));

            //arp -n will return a MAC address of 000000000000 if no hardware was found, so we need to ignore it
            if ($mac_strip != "000000000000") {
                //only use the first 6 characters for the oui: http://en.wikipedia.org/wiki/Organizationally_Unique_Identifier
                $oui = substr($mac_strip, 0, 6);

                //Find the matching brand model to the oui
                $oui_sql = "SELECT endpointman_brand_list.name, endpointman_brand_list.id FROM endpointman_oui_list, endpointman_brand_list WHERE oui LIKE '%" . $oui . "%' AND endpointman_brand_list.id = endpointman_oui_list.brand AND endpointman_brand_list.installed = 1 LIMIT 1";

                $brand = $this->eda->sql($oui_sql, 'getRow', \PDO::FETCH_ASSOC);

                $res = $this->eda->sql($oui_sql);
                $brand_count = count(array($res));

                if (!$brand_count) {
                    //oui doesn't have a matching mysql reference, probably a PC/router/wap/printer of some sort.
                    $brand['name'] = FALSE;
                    $brand['id'] = NULL;
                }

                //Find out if endpoint has already been configured for this mac address
                $epm_sql = "SELECT * FROM endpointman_mac_list WHERE mac LIKE  '%" . $mac_strip . "%'";
                $epm_row = $this->eda->sql($epm_sql, 'getRow', \PDO::FETCH_ASSOC);

                $res = $this->eda->sql($epm_sql);

                $epm = count(array($res)) ? TRUE : FALSE;

                //Add into a final array
                $final[$z] = array("ip" => $ip, "mac" => $mac, "mac_strip" => $mac_strip, "oui" => $oui, "brand" => $brand['name'], "brand_id" => $brand['id'], "endpoint_managed" => $epm);
                $z++;
            }
        }
        return !is_array($final) ? FALSE : $final;
    }



   




    function display_templates($product_id, $temp_select = NULL) {
        $i = 0;
        $sql = "SELECT id FROM  endpointman_product_list WHERE endpointman_product_list.id ='" . $product_id . "'";
        $id = sql($sql, 'getOne');

        $sql = "SELECT * FROM  endpointman_template_list WHERE  product_id = '" . $id . "'";
        $data = sql($sql, 'getAll', \PDO::FETCH_ASSOC);

        foreach ($data as $row) {
            $temp[$i]['value'] = $row['id'];
            $temp[$i]['text'] = $row['name'];
            if ($row['id'] == $temp_select) {
                $temp[$i]['selected'] = "selected";
            }
            $i++;
        }
        $temp[$i]['value'] = 0;
        if ($temp_select == 0) {
            $temp[$i]['text'] = "Custom...";
            $temp[$i]['selected'] = "selected";
        } else {
            $temp[$i]['text'] = "Custom...";
        }

        return($temp);
    }

    function validate_json($json) {
        return(TRUE);
    }

















    /**

    function update_device($macid, $model, $template, $luid=NULL, $name=NULL, $line=NULL, $update_lines=TRUE) {
        $sql = "UPDATE endpointman_mac_list SET model = " . $model . ", template_id =  " . $template . " WHERE id = " . $macid;
        sql($sql);

        if ($update_lines) {
            if (isset($luid)) {
                $this->update_line($luid, NULL, $name, $line);
                return(TRUE);
            } else {
                $this->update_line(NULL, $macid);
                return(TRUE);
            }
        }
    }

    function update_line($luid=NULL, $macid=NULL, $name=NULL, $line=NULL) {
        if (isset($luid)) {
            $sql = "SELECT * FROM endpointman_line_list WHERE luid = " . $luid;
            $row = $this->eda->sql($sql, 'getRow', \PDO::FETCH_ASSOC);

            if (!isset($name)) {
                $sql = "SELECT description FROM devices WHERE id = " . $row['ext'];
                $name = sql($sql, 'getOne');
            }

            if (!isset($line)) {
                $line = $row['line'];
            }
            $sql = "UPDATE endpointman_line_list SET line = '" . $line . "', ext = '" . $row['ext'] . "', description = '" . $this->eda->escapeSimple($name) . "' WHERE luid =  " . $row['luid'];
            sql($sql);
            return(TRUE);
        } else {
            $sql = "SELECT * FROM endpointman_line_list WHERE mac_id = " . $macid;
            $lines_info = sql($sql, 'getAll', \PDO::FETCH_ASSOC);
            foreach ($lines_info as $row) {
                $sql = "SELECT description FROM devices WHERE id = " . $row['ext'];
                $name = sql($sql, 'getOne');

                $sql = "UPDATE endpointman_line_list SET line = '" . $row['line'] . "', ext = '" . $row['ext'] . "', description = '" . $this->eda->escapeSimple($name) . "' WHERE luid =  " . $row['luid'];
                sql($sql);
            }
            return(TRUE);
        }
    }


     * This will either a. delete said line or b. delete said device from line
     * @param <type> $line
     * @return <type>

    function delete_line($lineid, $allow_device_remove=FALSE) {
        $sql = 'SELECT mac_id FROM endpointman_line_list WHERE luid = ' . $lineid;
        $mac_id = sql($sql, 'getOne');
        $row = $this->get_phone_info($mac_id);

        $sql = 'SELECT COUNT(*) FROM endpointman_line_list WHERE mac_id = ' . $mac_id;
        $num_lines = sql($sql, 'getOne');
        if ($num_lines > 1) {
            $sql = "DELETE FROM endpointman_line_list WHERE luid=" . $lineid;
            sql($sql);
            $this->message['delete_line'] = "Deleted!";
            return(TRUE);
        } else {
            if ($allow_device_remove) {
                $sql = "DELETE FROM endpointman_line_list WHERE luid=" . $lineid;
                sql($sql);

                $sql = "DELETE FROM endpointman_mac_list WHERE id=" . $mac_id;
                sql($sql);
                $this->message['delete_line'] = "Deleted!";
                return(TRUE);
            } else {
                $this->error['delete_line'] = _("You can't remove the only line left") . "!";
                return(FALSE);
            }
        }
    }

    function delete_device($mac_id) {
        $sql = "DELETE FROM endpointman_mac_list WHERE id=" . $mac_id;
        sql($sql);

        $sql = "DELETE FROM endpointman_line_list WHERE mac_id=" . $mac_id;
        sql($sql);
        $this->message['delete_device'] = "Deleted!";
        return(TRUE);
    }
*/
 /*   function get_message($function_name) {
        if (isset($this->message[$function_name])) {
            return($this->message[$function_name]);
        } else {
            return("Unknown Message");
        }
    }








    /**
     * Save template from the template view pain
     * @param int $id Either the MAC ID or Template ID
     * @param int $custom Either 0 or 1, it determines if $id is MAC ID or Template ID
     * @param array $variables The variables sent from the form. usually everything in $_REQUEST[]
     * @return string Location of area to return to in Endpoint Manager
     */
    function save_template($id, $custom, $variables) {
        //Custom Means specific to that MAC
        //This function is reversed. Not sure why

        if ($custom != "0") {
            $sql = "SELECT endpointman_model_list.max_lines, endpointman_product_list.config_files, endpointman_mac_list.*, endpointman_product_list.id as product_id, endpointman_product_list.long_name, endpointman_model_list.template_data, endpointman_product_list.cfg_dir, endpointman_brand_list.directory FROM endpointman_brand_list, endpointman_mac_list, endpointman_model_list, endpointman_product_list WHERE endpointman_mac_list.id=" . $id . " AND endpointman_mac_list.model = endpointman_model_list.id AND endpointman_model_list.brand = endpointman_brand_list.id AND endpointman_model_list.product_id = endpointman_product_list.id";
        } else {
            $sql = "SELECT endpointman_model_list.max_lines, endpointman_brand_list.directory, endpointman_product_list.cfg_dir, endpointman_product_list.config_files, endpointman_product_list.long_name, endpointman_model_list.template_data, endpointman_model_list.id as model_id, endpointman_template_list.* FROM endpointman_brand_list, endpointman_product_list, endpointman_model_list, endpointman_template_list WHERE endpointman_product_list.id = endpointman_template_list.product_id AND endpointman_brand_list.id = endpointman_product_list.brand AND endpointman_template_list.model_id = endpointman_model_list.id AND endpointman_template_list.id = " . $id;
        }

        //Load template data
        $row = sql($sql, 'getRow', \PDO::FETCH_ASSOC);

        $cfg_data = unserialize($row['template_data']);
        $count = count($cfg_data);

        $custom_cfg_data_ari = array();

        foreach ($cfg_data['data'] as $cats) {
            foreach ($cats as $items) {
                foreach ($items as $key_name => $config_options) {
                    if (preg_match('/(.*)\|(.*)/i', $key_name, $matches)) {
                        $type = $matches[1];
                        $key = $matches[2];
                    } else {
                        die('invalid');
                    }
                    switch ($type) {
                        case "loop":
                            $stuffing = explode("_", $key);
                            $key2 = $stuffing[0];
                            foreach ($config_options as $item_key => $item_data) {
                                $lc = isset($item_data['loop_count']) ? $item_data['loop_count'] : '';
                                $key = 'loop|' . $key2 . '_' . $item_key . '_' . $lc;
                                if ((isset($item_data['loop_count'])) AND (isset($variables[$key]))) {
                                    $custom_cfg_data[$key] = $variables[$key];
                                    $ari_key = "ari_" . $key;
                                    if (isset($variables[$ari_key])) {
                                        if ($variables[$ari_key] == "on") {
                                            $custom_cfg_data_ari[$key] = 1;
                                        }
                                    }
                                }
                            }
                            break;
                        case "lineloop":
                            foreach ($config_options as $item_key => $item_data) {
                                $lc = isset($item_data['line_count']) ? $item_data['line_count'] : '';
                                $key = 'line|' . $lc . '|' . $item_key;
                                if ((isset($item_data['line_count'])) AND (isset($variables[$key]))) {
                                    $custom_cfg_data[$key] = $variables[$key];
                                    $ari_key = "ari_" . $key;
                                    if (isset($variables[$ari_key])) {
                                        if ($variables[$ari_key] == "on") {
                                            $custom_cfg_data_ari[$key] = 1;
                                        }
                                    }
                                }
                            }
                            break;
                        case "option":
                            if (isset($variables[$key])) {
                                $custom_cfg_data[$key] = $variables[$key];
                                $ari_key = "ari_" . $key;
                                if (isset($variables[$ari_key])) {
                                    if ($variables[$ari_key] == "on") {
                                        $custom_cfg_data_ari[$key] = 1;
                                    }
                                }
                            }
                            break;
                        default:
                            break;
                    }
                }
            }
        }

        $config_files = explode(",", $row['config_files']);

        $i = 0;
        while ($i < count($config_files)) {
            $config_files[$i] = str_replace(".", "_", $config_files[$i]);

            if (isset($variables['config_files'][$i])) {

                $variables[$config_files[$i]] = explode("_", $variables['config_files'][$i], 2);

                $variables[$config_files[$i]] = $variables[$config_files[$i]][0];
                if ($variables[$config_files[$i]] > 0) {
                    $config_files_selected[$config_files[$i]] = $variables[$config_files[$i]];


                }
            }
            $i++;
        }
        if (!isset($config_files_selected)) {
            $config_files_selected = "";
        } else {
            $config_files_selected = serialize($config_files_selected);
        }
        $custom_cfg_data_temp['data'] = $custom_cfg_data;
        $custom_cfg_data_temp['ari'] = $custom_cfg_data_ari;

        $save = serialize($custom_cfg_data_temp);
        if ($custom == "0") {
            $sql = 'UPDATE endpointman_template_list SET config_files_override = \'' . addslashes($config_files_selected) . '\', global_custom_cfg_data = \'' . addslashes($save) . '\' WHERE id =' . $id;
            $location = "template_manager";
			//print_r($sql);
        } else {
            $sql = 'UPDATE endpointman_mac_list SET config_files_override = \'' . addslashes($config_files_selected) . '\', template_id = 0, global_custom_cfg_data = \'' . addslashes($save) . '\' WHERE id =' . $id;
            $location = "devices_manager";
        }
        sql($sql);

        $phone_info = array();
/*
        if ($custom != 0) {
            $phone_info = $this->get_phone_info($id);
            if (isset($variables['epm_reboot'])) {
                $this->prepare_configs($phone_info);
            } else {
                $this->prepare_configs($phone_info, FALSE);
            }
        } else {
            $sql = 'SELECT id FROM endpointman_mac_list WHERE template_id = ' . $id;
            $phones = sql($sql, 'getAll', \PDO::FETCH_ASSOC);
            foreach ($phones as $data) {
                $phone_info = $this->get_phone_info($data['id']);
                if (isset($variables['epm_reboot'])) {
                    $this->prepare_configs($phone_info);
                } else {
                    $this->prepare_configs($phone_info, FALSE);
                }
            }
        }
*/

        if (isset($variables['silent_mode'])) {
            echo '<script language="javascript" type="text/javascript">window.close();</script>';
        } else {
            return($location);
        }
    }



    function display_configs() {

    }












    //BORRAR!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
	//OBSOLETO, ANTIGUAMENTE VENTANAS EMERGENTES, AHORA SON DIALOGOS JQUERY.
 /*   function prepare_message_box() {
        $error_message = NULL;
        foreach ($this->error as $key => $error) {
            $error_message .= $error;
            if ($this->eda->global_cfg['debug']) {
                $error_message .= " Function: [" . $key . "]";
            }
            $error_message .= "<br />";
        }
        $message = NULL;
        foreach ($this->message as $key => $error) {
            if (is_array($error)) {
                foreach ($error as $sub_error) {
                    $message .= $sub_error;
                    if ($this->eda->global_cfg['debug']) {
                        $message .= " Function: [" . $key . "]";
                    }
                    $message .= "<br />";
                }
            } else {
                $message .= $error;
                if ($this->eda->global_cfg['debug']) {
                    $message .= " Function: [" . $key . "]";
                }
                $message .= "<br />";
            }
        }

        if (isset($message)) {
            $this->display_message_box($message, 0);
        }

        if (isset($error_message)) {
            $this->display_message_box($error_message, 1);
        }
    }
*/




}
