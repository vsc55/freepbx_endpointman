<?php if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); } ?>

<div class="section-title" data-for="ma_up_im_package">
	<h3><?= _("Import Packages") ?></h3>
</div>
<div class="section custom_box_import" data-id="ma_up_im_package">
	<div class="alert alert-info" role="alert">
		<?= _("Download updated releases from: ") ?><a href="http://wiki.provisioner.net/index.php/Releases" target="_blank">http://wiki.provisioner.net/index.php/Releases <i class='icon-globe'></i></a>
	</div>
	
	<font style="font-size: 0.8em"><?= sprintf(_('Local Date Last Modified: %s'), $config['provisioner_ver']) ?></font><br />
	<br />
	
	<form name="manual_upload_form_import_provisioner" enctype="multipart/form-data" method="post">
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-3">
							<label class="control-label" for="provisioner_pack"><?= _("Provisioner Package")?> (<code>.tgz</code>)</label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="provisioner_pack"></i>
						</div>
						<div class="col-md-9 text-right">
                        	<span>
								<input id="fileField" type="file" class="form-control_off" name="files[]" multiple>
							</span>
							<span>
								<a class='btn btn-default' id='upload_provisioner' name="upload_provisioner" href="javascript:epm_advanced_tab_manual_upload_bt_upload('upload_provisioner', 'manual_upload_form_import_provisioner');"><i class='fa fa-upload'></i> <?= _('Import')?></a>
							</span>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span class="help-block fpbx-help-block" id="provisioner_pack-help"><?= _("Import a package Provisioner in manual mode.")?></span>
			</div>
		</div>
	</div>
	</form>
	
	<form name="manual_upload_form_import_brand" enctype="multipart/form-data" method="post">
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-3">
							<label class="control-label" for="brand_pack"><?= _("Brand Package")?> (<code>.tgz</code>)</label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="brand_pack"></i>
						</div>
						<div class="col-md-9 text-right">
							<span>
								<input id="fileField" type="file" class="form-control_off" name="files[]" multiple>
							</span>
							<span>
								<a class='btn btn-default' id='upload_brand' name="upload_brand" href="javascript:epm_advanced_tab_manual_upload_bt_upload('upload_brand', 'manual_upload_form_import_brand');"><i class='fa fa-upload'></i> <?= _('Import')?></a>
							</span>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span class="help-block fpbx-help-block" id="brand_pack-help"><?= _("Import a package brand in manual mode.")?></span>
			</div>
		</div>
	</div>
	</form>
</div>

<div class="section-title" data-for="ma_up_ex_brand_package">
	<h3><?= _("Export Brand Packages") ?></h3>
</div>
<div class="section" data-id="ma_up_ex_brand_package">
	<div class="alert alert-info" role="alert">
		<?= _("Learn how to create your own brand package at "); ?><a target="_blank" href="http://www.provisioner.net/adding_new_phones">http://www.provisioner.net/adding_new_phones <i class='icon-globe'></i></a>
	</div>
	
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-3">
							<label class="control-label" for="brand_export_pack"><?= _("Brand's Available")?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="brand_export_pack"></i>
						</div>
						<div class="col-md-9">
							<?php if ($config['brands_available'] == "") : ?>
								<div class="alert alert-info text-left" role="alert">
									<strong><?= _("Heads up!") ?></strong> <?= _("List Bran's Availables empty.") ?> <i class='icon-globe'></i>
								</div>
							<?php else: ?>
								<div class="input-group">
	      							<select class="form-control selectpicker show-tick" data-style="btn-primary" data-live-search-placeholder="<?= _('Search') ?>" data-size="10" data-live-search="true" name="brand_export_pack_selected" id="brand_export_pack_selected">
										<option value=""><?= _('Select Brand:') ?></option>
											<?php foreach ($config['brands_available'] as $row) : ?>
												<option value="<?= $row['value'] ?>"><?= $row['text'] ?></option>
											<?php endforeach; ?>
									</select>
	      							<span class="input-group-append">
	        							<button class="btn btn-default" type="button" name='brand_export_pack' id='brand_export_pack' onclick="epm_advanced_tab_manual_upload_bt_explor_brand()"><i class="fa fa-download"></i> <?= _("Export")?></button>
	      							</span>
    							</div>
							<?php endif; ?>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span class="help-block fpbx-help-block" id="brand_export_pack-help"><?= _("Explor a package brand's availables.") ?></span>
			</div>
		</div>
	</div>
	
	<div class="element-container">
		<div class="row">
			<div class="col-md-12">
				<div class="row">
					<div class="form-group">
						<div class="col-md-3">
							<label class="control-label" for="brand_export_pack_list"><?= _("List of other exports") ?></label>
							<i class="fa fa-question-circle fpbx-help-icon" data-for="brand_export_pack_list"></i>
						</div>
						<div class="col-md-9">
							<ul class="list-group" id="list-brands-export-item-loading">
								<li class="list-group-item text-center bg-info">
									<i class="fa fa-spinner fa-pulse"></i>&nbsp; <?= _("Loading...") ?>
								</li>
							</ul>
							<ul class="list-group" id="list-brands-export">
							</ul>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<span class="help-block fpbx-help-block" id="brand_export_pack_list-help"><?= _("List packages generated in other exports.") ?></span>
			</div>
		</div>
	</div>
	
</div>