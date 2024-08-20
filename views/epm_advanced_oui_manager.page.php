<?php if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); } ?>

<div class="container-fluid">
	<div class="row">
		<div class="col-sm-12">
			<div id="toolbar-all">
				<button type="button" class="btn btn-primary btn-lg" data-toggle="modal" data-target="#AddDlgModal"><i class='fa fa-plus'></i> <?= _('Add Custom OUI') ?></button>
				<a class='btn btn-default' href="javascript:epm_advanced_tab_oui_manager_refresh_table();" ><i class='fa fa-refresh'></i> <?= _('Refresh Table') ?></a>
			</div>
			<table id="mygrid"
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
						<th data-field="custom" data-formatter="epm_advanced_tab_oui_manager_grid_customFormatter"><?= _("Type")?></th>
						<th data-field="id" data-formatter="epm_advanced_tab_oui_manager_grid_actionFormatter"><?= _("Actions")?></th>
					</tr>
				</thead>
			</table>
		</div>
	</div>
</div>


<div class="modal fade" id="AddDlgModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title"><?= _('New OUI Custom') ?></h4>
			</div>
			<div class="modal-body">
				<div class="element-container">
					<div class="row">
						<div class="col-md-12">
							<div class="row">
								<div class="form-group">
									<div class="col-md-3">
										<label class="control-label" for="number_new_oui"><?= _("OUI") ?></label>
										<i class="fa fa-question-circle fpbx-help-icon" data-for="number_new_oui"></i>
									</div>
									<div class="col-md-9">
										<input type="text" maxlength="6" class="form-control" id="number_new_oui" name="number_new_oui" value="" placeholder="<?= _("OUI Brand") ?>">
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-12">
							<span class="help-block fpbx-help-block" id="number_new_oui-help"><?= _("They are the first 6 characters of the MAC device that identifies the brand (manufacturer).") ?></span>
						</div>
					</div>
				</div>
				<div class="element-container">
					<div class="row">
						<div class="col-md-12">
							<div class="row">
								<div class="form-group">
									<div class="col-md-3">
										<label class="control-label" for="brand_new_oui"><?= _("Brand") ?></label>
										<i class="fa fa-question-circle fpbx-help-icon" data-for="brand_new_oui"></i>
									</div>
									<div class="col-md-9">
			      						<select class="form-control selectpicker show-tick" data-style="btn-info" data-live-search-placeholder="<?= _('Search') ?>" data-size="10" data-live-search="true" id="brand_new_oui" name="brand_new_oui">
			      							<option value=""><?= _("Select Brand:") ?></option>
											<?php foreach ($config['brands']  as $row) : ?>
												<option value="<?= $row['id'] ?>"><?= $row['name'] ?></option>
											<?php endforeach; ?>
										</select>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-12">
							<span class="help-block fpbx-help-block" id="brand_new_oui-help"><?= _("It is the brand of OUI we specified.") ?></span>
						</div>
					</div>
				</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-danger" data-dismiss="modal"><i class='fa fa-times'></i> <?= _("Cancel") ?></button>
				<button type="button" class="btn btn-primary" id="AddDlgModal_bt_new"><i class='fa fa-check'></i> <?= _("Add New") ?></button>
			</div>
		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</div><!-- /.modal -->