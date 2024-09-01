"use strict";
var cmeditor = null;

function epm_advanced_document_ready () {

	var arrayJs = ['assets/endpointman/js/addon/simplescrollbars.js', 'assets/endpointman/js/mode/xml.js', 'assets/endpointman/js/addon/fullscreen.js'];
	arrayJs.forEach(function (item, index, array) {
		var x = document.createElement('script');
		x.src = item;
		document.getElementsByTagName("head")[0].appendChild(x);
	});
	
	
	//TAB SETTING
	$('#settings input[type=text]').change(function(){ epm_advanced_tab_setting_input_change(this); });
	$('#settings input[type=radio]').change(function(){ epm_advanced_tab_setting_input_change(this); });
	$('#settings select').change(function(){ epm_advanced_tab_setting_input_change(this); });
	
	
	//TAB OUT_MANAGER
	$('#AddDlgModal').on('show.bs.modal', function (event) {
		$(this).find('input, select').val("");
	});
	$('#AddDlgModal_bt_new').on("click", function(){ epm_advanced_tab_oui_manager_bt_new(); });
}

function epm_advanced_windows_load (nTab = "") {
	epm_advanced_select_tab_ajax(nTab);
}

function epm_advanced_change_tab (nTab = "") {
	epm_advanced_select_tab_ajax(nTab);
}


// INI: FUNCTION GLOBAL SEC

function epm_advanced_select_tab_ajax(idtab = "")
{
	if (idtab === "") {
		fpbxToast('epm_advanced_select_tab_ajax -> id invalid!','JS!','warning');
		return false;
	}
	
	if (idtab === "poce")
	{
		epm_advanced_tab_poce_update_list_brand_bootnav();
		if (cmeditor === null) {
			cmeditor = CodeMirror.fromTextArea(document.getElementById("config_textarea"), {
				lineNumbers: true,
				matchBrackets: true,
				readOnly: true,
				viewportMargin: Infinity,
				scrollbarStyle: "simple",
				extraKeys: {
					"F11": function(cm) {
						cm.setOption("fullScreen", !cm.getOption("fullScreen"));
					},
					"Esc": function(cm) {
						if (cm.getOption("fullScreen")) cm.setOption("fullScreen", false);
					}
				}
			});
		}
	}
	else if (idtab === "manual_upload") {
		epm_advanced_tab_manual_upload_list_files_brand_expor();
	}
	return true;
}


function close_module_actions_epm_advanced(goback, acctionname = "")
{
	
}

function end_module_actions_epm_advanced(acctionname = "")
{
	if (acctionname === "manual_upload_bt_export_brand") {
		epm_advanced_tab_manual_upload_list_files_brand_expor();
	}
}

// END: FUNCTION GLOBAL SEC 








// INI: FUNCTION TAB UPLOAD_MANUAL

function epm_advanced_tab_manual_upload_bt_explor_brand() 
{
	var packageid = $('#brand_export_pack_selected').val();
	if (packageid === "") {
		alert ("You have not selected a brand from the list!");
	}
	else if (packageid < 0) {
		alert ("The id of the selected mark is invalid!");
	}
	else {
		var urlStr = "config.php?display=epm_advanced&subpage=manual_upload&command=export_brands_availables&package="+packageid;
		epm_global_dialog_action("manual_upload_bt_export_brand", urlStr);
	}
}

function epm_advanced_tab_manual_upload_bt_upload(command, formname)
{
	if ((command === "") || (formname === "")) { return; }
	var urlStr = "config.php?display=epm_advanced&subpage=manual_upload&command="+command;
	epm_global_dialog_action("manual_upload_bt_upload", urlStr, formname);
}

function epm_advanced_tab_manual_upload_list_files_brand_expor()
{
	// waitingDialog.show();
	epm_global_html_find_show_hide("#list-brands-export-item-loading", true, 0, true);
	if ($("#list-brands-export li.item-list-brand-export").length > 0) {
		$("#list-brands-export li.item-list-brand-export").hide("slow" , function () {
			$(this).remove();
			epm_advanced_tab_manual_upload_list_files_brand_expor();
		});
	}
	else {
		$.ajax({
			type: 'POST',
			url: window.FreePBX.ajaxurl,
			data: {
				module: "endpointman",
				module_sec: "epm_advanced",
				module_tab: "manual_upload",
				command: "list_files_brands_export"
			},
			dataType: 'json',
			timeout: 60000,
			error: function(xhr, ajaxOptions, thrownError) {
				fpbxToast('ERROR AJAX:' + thrownError,'ERROR (' + xhr.status + ')!','error');
				$("#list-brands-export").append($('<li/>', { 'class' : 'list-group-item item-list-brand-export text-center bg-warning' }).text('ERROR AJAX:' + thrownError));
				return false;
			},
			beforeSend: function(){
				epm_global_html_find_show_hide("#list-brands-export-item-loading", true, 0, true);
			},
			complete: function(){
				epm_global_html_find_show_hide("#list-brands-export-item-loading", false, 1000, true);
			},
			success: function(data) {
				if (data.status == true) {
					if (data.countlist == 0) {
						$("#list-brands-export").append($('<li/>', { 'class' : 'list-group-item item-list-brand-export'	}).text("Empty list").append($('<span/>', { 'class' : 'label label-default label-pill pull-xs-right' }).text("0")));
					}
					else {
						$(data.list_brands).each(function(index, itemData) 
						{
							$("#list-brands-export").append(
								$('<li/>', { 'class' : 'list-group-item item-list-brand-export', 'id' : 'item-list-brans-export-' + itemData.name })
								.append(
									$('<a/>', { 
										'data-toggle' 	: 'collapse',
										'href'			: '#box_list_files_brand_' + itemData.name,
										'aria-expanded'	: 'false',
										'aria-controls' : 'box_list_files_brand_' + itemData.name,
										'class'			: 'collapse-item list-group-item'
									})
									.append(
										$('<span/>', { 'class' : 'label label-default label-pill pull-xs-right'	}).text(itemData.num),
										$('<i/>',    { 'class' : 'fa fa-expand' })
									)
									.append(
										$("<span/>", {}).text(" " + itemData.name)
									)
								)
							);
							if (itemData.num > 0) {
								$('#item-list-brans-export-' + itemData.name).append(
									$('<div/>', {
										'class' : 'list-group collapse',
										'id' : 'box_list_files_brand_'+ itemData.name
									})
								);
							}
						});
						
						$(data.list_files).each(function(index, itemData) 
						{
							$('#box_list_files_brand_' + itemData.brand).append(
								$('<a/>', { 
									'href'	: 'config.php?display=epm_advanced&subpage=manual_upload&command=export_brands_availables_file&file_package=' + itemData.file,
									'target': '_blank',
									'class'	: 'list-group-item'
								})
								.append(
									$('<span/>', {'class' : 'label label-default label-pill pull-xs-right'}).text(itemData.timestamp),
									$('<i/>',    {'class' : 'fa fa-file-archive-o' })
								)
								.append($("<span/>", {}).text(" " + itemData.basename + " (" + itemData.size + " bytes)"))
							);
						});
					}
					
					//$('#manual_upload a.collapse-item').removeattr('onclick');
					$('#manual_upload a.collapse-item').on("click", function(){
						epm_global_html_css_name(this,"auto","active");
						$(this).blur();
					});
					
					//fpbxToast(data.message, '', 'success');
					return true;
				} 
				else {
					$("#list-brands-export").append( $('<li/>', { 'class' : 'list-group-item item-list-brand-export text-center bg-warning' }).text(data.message));
					fpbxToast(data.message, data.txt.error, 'error');
					return false;
				}
			},
		});
		// setTimeout(function () {waitingDialog.hide();}, 500);
	}
}

// END: FUNCTION TAB UPLOAD_MANUAL 








// INI: FUNCTION TAB IEDL
function epm_advanced_tab_iedl_bt_import() 
{
	var urlStr = "config.php?display=epm_advanced&subpage=iedl&command=import";
	var formname = "iedl_form_import_cvs";
	epm_global_dialog_action("iedlimport", urlStr, formname);
}
// END: FUNCTION TAB IEDL








// INI: FUNCTION TAB POCE

function epm_advanced_tab_poce_update_list_brand_bootnav(forzar=false)
{
	var nListO = $("#lista_brand_bootnav").children('a').get().length;
	var nListL = $("#lista_brand_bootnav").children('a.bootnavloadingajax').get().length;
	var nListT = nListO - nListL
	

	if (nListT > 0) {
		if (frozar !== true) { return; }
	}
	
	if ((nListL === 0) && (nListO > 0)) {
		$("#lista_brand_bootnav")
		.empty()
		.append(
			$('<a/>', { 'href' : '#', 'class' : 'list-group-item bootnavloadingajax text-center' })
			.append(
				$('<i/>', { 'class' : 'fa fa-spinner fa-spin' }),
				$('<span/>', {}).text(" " + "Loading...")
			)
		);
	}
	
	// waitingDialog.show();
	epm_advanced_tab_poce_clear_select();
	$.ajax({
		type: 'POST',
		url: window.FreePBX.ajaxurl,
		data: {
			module: "endpointman",
			module_sec: "epm_advanced",
			module_tab: "poce",
			command: "poce_list_brands",
		},
		dataType: 'json',
		timeout: 60000,
		error: function(xhr, ajaxOptions, thrownError) {
			fpbxToast('ERROR AJAX:' + thrownError,'ERROR (' + xhr.status + ')!','error');
			return false;
		},
		success: function(data) {
			if (data.status == true) {
				if (data.ldatos.length == 0) {
					$("#lista_brand_bootnav")
					.append(
						$('<a/>', { 'href' : '#', 'class' : 'list-group-item' })
						.append(
							$('<i/>', { 'class' : 'fa fa-phone fa-fw fa-lg' }),
							$('<span/>', {}).text(" " + "List Product's Empty")
						)
					);
				}
				else 
				{
					$(data.ldatos).each(function(index, itemData) {
						$("#lista_brand_bootnav")
						.append(
							$('<a/>', { 
								'href' 	: 'javascript:epm_advanced_tab_poce_select_product(' + itemData.id + ');', 
								'class' : 'list-group-item',
								'id'	: 'list_product_' + itemData.id,
								'title' : itemData.name
							})
							.append(
								$('<i/>', { 'class' : 'fa fa-phone fa-fw fa-lg' }),
								$('<span/>', {}).text(" " + itemData.name_mini)
							)
						);
					});
				}
				
				
				$("#lista_brand_bootnav a.bootnavloadingajax").remove();
//				fpbxToast('Load date Done!', '', 'success');
				return true;
			} 
			else {
				$("#lista_brand_bootnav a.bootnavloadingajax").text("Error get data!");
				fpbxToast(data.message, data.txt.error, 'error');
				return false;
			}
		},
	});
	// setTimeout(function () {waitingDialog.hide();}, 500);
	
}

function epm_advanced_tab_poce_clear_select()
{
	$("#poce_NameProductSelect").text("No Selected");
	$("#poce_file_name_path").text("No Selected");
	$('#config_textarea').prop('disabled', true);
	if (cmeditor !== null) {
		cmeditor.setValue("Select file to config...");
		cmeditor.setOption("readOnly",true);
	}
	$("#box_sec_source button").prop('disabled', true);
	$("#box_bt_save button").prop('disabled', true);
	$("#box_bt_share button").prop('disabled', true);
	$("#box_bt_save_as button").prop('disabled', true);
	$("#box_bt_save_as input").prop('disabled', true).val("");
	$('form[name=form_config_text_sec_button] input[name=datosok]').val("false");

	epm_advanced_tab_poce_create_file_list("#select_product_list_files_config", "");
	epm_advanced_tab_poce_create_file_list("#select_product_list_files_template_custom", "");
	epm_advanced_tab_poce_create_file_list("#select_product_list_files_user_config", "");
}

function epm_advanced_tab_poce_select_product(idsel = null, bclear = true)
{
	if ($.isNumeric(idsel) === false) { return; }
	$("div.list-group>a.active").removeClass("active");
	$("#list_product_"+idsel).addClass("active").blur();

	// waitingDialog.show();
	$.ajax({
		type: 'POST',
		url: window.FreePBX.ajaxurl,
		data: {
			module: "endpointman",
			module_sec: "epm_advanced",
			module_tab: "poce",
			command: "poce_select",
			product_select:  idsel
		},
		dataType: 'json',
		timeout: 60000,
		error: function(xhr, ajaxOptions, thrownError) {
			fpbxToast('ERROR AJAX:' + thrownError,'ERROR (' + xhr.status + ')!','error');
			return false;
		},
		success: function(data) {
			if (bclear == true) {
				epm_advanced_tab_poce_clear_select();
			}
			
			if (data.status == true) {
				epm_advanced_tab_poce_create_file_list("#select_product_list_files_config", data.file_list, data.product_select, "file");
				epm_advanced_tab_poce_create_file_list("#select_product_list_files_template_custom", data.template_file_list, data.product_select, "tfile");
				epm_advanced_tab_poce_create_file_list("#select_product_list_files_user_config", data.sql_file_list, data.product_select, "sql");
				
				if (bclear == true) {
					$("#poce_NameProductSelect").text(data.product_select_info.long_name);
				}
//				fpbxToast('Load date Done!', '', 'success');
				return true;
			} 
			else {
				epm_advanced_tab_poce_create_file_list("#select_product_list_files_config", "Error");
				epm_advanced_tab_poce_create_file_list("#select_product_list_files_template_custom", "Error");
				epm_advanced_tab_poce_create_file_list("#select_product_list_files_user_config", "Error");
				
				$("#poce_NameProductSelect").text("Error get data!");
				
				fpbxToast(data.message, data.txt.error, 'error');
				return false;
			}
		},
	});	
	// setTimeout(function () {waitingDialog.hide();}, 500);
}

function epm_advanced_tab_poce_create_file_list(idname, data = "", product_select = "", typefile = "") 
{
	$(idname + " div.dropdown-menu").empty();
	if (Array.isArray(data) === false)
	{
		$(idname + " span.label").text(0);
		if (data === null) { data = "Emtry"; }
		$(idname + " div.dropdown-menu")
		.append(
			$('<a/>', { 'href' : '#', 'class' : 'dropdown-item disable' }).text(data)
		);
		return;
	}
	$(idname + " span.label").text(data.length);
	$(data).each(function(index, itemData) 
	{
		$(idname + " div.dropdown-menu")
		.append(
			$('<a/>', { 
				'href' 	: 'javascript:epm_advanced_tab_poce_select_file_edit("'+ product_select +'", "'+ itemData.text +'", "'+ itemData.value +'", "'+ typefile +'");', 
				'class' : 'dropdown-item bt',
				'id'	: typefile + '_' +  product_select + '_' + itemData.text +'_'+ itemData.value 
			})
			.text(itemData.text)
		);
	});
	return;
}

function epm_advanced_tab_poce_select_file_edit (idpro_select, txtnamefile, idnamefile, typefile)
{
	// waitingDialog.show();
	$.ajax({
		type: 'POST',
		url: window.FreePBX.ajaxurl,
		data: {
			module: "endpointman",
			module_sec: "epm_advanced",
			module_tab: "poce",
			command: "poce_select_file",
			product_select:  idpro_select,
			file_id : idnamefile,
			file_name : txtnamefile,
			type_file : typefile
		},
		dataType: 'json',
		timeout: 60000,
		error: function(xhr, ajaxOptions, thrownError) {
			fpbxToast('ERROR AJAX:' + thrownError,'ERROR (' + xhr.status + ')!','error');
			$("#poce_file_name_path").text("Error ajax!");
			
			$('#config_textarea').prop('disabled', true);
			if (cmeditor !== null) {
				cmeditor.setValue("");
				cmeditor.setOption("readOnly",true);
			}
			$("#box_sec_source button").prop('disabled', true);
			$("#box_bt_save button").prop('disabled', true);
			$("#box_bt_share button").prop('disabled', true);
			$("#box_bt_save_as button").prop('disabled', true);
			$("#box_bt_save_as input").prop('disabled', true).val("");
			$('form[name=form_config_text_sec_button] input[name=datosok]').val("false");
			return false;
		},
		success: function(data) {
			if (data.status == true) {
				$("#poce_file_name_path").text(data.location);
				$('#config_textarea').prop('disabled', false);
				if (cmeditor !== null) {
					$("#box_sec_source button").prop('disabled', false);
					cmeditor.setValue(data.config_data);
					cmeditor.setOption("readOnly",false);
				}
				
				if (data.type === "file") {
					$("#box_bt_save button[name=button_save]").prop('disabled', false);
					$("#box_bt_save button[name=button_delete]").prop('disabled', true);
					
					$("#box_bt_save_as button").prop('disabled', false);
					$("#box_bt_save_as input").prop('disabled', false).val(data.save_as_name_value);
					
					$("#box_bt_share button").prop('disabled', true);
				}
				else if (data.type === "tfile") {
					$("#box_bt_save button").prop('disabled', true);
					$("#box_bt_share button").prop('disabled', true);
				
					$("#box_bt_save_as button").prop('disabled', true);
					$("#box_bt_save_as input").prop('disabled', true).val(data.save_as_name_value);
				}
				else if (data.type === "sql") {
					$("#box_bt_save button[name=button_save]").prop('disabled', false);
					$("#box_bt_save button[name=button_delete]").prop('disabled', false);
					
					$("#box_bt_save_as button").prop('disabled', false);
					$("#box_bt_save_as input").prop('disabled', false).val(data.save_as_name_value);
					
					$("#box_bt_share button").prop('disabled', true);
				}
				
				$('form[name=form_config_text_sec_button] input[name=type_file]').val(data.type);
				$('form[name=form_config_text_sec_button] input[name=sendid]').val(data.sendidt);
				$('form[name=form_config_text_sec_button] input[name=product_select]').val(data.product_select);
				$('form[name=form_config_text_sec_button] input[name=save_as_name]').val(data.save_as_name_value);
				$('form[name=form_config_text_sec_button] input[name=original_name]').val(data.original_name);
				$('form[name=form_config_text_sec_button] input[name=filename]').val(data.filename);
				$('form[name=form_config_text_sec_button] input[name=location]').val(data.location);
				$('form[name=form_config_text_sec_button] input[name=datosok]').val("true");
				
//				fpbxToast('File Load date Done!', '', 'success');
				return true;
			} 
			else {
				$("#poce_file_name_path").text("Error obteniendo datos!");
				$('#config_textarea').prop('disabled', true);ff
				if (cmeditor !== null) {
					cmeditor.setValue("");
					cmeditor.setOption("readOnly",true);
				}
				$("#box_sec_source button").prop('disabled', true);
				$("#box_bt_save button").prop('disabled', true);
				$("#box_bt_share button").prop('disabled', true);
				$("#box_bt_save_as button").prop('disabled', true);
				$("#box_bt_save_as input").prop('disabled', true).val("");
				$('form[name=form_config_text_sec_button] input[name=datosok]').val("false");
				fpbxToast(data.message, "Error!", 'error');
				return false;
			}
		},
	});	
	// setTimeout(function () {waitingDialog.hide();}, 500);
}

function epm_advanced_tab_poce_bt_acction (command)
{
	if (command === "") { return; }
	var obj_name = $(command).attr("name").toLowerCase();
	
	if (obj_name === "bt_source_full_screen")
	{
		cmeditor.setOption('fullScreen', !cmeditor.getOption('fullScreen'));
		return true;
	}
	
	if (epm_global_get_value_by_form("form_config_text_sec_button","datosok") === false)
	{
		fpbxToast("The form is not ready!", "Error!", 'error');
		return false;
	}
	
	var cfg_data = "";
	switch(obj_name) {
    	case "button_save":
    		if (confirm("Are you sure to save your changes will be overwritten irreversibly?") === false) { return; }
    		
    		cfg_data = {
    			module: "endpointman",
    			module_sec: "epm_advanced",
    			module_tab: "poce",
    			command: "poce_save_file",
    			type_file: epm_global_get_value_by_form("form_config_text_sec_button","type_file"),
    			sendid : epm_global_get_value_by_form("form_config_text_sec_button","sendid"),
    			product_select: epm_global_get_value_by_form("form_config_text_sec_button","product_select"),
    			save_as_name: epm_global_get_value_by_form("form_config_text_sec_button","save_as_name"),
    			original_name: epm_global_get_value_by_form("form_config_text_sec_button","original_name"),
    			file_name: epm_global_get_value_by_form("form_config_text_sec_button","filename"),
    			config_text: cmeditor.getValue()
    		};
    		break;
    	
    	case "button_save_as":
    		cfg_data = {
    			module: "endpointman",
    			module_sec: "epm_advanced",
    			module_tab: "poce",
    			command: "poce_save_as_file",
    			type_file: epm_global_get_value_by_form("form_config_text_sec_button","type_file"),
    			sendid : epm_global_get_value_by_form("form_config_text_sec_button","sendid"),
    			product_select: epm_global_get_value_by_form("form_config_text_sec_button","product_select"),
    			save_as_name: epm_global_get_value_by_form("form_config_text_sec_button","save_as_name"),
    			original_name: epm_global_get_value_by_form("form_config_text_sec_button","original_name"),
    			file_name: epm_global_get_value_by_form("form_config_text_sec_button","filename"),
    			config_text: cmeditor.getValue()
    		};
    		break;
    		
    	case "button_delete":
    		if (confirm("Are you sure you want to delete this file from the database?") === false) { return; }
    		
    		cfg_data = {
    			module: "endpointman",
    			module_sec: "epm_advanced",
    			module_tab: "poce",
    			command: "poce_delete_config_custom",
    			type_file : epm_global_get_value_by_form("form_config_text_sec_button","type_file"),
    			product_select: epm_global_get_value_by_form("form_config_text_sec_button","product_select"),
    			sql_select: epm_global_get_value_by_form("form_config_text_sec_button","sendid"),
    		};
    		break;
    	
    	case "button_share":
    		cfg_data = {
    			module: "endpointman",
    			module_sec: "epm_advanced",
    			module_tab: "poce",
    			command: "poce_sendid",
    			type_file : epm_global_get_value_by_form("form_config_text_sec_button","type_file"),
    			sendid : epm_global_get_value_by_form("form_config_text_sec_button","sendid"),
    			product_select: epm_global_get_value_by_form("form_config_text_sec_button","product_select"),
    			original_name: epm_global_get_value_by_form("form_config_text_sec_button","original_name"),
    			file_name: epm_global_get_value_by_form("form_config_text_sec_button","filename"),
    			config_text : cmeditor.getValue()
    		};
    		break;
    		
    	default:
    		alert ("Command not found!");
        	return false;
	}
	
	$.ajax({
		type: 'POST',
		url: window.FreePBX.ajaxurl,
		data: cfg_data,
		dataType: 'json',
		timeout: 60000,
		error: function(xhr, ajaxOptions, thrownError) {
			fpbxToast('ERROR AJAX:' + thrownError,'ERROR (' + xhr.status + ')!','error');
			$("#poce_file_name_path").text("Error ajax!");
			return false;
		},
		success: function(data) {
			if (data.status == true) {
				switch(obj_name) {
			    	case "button_save":
			    		
			    		epm_advanced_tab_poce_select_product(epm_global_get_value_by_form("form_config_text_sec_button","product_select"), false);
			    		fpbxToast(data.message, 'Save!', 'success');
			    		break;
			    	
			    	case "button_save_as":
			    		$('form[name=form_config_text_sec_button] input[name=type_file]').val(data.type_file);
						$('form[name=form_config_text_sec_button] input[name=sendid]').val(data.sendidt);
						$('form[name=form_config_text_sec_button] input[name=location]').val(data.location);
						
						$("#poce_file_name_path").text(data.location);
						$("#box_bt_save button").prop('disabled', false);
						$("#box_bt_share button").prop('disabled', false);
						$("#box_bt_save_as button").prop('disabled', false);
						$("#box_bt_save_as input").prop('disabled', false);
						
						epm_advanced_tab_poce_select_product(epm_global_get_value_by_form("form_config_text_sec_button","product_select"), false);
						fpbxToast(data.message, 'Save as!', 'success');
			    		break;
			    		
			    	case "button_delete":
			    		
			    		epm_advanced_tab_poce_select_product(epm_global_get_value_by_form("form_config_text_sec_button","product_select"));
			    		fpbxToast(data.message, 'Delete!', 'success');
			    		break;
			    	
			    	case "button_share":
			    		fpbxToast(data.message, 'Share!', 'success');
			    		break;
			    		
			    	default:
			    		fpbxToast(data.message, '', 'success');
				}
				return true;
			} 
			else {
				fpbxToast(data.message, "Error!", 'error');
				return false;
			}
		},
	});	
	
}
// END: FUNCTION TAB POCE








// INI: FUNCTION TAB OUI MANAGER
function epm_advanced_tab_oui_manager_grid_actionFormatter(value, row, index){
	var html = '';
    if (row.custom == 1) {
    	html += '<a href="javascript:epm_advanced_tab_oui_manager_bt_del('+value+')" class="delAction"><i class="fa fa-trash"></i></a>';
	}
    else {
    	html += '<i class="fa fa-trash"></i>';
    }
    return html;
}

function epm_advanced_tab_oui_manager_grid_customFormatter(value, row, index){
	var html = '';
    if (value == 1) {
    	html += '<i class="fa fa-pencil-square-o"></i> Custom';
	}
    else {
    	html += '<i class="fa fa-lock"></i> Required';
    }
    return html;
}


function epm_advanced_tab_oui_manager_refresh_table(showmsg = true)
{
	$("#mygrid").bootstrapTable('refresh');
	if (showmsg === true) {
		fpbxToast("Table Refrash Ok!", '', 'success');
	}
}

function epm_advanced_tab_oui_manager_bt_new()
{
	var new_oui = $("#number_new_oui").val().trim();
	var new_brand = $("#brand_new_oui").val();
	
	if (new_oui.length < "6") {
		fpbxToast('New: Input OUI not valid!','Warning!','warning');
	}
	else if (new_brand === "") {
		fpbxToast('New: No select Brand!','Warning!','warning');
	}
	else {
		var data_ajax = { module: "endpointman", module_sec: "epm_advanced", module_tab: "oui_manager", command: "oui_add", number_new_oui: new_oui, brand_new_oui: new_brand };
		if (epm_advanced_tab_oui_manager_ajax(data_ajax) === true) {
			fpbxToast("New OUI add Ok!", '', 'success');
			$("#mygrid").bootstrapTable('refresh');
			$("#AddDlgModal").modal('hide');
		}

		//epm_advanced_tab_oui_manager_ajax("new", data_ajax, objbox);
	}
}

function epm_advanced_tab_oui_manager_bt_del(id_del)
{
	if (id_del === "") {
		fpbxToast('Delete: No ID set!','Warning!','warning');
	}
	else {
		var data_ajax = { module: "endpointman", module_sec: "epm_advanced", module_tab: "oui_manager", command: "oui_del", id_del: id_del };
		if (epm_advanced_tab_oui_manager_ajax(data_ajax) === true) {
			fpbxToast("OUI delete Ok!", '', 'success');	
			$("#mygrid").bootstrapTable('refresh');
		}
		
		//epm_advanced_tab_oui_manager_ajax("del", data_ajax);
	}
}

function epm_advanced_tab_oui_manager_ajax (data_ajax = "")
{
	var response = false;
	if (data_ajax !== "") { 
		$.ajax({
	        async: false,
			type: 'POST',
			url: window.FreePBX.ajaxurl,
			data:  data_ajax,
			dataType: 'json',
			timeout: 60000,
			error: function(xhr, ajaxOptions, thrownError) {
				fpbxToast('ERROR AJAX:' + thrownError,'ERROR (' + xhr.status + ')!','error');
				return false;
			},
			success: function(data) {
				if (data.status === true) {
					response  = true;
				} 
				else {
					fpbxToast(data.message, "Error!", 'error');
					response  = false;
				}
			}
		});
	}
	return response;
}
// END: FUNCTION TAB OUI MANAGER




// INI: FUNCTION TAB SETTING
function epm_advanced_tab_setting_input_value_change_bt(sNameID = "", sValue = "", bSaveChange = true, bSetFocus = false)
{
	if (sNameID === "" ) { return false; }
	
	epm_global_input_value_change_bt(sNameID, sValue, bSetFocus);
	if (bSaveChange === true) {
		epm_advanced_tab_setting_input_change(sNameID);
	}
}

function epm_advanced_tab_setting_input_change(obt)
{
	var idtab = epm_global_get_tab_actual();
	if (idtab === "") {
		fpbxToast(_('ERROR: Missing tabs!'), 'error');
		return false;
	}
	
	var obt_name = $(obt).attr("name").toLowerCase();
	var obt_val = $(obt).val();
	if (obt_val == null) {
		fpbxToast(_('ERROR: Value is Null!'), 'error');
		return false;
	}
	obt_val = obt_val.toLowerCase();

	$.ajax({
		type: 'POST',
		url: window.FreePBX.ajaxurl,
		data: {
			module: "endpointman",
			module_sec: "epm_advanced",
			module_tab: idtab,
			command: "saveconfig",
			name:  obt_name,
			value: obt_val
		},
		dataType: 'json',
		timeout: 60000,
		error: function(xhr, ajaxOptions, thrownError) {
			fpbxToast( sprintf(_('ERROR AJAX: %s'), thrownError),sprintf('ERROR (%s)!', xhr.status), 'error');
			$("#" + obt_name + "_no").attr("disabled", true).prop( "checked", false);
			$("#" + obt_name + "_yes").attr("disabled", true).prop( "checked", false);
			return false;
		},
		success: function(data) {
			if (data.status == true) {
				if (obt_val == "1")
				{
					//true
				}
				else 
				{
					//false
				}
				fpbxToast(data.txt.save_changes_ok, '', 'success');
				//if (data.reload == true) { location.reload(); }
				//if (data.name == "tftp_check") { location.reload(); }
				//if (data.name == "use_repo") { location.reload(); }
				
				
				return true;
			} 
			else {
				fpbxToast(data.message, data.txt.error, 'error');
				$("#" + obt_name + "_no").attr("disabled", true).prop("checked", false);
				$("#" + obt_name + "_yes").attr("disabled", true).prop("checked", false);
				return false;
			}
		},
	});	
}