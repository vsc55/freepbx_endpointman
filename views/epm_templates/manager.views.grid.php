<div class="container-fluid">
	<div class="row">
		<div class="col-sm-12">
			<div id="toolbar-grid">
				<button type="button" class="btn btn-primary btn-lg" data-toggle="modal" data-target="#AddDlgModal"><i class='fa fa-plus'></i> <?php echo _('Add New Template')?></button>
				<a class='btn btn-default' href="javascript:epm_global_refresh_table('#mygrid', true);" ><i class='fa fa-refresh fa-spin'></i> <?php echo _('Refresh Table')?></a>
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
						<th data-field="name" data-sortable="true"><?php echo _("Template Name")?></th>
						<th data-field="model_class" data-sortable="true"><?php echo _("Model Classification")?></th>
						<th data-field="model_clone" data-sortable="true"><?php echo _("Model Clone")?></th>
						<th data-field="enabled" data-sortable="true" data-formatter="epm_templates_grid_FormatThEnabled"><?php echo _("Enabled")?></th>
						<th data-field="id" data-formatter="epm_templates_grid_FormatThAction"><?php echo _("Action")?></th>
					</tr>
				</thead>
			</table>
			
		</div>
	</div>
</div>