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