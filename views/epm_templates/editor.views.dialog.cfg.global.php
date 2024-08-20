<?php
	/*
	$product_list = "SELECT * FROM endpointman_product_list WHERE id > 0";
	$product_list =& sql($product_list,'getAll', \PDO::FETCH_ASSOC);
	*/
	
    //Because we are working with global variables we probably updated them, so lets refresh those variables
    //$endpoint->global_cfg = $endpoint->eda->sql("SELECT var_name, value FROM endpointman_global_vars",'getAssoc');	
?>



<div class="modal fade" id="CfgGlobalTemplate" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
            	<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title"><?php echo _('End Point Configuration Manager')?></h4>
			</div>
			<div class="modal-body">
				<form action="" id="FormCfgGlobalTemplate" name="FormCfgGlobalTemplate">

           
           
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
      							<input type="text" class="form-control" placeholder="<?php echo _("Server PBX..."); ?>" id="srvip" name="srvip" value="">
      							<span class="input-group-append">
        							<button class="btn btn-default" type="button" id='autodetect' onclick="epm_global_input_value_change_bt('#srvip', sValue = '<?php echo $_SERVER["SERVER_ADDR"]; ?>');"><i class='fa fa-search'></i> <?php echo _("Use me!")?></button>
      							</span>
    						</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span class="help-block fpbx-help-block" id="srvip-help"><?php echo _("IP address of the PBX server that will use the terminals."); ?></span>
			</div>
		</div>
	</div>
	<!--END IP address of phone server-->
	<!--Configuration Type-->
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-3">
							<label class="control-label" for="server_type"><?php echo _("Configuration Type")?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="server_type"></i>
						</div>
						<div class="col-md-9">
							<select class="form-control selectpicker show-tick" data-style="btn-info" name="server_type" id="server_type">
								<option value="file"><?php echo _("File (TFTP/FTP)")?></option>
								<option value="http"><?php echo _("Web (HTTP)")?></option>
                                <option value="https" disabled><?php echo _("Web (HTTPS)")?></option>
							</select>
							<div class="alert alert-info" role="alert" id="server_type_alert">
								<strong><?php echo _("Updated!"); ?></strong><?php echo _(" - Point your phones to: "); ?><a href="http://<?php echo $_SERVER['SERVER_ADDR']; ?>/provisioning/p.php/" class="alert-link" target="_blank">http://<?php echo $_SERVER['SERVER_ADDR']; ?>/provisioning/p.php/</a>.
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span class="help-block fpbx-help-block" id="server_type-help"><?php echo _("Type Terminal Server where to obtain configuration data and firmware update. It can be http or ftp."); ?></span>
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
							<label class="control-label" for="config_loc"><?php echo _("Global Final Config & Firmware Directory")?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="config_loc"></i>
						</div>
						<div class="col-md-9">
							<input type="text" class="form-control" placeholder="<?php echo _("Path...."); ?>" id="config_loc" name="config_loc" value="">
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span class="help-block fpbx-help-block" id="config_loc-help"><?php echo _("Location where configuration files and firmware for terminals are housed."); ?></span>
			</div>
		</div>
	</div>
	<!--END Global Final Config & Firmware Directory-->
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
							<div class="input-group">
      							<select class="form-control selectpicker show-tick" data-style="" data-live-search-placeholder="Search" data-live-search="true" name="tz" id="tz">
                                <?php
									//TODO: Pendiente actualizar a ajax!!!!
									foreach (FreePBX::Endpointman()->listTZ("") as $row) {
										echo '<option data-icon="fa fa-clock-o" value="'.$row['value'].'" > '.$row['text'].'</option>';
									}
								?>
								</select>
      							<span class="input-group-append">
        							<button class="btn btn-default" type="button" id='tzphp' onclick="epm_global_input_value_change_bt('#tz', sValue = '<?php echo FreePBX::Endpointman()->config->get('PHPTIMEZONE'); ?>');"><i class="fa fa-clock-o"></i> <?php echo _("TimeZone PBX")?></button>
      							</span>
    						</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span class="help-block fpbx-help-block" id="tz-help"><?php echo _("Select the time zone for the terminals. Example England/London"); ?></span>
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
      							<input type="text" class="form-control" placeholder="<?php echo _("Server NTP..."); ?>" id="ntp_server" name="ntp_server" value="">
      							<span class="input-group-append">
        							<button class="btn btn-default" type="button" id='autodetectntp' onclick="epm_global_input_value_change_bt('#ntp_server', sValue = '<?php echo $_SERVER["SERVER_ADDR"]; ?>');"><i class='fa fa-search'></i> <?php echo _("Use me!")?></button>
      							</span>
    						</div>
							
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span class="help-block fpbx-help-block" id="ntp_server-help"><?php echo _("NTP server that syncs the time the terminals."); ?></span>
			</div>
		</div>
	</div>
	<!--END Time Server - NTP Server-->
</div>



				</form>
			</div>
			<div class="modal-footer">
                <button type="button" class="btn btn-success" name="button_undo_globals" onclick="epm_template_custom_config_get_global(this)"><i class="fa fa-undo" aria-hidden="true"></i> <?php echo _('Reload Config')?></button>
                <button type="button" class="btn btn-success" name="button_update_globals" onclick="epm_template_custom_config_update_global(this)"><i class="fa fa-floppy-o" aria-hidden="true"></i> <?php echo _('Update Global Overrides')?></button>
				<button type="button" class="btn btn-danger" name="button_reset_globals" onclick="epm_template_custom_config_reset_global(this)"><i class="fa fa-refresh" aria-hidden="true"></i> <?php echo _('Reset Global Overrides to Default')?></button>
			</div>
		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</div><!-- /.modal -->