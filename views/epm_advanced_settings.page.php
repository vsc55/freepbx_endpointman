<?php if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); } ?>

<div class="section-title" data-for="setting_provision">
	<h3><i class="fa fa-minus"></i><?= _("Setting Provision") ?></h3>
</div>
<div class="section" data-id="setting_provision">

	<!--IP address of phone server-->
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-3">
							<label class="control-label" for="srvip"><?= _("IP address of phone server")?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="srvip"></i>
						</div>
						<div class="col-md-9">
							<div class="input-group">
      							<input type="text" class="form-control" placeholder="<? _('Server PBX...') ?>" id="srvip" name="srvip" value="<?= $config['srvip'] ?>">
      							<span class="input-group-btn">
        							<button class="btn btn-default" type="button" id='autodetect' onclick="epm_advanced_tab_setting_input_value_change_bt('#srvip', sValue = '<?= $_SERVER["SERVER_ADDR"] ?>', bSaveChange = true);"><i class='fa fa-search'></i> <?= _("Use me!")?></button>
      							</span>
    						</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span class="help-block fpbx-help-block" id="srvip-help"><?= _("IP Address of your PBX.") ?></span>
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
							<label class="control-label" for="intsrvip"><?= _("Internal IP address of phone server")?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="intsrvip"></i>
						</div>
						<div class="col-md-9">
							<div class="input-group">
      							<input type="text" class="form-control" placeholder="<?= _('Server PBX...') ?>" id="intsrvip" name="intsrvip" value="<?= $config['intsrvip'] ?>">
      							<span class="input-group-btn">
        							<button class="btn btn-default" type="button" id='autodetect' onclick="epm_advanced_tab_setting_input_value_change_bt('#intsrvip', sValue = '<?= $_SERVER["SERVER_ADDR"] ?>', bSaveChange = true);"><i class='fa fa-search'></i> <?= _("Use me!")?></button>
      							</span>
    						</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span class="help-block fpbx-help-block" id="intsrvip-help"><?= _("Internal IP address of phone server") ?></span>
			</div>
		</div>
	</div>
	<!--END Internal IP address of phone server-->
	<!--Configuration Type-->
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
                            	<option data-icon="fa fa-upload" value="file"  <?= ($config['server_type'] == "file" ? 'selected="selected"' : '') ?> ><?= _("File (TFTP/FTP)")?></option>
								<option data-icon="fa fa-upload" value="http"  <?= ($config['server_type'] == "http" ? 'selected="selected"' : '') ?> ><?= _("Web (HTTP)")?></option>
                                <option data-icon="fa fa-upload" value="https" <?= ($config['server_type'] == "https"? 'selected="selected"' : '') ?> ><?= _("Web (HTTPS)")?></option>
							</select>
                            <br /><br />
							<!-- TODO: Pending implement msg via JS after change value -->
							<?php if ( in_array( strtolower($config['server_type']), array('http', 'https')) ) : ?>
								<div class="alert alert-info" role="alert" id="cfg_type_alert">
									<strong><?= _("Updated!") ?></strong><?= sprintf(_(" - Point your phones to: %s"), '<a href="'.$config['provisioning_url'].'" class="alert-link" target="_blank">' . $config['provisioning_url'].'</a>') ?>
								</div>
							<?php endif; ?>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span class="help-block fpbx-help-block" id="cfg_type-help"><?= _("Type the server by aprovisonament setting. Server TFTP, Server HTTP, Server HTTPS.") ?></span>
			</div>
		</div>
	</div>
	
	<!--END Configuration Type-->
	<!--Global Final Config & Firmware Directory-->
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-3">
							<label class="control-label" for="config_loc"><?= _("Global Final Config & Firmware Directory")?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="config_loc"></i>
						</div>
						<div class="col-md-9">
							<input type="text" class="form-control" id="config_loc" name="config_loc" value="<?= $config['config_location'] ?>">
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span class="help-block fpbx-help-block" id="config_loc-help"><?= _("Path location root TFTP server.") ?></span>
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
							<label class="control-label" for="adminpass"><?= _("Phone Admin Password") ?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="adminpass"></i>
						</div>
						<div class="col-md-9">
							<input type="text" class="form-control" id="adminpass" name="adminpass" value="<?= $config['adminpass'] ?>">
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span class="help-block fpbx-help-block" id="adminpass-help"><?= _("Enter a admin password for your phones. Must be 6 characters and only nummeric is recommendet!") ?></span>
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
							<label class="control-label" for="userpass"><?= _("Phone User Password")?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="userpass"></i>
						</div>
						<div class="col-md-9">
							<input type="text" class="form-control" id="userpass" name="userpass" value="<?= $config['userpass'] ?>">
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span class="help-block fpbx-help-block" id="userpass-help"><?= _("Enter a user password for your phones. Must be 6 characters and only nummeric is recommendet!") ?></span>
			</div>
		</div>
	</div>
	<!--Global User Password-->
</div>

<div class="section-title" data-for="setting_time">
	<h3><i class="fa fa-minus"></i><?= _("Time") ?></h3>
</div>
<div class="section" data-id="setting_time">
	<!--Time Zone-->
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-3">
							<label class="control-label" for="tz"><?= _("Time Zone") ?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="tz"></i>
						</div>
						<div class="col-md-9">
                        	<div class="input-group input-group-br">
                            	<select class="form-control selectpicker show-tick" data-style="btn-primary" data-live-search-placeholder="<?= _('Search') ?>" data-size="10" data-live-search="true" name="tz" id="tz">
									<?php foreach($config['ls_tz'] as $row) : ?>
										<option data-icon="fa fa-clock-o" value="<?= $row['value'] ?>" <?= ($row['selected'] == 1) ? 'selected="selected"' : '' ?> ><?= $row['text']?> </option>
									<?php endforeach; ?>
								</select>
								<span class="input-group-btn">
									<button class="btn btn-default" type="button" id='tzphp' onclick="epm_advanced_tab_setting_input_value_change_bt('#tz', sValue = '<?= $config['PHPTIMEZONE'] ?>', bSaveChange = true);"><i class="fa fa-clock-o"></i> <?= _("TimeZone PBX")?></button>
								</span>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span class="help-block fpbx-help-block" id="tz-help"><?= _("TimeZone configuration terminasl. Like England/London") ?></span>
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
							<label class="control-label" for="ntp_server"><?= _("Time Server (NTP Server)")?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="ntp_server"></i>
						</div>
						<div class="col-md-9">
							<div class="input-group">
      							<input type="text" class="form-control" placeholder="<?= _('Server NTP...') ?>" id="ntp_server" name="ntp_server" value="<?= $config['ntp'] ?>">
      							<span class="input-group-btn">
        							<button class="btn btn-default" type="button" id='autodetectntp' onclick="epm_advanced_tab_setting_input_value_change_bt('#ntp_server', sValue = '<?= $_SERVER["SERVER_ADDR"] ?>', bSaveChange = true);"><i class='fa fa-search'></i> <?= _("Use me!")?></button>
      							</span>
    						</div>
							
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span class="help-block fpbx-help-block" id="ntp_server-help"><?= _("Server NTP use the configuration terminals.") ?></span>
			</div>
		</div>
	</div>
	<!--END Time Server - NTP Server-->
</div>

<div class="section-title" data-for="setting_local_paths">
	<h3><i class="fa fa-minus"></i><?= _("Local Paths") ?></h3>
</div>
<div class="section" data-id="setting_local_paths">
	<!--NMAP Executable Path-->
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-3">
							<label class="control-label" for="nmap_loc"><?= _("NMAP Executable Path")?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="nmap_loc"></i>
						</div>
						<div class="col-md-9">
							<input type="text" class="form-control" id="nmap_loc" name="nmap_loc" value="<?= $config['nmap_location'] ?>">
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span class="help-block fpbx-help-block" id="nmap_loc-help"><?= _("Path location NMAP."); ?></span>
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
							<label class="control-label" for="arp_loc"><?= _("ARP Executable Path")?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="arp_loc"></i>
						</div>
						<div class="col-md-9">
							<input type="text" class="form-control" id="arp_loc" name="arp_loc" value="<?= $config['arp_location'] ?>">
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span class="help-block fpbx-help-block" id="arp_loc-help"><?= _("Path location ARP.") ?></span>
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
							<label class="control-label" for="asterisk_loc"><?= _("Asterisk Executable Path")?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="asterisk_loc"></i>
						</div>
						<div class="col-md-9">
							<input type="text" class="form-control" id="asterisk_loc" name="asterisk_loc" value="<?= $config['asterisk_location'] ?>">
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span class="help-block fpbx-help-block" id="asterisk_loc-help"><?= _("Path location Asterisk.") ?></span>
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
							<label class="control-label" for="tar_loc"><?= _("Tar Executable Path")?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="tar_loc"></i>
						</div>
						<div class="col-md-9">
							<input type="text" class="form-control" id="tar_loc" name="tar_loc" value="<?= $config['tar_location'] ?>">
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span class="help-block fpbx-help-block" id="tar_loc-help"><?= _("Path location Tar.") ?></span>
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
							<label class="control-label" for="netstat_loc"><?= _("Netstat Executable Path")?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="netstat_loc"></i>
						</div>
						<div class="col-md-9">
							<input type="text" class="form-control" id="netstat_loc" name="netstat_loc" value="<?= $config['netstat_location'] ?>">
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span class="help-block fpbx-help-block" id="netstat_loc-help"><?= _("Path location Netstat.") ?></span>
			</div>
		</div>
	</div>
	<!--END Netstat Executable Path-->
</div>

<div class="section-title" data-for="setting_web_directories">
	<h3><i class="fa fa-minus"></i><?= _("Web Directories") ?></h3>
</div>
<div class="section" data-id="setting_web_directories">
	<!--Package Server-->
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-3">
							<label class="control-label" for="package_server"><?= _("Package Server")?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="package_server"></i>
						</div>
						<div class="col-md-9">
							<div class="input-group">
      							<input type="text" class="form-control" placeholder=" <?= _('Server Packages...') ?>" id="package_server" name="package_server" value="<?= $config['update_server'] ?>">
      							<span class="input-group-btn">
        							<button class="btn btn-default" type="button" id='default_package_server' onclick="epm_advanced_tab_setting_input_value_change_bt('#package_server', sValue = '<?= $config['default_mirror'] ?>', bSaveChange = true);"><i class='fa fa-undo'></i> <?= _("Default Mirror FreePBX")?></button>
      							</span>
    						</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span class="help-block fpbx-help-block" id="package_server-help"><?= _("URL download files and packages the configuration terminals.") ?></span>
			</div>
		</div>
	</div>
	<!--END Package Server-->
</div>

<div class="section-title" data-for="setting_other">
	<h3><i class="fa fa-minus"></i><?= _("Other Settings") ?></h3>
</div>
<div class="section" data-id="setting_other">
	<!--Disable Tooltips-->
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-12">
							<label class="control-label" for="disable_endpoint_warning"><?= _("Disable Endpoint Manager Conflict Warning")?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="disable_endpoint_warning"></i>
                            <div class="radioset pull-xs-right">
                                <input type="radio" name="disable_endpoint_warning" id="disable_endpoint_warning_yes" value="Yes" <?= ($config['disable_endpoint_warning']  == 1) ? "CHECKED" : "" ?> >
                                <label for="disable_endpoint_warning_yes"><i class="fa fa-check"></i> <?= _("Yes") ?></label>
                                <input type="radio" name="disable_endpoint_warning" id="disable_endpoint_warning_no" value="No"   <?= ($config['disable_endpoint_warning'] == 0) ? "CHECKED" : "" ?> >
                                <label for="disable_endpoint_warning_no"><i class="fa fa-times"></i> <?= _("No") ?></label>
                            </div>
                      	</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span class="help-block fpbx-help-block" id="disable_endpoint_warning-help"><?= _('Enable this setting if you dont want to get a warning message anymore if you have the Commercial Endpoint Manager installed together with OSS Endpoint Manager') ?></span>
			</div>
		</div>
	</div>
	<!--END Disable Tooltips-->

</div>
<div class="section-title" data-for="setting_experimental">
	<h3><i class="fa fa-minus"></i><?= _("Experimental") ?></h3>
</div>
<div class="section" data-id="setting_experimental">
	<!--Enable FreePBX ARI Module-->
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-12">
							<label class="control-label" for="enable_ari"><?= _("Enable FreePBX ARI Module")?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="enable_ari"></i>
                            <div class="radioset pull-xs-right">
                                <input type="radio" name="enable_ari" id="enable_ari_yes" value="Yes" <?= ($config['enable_ari'] == 1) ? "CHECKED" : "" ?> >
                                <label for="enable_ari_yes"><i class="fa fa-check"></i> <?= _("Yes") ?></label>
                                <input type="radio" name="enable_ari" id="enable_ari_no" value="No"   <?= ($config['enable_ari'] == 0) ? "CHECKED" : "" ?> >
                                <label for="enable_ari_no"><i class="fa fa-times"></i> <?= _("No") ?></label>
                            </div>
                     	</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span class="help-block fpbx-help-block" id="enable_ari-help"> <?= _('Enable FreePBX ARI Module <a href="http://wiki.provisioner.net/index.php/Endpoint_manager_manual_ari" target="_blank">What?</a>') ?></span>
			</div>
		</div>
	</div>
	<!--END Enable FreePBX ARI Module-->
	<!--Enable Debug Mode-->
	<?php 
		if ($config['debug'])
		{
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
							<label class="control-label" for="enable_debug" disabled><?= _("Enable Debug Mode")?> </label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="enable_debug"></i>
                            <div class="radioset pull-xs-right">
                                <input disabled type="radio" name="enable_debug" id="enable_debug_yes" value="Yes" <?= ($config['debug'] == 1) ? "CHECKED" : "" ?> >
                                <label disabled for="enable_debug_yes"><i class="fa fa-check"></i> <?= _("Yes") ?></label>
                                <input type="radio" name="enable_debug" id="enable_debug_no" value="No"            <?= ($config['debug'] == 0) ? "CHECKED" : "" ?> >
                                <label for="enable_debug_no"><i class="fa fa-times"></i> <?= _("No") ?></label>
                            </div>
                     	</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span class="help-block fpbx-help-block" id="enable_debug-help"> <?= _('Enable Advanced debugging mode for endpoint manager') ?></span>
			</div>
		</div>
	</div>
	<!--END Enable Debug Mode-->
	<!--Disable Tooltips-->
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-12">
							<label class="control-label" for="disable_help"><?= _("Disable Tooltips")?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="disable_help"></i>
                            <div class="radioset pull-xs-right">
                                <input type="radio" name="disable_help" id="disable_help_yes" value="Yes" <?= ($config['disable_help'] == 1) ? "CHECKED" : "" ?> >
                                <label for="disable_help_yes"><i class="fa fa-check"></i> <?= _("Yes") ?></label>
                                <input type="radio" name="disable_help" id="disable_help_no" value="No"   <?= ($config['disable_help'] == 0) ? "CHECKED" : "" ?> >
                                <label for="disable_help_no"><i class="fa fa-times"></i> <?= _("No") ?></label>
                            </div>
                      	</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span class="help-block fpbx-help-block" id="disable_help-help"><?= _('Disable Tooltip popups') ?></span>
			</div>
		</div>
	</div>
	<!--END Disable Tooltips-->
	<!--Allow Duplicate Extensions-->
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-12">
							<label class="control-label" for="allow_dupext"><?= _("Allow Duplicate Extensions")?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="allow_dupext"></i>
                            <div class="radioset pull-xs-right">
                                <input type="radio" name="allow_dupext" id="allow_dupext_yes" value="Yes" <?= ($config['show_all_registrations'] == 1) ? "CHECKED" : "" ?> >
                                <label for="allow_dupext_yes"><i class="fa fa-check"></i> <?= _("Yes") ?></label>
                                <input type="radio" name="allow_dupext" id="allow_dupext_no" value="No"   <?= ($config['show_all_registrations'] == 0) ? "CHECKED" : "" ?> >
                                <label for="allow_dupext_no"><i class="fa fa-times"></i> <?= _("No") ?></label>
                            </div>
                    	</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span class="help-block fpbx-help-block" id="allow_dupext-help"><?= _('Assign the same extension to multiple phones (Note: This is not supported by Asterisk)') ?></span>
			</div>
		</div>
	</div>
	<!--END Allow Duplicate Extensions-->
	<!--Allow Saving Over Default Configuration Files-->
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-12">
							<label class="control-label" for="allow_hdfiles"><?= _("Allow Saving Over Default Configuration Files")?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="allow_hdfiles"></i>
                            <div class="radioset pull-xs-right">
                                <input type="radio" name="allow_hdfiles" id="allow_hdfiles_yes" value="Yes" <?= ($config['allow_hdfiles'] == 1) ? "CHECKED" : "" ?> >
                                <label for="allow_hdfiles_yes"><i class="fa fa-check"></i> <?= _("Yes") ?></label>
                                <input type="radio" name="allow_hdfiles" id="allow_hdfiles_no" value="No"   <?= ($config['allow_hdfiles'] == 0) ? "CHECKED" : "" ?> >
                                <label for="allow_hdfiles_no"><i class="fa fa-times"></i> <?= _("No") ?></label>
                            </div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span class="help-block fpbx-help-block" id="allow_hdfiles-help"><?= _('When editing the configuration files allows one to save over the global template default instead of saving directly to the database. These types of changes can and will be overwritten when updating the brand packages from the configuration/installation page') ?></span>
			</div>
		</div>
	</div>
	<!--END Allow Saving Over Default Configuration Files-->
	<!--Disable TFTP Server Check-->
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-12">
							<label class="control-label" for="tftp_check"><?= _("Disable TFTP Server Check")?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="tftp_check"></i>
							<div class="radioset pull-xs-right">
								<input type="radio" name="tftp_check" id="tftp_check_yes" value="Yes" <?= ($config['tftp_check'] == 1) ? "CHECKED" : "" ?> >
								<label for="tftp_check_yes"><i class="fa fa-check"></i> <?= _("Yes") ?></label>
								<input type="radio" name="tftp_check" id="tftp_check_no" value="No"   <?= ($config['tftp_check'] == 0) ? "CHECKED" : "" ?> >
								<label for="tftp_check_no"><i class="fa fa-times"></i> <?= _("No") ?></label>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span class="help-block fpbx-help-block" id="tftp_check-help"><?= _('Disable checking for a valid, working TFTP server which can sometimes cause Apache to crash') ?></span>
			</div>
		</div>
	</div>
	<!--END Disable TFTP Server Check-->
	<!--Disable Configuration File Backups-->
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-12">
							<label class="control-label" for="backup_check"><?= _("Disable Configuration File Backups")?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="backup_check"></i>
                            <div class="radioset pull-xs-right">
                                <input type="radio" name="backup_check" id="backup_check_yes" value="Yes" <?= ($config['backup_check'] == 1) ? "CHECKED" : "" ?> >
                                <label for="backup_check_yes"><i class="fa fa-check"></i> <?= _("Yes") ?></label>
                                <input type="radio" name="backup_check" id="backup_check_no" value="No"   <?= ($config['backup_check'] == 0) ? "CHECKED" : "" ?> >
                                <label for="backup_check_no"><i class="fa fa-times"></i> <?= _("No") ?></label>
                            </div>
   						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span class="help-block fpbx-help-block" id="backup_check-help"> <?= _('Disable backing up the tftboot directory on every phone rebuild or save') ?></span>
			</div>
		</div>
	</div>

	<!--END GIT Branch-->
</div>