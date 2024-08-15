<?php
/**
 * Endpoint Manager Object Module - Sec Devices
 *
 * @author Javier Pastor
 * @license MPL / GPLv2 / LGPL
 * @package Provisioner
 */

namespace FreePBX\modules;

#[\AllowDynamicProperties]
class Endpointman_Devices
{
	// public function __construct($freepbx = null, $cfgmod = null) 
	public function __construct($epm) 
	{
		$epm			 = $epm;
		$this->freepbx   = $epm->freepbx;
		$this->db 	     = $epm->freepbx->Database;
		$this->config    = $epm->freepbx->Config;
		$this->configmod = $epm->configmod;			
	}

	public function myShowPage(&$pagedata) {
		if(empty($pagedata))
		{
			$pagedata['main'] = array(
					"name" => _("Devices"),
					"page" => 'views/epm_devices_main.page.php'
			);
		}
	}

	public function ajaxRequest($req, &$setting) {
		/*
		$arrVal = array("");
		if (in_array($req, $arrVal)) {
			$setting['authenticate'] = true;
			$setting['allowremote'] = false;
			return true;
		}
		*/
		return false;
	}
	
    public function ajaxHandler($module_tab = "", $command = "") 
	{
		$retarr = "";
		if ($module_tab == "manager")
		{
			switch ($command)
			{
				default:
					$retarr = array("status" => false, "message" => _("Command not found!") . " [" .$command. "]");
					break;
			}
		}
		else {
			$retarr = array("status" => false, "message" => _("Tab not found!") . " [" .$module_tab. "]");
		}
		return $retarr;
	}
	
	public function doConfigPageInit($module_tab = "", $command = "") {
		
	}
	
	public function getRightNav($request) {
		return "";
	}
	
	public function getActionBar($request) {
		return "";
	}
	
}