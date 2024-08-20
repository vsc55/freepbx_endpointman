<?php if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); } ?>	

<div class="container-fluid">
	<div class="row">
		<div class="col-sm-12">
			<div id="toolbar-grid">
				<button type="button" class="btn btn-primary btn-lg" data-toggle="modal" data-target="#AddDlgModal"><i class='fa fa-plus'></i> <?= _('Add New Template')?></button>
				<a class='btn btn-default' href="javascript:epm_global_refresh_table('#mygrid', true);" ><i class='fa fa-refresh'></i> <?= _('Refresh Table')?></a>
			</div>
			
			<table id="mygrid"
				data-url="ajax.php?module=endpointman&amp;module_sec=epm_templates&amp;module_tab=manager&amp;command=list_current_template"
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




<div class="modal fade" id="AddDlgModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title"><?= _("Add New Template") ?></h4>
			</div>
			<div class="modal-body">

				<div class="element-container">
					<div class="row">
						<div class="col-md-12">
							<div class="row">
								<div class="form-group">
									<div class="col-md-4">
										<label class="control-label" for="NewTemplateName"><?= _("Template Name")?></label>
										<i class="fa fa-question-circle fpbx-help-icon" data-for="NewTemplateName"></i>
									</div>
									<div class="col-md-8">
										<input type="text" class="form-control" id="NewTemplateName" name="NewTemplateName" value="" placeholder="<?= _("New Name Template...")?>">
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-12">
							<span class="help-block fpbx-help-block" id="NewTemplateName-help"><?= _("TODO: Help")?></span>
						</div>
					</div>
				</div>

				<div class="element-container">
					<div class="row">
						<div class="col-md-12">
							<div class="row">
								<div class="form-group">
									<div class="col-md-4">
										<label class="control-label" for="NewProductSelect"><?= _("Product Select")?></label>
										<i class="fa fa-question-circle fpbx-help-icon" data-for="NewProductSelect"></i>
									</div>
									<div class="col-md-8">
										<select class="form-control selectpicker show-tick" data-style="" data-live-search-placeholder="<?= _('Search') ?>" data-live-search="true" name="NewProductSelect" id="NewProductSelect">
											<option value=""><?= _("Select Product:")?></option>
											<?php foreach($template_list as $row) : ?>
												<option value="<?= $row['id'] ?>"><?= $row['short_name'] ?></option>
											<?php endforeach; ?>
										</select>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-12">
							<span class="help-block fpbx-help-block" id="NewProductSelect-help"><?= _("TODO: Help")?></span>
						</div>
					</div>
				</div>

				<div class="element-container">
					<div class="row">
						<div class="col-md-12">
							<div class="row">
								<div class="form-group">
									<div class="col-md-4">
										<label class="control-label" for="NewCloneModel"><?= _("Clone Template From") ?></label>
										<i class="fa fa-question-circle fpbx-help-icon" data-for="NewCloneModel"></i>
									</div>
									<div class="col-md-8">
										<select class="form-control selectpicker show-tick" data-style="" data-live-search-placeholder="<?= _('Search') ?>" data-live-search="true" name="NewCloneModel" id="NewCloneModel"></select>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-12">
							<span class="help-block fpbx-help-block" id="NewCloneModel-help"><?= _("TODO: Help")?></span>
						</div>
					</div>
				</div>

			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-danger" data-dismiss="modal"><i class='fa fa-times'></i> <?= _("Cancel")?></button>
				<button type="button" class="btn btn-primary" name="button_save" id="AddDlgModal_bt_new"><i class='fa fa-check'></i> <?= _("Save")?></button>
			</div>
		</div>
	</div>
</div>


	
	
 <?php
/*
 <script type="text/javascript" charset="utf-8">
 $(function(){
 $("select#model_class").change(function(){
 $.ajaxSetup({ cache: false });
 $.getJSON("config.php?type=tool&quietmode=1&handler=file&module=endpointman&file=ajax_select.html.php&atype=model_clone",{id: $(this).val()}, function(j){
 var options = '';
 for (var i = 0; i < j.length; i++) {
 options += '<option value="' + j[i].optionValue + '">' + j[i].optionDisplay + '</option>';
 }
 $("#model_clone").html(options);
 $('#model_clone option:first').attr('selected', 'selected');
 })
 })
 })
 </script>
 */