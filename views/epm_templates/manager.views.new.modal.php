<div class="modal fade" id="AddDlgModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title"><?php echo _("Add New Template") ?></h4>
			</div>
			<div class="modal-body">

				<div class="element-container">
					<div class="row">
						<div class="col-md-12">
							<div class="row">
								<div class="form-group">
									<div class="col-md-4">
										<label class="control-label" for="NewTemplateName"><?php echo _("Template Name")?></label>
										<i class="fa fa-question-circle fpbx-help-icon" data-for="NewTemplateName"></i>
									</div>
									<div class="col-md-8">
										<input type="text" class="form-control" id="NewTemplateName" name="NewTemplateName" value="" placeholder="<?php echo _("New Name Template....")?>">
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-12">
							<span class="help-block fpbx-help-block" id="NewTemplateName-help"><?php echo _("Texto Ayuda.")?></span>
						</div>
					</div>
				</div>

				<div class="element-container">
					<div class="row">
						<div class="col-md-12">
							<div class="row">
								<div class="form-group">
									<div class="col-md-4">
										<label class="control-label" for="NewProductSelect"><?php echo _("Product Select")?></label>
										<i class="fa fa-question-circle fpbx-help-icon" data-for="NewProductSelect"></i>
									</div>
									<div class="col-md-8">
										<select class="form-control" name="NewProductSelect" id="NewProductSelect">
											<option value=""><?php echo _("Select Product:")?></option>
											<?php
											$sql = "SELECT DISTINCT endpointman_product_list.* FROM endpointman_product_list, endpointman_model_list WHERE endpointman_product_list.id = endpointman_model_list.product_id AND endpointman_model_list.hidden = 0 AND endpointman_model_list.enabled = 1 AND endpointman_product_list.hidden != 1 AND endpointman_product_list.cfg_dir !=  ''";
											$template_list = sql($sql, 'getAll', DB_FETCHMODE_ASSOC);
											foreach($template_list as $row) {
												echo '<option value="'.$row['id'].'">'.$row['short_name'].'</option>';
											}
											unset ($template_list);
											unset ($sql);
											?>
		        						</select>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-12">
							<span class="help-block fpbx-help-block" id="NewProductSelect-help"><?php echo _("Texto Ayuda.")?></span>
						</div>
					</div>
				</div>

				<div class="element-container">
					<div class="row">
						<div class="col-md-12">
							<div class="row">
								<div class="form-group">
									<div class="col-md-4">
										<label class="control-label" for="NewCloneModel"><?php echo _("Clone Template From")?></label>
										<i class="fa fa-question-circle fpbx-help-icon" data-for="NewCloneModel"></i>
									</div>
									<div class="col-md-8">
										<select class="form-control" name="NewCloneModel" id="NewCloneModel"></select>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-12">
							<span class="help-block fpbx-help-block" id="NewCloneModel-help"><?php echo _("Texto Ayuda.")?></span>
						</div>
					</div>
				</div>

			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-danger" data-dismiss="modal"><i class='fa fa-times'></i> <?php echo _("Cancel")?></button>
				<button type="button" class="btn btn-primary" name="button_save" id="AddDlgModal_bt_new"><i class='fa fa-check'></i> <?php echo _("Save")?></button>
			</div>
		</div>
	</div>
</div>
