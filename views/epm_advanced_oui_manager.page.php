<?php if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); } ?>

<div class="container-fluid">
	<div class="row">
		<div class="col-sm-12">
			<div id="toolbar-all">
				<button type="button" class="btn btn-primary btn-lg" data-toggle="modal" data-target="#epm_advanced_tab_oui_add_modal"><i class='fa fa-plus'></i> <?= _('Add Custom OUI') ?></button>
				<button type="button" class="btn btn-primary btn-lg" id="epm_advanced_tab_oui_refresh"><i class='fa fa-refresh'></i> <?= _('Refresh Table') ?></button>
			</div>
			<table id="epm_advanced_tab_oui_grid"
				data-url="<?= $config['url_grid'] ?>"
				data-cache="false"
				data-cookie="true"
				data-cookie-id-table="oui_manager-all"
				data-toolbar="#toolbar-all"
				data-maintain-selected="true"
				data-show-columns="true"
				data-show-toggle="true"
				data-toggle="table"
				data-pagination="true"
				data-search="true"
				data-sort-name="oui"
				class="table table-striped">
				<thead>
					<tr>
						<th data-field="oui" data-sortable="true" data-formatter="<code>%s</code>"><?= _("OUI")?></th>
						<th data-field="brand" data-sortable="true"><?php echo _("Brand")?></th>
						<th data-field="custom" data-sortable="true" data-formatter="epm_advanced_tab_oui_manager_grid_customFormatter"><?= _("Type")?></th>
						<th data-field="id" data-formatter="epm_advanced_tab_oui_manager_grid_actionFormatter"><?= _("Actions")?></th>
					</tr>
				</thead>
			</table>
		</div>
	</div>
</div>


<div class="modal fade" id="epm_advanced_tab_oui_add_modal" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-labelledby="epm_advanced_tab_oui_add_modal_label" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h4 id="epm_advanced_tab_oui_add_modal_label" class="modal-title"><?= _('New OUI Custom') ?></h4>
			</div>
			<div class="modal-body">
				<div class="element-container">
					<div class="row">
						<div class="col-md-12">
							<div class="row">
								<div class="form-group">
									<div class="col-md-3">
										<label class="control-label" for="modal_form_new_oui_number"><?= _("OUI") ?></label>
										<i class="fa fa-question-circle fpbx-help-icon" data-for="modal_form_new_oui_number"></i>
									</div>
									<div class="col-md-9">
										<input type="text" maxlength="6" class="form-control" id="modal_form_new_oui_number" name="modal_form_new_oui_number" value="" placeholder="<?= _("OUI Brand") ?>">
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-12">
							<span class="help-block fpbx-help-block" id="modal_form_new_oui_number-help"><?= _("They are the first 6 characters of the MAC device that identifies the brand (manufacturer).") ?></span>
						</div>
					</div>
				</div>
				<div class="element-container">
					<div class="row">
						<div class="col-md-12">
							<div class="row">
								<div class="form-group">
									<div class="col-md-3">
										<label class="control-label" for="modal_form_new_oui_brand"><?= _("Brand") ?></label>
										<i class="fa fa-question-circle fpbx-help-icon" data-for="modal_form_new_oui_brand"></i>
									</div>
									<div class="col-md-9">

										<div class="input-group">
											<select class="form-control selectpicker show-tick" data-style="btn-info" data-live-search-placeholder="<?= _('Search') ?>" data-size="10" data-live-search="true" id="modal_form_new_oui_brand" name="modal_form_new_oui_brand">
												<option value=""><?= _("Select Brand:") ?></option>
											</select>
											<div class="input-group-append">
												<button class="btn btn-secondary" type="button" id="epm_advanced_tab_oui_add_modal_btn_refresh">
													<i class="fa fa-refresh" aria-hidden="true"></i>
												</button>
											</div>
										</div>

									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-12">
							<span class="help-block fpbx-help-block" id="modal_form_new_oui_brand-help"><?= _("It is the brand of OUI we specified.") ?></span>
						</div>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-danger" data-dismiss="modal"><i class='fa fa-times'></i> <?= _("Cancel") ?></button>
				<button type="button" class="btn btn-primary" id="modal_form_new_oui_btn_add"><i class='fa fa-check'></i> <?= _("Add New OUI") ?></button>
			</div>
		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</div><!-- /.modal -->