<?php if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); } ?>

<div class="section-title" data-for="iedl_export_csv">
	<h3><?= _("Export CSV") ?></h3>
</div>
<div class="section" data-id="iedl_export_csv">

	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-3">
							<label class="control-label" for="bt_export_csv"><?= _("Export CSV file of devices")?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="bt_export_csv"></i>
						</div>
						<div class="col-md-9 text-right">
							<a class='btn btn-default' id='bt_export_csv' name="bt_export_csv" href="<?= $config['url_export'] ?>" target="_blank"><i class='fa fa-download'></i> <?= _('Export')?></a>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<span id="bt_export_csv-help" class="help-block fpbx-help-block"><?= _("Export data configuracion devices.")?></span>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="section-title" data-for="iedl_import_csv">
	<h3><?= _("Import CSV") ?></h3>
</div>
<div class="section" data-id="iedl_import_csv">

	<form name="iedl_form_import_cvs" enctype="multipart/form-data" method="post">
	<input type="hidden" name="MAX_FILE_SIZE" value="30000" />
	
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group custom_box_import">
						<div class="col-md-3">
							<label class="control-label" for="bt_import_csv"><?= _("Import CSV file of devices")?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="bt_import_csv"></i>
							<!-- 
							data-url="ajax.php?module=endpointman&amp;module_sec=epm_advanced&amp;module_tab=iedl&amp;command=upload&amp;MAX_FILE_SIZE=30000"
							<button type="submit" class='btn btn-default' id='button_import' name='button_import'><i class='fa fa-upload'></i>Import</button>
							 -->
						</div>
						<div class="col-md-9 text-right">
							<span>
								<input id="bt_import_csv" type="file" class="form-control_off" name="files[]" multiple>
							</span>
							<span>
								<a class='btn btn-default' id='button_import' name="button_import" href="javascript:epm_advanced_tab_iedl_bt_import();"><i class='fa fa-upload'></i> <?= _('Import')?></a>
							</span>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<span id="bt_import_csv-help" class="help-block fpbx-help-block"><i class='icon-warning-sign'></i> <?= _("Warning: The extensions need to be added into FreePBX before you import."); ?></span>
					</div>
				</div>
			</div>
		</div>
	</div>

	</form>
</div>

<div class="section-title" data-for="iedl_csv_file_format">
	<h3><?= _("CSV File Format") ?></h3>
</div>
<div class="section" data-id="iedl_csv_file_format">
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-12">
							<label class="control-label" for="structure_csv"><?= _("Structure")?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="structure_csv"></i>
							<br />
							<code class='inline'>&lt;<?= _('MAC Address')?>&gt;,&lt;<?= _('Brand')?>&gt;,&lt;<?= _('Model')?>&gt;,&lt;<?= _('Extension')?>&gt;,&lt;<?= _('Line')?>&gt;</code>
							<ul class='nobullets'>
								<li><code class='inline'><?= _('MAC Address')?></code> - <?= _('is required')?></li>
								<li><code class='inline'><?= _('Brand')?></code> - <?= _('can be blank')?></li>
								<li><code class='inline'><?= _('Model')?></code> - <?= _('can be blank')?></li>
								<li><code class='inline'><?= _('Extension')?></code> - <?= _('can be blank')?></li>
								<li><code class='inline'><?= _('Line')?></code> - <?= _('can be blank')?></li>
							</ul>
	
							<h6><?= _('Examples')?>:</h6>
							<code class='inline'>001122334455,Cisco/Linksys,7940,4321,1</code><br/>
							<code class='inline'>112233445566,Cisco/Linksys,7945,,</code>
							
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>