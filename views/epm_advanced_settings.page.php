<?php
	if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }
	
	FreePBX::Endpointman()->configmod->getConfigModuleSQL(false);
	
	
	if ((FreePBX::Endpointman()->configmod->get("server_type") == 'file') AND (FreePBX::Endpointman()->epm_advanced->epm_advanced_config_loc_is_writable())) {
		FreePBX::Endpointman()->tftp_check();
	}
	
	if (FreePBX::Endpointman()->configmod->get("use_repo") == "1") {
		if (FreePBX::Endpointman()->has_git()) {
			
			if (!file_exists(FreePBX::Endpointman()->PHONE_MODULES_PATH . '/.git')) {
				$o = getcwd();
				chdir(dirname(FreePBX::Endpointman()->PHONE_MODULES_PATH));
				FreePBX::Endpointman()->rmrf(FreePBX::Endpointman()->PHONE_MODULES_PATH);
				$path = FreePBX::Endpointman()->has_git();
				exec($path . ' clone https://github.com/provisioner/Provisioner.git _ep_phone_modules', $output);
				chdir($o);
			}
		} else {
			echo  _("Git not installed!");
		}
	} else {
		if (file_exists(FreePBX::Endpointman()->PHONE_MODULES_PATH . '/.git')) {
			FreePBX::Endpointman()->rmrf(FreePBX::Endpointman()->PHONE_MODULES_PATH);
			$sql = "SELECT * FROM  `endpointman_brand_list` WHERE  `installed` =1";
			$result = & sql($sql, 'getAll', DB_FETCHMODE_ASSOC);
			foreach ($result as $row) {
				FreePBX::Endpointman()->remove_brand($row['id'], FALSE, TRUE);
			}
		}
	}
?>

<div class="section-title" data-for="setting_provision">
	<h3><i class="fa fa-minus"></i><?php echo _("Setting Provision") ?></h3>
</div>
<div class="section" data-id="setting_provision">

	<!--IP address of phone server-->
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-3">
							<label class="control-label" for="srvip"><?php echo _("IP address of phone server")?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="srvip"></i>
						</div>
						<div class="col-md-9">
							<div class="input-group">
      							<input type="text" class="form-control" placeholder="Server PBX..." id="srvip" name="srvip" value="<?php echo FreePBX::Endpointman()->configmod->get("srvip"); ?>">
      							<span class="input-group-btn">
        							<button class="btn btn-default" type="button" id='autodetect' onclick="epm_advanced_tab_setting_input_value_change_bt('#srvip', sValue = '<?php echo $_SERVER["SERVER_ADDR"]; ?>', bSaveChange = true);"><i class='fa fa-search'></i> <?php echo _("Use me!")?></button>
      							</span>
    						</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span class="help-block fpbx-help-block" id="srvip-help"><?php echo _("IP Address of your PBX."); ?></span>
			</div>
		</div>
	</div>
	<!--END IP address of phone server-->
	<!--Internal IP address of phone server-->
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-3">
							<label class="control-label" for="intsrvip"><?php echo _("Internal IP address of phone server")?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="intsrvip"></i>
						</div>
						<div class="col-md-9">
							<div class="input-group">
      							<input type="text" class="form-control" placeholder="Server PBX..." id="intsrvip" name="intsrvip" value="<?php echo FreePBX::Endpointman()->configmod->get("intsrvip"); ?>">
      							<span class="input-group-btn">
        							<button class="btn btn-default" type="button" id='autodetect' onclick="epm_advanced_tab_setting_input_value_change_bt('#intsrvip', sValue = '<?php echo $_SERVER["SERVER_ADDR"]; ?>', bSaveChange = true);"><i class='fa fa-search'></i> <?php echo _("Use me!")?></button>
      							</span>
    						</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span class="help-block fpbx-help-block" id="intsrvip-help"><?php echo _("Internal IP address of phone server"); ?></span>
			</div>
		</div>
	</div>
	<!--END Internal IP address of phone server-->
	<!--Configuration Type-->
	<?php
		$server_type = FreePBX::Endpointman()->configmod->get("server_type");
	?>
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-3">
							<label class="control-label" for="cfg_type"><?php echo _("Configuration Type")?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="cfg_type"></i>
						</div>
						<div class="col-md-9">
	                        <select class="form-control selectpicker show-tick" data-style="btn-info" name="cfg_type" id="cfg_type">
                            	<option data-icon="fa fa-upload" value="file" <?php echo ($server_type == "file" ? 'selected="selected"' : '') ?> ><?php echo _("File (TFTP/FTP)")?></option>
								<option data-icon="fa fa-upload" value="http" <?php echo ($server_type == "http"? 'selected="selected"' : '') ?> ><?php echo _("Web (HTTP)")?></option>
                                <option data-icon="fa fa-upload" value="https" <?php echo ($server_type == "https"? 'selected="selected"' : '') ?>><?php echo _("Web (HTTPS)")?></option>
							</select>
                            <br /><br />
							<?php
							if ($server_type == 'http') {
							echo '<div class="alert alert-info" role="alert" id="cfg_type_alert">';
								echo '<strong>' . _("Updated!") . '</strong>' . _(" - Point your phones to: ") . '<a href="' . $server_type . '://' . FreePBX::Endpointman()->configmod->get("srvip") . '/provisioning/p.php/" class="alert-link" target="_blank">' . $server_type . '://' . FreePBX::Endpointman()->configmod->get("srvip") . '/provisioning/p.php/</a>';
							echo '</div>';	
							}
							if ($server_type == 'https') {
							echo '<div class="alert alert-info" role="alert" id="cfg_type_alert">';
								echo '<strong>' . _("Updated!") . '</strong>' . _(" - Point your phones to: ") . '<a href="' . $server_type . '://' . FreePBX::Endpointman()->configmod->get("srvip") . '/provisioning/p.php/" class="alert-link" target="_blank">' . $server_type . '://' . FreePBX::Endpointman()->configmod->get("srvip") . '/provisioning/p.php/</a>';
							echo '</div>';	
							}
								?>

						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span class="help-block fpbx-help-block" id="cfg_type-help"><?php echo _("Type the server by aprovisonament setting. Server TFTP, Server HTTP, Server HTTPS."); ?></span>
			</div>
		</div>
	</div>
	<?php
		unset($server_type);
	?>
	<!--END Configuration Type-->
	<!--Global Final Config & Firmware Directory-->
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-3">
							<label class="control-label" for="config_loc"><?php echo _("Global Final Config & Firmware Directory")?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="config_loc"></i>
						</div>
						<div class="col-md-9">
							<input type="text" class="form-control" id="config_loc" name="config_loc" value="<?php echo FreePBX::Endpointman()->configmod->get("config_location"); ?>">
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span class="help-block fpbx-help-block" id="config_loc-help"><?php echo _("Path location root TFTP server."); ?></span>
			</div>
		</div>
	</div>
	<!--END Global Final Config & Firmware Directory-->
	<!--Global Admin Password-->
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-3">
							<label class="control-label" for="adminpass"><?php echo _("Phone Admin Password")?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="adminpass"></i>
						</div>
						<div class="col-md-9">
							<input type="text" class="form-control" id="adminpass" name="adminpass" value="<?php echo FreePBX::Endpointman()->configmod->get("adminpass"); ?>">
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span class="help-block fpbx-help-block" id="adminpass-help"><?php echo _("Enter a admin password for your phones. Must be 6 characters and only nummeric is recommendet!"); ?></span>
			</div>
		</div>
	</div>
	<!--Global Admin Password-->
	<!--Global User Password-->
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-3">
							<label class="control-label" for="userpass"><?php echo _("Phone User Password")?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="userpass"></i>
						</div>
						<div class="col-md-9">
							<input type="text" class="form-control" id="userpass" name="userpass" value="<?php echo FreePBX::Endpointman()->configmod->get("userpass"); ?>">
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span class="help-block fpbx-help-block" id="userpass-help"><?php echo _("Enter a user password for your phones. Must be 6 characters and only nummeric is recommendet!"); ?></span>
			</div>
		</div>
	</div>
	<!--Global User Password-->
</div>

<div class="section-title" data-for="setting_time">
	<h3><i class="fa fa-minus"></i><?php echo _("Time") ?></h3>
</div>
<div class="section" data-id="setting_time">
	<!--Time Zone-->
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-3">
							<label class="control-label" for="tz"><?php echo _("Time Zone")?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="tz"></i>
						</div>
						<div class="col-md-9">
                        	<div class="input-group input-group-br">
                            	<select class="form-control selectpicker show-tick" data-style="btn-primary" data-live-search-placeholder="Search" data-size="10" data-live-search="true" name="tz" id="tz">
								<?php
									$list_tz = FreePBX::Endpointman()->listTZ(FreePBX::Endpointman()->configmod->get("tz"));
								   	foreach ($list_tz as $row) {
										echo '<option data-icon="fa fa-clock-o" value="'.$row['value'].'" '.($row['selected'] == 1 ? 'selected="selected"' : '' ).'>'.$row['text'].'</option>';
									}
									unset ($list_tz);
								?>
								</select>
								<span class="input-group-btn">
									<button class="btn btn-default" type="button" id='tzphp' onclick="epm_advanced_tab_setting_input_value_change_bt('#tz', sValue = '<?php echo FreePBX::Endpointman()->config->get('PHPTIMEZONE'); ?>', bSaveChange = true);"><i class="fa fa-clock-o"></i> <?php echo _("TimeZone PBX")?></button>
								</span>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span class="help-block fpbx-help-block" id="tz-help"><?php echo _("TimeZone configuration terminasl. Like England/London"); ?></span>
			</div>
		</div>
	</div>
	<!--END Time Zone-->
	<!--Time Server - NTP Server-->
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-3">
							<label class="control-label" for="ntp_server"><?php echo _("Time Server (NTP Server)")?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="ntp_server"></i>
						</div>
						<div class="col-md-9">
							<div class="input-group">
      							<input type="text" class="form-control" placeholder="Server NTP..." id="ntp_server" name="ntp_server" value="<?php echo FreePBX::Endpointman()->configmod->get("ntp"); ?>">
      							<span class="input-group-btn">
        							<button class="btn btn-default" type="button" id='autodetectntp' onclick="epm_advanced_tab_setting_input_value_change_bt('#ntp_server', sValue = '<?php echo $_SERVER["SERVER_ADDR"]; ?>', bSaveChange = true);"><i class='fa fa-search'></i> <?php echo _("Use me!")?></button>
      							</span>
    						</div>
							
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span class="help-block fpbx-help-block" id="ntp_server-help"><?php echo _("Server NTP use the configuration terminals."); ?></span>
			</div>
		</div>
	</div>
	<!--END Time Server - NTP Server-->
</div>

<div class="section-title" data-for="setting_local_paths">
	<h3><i class="fa fa-minus"></i><?php echo _("Local Paths") ?></h3>
</div>
<div class="section" data-id="setting_local_paths">
	<!--NMAP Executable Path-->
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-3">
							<label class="control-label" for="nmap_loc"><?php echo _("NMAP Executable Path")?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="nmap_loc"></i>
						</div>
						<div class="col-md-9">
							<input type="text" class="form-control" id="nmap_loc" name="nmap_loc" value="<?php echo FreePBX::Endpointman()->configmod->get("nmap_location"); ?>">
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span class="help-block fpbx-help-block" id="nmap_loc-help"><?php echo _("Path location NMAP."); ?></span>
			</div>
		</div>
	</div>
	<!--END NMAP Executable Path-->
	<!--ARP Executable Path-->
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-3">
							<label class="control-label" for="arp_loc"><?php echo _("ARP Executable Path")?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="arp_loc"></i>
						</div>
						<div class="col-md-9">
							<input type="text" class="form-control" id="arp_loc" name="arp_loc" value="<?php echo FreePBX::Endpointman()->configmod->get("arp_location"); ?>">
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span class="help-block fpbx-help-block" id="arp_loc-help"><?php echo _("Path location ARP."); ?></span>
			</div>
		</div>
	</div>
	<!--END ARP Executable Path-->
	<!--Asterisk Executable Path-->
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-3">
							<label class="control-label" for="asterisk_loc"><?php echo _("Asterisk Executable Path")?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="asterisk_loc"></i>
						</div>
						<div class="col-md-9">
							<input type="text" class="form-control" id="asterisk_loc" name="asterisk_loc" value="<?php echo FreePBX::Endpointman()->configmod->get("asterisk_location"); ?>">
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span class="help-block fpbx-help-block" id="asterisk_loc-help"><?php echo _("Path location Asterisk."); ?></span>
			</div>
		</div>
	</div>
	<!--END Asterisk Executable Path-->
	<!--Tar Executable Path-->
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-3">
							<label class="control-label" for="tar_loc"><?php echo _("Tar Executable Path")?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="tar_loc"></i>
						</div>
						<div class="col-md-9">
							<input type="text" class="form-control" id="tar_loc" name="tar_loc" value="<?php echo FreePBX::Endpointman()->configmod->get("tar_location"); ?>">
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span class="help-block fpbx-help-block" id="tar_loc-help"><?php echo _("Path location Tar."); ?></span>
			</div>
		</div>
	</div>
	<!--END Tar Executable Path-->
	<!--Netstat Executable Path-->
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-3">
							<label class="control-label" for="netstat_loc"><?php echo _("Netstat Executable Path")?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="netstat_loc"></i>
						</div>
						<div class="col-md-9">
							<input type="text" class="form-control" id="netstat_loc" name="netstat_loc" value="<?php echo FreePBX::Endpointman()->configmod->get("netstat_location"); ?>">
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span class="help-block fpbx-help-block" id="netstat_loc-help"><?php echo _("Path location Netstat."); ?></span>
			</div>
		</div>
	</div>
	<!--END Netstat Executable Path-->
</div>

<div class="section-title" data-for="setting_web_directories">
	<h3><i class="fa fa-minus"></i><?php echo _("Web Directories") ?></h3>
</div>
<div class="section" data-id="setting_web_directories">
	<!--Package Server-->
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-3">
							<label class="control-label" for="package_server"><?php echo _("Package Server")?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="package_server"></i>
						</div>
						<div class="col-md-9">
							<div class="input-group">
      							<input type="text" class="form-control" placeholder="Server Packages..." id="package_server" name="package_server" value="<?php echo FreePBX::Endpointman()->configmod->get("update_server"); ?>">
      							<span class="input-group-btn">
        							<button class="btn btn-default" type="button" id='default_package_server' onclick="epm_advanced_tab_setting_input_value_change_bt('#package_server', sValue = 'http://mirror.freepbx.org/provisioner/v3/', bSaveChange = true);"><i class='fa fa-undo'></i> <?php echo _("Default Mirror FreePBX")?></button>
      							</span>
    						</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span class="help-block fpbx-help-block" id="package_server-help"><?php echo _("URL download files and packages the configuration terminals."); ?></span>
			</div>
		</div>
	</div>
	<!--END Package Server-->
</div>

<div class="section-title" data-for="setting_other">
	<h3><i class="fa fa-minus"></i><?php echo _("Other Settings") ?></h3>
</div>
<div class="section" data-id="setting_other">
	<?php $endpoint_warning_selected = FreePBX::Endpointman()->configmod->get("disable_endpoint_warning"); ?>
	<!--Disable Tooltips-->
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-12">
							<label class="control-label" for="disable_endpoint_warning"><?php echo _("Disable Endpoint Manager Conflict Warning")?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="disable_endpoint_warning"></i>
                            <div class="radioset pull-xs-right">
                                <input type="radio" name="disable_endpoint_warning" id="disable_endpoint_warning_yes" value="Yes" <?php echo ($endpoint_warning_selected  == 1 ? "CHECKED" : "") ?>>
                                <label for="disable_endpoint_warning_yes"><i class="fa fa-check"></i> <?php echo _("Yes");?></label>
                                <input type="radio" name="disable_endpoint_warning" id="disable_endpoint_warning_no" value="No" <?php echo ($endpoint_warning_selected == 0 ? "CHECKED" : "") ?>>
                                <label for="disable_endpoint_warning_no"><i class="fa fa-times"></i> <?php echo _("No");?></label>
                            </div>
                      	</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span class="help-block fpbx-help-block" id="disable_endpoint_warning-help">Enable this setting if you dont want to get a warning message anymore if you have the Commercial Endpoint Manager installed together with OSS Endpoint Manager</span>
			</div>
		</div>
	</div>
	<?php unset($help_selected); ?>
	<!--END Disable Tooltips-->

</div>
<div class="section-title" data-for="setting_experimental">
	<h3><i class="fa fa-minus"></i><?php echo _("Experimental") ?></h3>
</div>
<div class="section" data-id="setting_experimental">
	<!--Enable FreePBX ARI Module-->
	<?php $ari_selected = FreePBX::Endpointman()->configmod->get("enable_ari"); ?>
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-12">
							<label class="control-label" for="enable_ari"><?php echo _("Enable FreePBX ARI Module")?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="enable_ari"></i>
                            <div class="radioset pull-xs-right">
                                <input type="radio" name="enable_ari" id="enable_ari_yes" value="Yes" <?php echo ($ari_selected  == 1 ? "CHECKED" : "") ?>>
                                <label for="enable_ari_yes"><i class="fa fa-check"></i> <?php echo _("Yes");?></label>
                                <input type="radio" name="enable_ari" id="enable_ari_no" value="No" <?php echo ($ari_selected == 0 ? "CHECKED" : "") ?>>
                                <label for="enable_ari_no"><i class="fa fa-times"></i> <?php echo _("No");?></label>
                            </div>
                     	</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span class="help-block fpbx-help-block" id="enable_ari-help">Enable FreePBX ARI Module <a href="http://wiki.provisioner.net/index.php/Endpoint_manager_manual_ari" target="_blank">What?</a></span>
			</div>
		</div>
	</div>
	<?php unset($ari_selected); ?>
	<!--END Enable FreePBX ARI Module-->
	<!--Enable Debug Mode-->
	<?php 
		$debug_selected = FreePBX::Endpointman()->configmod->get("debug");
		if ($debug_selected) {
			global $debug;
			//$debug = $debug . print_r($_REQUEST, true);
			//$endpointman->tpl->assign("debug", $debug);
		}
	?>
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-12">
							<label class="control-label" for="enable_debug" disabled><?php echo _("Enable Debug Mode")?> </label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="enable_debug"></i>
                            <div class="radioset pull-xs-right">
                                <input disabled type="radio" name="enable_debug" id="enable_debug_yes" value="Yes" <?php echo ($debug_selected  == 1 ? "CHECKED" : "") ?>>
                                <label disabled for="enable_debug_yes"><i class="fa fa-check"></i> <?php echo _("Yes");?></label>
                                <input type="radio" name="enable_debug" id="enable_debug_no" value="No" <?php echo ($debug_selected == 0 ? "CHECKED" : "") ?>>
                                <label for="enable_debug_no"><i class="fa fa-times"></i> <?php echo _("No");?></label>
                            </div>
                     	</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span class="help-block fpbx-help-block" id="enable_debug-help">Enable Advanced debugging mode for endpoint manager</span>
			</div>
		</div>
	</div>
	<?php unset($debug_selected); ?>
	<!--END Enable Debug Mode-->
	<!--Disable Tooltips-->
	<?php $help_selected = FreePBX::Endpointman()->configmod->get("disable_help"); ?>
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-12">
							<label class="control-label" for="disable_help"><?php echo _("Disable Tooltips")?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="disable_help"></i>
                            <div class="radioset pull-xs-right">
                                <input type="radio" name="disable_help" id="disable_help_yes" value="Yes" <?php echo ($help_selected  == 1 ? "CHECKED" : "") ?>>
                                <label for="disable_help_yes"><i class="fa fa-check"></i> <?php echo _("Yes");?></label>
                                <input type="radio" name="disable_help" id="disable_help_no" value="No" <?php echo ($help_selected == 0 ? "CHECKED" : "") ?>>
                                <label for="disable_help_no"><i class="fa fa-times"></i> <?php echo _("No");?></label>
                            </div>
                      	</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span class="help-block fpbx-help-block" id="disable_help-help">Disable Tooltip popups</span>
			</div>
		</div>
	</div>
	<?php unset($help_selected); ?>
	<!--END Disable Tooltips-->
	<!--Allow Duplicate Extensions-->
	<?php $dupext_selected = FreePBX::Endpointman()->configmod->get("show_all_registrations"); ?>
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-12">
							<label class="control-label" for="allow_dupext"><?php echo _("Allow Duplicate Extensions")?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="allow_dupext"></i>
                            <div class="radioset pull-xs-right">
                                <input type="radio" name="allow_dupext" id="allow_dupext_yes" value="Yes" <?php echo ($dupext_selected  == 1 ? "CHECKED" : "") ?>>
                                <label for="allow_dupext_yes"><i class="fa fa-check"></i> <?php echo _("Yes");?></label>
                                <input type="radio" name="allow_dupext" id="allow_dupext_no" value="No" <?php echo ($dupext_selected == 0 ? "CHECKED" : "") ?>>
                                <label for="allow_dupext_no"><i class="fa fa-times"></i> <?php echo _("No");?></label>
                            </div>
                    	</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span class="help-block fpbx-help-block" id="allow_dupext-help">Assign the same extension to multiple phones (Note: This is not supported by Asterisk)</span>
			</div>
		</div>
	</div>
	<?php unset($dupext_selected); ?>
	<!--END Allow Duplicate Extensions-->
	<!--Allow Saving Over Default Configuration Files-->
	<?php $allow_hdfiles = FreePBX::Endpointman()->configmod->get("allow_hdfiles"); ?>
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-12">
							<label class="control-label" for="allow_hdfiles"><?php echo _("Allow Saving Over Default Configuration Files")?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="allow_hdfiles"></i>
                            <div class="radioset pull-xs-right">
                                <input type="radio" name="allow_hdfiles" id="allow_hdfiles_yes" value="Yes" <?php echo ($allow_hdfiles  == 1 ? "CHECKED" : "") ?>>
                                <label for="allow_hdfiles_yes"><i class="fa fa-check"></i> <?php echo _("Yes");?></label>
                                <input type="radio" name="allow_hdfiles" id="allow_hdfiles_no" value="No" <?php echo ($allow_hdfiles == 0 ? "CHECKED" : "") ?>>
                                <label for="allow_hdfiles_no"><i class="fa fa-times"></i> <?php echo _("No");?></label>
                            </div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span class="help-block fpbx-help-block" id="allow_hdfiles-help">When editing the configuration files allows one to save over the global template default instead of saving directly to the database. These types of changes can and will be overwritten when updating the brand packages from the configuration/installation page</span>
			</div>
		</div>
	</div>
	<?php unset($allow_hdfiles); ?>
	<!--END Allow Saving Over Default Configuration Files-->
	<!--Disable TFTP Server Check-->
	<?php $tftp_checked = FreePBX::Endpointman()->configmod->get("tftp_check"); ?>
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-12">
							<label class="control-label" for="tftp_check"><?php echo _("Disable TFTP Server Check")?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="tftp_check"></i>
							<div class="radioset pull-xs-right">
								<input type="radio" name="tftp_check" id="tftp_check_yes" value="Yes" <?php echo ($tftp_checked  == 1 ? "CHECKED" : "") ?>>
								<label for="tftp_check_yes"><i class="fa fa-check"></i> <?php echo _("Yes");?></label>
								<input type="radio" name="tftp_check" id="tftp_check_no" value="No" <?php echo ($tftp_checked == 0 ? "CHECKED" : "") ?>>
								<label for="tftp_check_no"><i class="fa fa-times"></i> <?php echo _("No");?></label>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span class="help-block fpbx-help-block" id="tftp_check-help">Disable checking for a valid, working TFTP server which can sometimes cause Apache to crash</span>
			</div>
		</div>
	</div>
	<?php unset($tftp_checked); ?>
	<!--END Disable TFTP Server Check-->
	<!--Disable Configuration File Backups-->
	<?php $backup_checked = FreePBX::Endpointman()->configmod->get("backup_check"); ?>
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-12">
							<label class="control-label" for="backup_check"><?php echo _("Disable Configuration File Backups")?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="backup_check"></i>
                            <div class="radioset pull-xs-right">
                                <input type="radio" name="backup_check" id="backup_check_yes" value="Yes" <?php echo ($backup_checked  == 1 ? "CHECKED" : "") ?>>
                                <label for="backup_check_yes"><i class="fa fa-check"></i> <?php echo _("Yes");?></label>
                                <input type="radio" name="backup_check" id="backup_check_no" value="No" <?php echo ($backup_checked == 0 ? "CHECKED" : "") ?>>
                                <label for="backup_check_no"><i class="fa fa-times"></i> <?php echo _("No");?></label>
                            </div>
   						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span class="help-block fpbx-help-block" id="backup_check-help">Disable backing up the tftboot directory on every phone rebuild or save</span>
			</div>
		</div>
	</div>
	<?php unset($backup_checked); ?>


	<!--END GIT Branch-->
</div>