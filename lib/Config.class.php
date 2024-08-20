<?php
/**
 * Endpoint Manager Config
 *
 * @author Javier Pastor
 * @license MPL / GPLv2 / LGPL
 * @package Provisioner
 */

namespace FreePBX\modules\Endpointman;

class Config {
	private $module_conf;
	
	public function __construct() 
	{
		$this->getConfigModuleSQL();
	}
	
	public function getConfigModuleSQL($clear = true)
	{
		if ($clear) { $this->module_conf = array(); }
		$sql = "SELECT var_name, value FROM endpointman_global_vars";
		foreach (sql($sql, 'getAll', \PDO::FETCH_ASSOC) as $row) {
			$this->module_conf[$row['var_name']] = $row['value'];
		}
	}
	
	
	public function getall() {
		return $this->module_conf;
	}
	public function isExiste($var) 	{ 
		return isset($this->module_conf[$var]); 
	}
	public function get($var, $defvar="") {
		$varreturn = "";
		if ($this->isExiste($var)) { 
			$varreturn = $this->module_conf[$var]; 
		}
		elseif ($defvar != "") {
			$this->module_conf[$var] = $defvar;
		}
		if ($varreturn == "") {
			$varreturn = $defvar;
		}
		return $varreturn;
	}
	public function set($var, $val) {
		$this->module_conf[$var] = $val;
	}
	public function del($var) { 
		if ($this->isExiste($var)) { unset($this->module_conf[$var]); } 
	}
}

?>