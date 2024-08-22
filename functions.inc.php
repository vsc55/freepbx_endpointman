<?PHP

/**
 * Endpoint Manager FreePBX Hooks File
 *
 * @author Andrew Nagy
 * @license MPL / GPLv2 / LGPL
 * @package Endpoint Manager
 */

// Migrate to doDialplanHook
// function endpointman_get_config($engine) {}

// Migrate to doConfigPageInit
// function endpointman_configpageinit($pagename) { }

// function endpointman_hookProcess_core($viewing_itemid, $request) { }

function endpointman_module_install_check_callback($mods = array()) {
    global $active_modules;

    $ret = array();
    $current_mod = 'endpointman';
    $conflicting_mods = array('restart');

	foreach($mods as $k => $v) {
		if (in_array($k, $conflicting_mods) && !in_array($active_modules[$current_mod]['status'], array(MODULE_STATUS_NOTINSTALLED, MODULE_STATUS_BROKEN)))
        {
			$ret[] = $v['name'];
		}
	}

	if (!empty($ret))
    {
		$modules = implode(',', $ret);
		return sprintf(_('Failed to install %s due to the following conflicting module(s): %s'), $modules, $active_modules[$current_mod]['displayname']);
	}

	return TRUE;
}