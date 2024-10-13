"use strict";
var cmeditor = null;

function epm_templates_document_ready () {
	
	var arrayJs = [
		'assets/endpointman/js/addon/simplescrollbars.js',
		'assets/endpointman/js/mode/xml.js'
	];
	
	arrayJs.forEach(function (item, index, array) {
		var x = document.createElement('script');
		x.src = item;
		document.getElementsByTagName("head")[0].appendChild(x);
	});
	

	$('#epm_template_refresh').on("click", function() {
		$("#epm_templates_grid").bootstrapTable('refresh');
		fpbxToast(_("Refreshed Successfully!"), '', 'success');
	});


	$('#modal_add_tempalte')
	.on('show.bs.modal', function (event)
	{
		var modal			= $(this);
		var select_products = $('#modal_form_new_template_products');

		// Check if the ajaxState is initialized
		if (typeof modal.data('ajaxState') === 'undefined')
		{
			// Initialize in the first time in mode false
			modal.data('ajaxState', 'not-started');
		}

		// Check if the ajax request is in progress
		switch (modal.data('ajaxState'))
		{
			case 'in-progress':
				// Ajax request in progress, prevent the modal from opening
				event.preventDefault();
				return;
	
			case 'completed':
				// Ajax request completed, allow the modal to open without making the ajax request again
				return;
	
			case 'not-started':
				// Ajax request not started, make the ajax request now
				modal.data('ajaxState', 'in-progress');
				waitingDialog.show();
				break;
	
			default:
				// Unknown state, prevent the modal from opening
				event.preventDefault();
				return;
		}

		modal.find('input[type="text"]').val("");
		modal.find('select').empty().selectpicker('refresh');

		epm_template_new_list_products(modal, select_products, function(status, data) {
			if (status === true)
			{
				modal.data('ajaxState', 'completed');
				modal.modal('show');
			}
			else
			{
				modal.data('ajaxState', 'not-started');
				event.preventDefault(); // Prevent the opening of the modal opening
			}
			waitingDialog.hide();
			return status;
		});

		// Prevent the opening of the modal until the ajax request is completed
		return false;

	}).on('hidden.bs.modal', function (event)
	{
		// Clear the input fields and select elements when the modal is closed
		var modal = $(this);
		modal.find('input[type="text"]').val('');
		modal.find('select').empty().selectpicker('refresh');
		modal.data('ajaxState', 'not-started');
    });

	$('#epm_template_new_modal_btn_refresh_products').on('click', function()
	{
		var modal = $("#modal_add_tempalte");

		// Clear options in the all select elements in the modal
		modal.find('select').empty().selectpicker('refresh');

		var select_product = $('#modal_form_new_template_products');
		epm_template_new_list_products(modal, select_product);
	});

	$('#epm_template_new_modal_btn_refresh_model_clone').on('click', function() {

		var modal 		 = $("#modal_add_tempalte");
		var select_model = $('#modal_form_new_template_model_clone');
		epm_template_new_list_model_clone(modal, select_model);
	});

	$('#modal_form_new_template_products').on('change', function()
	{
		var modal 		   = $("#modal_add_tempalte");
		var product_select = $(this).val();
		var select_model   = $('#modal_form_new_template_model_clone');

		// Clean the select elements since the product may not have been selected
		select_model.empty().selectpicker('refresh');

		if (product_select === "")
		{
			fpbxToast(_("Please Select a Product!"), '', 'warning');
		}
		else
		{
			epm_template_new_list_model_clone(modal, select_model, product_select);
		}
	});

	$('#modal_form_new_template_btn_add').on('click', function()
	{
		var $nameTemplate = $('#modal_form_new_template_name_template');
		var $productSelec = $('#modal_form_new_template_products');
		var $cloneModel	 = $('#modal_form_new_template_model_clone');

		var nameTemplate = $nameTemplate.val();
		var productSelec = $productSelec.val();
		var cloneModel	 = $cloneModel.val();


		const fieldsToValidate = [
			{ field: $nameTemplate, isSelectpicker: false },
			{ field: $productSelec, isSelectpicker: true },
			{ field: $cloneModel, isSelectpicker: true }
		];
		
		let hasEmptyField = false;
		fieldsToValidate.forEach(({ field, isSelectpicker }) => {
			if (typeof field.val() !== 'string' || field.val() === "")
			{
				field.addClass('is-invalid');
				hasEmptyField = true;
			}
			else
			{
				field.removeClass('is-invalid');
				if (isSelectpicker)
				{
					// Fix remove is-invalid class from selectpicker
					// https://github.com/snapappointments/bootstrap-select/issues/2304
					field.parent().removeClass('is-invalid');
				}
			}
			if (isSelectpicker)
			{
				field.selectpicker('refresh');
			}
		});

		if (hasEmptyField) {
			fpbxToast(_("Please fill in all fields!"), '', 'warning');
			return false;
		}

		$.ajax({
			type: 'POST',
			url: window.FreePBX.ajaxurl,
			data: {
				'module'		: "endpointman",
				'module_sec'	: "epm_templates",
				'module_tab'	: "manager",
				'command'		: "add_template",
				'new_template'	: {
					'name'	 : nameTemplate,
					'product': productSelec,
					'model'	 : cloneModel
				}
			},
			dataType: 'json',
			timeout: 60000,
			error: function(xhr, ajaxOptions, thrownError)
			{
				fpbxToast( sprintf(_('ERROR AJAX (%s): %s'), xhr.status, thrownError), '', 'error');
				return false;
			},
			success: function(data)
			{
				fpbxToast(data.message, '', data.status ? 'success' : 'error');
				if (data.status == true) 
				{
					var redirect = data.redirect ?? '';
					if (redirect !== '')
					{
						var delay = data.redirect_delay ?? 500;
						setTimeout(function() { window.location.href = redirect; }, delay);
					}
				} 
			}
		});
	});
	

	$(document).on("click", ".epm_template_btn_edit_row", function() {
		var $button  = $(this);
		var $row 	 = $button.closest('tr');						// Find the parent row of the button (the <tr>)
		var $table 	 = $row.closest('table');						// Find the parent table of the button
		var rowIndex = $row.data('index');							// Get the row index from the 'data-index' attribute that Bootstrap Table uses
		var rowData  = $table.bootstrapTable('getData')[rowIndex]; 	// Get the row data using the index (this assumes the table uses Bootstrap Table)

		if (rowData && rowData.id)
		{
			window.location.href = sprintf('?display=epm_templates&subpage=edit&custom=%s&idsel=%s', rowData.custom, rowData.id);
		}
		else {
			fpbxToast(_("The row data is invalid!"), '', 'warning');
		}
	});
	
	$(document).on("click", ".epm_template_btn_remove_row", function() {
		var $button  = $(this);
		var $row 	 = $button.closest('tr');						// Find the parent row of the button (the <tr>)
		var $table 	 = $row.closest('table');						// Find the parent table of the button
		var rowIndex = $row.data('index');							// Get the row index from the 'data-index' attribute that Bootstrap Table uses
		var rowData  = $table.bootstrapTable('getData')[rowIndex]; 	// Get the row data using the index (this assumes the table uses Bootstrap Table)

		if (rowData && rowData.id)
		{
			fpbxConfirm(
				sprintf(_("Are you sure to delete Template '%s'?"), rowData.name),
				_("YES"), _("NO"),
				function()
				{
					var data_ajax = { 
						'module'	  : "endpointman",
						'module_sec'  : "epm_templates",
						'module_tab'  : "manager",
						'command'	  : "del_template",
						'id_template' : rowData.id
					};
					var result = epm_gloabl_manager_ajax(data_ajax, function(status, data) {
						if (status === true)
						{
							fpbxToast(data.message, '', 'success');
							$table.bootstrapTable('refresh');
						}
					});
					if (result === false)
					{
						fpbxToast(_("Data Ajax is invalid!"), '', 'error');
					}
				}
			);
		}
		else {
			fpbxToast(_("The row data is invalid!"), '', 'warning');
		}
	});



	

	// //http://kevinbatdorf.github.io/liquidslider/examples/page1.html#right
	// $('#main-slider').liquidSlider({
	// 	includeTitle:false,
	// 	continuous:false,
	// 	slideEaseFunction: "easeInOutCubic",
	// 	preloader:true,
	// 	onload: function() {
	// 		this.alignNavigation();
	// 		$('.liquid-slider').css('visibility', 'visible');
	// 	}
	// });
		
	// //Al iniciar la apertura de la ventana
	// $('#CfgGlobalTemplate').on('show.bs.modal', function (e) {
	// 	epm_template_custom_config_get_global(e);	
	// });
	
	// //Al finalizar la apertura de la ventana	$('#CfgGlobalTemplate').on('shown.bs.modal', function (e) { });
	// //Antes de iniciar el cierre de la ventana	$('#CfgGlobalTemplate').on('hide.bs.modal', function (e) { });
	// //Despues de Cerrar la ventana				$('#CfgGlobalTemplate').on('hidden.bs.modal', function (e) { });
	
	
	
	
	
	// $('#CfgEditFileTemplate').on('show.bs.modal', function (e) {         });	
	
	// $('#CfgEditFileTemplate').on('shown.bs.modal', function (e) {
	// 	if (cmeditor === null) {
	// 		cmeditor = CodeMirror.fromTextArea(document.getElementById("config_textarea"), {
	// 			lineNumbers: true,
	// 			matchBrackets: true,
	// 			readOnly: false,
	// 			viewportMargin: Infinity,
	// 			scrollbarStyle: "simple"
	// 		});
	// 	}
	// 	cmeditor.setValue(document.getElementById("config_textarea").value);
	// });
	
	// $('#CfgEditFileTemplate').on('hidden.bs.modal', function (e) {
	// 	/* DESPUES DE CERRAR: CODIGO QUE ACTUALIZA EL SELECT... */
		
	// 	$('#edit_file_name_path').val("No Selected");
	// 	$('#config_textarea').val("");
	// 	cmeditor.setValue("");
	// });
	// $('#CfgEditFileTemplate').on('hidden.bs.modal', function (e) {        });
	
		
		
		
	// $('.files_edit_configs button').click(function(e){
	// 	var NameBox = e.target.parentNode.parentNode.id;
	// 	var NameBoxSel = "sl_" + NameBox;
	// 	var ValueSel = $('#' + NameBoxSel).val();
				
	// 	var ids =  ValueSel.split("_", 1);
	// 	var NameFile = ValueSel.substr( ValueSel.lastIndexOf("_") + 1 , ValueSel.len);

	// 	if (ids[0] == "0"){
	// 		$('#edit_file_name_path').text(NameFile);
	// 		$('#config_textarea').val("Texto 456");
	// 	}
	// 	else 
	// 	{
	// 		$('#edit_file_name_path').text("SQL:" + NameFile);
	// 		$('#config_textarea').val("Texto 789");
	// 	}
	// 	$('#CfgEditFileTemplate').modal('show');

	// });
	
	// $('select[class~="selectpicker"][data-url]').each(function(index, value) { epm_template_update_select_files_config($(this)); });
}

function epm_templates_windows_load (nTab = "") { }
function epm_templates_change_tab (nTab = "") 	{ }



/*** NEW TEMPLATE ***/

function epm_template_new_list_products(modal, select_list, callback)
{
	var data = {
		'module'		: "endpointman",
		'module_sec'	: "epm_templates",
		'module_tab'	: "manager",
		'command'		: "add_products_list",
	}
	return epm_global_options_list_ajax(modal, select_list, data, callback);
}

function epm_template_new_list_model_clone(modal, select_list, product_select, callback)
{
	var data = {
		'module'		: "endpointman",
		'module_sec'	: "epm_templates",
		'module_tab'	: "manager",
		'command'		: "model_clone",
		'product_select': product_select
	}
	return epm_global_options_list_ajax(modal, select_list, data, callback);
}

function epm_templates_grid_FormatThEnabled(value, row, index)
{
	var html = '<i class="fa %s fa-lg"></i> %s';
    if (value == 1)
	{
		html = sprintf(html, "fa-check-square-o", _("Enabled"));
	}
    else
	{
		html = sprintf(html, "fa-square-o", _("Disabled"));
    }
    return html;
}

function epm_templates_grid_FormatThAction(value, row, index)
{
	var $div = $('<div>', {
		class: 'btn-group',
		role: 'group'
	});

	// Button Edit
	var $editButton = $('<button>', {
		type: 'button',
		class: 'btn btn-primary action-btn epm_template_btn_edit_row'
	}).append($('<i>', {
		class: 'fa fa-edit',
		'aria-hidden': 'true'
	}));

	// Button Remove
	var $removeButton = $('<button>', {
		type: 'button',
		class: 'btn btn-primary action-btn epm_template_btn_remove_row'
	}).append($('<i>', {
		class: 'fa fa-trash',
		'aria-hidden': 'true'
	}));
	
	// Add the buttons to the 'div' container
	$div.append($editButton).append($removeButton);

	// Return the outerHTML of the 'div' container
	return $div.prop('outerHTML');
}











function epm_template_update_select_files_config (e) {
	var select = e;
	var url    = e.attr('data-url');
	var id     = e.attr('data-id');
	var label  = e.attr('data-label');
	select.html('');
	select.append('<option data-icon="fa fa-refresh fa-spin fa-fw" value="" selected>Loading...</option>');
	select.selectpicker('refresh');
	$.getJSON(url, function(data)
	{
		select.html('');
		$.each(data.only_configs, function(key, val)
		{
			if (val['select'] == "ON") {
				select.append('<option data-icon="fa fa-files-o" value="' + val[id] + '_' + val[label] + '" selected>' + val[label] + ' (No Change)</option>');
			}
			else {
				select.append('<option data-icon="fa fa-files-o" value="' + val[id] + '_' + val[label] + '">' + val[label] + ' (No Change)</option>');
			}
		});
		if (data.alt_configs != null) 
		{
			select.append('<optgroup label="Modificaiones"></optgroup>');
			var seloptgroup = select.find("optgroup");
			$.each(data.alt_configs, function(key, val)
			{
				if (val['select'] == "ON") {
					seloptgroup.append('<option data-icon="fa fa-pencil-square-o" style="background: #5cb85c; color: #fff;" value="' + val[id] + '_' + val[label] + '" selected>' + val[label] + '</option>');
				}
				else {
					seloptgroup.append('<option data-icon="fa fa-pencil-square-o" style="background: #5cb85c; color: #fff;" value="' + val[id] + '_' + val[label] + '">' + val[label] + '</option>');
				}
			});
			
		};
		select.selectpicker('refresh');
	});
}




// $("#table-all-side").on('click-row.bs.table',function(e,row,elem){
// 	window.location = '?display=epm_templates&subpage=edit&custom='+row['custom']+'&idsel='+row['id'];
// })



function epm_template_custom_config_get_global(elmnt)
{
	$.ajax({
		type: 'POST',
		url: window.FreePBX.ajaxurl,
		data: {
			module: "endpointman",
			module_sec: "epm_templates",
			module_tab: "edit",
			command: "custom_config_get_gloabl",
			custom : $.getUrlVar('custom'),
			tid : $.getUrlVar('idsel')
		},
		dataType: 'json',
		timeout: 60000,
		error: function(xhr, ajaxOptions, thrownError) {
			fpbxToast('ERROR AJAX:' + thrownError,'ERROR (' + xhr.status + ')!','error');
			return false;
		},
		success: function(data) {
			if (data.status == true) 
			{
				epm_global_input_value_change_bt("#srvip", data.settings.srvip, false);
				epm_global_input_value_change_bt("#server_type", data.settings.server_type, false);
				epm_global_input_value_change_bt("#config_loc", data.settings.config_location, false);
				epm_global_input_value_change_bt("#tz", data.settings.tz, false);
				epm_global_input_value_change_bt("#ntp_server", data.settings.ntp, false);
				
				if (elmnt.name == "button_undo_globals") {
					fpbxToast(data.message, '', 'success');
				}
			} 
			else { fpbxToast(data.message, "Error!", 'error'); }
		}
	});
}

function epm_template_custom_config_update_global(elmnt)
{
	$.ajax({
		type: 'POST',
		url: window.FreePBX.ajaxurl,
		data: {
			module: "endpointman",
			module_sec: "epm_templates",
			module_tab: "edit",
			command: "custom_config_update_gloabl",
			custom : $.getUrlVar('custom'),
			tid : $.getUrlVar('idsel'),
			tz: epm_global_get_value_by_form("FormCfgGlobalTemplate","tz"),
			ntp_server: epm_global_get_value_by_form("FormCfgGlobalTemplate","ntp_server"),
			srvip: epm_global_get_value_by_form("FormCfgGlobalTemplate","srvip"),			
			config_loc: epm_global_get_value_by_form("FormCfgGlobalTemplate","config_loc"),
			server_type: epm_global_get_value_by_form("FormCfgGlobalTemplate","server_type")
		},
		dataType: 'json',
		timeout: 60000,
		error: function(xhr, ajaxOptions, thrownError) {
			fpbxToast('ERROR AJAX:' + thrownError,'ERROR (' + xhr.status + ')!','error');
			return false;
		},
		success: function(data) {
			if (data.status == true) 
			{
				fpbxToast(data.message, '', 'success');
			} 
			else { fpbxToast(data.message, "Error!", 'error'); }
		}
	});	
}

function epm_template_custom_config_reset_global(elmnt)
{
	$.ajax({
		type: 'POST',
		url: window.FreePBX.ajaxurl,
		data: {
			module: "endpointman",
			module_sec: "epm_templates",
			module_tab: "edit",
			command: "custom_config_reset_gloabl",
			custom : $.getUrlVar('custom'),
			tid : $.getUrlVar('idsel')
		},
		dataType: 'json',
		timeout: 60000,
		error: function(xhr, ajaxOptions, thrownError) {
			fpbxToast('ERROR AJAX:' + thrownError,'ERROR (' + xhr.status + ')!','error');
			return false;
		},
		success: function(data) {
			if (data.status == true) 
			{
				fpbxToast(data.message, '', 'success');
				epm_template_custom_config_get_global(elmnt);
			} 
			else { fpbxToast(data.message, "Error!", 'error'); }
		}
	});
}



function epm_template_edit_select_area_list (obj)
{
	
	var maxlines = obj.options[obj.selectedIndex].value;
	var id = epm_global_get_value_by_form("epm_template_edit_form", "id");
	
	var silent_mode = $.getUrlVar('silent_mode');
	if (silent_mode == true) 
	{
		
		alert ("true");
		

		if (id == 0) {
			fpbxToast("No Device Selected to Edit!!", "Error!", 'error');
		}
		else {

			
			/*
			model_list = 126
			template_list = 0
			and = new Date().getTime()
		
	    <?php if (isset($_REQUEST['silent_mode'])) { echo '<input name="silent_mode" id="silent_mode" type="hidden" value="1">'; } ?>
		<input name="" id="id" type="hidden" value="<?php echo $dtemplate['hidden_id']; ?>">
		<input name="custom" id="custom" type="hidden" value="<?php echo $dtemplate['hidden_custom'] ; ?>">

		
			// --> PHP
			$template_editor = TRUE;
			$sql = "UPDATE  endpointman_mac_list SET  model =  '".$_REQUEST['model_list']."' WHERE  id =".$_REQUEST['edit_id']; -> id cambiar por template_id
			$endpoint->eda->sql($sql);
			$endpoint->tpl->assign("silent_mode", 1);
	
			if ($_REQUEST['template_list'] == 0) {
				$endpoint->edit_template_display($_REQUEST['edit_id'],1);
			} else {
				$endpoint->edit_template_display($_REQUEST['template_list'],0);
			}
			// <-- PHP
		*/	
		}
	}
	else 
	{
		var custom = $.getUrlVar('custom');
		window.location.href='config.php?display=epm_templates&subpage=edit&custom=' + custom + '&idsel=' + id + '&maxlines=' + maxlines
	}
	
}







	
	/*
		//edit
		//<a href="#" onclick="return popitup('config.php?type=tool&display=epm_config&amp;quietmode=1&amp;handler=file&amp;file=popup.html.php&amp;module=endpointman&amp;pop_type=alt_cfg_edit', '<?php echo $row['name']; ?>')">
		function popitup(url, name) {
            newwindow=window.open(url + '&custom=' + document.getElementById('custom').value + '&tid=' + document.getElementById('id').value + '&value=' + document.getElementById('altconfig_'+ name).value + '&rand=' + new Date().getTime(),'name','height=710,width=800,scrollbars=yes,location=no');
                if (window.focus) {newwindow.focus()}
                return false;
        }
		//edit
		//<a href='#' onclick='return popitup2("config.php?type=tool&display=epm_config&amp;quietmode=1&amp;handler=file&amp;file=popup.html.php&amp;module=endpointman&amp;pop_type=alt_cfg_edit", "<?php echo $row['name']?>")'>
        function popitup2(url, name) {
            newwindow=window.open(url + '&custom=' + document.getElementById('custom').value + '&tid=' + document.getElementById('id').value + '&value=0_' + name + '&rand=' + new Date().getTime(),'name','height=700,width=800,scrollbars=yes,location=no');
                if (window.focus) {newwindow.focus()}
                return false;
        }
		

*/	