"use strict";
var box = null;

$(document).ready(function() {
	var displayActual = epm_global_getDisplayActual();
	
	$('ul[role=tablist] li a').on("click", function(){
		var tabclick =  $(this).attr('aria-controls');
		if (tabclick !== "") {
			if (displayActual !== "") {
				var func = displayActual + "_change_tab";
				if (typeof window[func] === 'function') { 
					setTimeout(function () { window[func](tabclick); }, 500);
				}
			}
		}
	});
	
	if (displayActual !== "") {
		var func = displayActual + "_document_ready";
		if (typeof window[func] === 'function') { 
			window[func]();
		}
	}
	
});

$(window).load(function() {
	var displayActual = epm_global_getDisplayActual();
	if (displayActual !== "") {
		var func = displayActual + "_windows_load";
		if (typeof window[func] === 'function') { 
			window[func](epm_global_get_tab_actual());
		}
	}
});


function epm_global_getDisplayActual ()
{
	var displayActual = $.getUrlVar('display');
	displayActual = displayActual.replace("#", ""); 
	return displayActual;
}


function epm_global_html_find_hide_and_remove(name = "", tDelay = 1, bSlow = false) {
	if ($(name).length > 0) {
		$(name).delay(tDelay).hide(((bSlow === true)  ? "slow" : ""), function () {
			$(this).remove();
		});
	}
}

function epm_global_html_find_show_hide(name = "", bShow = "auto", tDelay = 1, slow = false) {
	if ($(name).length > 0) {
		if (bShow === true) 		{ $(name).delay(tDelay).show(((slow === true)  ? "slow" : "")); }
		else if (bShow === false)	{ $(name).delay(tDelay).hide(((slow === true)  ? "slow" : "")); }
		else if (bShow === "auto")	{
			if( $(name).is(":visible") ) {
				$(name).delay(tDelay).hide(((slow === true)  ? "slow" : "")); 
			} else{
				$(name).delay(tDelay).show(((slow === true)  ? "slow" : ""));
			}
		}
	}
}

function epm_global_html_css_name(name, bStatus, classname)
{
	if ($(name).length > 0) {
		if (bStatus === true) 			{ $(name).addClass(classname); }
		else if (bStatus === false)		{ $(name).removeClass(classname); }
		else if (bStatus === "auto")	{
			if($(name).hasClass(classname)) { $(name).removeClass(classname); }
			else							{ $(name).addClass(classname); }
		}
	}
}

function epm_global_get_tab_actual()
{
	var sTab = "";
	$("ul[role=tablist] li.active a").each(function() {
		sTab = $(this).attr('aria-controls');
	});
	return sTab;
}

function epm_global_get_value_by_form(sform, snameopt, formtype = "name")
{
	var rdata = null;
	$('form['+formtype+'='+sform+']')
	.find("input, textarea, select")
	.each( function(index) {
		var input = $(this);
		if (snameopt === input.attr('name'))
		{
			rdata = input.val();
		}
	});
	return rdata;
}

//http://oldblog.jesusyepes.com/jquery/limpiar-todos-los-campos-de-un-formulario-con-jquery/
function epm_global_limpiaForm(miForm) {
	// recorremos todos los campos que tiene el formulario
	$(':input', miForm).each(function() {
		var type = this.type;
		var tag = this.tagName.toLowerCase();
		//limpiamos los valores de los campos…
		if (type == 'text' || type == 'password' || tag == 'textarea')
			this.value = "";
			// excepto de los checkboxes y radios, le quitamos el checked
			// pero su valor no debe ser cambiado
		else if (type == 'checkbox' || type == 'radio')
			this.checked = false;
			// los selects le ponesmos el indice a -
		else if (tag == 'select')
			this.selectedIndex = -1;
	});
}




function epm_global_dialog_action(actionname = "", urlStr = "", formname = null, titleStr = "Status", ClassDlg = "", buttons = "", $ShowCloseBtn = true)
{
	var oData = null;

	if ((actionname === "") || (urlStr === "")) { return null; }

	box = $('<div id="moduledialogwrapper" ></div>')
	.dialog({
		title: titleStr,
		resizable: false, 
		draggable: false, 				// Disable the posivility to drag the dialog
		dialogClass: ClassDlg,
		modal: true, 					// Disable the posivility to interact with the page
		closeOnEscape: $ShowCloseBtn, 	// Disable the posivility to close the dialog with the ESC key
		width: $(window).width() * 0.8,
		height: 'auto',
		// maxHeight: 350,
		maxHeight: $(window).height() * 0.8,
        maxWidth: $(window).width() * 0.8,
		scroll: true,
		// position: { my: "top-175", at: "center", of: window },
		position: { my: "center", at: "center", of: window },
		buttons: buttons,
		open: function (e) {

			$(this).parent().css({
				"position": "fixed", 
				"top": "50%", 
				"left": "50%", 
				"transform": "translate(-50%, -50%)"
			});

			// Hide the close button
			if (!$ShowCloseBtn)	{
				$(this).parent().find('.ui-dialog-titlebar-close').hide();
			}


			$('#moduledialogwrapper').html('Loading... ' + '<i class="fa fa-spinner fa-spin fa-2x">');
			$('#moduledialogwrapper').dialog('widget').find('div.ui-dialog-buttonpane div.ui-dialog-buttonset button').eq(0).button('disable');
			
			if (formname !== null) {
				var form = document.forms.namedItem(formname);
				oData = new FormData(form);	
			}
			
			var xhr = new XMLHttpRequest(),
			timer = null;
			xhr.open('POST', urlStr, true);
			xhr.send(oData);
			timer = window.setInterval(function() {
				//$('#moduledialogwrapper').animate({ scrollTop: $(this).scrollTop() + $(document).height() });
				if (xhr.readyState === XMLHttpRequest.DONE) {
					window.clearTimeout(timer);
					if (typeof end_module_actions === 'function') {
						$('#moduledialogwrapper').dialog('widget').find('div.ui-dialog-buttonpane div.ui-dialog-buttonset button').eq(0).button('enable');
						end_module_actions(actionname); 
					}
				}
				if (xhr.responseText.length > 0) {
					if ($('#moduledialogwrapper').html().trim() !== xhr.responseText.trim()) {
						$('#moduledialogwrapper').html(xhr.responseText);
						//$('#moduleprogress').scrollTop(1E10);
						$('#moduledialogwrapper').animate({ scrollTop: $(this).scrollTop() + $(document).height() });
						// box.dialog('option', 'position', { my: "center", at: "center", of: window });
					}
				}
				if (xhr.readyState === XMLHttpRequest.DONE) {
					// $("#moduleprogress").css("overflow", "auto");
					//$('#moduleprogress').scrollTop(1E10);
					$('#moduledialogwrapper').animate({ scrollTop: $(this).scrollTop() + $(document).height() });
					$("#moduleBoxContents a").focus();
					// box.dialog('option', 'position', { my: "center", at: "center", of: window });
				}
			}, 500);
			
		},
		close: function(e) {
			if (typeof close_module_actions === 'function') { 
				close_module_actions(false, actionname); 
			}
			// $(this).dialog('destroy').remove();
			// $(e.target).dialog("destroy").remove();
		}
	});

    // $(window).on('resize.dialogResize', function () {
	// 	if (box !== null && box.dialog('isOpen'))
	// 	{
	// 		var windowWidth = $(window).width() * 0.8;
	// 		box.dialog('option', 'width', windowWidth);
	// 		// box.dialog('option', 'position', { my: "center", at: "center", of: window });
	// 	}        
    // });
}

function close_module_actions(goback, acctionname = "") 
{
	if (box !== null) {
		box.dialog("destroy").remove();
	}
	
	var displayActual = epm_global_getDisplayActual();
	if (displayActual !== "") {
		var func = 'close_module_actions_'+displayActual;
		if (typeof window[func] === 'function') { 
			window[func](goback, acctionname); 
		}
	}
	
	if (goback) {
		location.reload();
	}		
}

function end_module_actions(acctionname = "") 
{
	var displayActual = epm_global_getDisplayActual();
	if (displayActual !== "") {
		var func = 'end_module_actions_'+displayActual;
		if (typeof window[func] === 'function') { 
			window[func](acctionname); 
		}
	}
}

function epm_global_refresh_table(snametable = "", showmsg = false)
{
	if (snametable === "") { return; }
	$(snametable).bootstrapTable('refresh');
	if (showmsg === true) {
		fpbxToast("Table Refrash Ok!", '', 'success');
	}
}


function epm_global_input_value_change_bt(sNameID = "", sValue = "", bSetFocus = false)
{
	if (sNameID === "" ) { return false; }
	
	if ($(sNameID).hasClass("selectpicker") == true) {
		$(sNameID).selectpicker('val', sValue);
	}
	else {
		$(sNameID).val(sValue);
	}
	if (bSetFocus === true) { $(sNameID).focus(); }
}







/**
 * Perform an AJAX request to FreePBX and handle the response.
 *
 * @param {object|null} data_ajax     - Array containing the data to be sent with the AJAX request. Must be an array of key-value pairs or null.
 * @param {function} [callback]       - Optional callback function that will be called after the AJAX request completes. It receives two parameters:
 *                                    - {boolean} status: `true` if the request was successful, `false` otherwise.
 *                                    - {Object|null} data: The data returned from the server on success, or `null` on failure.
 * @param {boolean} [errShowMsg=true] - Whether or not to show an error message if the request fails.
 * @returns {boolean} Returns `false` if `data_ajax` is invalid, otherwise performs the AJAX request.
 * 
 * @example
 * // Example 1: Call the function with valid data and a callback
 * var data = {
 *     module: "endpointman",
 *     module_sec: "epm_advanced",
 *     module_tab: "oui_manager",
 *     command: "oui_brands"
 * };
 * epm_gloabl_manager_ajax(data, function(status, response) {
 *     if (status) {
 *         console.log('AJAX request succeeded', response);
 *     } else {
 *         console.error('AJAX request failed');
 *     }
 * });
 * 
 * @example
 * // Example 2: Call the function without a callback and not showing an error message of the request fails
 * var data = {
 *     module: "endpointman",
 *     module_sec: "epm_advanced",
 *     module_tab: "oui_manager",
 *     command: "oui_brands"
 * };
 * epm_gloabl_manager_ajax(data, null, false);
 */
function epm_gloabl_manager_ajax(data_ajax = null, callback, errShowMsg = true)
{
	// Set callback to function if not defined
	callback = callback || function(status, data) { };

	// Check if the modal is valid
	if (typeof data_ajax !== 'object' || data_ajax === null)
	{
		callback(false, null);
		return false;
	}

	$.ajax({
		type	: 'POST',
		url		: window.FreePBX.ajaxurl,
		data	: data_ajax,
		dataType: 'json',
		timeout	: 60000,
		error: function(xhr, ajaxOptions, thrownError)
		{
			fpbxToast( sprintf(_('ERROR AJAX (%s): %s'), xhr.status, thrownError), '', 'error');
			callback(false, null);
			return;
		},
		success: function(data)
		{
			if (data.status !== true && errShowMsg === true)
			{
				fpbxToast(data.message, '', 'error');
			}
			callback(data.status, data);
		}
	});
	return true;
}


/**
 * Fetch the list of brands via AJAX and populate the select element in the modal.
 * 
 * @param {jQuery} modal 		- jQuery object representing the modal container.
 * @param {jQuery} select_list 	- jQuery object representing the select element for brands.
 * @param {object} ajaxData		- Object containing the data to be sent via AJAX.
 * @param {function} [callback] - Optional callback function to be called after the AJAX request.
 *                                 It receives two parameters: 
 *                                 - {boolean} status: Whether the request was successful or not.
 *                                 - {Object|null} data: The data returned from the server, or null on error.
 * @returns {boolean} Returns `false` if the `modal` or `select_list` are invalid, otherwise performs the AJAX request.
 * 
 * @example
 * // Example 1: Basic usage with a modal and select element
 * var modal = $('#myModal');
 * var select_list = modal.find('#modal_form_new_oui_brand');
 * var ajaxData = {
 *    module: "endpointman",
 *    module_sec: "epm_templates",
 *    module_tab: "manager",
 *    command: "add_template_list_products"
 * };
 * epm_global_options_list_ajax(modal, select_list, ajaxData, function(status, data) {
 *     if (status) {
 *         console.log('Brands loaded successfully');
 *     } else {
 *         console.error('Failed to load brands');
 *     }
 * });
 * 
 * @example
 * // Example 2: Usage without a callback function (default behavior)
 * var modal = $('#myModal');
 * var select_list = modal.find('#modal_form_new_oui_brand');
 * var ajaxData = {
 *    module: "endpointman",
 *    module_sec: "epm_templates",
 *    module_tab: "manager",
 *    command: "add_template_list_products"
 * };
 * epm_global_options_list_ajax(modal, select_list, ajaxData);
 */
function epm_global_options_list_ajax(modal, select_list, ajaxData, callback)
{
	// Set callback to function if not defined
	callback = callback || function(status, data) { };

	// Check if the ajaxData is valid
	if (typeof ajaxData !== 'object' || ajaxData === null) {
		callback(false, null);
		return false;
	}

	// Check if the modal is valid
	if (typeof modal === 'undefined' || modal === null || modal === "" || modal === false)
	{
		callback(false, null);
		return false;
	}

	// Check if the select_list is valid
	if (typeof select_list === 'undefined' || select_list === null || select_list === "" || select_list === false)
	{
		callback(false, null);
		return false;
	}

	select_list.empty().val('').selectpicker('refresh');

	$.ajax({
		type: 'POST',
		url: window.FreePBX.ajaxurl,
		data: ajaxData,
		dataType: 'json',
		timeout: 60000,
		error: function(xhr, ajaxOptions, thrownError)
		{
			fpbxToast( sprintf(_('ERROR AJAX (%s): %s'), xhr.status, thrownError), '', 'error');
			callback(false, null);
		},
		success: function(data)
		{
			if (data.status === true)
			{
				$.each(data.options, function(index, value)
				{
					select_list.append($('<option>', {
						value: value.id,
						text: value.name,
						selected: value.is_select
					}));
				});
				select_list.selectpicker('refresh');
			}
			else
			{
				fpbxToast(data.message, '', 'error');
			}
			callback(data.status, data);
		}
	});
}



// INI: CODIGO DE FREEPBX
function epm_global_update_jquery_msg_help()
{
	if($(".fpbx-container").length>0){
		var loc=window.location.hash.replace("#","");
		if(loc!==""&&$(".fpbx-container li[data-name="+loc+"] a").length>0){
			$(".fpbx-container li[data-name="+loc+"] a").tab('show');
		}
		$(".fpbx-container i.fpbx-help-icon").on("mouseenter",function(){
			var id=$(this).data("for");
			var container=$(this).parents(".element-container");
			$(".fpbx-help-block").removeClass("active");
			$("#"+id+"-help").addClass("active");
			container.one("mouseleave",function(event){
				if(event.relatedTarget&&(event.relatedTarget.type=="submit"||event.relatedTarget.type=="button")){return;}
				var act=$("#"+id+"-help").data("activate");
				if(typeof act!=="undefined"&&act=="locked"){return;}
				$("#"+id+"-help").fadeOut("slow",function(){
					$(this).removeClass("active").css("display","");
				});
				$(this).off("mouseleave");
			});
		});
	}
}
// END: CODIGO DE FREEPBX