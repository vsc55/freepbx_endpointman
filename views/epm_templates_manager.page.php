<?php if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); } ?>	

<div class="container-fluid">
	<div class="row">
		<div class="col-sm-12">
			<div id="toolbar-grid">
				<button type="button" class="btn btn-primary btn-lg" data-toggle="modal" data-target="#modal_add_tempalte"><i class='fa fa-plus'></i> <?= _('Add New Template')?></button>
				<button type="button" class="btn btn-primary btn-lg" id="epm_template_refresh"><i class='fa fa-refresh'></i> <?= _('Refresh Table') ?></button>
			</div>
			
			<table id="epm_templates_grid"
				data-url="<?= $config['url_grid'] ?>"
				data-cache="false"
				data-cookie="true"
				data-cookie-id-table="template_custom_table"
				data-toolbar="#toolbar-grid"
				data-escape="true"
				data-maintain-selected="true"
				data-show-columns="true"
				data-show-toggle="true"
				data-toggle="table"
				data-pagination="true"
				data-search="true"
				data-sort-name="name"
				class="table table-striped">
				<thead>
					<tr>
						<th data-field="name" data-sortable="true"><?= _("Template Name")?></th>
						<th data-field="model_class" data-sortable="true"><?= _("Model Classification")?></th>
						<th data-field="model_clone" data-sortable="true"><?= _("Model Clone")?></th>
						<th data-field="enabled" data-sortable="true" data-formatter="epm_templates_grid_FormatThEnabled"><?= _("Enabled")?></th>
						<th data-field="id" data-formatter="epm_templates_grid_FormatThAction"><?= _("Action")?></th>
					</tr>
				</thead>
			</table>
			
		</div>
	</div>
</div>




<div class="modal fade" id="modal_add_tempalte" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false" aria-labelledby="modal_add_tempalte_title" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h4 id="modal_add_tempalte_title" class="modal-title"><?= _("New Template") ?></h4>
			</div>
			<div class="modal-body">

				<div class="element-container">
					<div class="row">
						<div class="col-md-12">
							<div class="row">
								<div class="form-group">
									<div class="col-md-3">
										<label class="control-label" for="modal_form_new_template_name_template"><?= _("Template Name")?></label>
										<i class="fa fa-question-circle fpbx-help-icon" data-for="modal_form_new_template_name_template"></i>
									</div>
									<div class="col-md-9">
										<input type="text" class="form-control" id="modal_form_new_template_name_template" name="modal_form_new_template_name_template" value="" maxlength="255" placeholder="<?= _("New Name Template...")?>">
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-12">
							<span class="help-block fpbx-help-block" id="modal_form_new_template_name_template-help"><?= _("Name with which this template will be seen.")?></span>
						</div>
					</div>
				</div>

				<div class="element-container">
					<div class="row">
						<div class="col-md-12">
							<div class="row">
								<div class="form-group">
									<div class="col-md-3">
										<label class="control-label" for="modal_form_new_template_products"><?= _("Product Select")?></label>
										<i class="fa fa-question-circle fpbx-help-icon" data-for="modal_form_new_template_products"></i>
									</div>
									<div class="col-md-9">

										<div class="input-group">
											<select class="form-control selectpicker show-tick" data-style="" data-live-search-placeholder="<?= _('Search') ?>" data-size="10" data-live-search="true" name="modal_form_new_template_products" id="modal_form_new_template_products">
												<?php
												/*
												<option value=""><?= _("Select Product:")?></option>
												<?php foreach($template_list as $row) : ?>
													<option value="<?= $row['id'] ?>"><?= $row['short_name'] ?></option>
												<?php endforeach; 
												*/
												?>
											</select>
											<div class="input-group-append">
												<button class="btn btn-secondary" type="button" id="epm_template_new_modal_btn_refresh_products">
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
							<span class="help-block fpbx-help-block" id="modal_form_new_template_products-help"><?= _("List of installed products from which a template can be generated.")?></span>
						</div>
					</div>
				</div>

				<div class="element-container">
					<div class="row">
						<div class="col-md-12">
							<div class="row">
								<div class="form-group">
									<div class="col-md-3">
										<label class="control-label" for="modal_form_new_template_model_clone"><?= _("Clone Template From") ?></label>
										<i class="fa fa-question-circle fpbx-help-icon" data-for="modal_form_new_template_model_clone"></i>
									</div>
									<div class="col-md-9">

										<div class="input-group">
											<select class="form-control selectpicker show-tick" data-style="" data-live-search-placeholder="<?= _('Search') ?>" data-size="10" data-live-search="true" name="modal_form_new_template_model_clone" id="modal_form_new_template_model_clone"></select>
											<div class="input-group-append">
												<button class="btn btn-secondary" type="button" id="epm_template_new_modal_btn_refresh_model_clone">
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
							<span class="help-block fpbx-help-block" id="modal_form_new_template_model_clone-help"><?= _("TODO: Help")?></span>
						</div>
					</div>
				</div>

			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-danger" data-dismiss="modal"><i class='fa fa-times'></i> <?= _("Cancel")?></button>
				<button type="button" class="btn btn-primary" id="modal_form_new_template_btn_add"><i class='fa fa-check'></i> <?= _("Add New Tempalte")?></button>
			</div>
		</div>
	</div>
</div>