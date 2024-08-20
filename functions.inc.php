<?PHP

/**
 * Endpoint Manager FreePBX Hooks File
 *
 * @author Andrew Nagy
 * @license MPL / GPLv2 / LGPL
 * @package Endpoint Manager
 */
function endpointman_get_config($engine) {
    global $db;
    global $ext;
    global $core_conf;

    switch ($engine) {
        case "asterisk":
            if (isset($core_conf) && is_a($core_conf, "core_conf") && (method_exists($core_conf, 'addSipNotify'))) {
                $core_conf->addSipNotify('polycom-check-cfg', array('Event' => 'check-sync', 'Content-Length' => '0'));
                $core_conf->addSipNotify('polycom-reboot', array('Event' => 'check-sync', 'Content-Length' => '0'));
                $core_conf->addSipNotify('sipura-check-cfg', array('Event' => 'resync', 'Content-Length' => '0'));
                $core_conf->addSipNotify('grandstream-check-cfg', array('Event' => 'sys-control'));
                $core_conf->addSipNotify('cisco-check-cfg', array('Event' => 'check-sync', 'Content-Length' => '0'));
                $core_conf->addSipNotify('reboot-snom', array('Event' => 'reboot', 'Content-Length' => '0'));
                $core_conf->addSipNotify('aastra-check-cfg', array('Event' => 'check-sync', 'Content-Length' => '0'));
                $core_conf->addSipNotify('linksys-cold-restart', array('Event' => 'reboot_now', 'Content-Length' => '0'));
                $core_conf->addSipNotify('linksys-warm-restart', array('Event' => 'restart_now', 'Content-Length' => '0'));
                $core_conf->addSipNotify('spa-reboot', array('Event' => 'reboot', 'Content-Length' => '0'));
                $core_conf->addSipNotify('reboot-yealink', array('Event' => 'check-sync\;reboot=true', 'Content-Length' => '0'));
			    $core_conf->addSipNotify('reboot-gigaset', array('Event' => 'check-sync;reboot=true', 'Content-Length' => '0'));
				$core_conf->addSipNotify('panasonic-check-cfg', array('Event' => 'check-sync', 'Content-Length' => '0'));
				$core_conf->addSipNotify('snom-check-cfg', array('Event' => 'check-sync', 'Content-Length' => '0'));
            }
            break;
    }
}

function endpointman_configpageinit($pagename)
{
    global $currentcomponent, $amp_conf, $db;

    $epm = \FreePBX::Endpointman();

    $display    = $_REQUEST['display'] ?? null;
	$type       = '';
	$tech       = '';
	$extdisplay = '';

    switch($display)
    {
        case "extensions":
            $extdisplay = $_REQUEST['extension'] ?? $_REQUEST['extdisplay'] ?? null;
            break;

        case "devices":
            $extdisplay = $_REQUEST['deviceid'] ?? $_REQUEST['extdisplay'] ?? null;
            break;

        default:
            // we only care about extensions or devices, otherwise return
		    return true;
    }

    if (isset($extdisplay) && !empty($extdisplay))
    {
        // $sql = "SELECT tech FROM devices WHERE id = " . $extdisplay;
        // $tech = $db->getOne($sql);

        $sql = "SELECT tech FROM devices WHERE id = :id";
		$stmt = $epm->db->prepare($sql);
		$stmt->execute([':id' => $extdisplay]);
		if ($stmt->rowCount() === 0)
        {
			$tech = "sip";
			$type = 'new';
		}
        else
        {
            $tech = $stmt->fetch(PDO::FETCH_ASSOC);
            if(in_array($tech, ['sip', 'pjsip']))
            {
                $type = 'edit';
            }
        }
    }
    elseif(isset($_REQUEST['tech_hardware']) OR isset($_REQUEST['tech']))
    {
		$tech = $_REQUEST['tech_hardware'] ?? $_REQUEST['tech'];
        if (in_array($tech, ['sip_generic', 'sip', 'pjsip']))
        {
            $tech = "sip";
            $type = 'new';
        }
    }

    if ((($tech == 'sip') OR ($tech == 'pjsip')) AND (!empty($type)))
    {
        global $endpoint;

	    $action = $_REQUEST['action'] ?? null;
	    $delete = $_REQUEST['epm_delete'] ?? null;

        $doc_root = $amp_conf['AMPWEBROOT'] . "/admin/modules/endpointman/";
        if (file_exists($doc_root . "includes/functions.inc"))
        {
            require_once($doc_root . "includes/functions.inc");

            $endpoint = new endpointmanager();
            ini_set('display_errors', 0);

            if ($action == "del") {
                $sql = "SELECT mac_id,luid FROM endpointman_line_list WHERE ext = " . $extdisplay;
                $macid = $endpoint->eda->sql($sql, 'getRow', \PDO::FETCH_ASSOC);
                if ($macid) {
                    $endpoint->delete_line($macid['luid'], TRUE);
                }
            }

            if (($action == "edit") OR ($action == "add")) {
                if (isset($delete)) {
                    $sql = "SELECT mac_id,luid FROM endpointman_line_list WHERE ext = " . $extdisplay;
                    $macid = $endpoint->eda->sql($sql, 'getRow', \PDO::FETCH_ASSOC);
                    if ($macid) {
                        $endpoint->delete_line($macid['luid'], TRUE);
                    }
                }

                $mac = isset($_REQUEST['epm_mac']) ? $_REQUEST['epm_mac'] : null;

                if (!empty($mac)) {
                    //Mac is set
                    $brand = isset($_REQUEST['epm_brand']) ? $_REQUEST['epm_brand'] : null;
                    $model = isset($_REQUEST['epm_model']) ? $_REQUEST['epm_model'] : null;
                    $line = isset($_REQUEST['epm_line']) ? $_REQUEST['epm_line'] : null;
                    $temp = isset($_REQUEST['epm_temps']) ? $_REQUEST['epm_temps'] : null;
                    if (isset($_REQUEST['name'])) {
                        $name = isset($_REQUEST['name']) ? $_REQUEST['name'] : null;
                    } else {
                        $name = isset($_REQUEST['description']) ? $_REQUEST['description'] : null;
                    }
                    if (isset($_REQUEST['deviceid'])) {
                        if ($_REQUEST['devicetype'] == "fixed") {
                            //SQL to get the Description of the  extension from the extension table
                            $sql = "SELECT name FROM users WHERE extension = '" . $_REQUEST['deviceuser'] . "'";
                            $name_o = $endpoint->eda->sql($sql, 'getOne');
							if($name_o) {
								$name = $name_o;
							}
                        }
                    }

                    $reboot = isset($_REQUEST['epm_reboot']) ? $_REQUEST['epm_reboot'] : null;

                    if ($endpoint->mac_check_clean($mac)) {
                        $sql = "SELECT id FROM endpointman_mac_list WHERE mac = '" . $endpoint->mac_check_clean($mac) . "'";
                        $macid = $endpoint->eda->sql($sql, 'getOne');
                        if ($macid) {
                            //In Database already

                            $sql = 'SELECT * FROM endpointman_line_list WHERE ext = ' . $extdisplay . ' AND mac_id = ' . $macid;
                            $lines_list = & $endpoint->eda->sql($sql, 'getRow', \PDO::FETCH_ASSOC);

                            if (($lines_list) AND (isset($model)) AND (isset($line)) AND (!isset($delete)) AND (isset($temp))) {
                                //Modifying line already in the database
                                $endpoint->update_device($macid, $model, $temp, $lines_list['luid'], $name, $lines_list['line']);

                                $row = $endpoint->get_phone_info($macid);
                                if (isset($reboot)) {
                                    $endpoint->prepare_configs($row);
                                } else {
                                    $endpoint->prepare_configs($row, FALSE);
                                }
                            } elseif ((isset($model)) AND (!isset($delete)) AND (isset($line)) AND (isset($temp))) {
                                //Add line to the database

                                if (empty($line)) {
                                    $endpoint->add_line($macid, NULL, $extdisplay, $name);
                                } else {
                                    $endpoint->add_line($macid, $line, $extdisplay, $name);
                                }

                                $endpoint->update_device($macid, $model, $temp, NULL, NULL, NULL, FALSE);

                                $row = $endpoint->get_phone_info($macid);
                                if (isset($reboot)) {
                                    $endpoint->prepare_configs($row);
                                } else {
                                    $endpoint->prepare_configs($row, FALSE);
                                }
                            }
                        } elseif (!isset($delete)) {
                            //Add Extension/Phone to database
                            $mac_id = $endpoint->add_device($mac, $model, $extdisplay, $temp, NULL, $name);

                            if ($mac_id) {
                                debug('Write files?');
                                $row = $endpoint->get_phone_info($mac_id);
                                $endpoint->prepare_configs($row);
                            }
                        }
                    }
                }
            }
            endpointman_applyhooks();
        } else {
            //System can't find the include file.
        }
    }
}

function endpointman_applyhooks() {
    global $currentcomponent;

    // Add the 'process' function - this gets called when the page is loaded, to hook into
    // displaying stuff on the page.
    $currentcomponent->addguifunc('endpointman_configpageload');
}

// This is called before the page is actually displayed, so we can use addguielem().
function endpointman_configpageload() {
    global $currentcomponent, $endpoint, $db, $astman;
    $display    = $_REQUEST['display'] ?? null;
    $action     = $_REQUEST['action'] ?? null;
    $extdisplay = $_REQUEST['extdisplay'] ?? null;

    if (isset($extdisplay) && !empty($extdisplay))
    {
        $sql = "SELECT tech FROM devices WHERE id = " . $extdisplay;
        $tech = $endpoint->eda->sql($sql, 'getOne');
    }
    else
    {
        $tech = $_REQUEST['tech_hardware'] ?? null;
    }

    $extension_address = $astman->database_get("SIP","Registry"."/$extdisplay");
    $extension_address = explode(":",$extension_address);
    // echo $extension_address['0'];

    if (isset($tech) && (($tech == 'sip') OR ($tech == 'pjsip') OR ($tech == 'sip_generic'))) {
        // Don't display this stuff it it's on a 'This xtn has been deleted' page.
        if ($action != 'del')
        {
            $js = "
                $.ajaxSetup({ cache: false });

                $.getJSON(\"config.php?type=tool&quietmode=1&handler=file&module=endpointman&file=ajax_select.html.php&atype=model\",{id: value}, function(j){
                        var options = '';
                        for (var i = 0; i < j.length; i++) {
                                options += '<option value=\"' + j[i].optionValue + '\">' + j[i].optionDisplay + '</option>';
                        }
                        $('#epm_model').html(options);
                        $('#epm_model option:first').attr('selected', 'selected');
                        $('#epm_temps').html('<option></option>');
                        $('#epm_temps option:first').attr('selected', 'selected');
                        $('#epm_line').html('<option></option>');
                        $('#epm_line option:first').attr('selected', 'selected');
                })
            ";
            $currentcomponent->addjsfunc('brand_change(value)', $js);

            $section = _('End Point Manager');

            $sql = "SELECT mac_id,luid,line FROM endpointman_line_list WHERE ext = '" . $extdisplay . "' ";
            $line_info = $endpoint->eda->sql($sql, 'getRow', \PDO::FETCH_ASSOC);
            if ($line_info)
            {
                $js = "
                    $.ajaxSetup({ cache: false });
                    $.getJSON('config.php?type=tool&quietmode=1&handler=file&module=endpointman&file=ajax_select.html.php&atype=template2',{id: value}, function(j){
                            var options = '';
                            for (var i = 0; i < j.length; i++) {
                                    options += '<option value=\"' + j[i].optionValue + '\">' + j[i].optionDisplay + '</option>';
                            }
                            $('#epm_temps').html(options);
                            $('#epm_temps option:first').attr('selected', 'selected');
                    }),
                    $.ajaxSetup({ cache: false });
                    $.getJSON('config.php?type=tool&quietmode=1&handler=file&module=endpointman&file=ajax_select.html.php&macid='+ macid +'&atype=lines',{id: value}, function(j){
                            var options = '';
                            for (var i = 0; i < j.length; i++) {
                                    options += '<option value=\"' + j[i].optionValue + '\">' + j[i].optionDisplay + '</option>';
                            }
                            $('#epm_line').html(options);
                            $('#epm_line option:first').attr('selected', 'selected');
                    })
                ";
                $currentcomponent->addjsfunc('model_change(value,macid)', $js);

                $info = $endpoint->get_phone_info($line_info['mac_id']);

                $brand_list = $endpoint->brands_available($info['brand_id'], true);
                if (!empty($info['brand_id'])) {
                    $model_list = $endpoint->models_available(NULL, $info['brand_id']);
                    $line_list = $endpoint->linesAvailable($line_info['luid']);
                    $template_list = $endpoint->display_templates($info['product_id']);
                } else {
                    $model_list = array();
                    $line_list = array();
                    $template_list = array();
                }

                $checked = false;

                $currentcomponent->addguielem($section, new gui_checkbox('epm_delete', $checked, 'Delete', 'Delete this Extension from Endpoint Manager'), 9);

                // phone web interface link
	            class gui_link_nw_tab extends guitext {
                    function __construct($elemname, $text, $url, $userlang = true) {
                        $parent_class = get_parent_class($this);
                        $this->html_text = "<a href=\"$url\" target=\"_blank\" id =\"$this->elemname\">$text</a>";
                    }
                }        
				$currentcomponent->addguielem($section, new gui_link_nw_tab('epm_account_phone', 'Go to phone web interface', "http://$extension_address[0]"));
                //

				$currentcomponent->addguielem($section, new gui_textbox('epm_mac', $info['mac'], 'MAC Address', 'The MAC Address of the Phone Assigned to this Extension/Device. <br />(Leave Blank to Remove from Endpoint Manager)', '', 'Please enter a valid MAC Address', true, 17, false), 9);
                $currentcomponent->addguielem($section, new gui_selectbox('epm_brand', $brand_list, $info['brand_id'], 'Brand', 'The Brand of this Phone.', false, 'frm_' . $display . '_brand_change(this.options[this.selectedIndex].value)', false), 9);
                $currentcomponent->addguielem($section, new gui_selectbox('epm_model', $model_list, $info['model_id'], 'Model', 'The Model of this Phone.', false, 'frm_' . $display . '_model_change(this.options[this.selectedIndex].value,\'' . $line_info['luid'] . '\')', false), 9);
                $currentcomponent->addguielem($section, new gui_selectbox('epm_line', $line_list, $line_info['line'], 'Line', 'The Line of this Extension/Device.', false, '', false), 9);
                $currentcomponent->addguielem($section, new gui_selectbox('epm_temps', $template_list, $info['template_id'], 'Template', 'The Template of this Phone.', false, '', false), 9);
                $currentcomponent->addguielem($section, new gui_checkbox('epm_reboot', $checked, 'Reboot', 'Reboot this Phone on Submit'), 9);
            } else {

                $js = "
                    $.ajaxSetup({ cache: false });
                    $.getJSON('config.php?type=tool&quietmode=1&handler=file&module=endpointman&file=ajax_select.html.php&atype=template2',{id: value}, function(j){
                            var options = '';
                            for (var i = 0; i < j.length; i++) {
                                    options += '<option value=\"' + j[i].optionValue + '\">' + j[i].optionDisplay + '</option>';
                            }
                            $('#epm_temps').html(options);
                            $('#epm_temps option:first').attr('selected', 'selected');
                    }),
                    $.ajaxSetup({ cache: false });
                    $.getJSON('config.php?type=tool&quietmode=1&handler=file&module=endpointman&file=ajax_select.html.php&mac='+ mac +'&atype=lines',{id: value}, function(j){
                            var options = '';
                            for (var i = 0; i < j.length; i++) {
                                    options += '<option value=\"' + j[i].optionValue + '\">' + j[i].optionDisplay + '</option>';
                            }
                            $('#epm_line').html(options);
                            $('#epm_line option:first').attr('selected', 'selected');
                    })
                ";
                $currentcomponent->addjsfunc('model_change(value,mac)', $js);

                $brand_list = $endpoint->brands_available(NULL, true);
                $model_list = array();
                $line_list = array();
                $template_list = array();

                $currentcomponent->addguielem($section, new gui_textbox('epm_mac', $info['mac'], _('MAC Address'), _('The MAC Address of the Phone Assigned to this Extension/Device. <br />(Leave Blank to Remove from Endpoint Manager)', '', 'Please enter a valid MAC Address'), true, 17, false), 9);
                $currentcomponent->addguielem($section, new gui_selectbox('epm_brand', $brand_list, $info['brand_id'], _('Brand'), _('The Brand of this Phone.'), false, 'frm_' . $display . '_brand_change(this.options[this.selectedIndex].value)', false), 9);
                $currentcomponent->addguielem($section, new gui_selectbox('epm_model', $model_list, $info['model_id'], _('Model'), _('The Model of this Phone.'), false, 'frm_' . $display . '_model_change(this.options[this.selectedIndex].value,document.getElementById(\'epm_mac\').value)', false), 9);
                $currentcomponent->addguielem($section, new gui_selectbox('epm_line', $line_list, $line_info['line'], _('Line'), _('The Line of this Extension/Device.'), false, '', false), 9);
                $currentcomponent->addguielem($section, new gui_selectbox('epm_temps', $template_list, $info['template_id'], _('Template'), _('The Template of this Phone.'), false, '', false), 9);
                $currentcomponent->addguielem($section, new guitext('epm_note', _('Note: This might reboot the phone if it\'s already registered to Asterisk')));
		
            }
        }
    }
}

function endpointman_hookProcess_core($viewing_itemid, $request) {

}

function endpointman_module_install_check_callback($mods = array()) {
    global $active_modules;

    $ret = array();
    $current_mod = 'endpointman';
    $conflicting_mods = array('restart');

	foreach($mods as $k => $v) {
		if (in_array($k, $conflicting_mods) && !in_array($active_modules[$current_mod]['status'], array(MODULE_STATUS_NOTINSTALLED,MODULE_STATUS_BROKEN)))
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