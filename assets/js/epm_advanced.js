"use strict";
var cmeditor   = null;
var jsoneditor = null;

function epm_advanced_document_ready () {

	var arrayJs = [
		'assets/endpointman/js/addon/simplescrollbars.js',
		'assets/endpointman/js/mode/xml.js',
		'assets/endpointman/js/addon/fullscreen.js'
	];
	arrayJs.forEach(function (item, index, array) {
		var x = document.createElement('script');
		x.src = item;
		document.getElementsByTagName("head")[0].appendChild(x);
	});


	//TAB POCE
	epm_advanced_tab_poce_unselect();
	window.addEventListener('resize', epm_advanced_tab_poce_resize);
	
	$('#poceReloadTree').on('click', function()
	{
        $('#poce_tree_files').jstree(true).refresh();
    });

	$('#poceExpandTree').on('click', function()
	{
        $('#poce_tree_files').jstree('open_all');
    });

    $('#poceCollapseTree').on('click', function()
	{
        $('#poce_tree_files').jstree('close_all');
    });

	$('#tab_poce_bt_src_full_screen').on('click', function()
	{
		if (cmeditor === null) return;
        cmeditor.setOption('fullScreen', !cmeditor.getOption('fullScreen'));
    });

	$('#tab_poce_bt_delete').on('click', function() {
		epm_advanced_tab_poce_delete();
	});

	$('#tab_poce_bt_save').on('click', function() {
		epm_advanced_tab_poce_save();
	});

	$('#tab_poce_bt_save_as').on('click', function() {
		epm_advanced_tab_poce_save(true);
	});
		
	$('#tab_poce_bt_share').on('click', function() {
		epm_advanced_tab_poce_share();
	});

	$('#poce_tree_files').jstree({
		'core': {
			'themes': {
                'dots': false
            },
			'data': {
				'url': window.FreePBX.ajaxurl,
				'data': function(node) {
					return { 
						'module'	: "endpointman",
						'module_sec': "epm_advanced",
						'module_tab': "poce",
						'command'	: "poce_tree",
						'tree_id'	: node.id
					};
				}
			},
		},
		'checkbox': {
            'keep_selected_style': false,
			'three_state': false,
			'cascade': 'undetermined'
        },
		'plugins': ['checkbox', 'search', 'sort', 'wholerow'],
		'search': {
            'input': 'search_poce_tree',
			'case_insensitive': true,
            'show_only_matches': true
        }

	}).on('deselect_node.jstree', function(e, data) {

		var tree = $(this).jstree(true);
		var node = data.node;

		epm_advanced_tab_poce_unselect();

    }).on('select_node.jstree', function(e, data) {

		var tree 	  = $(this).jstree(true);
		var NodeId	  = data.node.id;
		var nodeData  = data.node.data || {};
		var node 	  = data.node;
		var returnVal = true;

		// console.log("NodeID: " + NodeId);
		// console.log(data.node);

		// Break if the node is disabled
		if (tree.is_disabled(node)) return;

		//Fix to avoid infinite loop when deselect_all() is called
		if ($(this).data('isSelecting')) return;
		$(this).data('isSelecting', true);
		
		if (node.parents.length <= 2)
		{
			if (!tree.is_loaded(node))
			{
				tree.load_node(node);
			}

			// Expand or collapse the node
			// tree.is_open(node) ? tree.close_node(node) : tree.open_node(node);

			// Block the selection of the node
			tree.deselect_node(node);

			// Block the checkboxk of the node
			returnVal = false;
		}
		else if (typeof nodeData.func === 'string' && typeof window[nodeData.func] === 'function')
		{
			// Is necessary set isSelecting to true before call function to avoid infinite loop. Remember set isSelecting to false after call function.
			//tree.deselect_all();
			tree.uncheck_all();

			tree.check_node(node);
			
			var params = nodeData.param !== undefined ? nodeData.param : null;
			window[nodeData.func](node, params);			
		}
		else if (nodeData.func)
		{
			console.log('Function Not Found: ' + nodeData.func);
		}

		$(this).data('isSelecting', false);
		return returnVal;
    }).on('search.jstree', function (nodes, str, res) {
		// if (str.nodes.length===0) {
		// 	$('#poce_tree_files').jstree(true).hide_all();
		// }
	}).on('refresh.jstree open_all.jstree close_all.jstree', function (e, data) {
		switch (e.type) {
			case 'refresh':
			case 'open_all':
			case 'close_all':
				epm_advanced_tab_poce_resize();
				break;
		}
	});

	$('#poce_tree_files_search, #poce_tree_files_search_show_only').on('keyup change', function(event) {

		var treeContainerId = 'poce_tree_files';
		var searchInputId 	= 'poce_tree_files_search';
		var checkboxId 		= 'poce_tree_files_search_show_only';

		switch (event.type)
		{ 
			case 'keyup':
				if (event.target.id !== searchInputId) {
					return;
				}
				break;
			case 'change':
				if (event.target.id !== checkboxId) {
					return;
				}
				break;
		}

		var treeInstance 	= $('#' + treeContainerId).jstree(true);
		var searchString    = $('#' + searchInputId).val();
		var showOnlyMatches = $('#' + checkboxId).is(':checked');
		
		treeInstance.settings.search.show_only_matches = showOnlyMatches;
		treeInstance.search(searchString);		
	});


	//TAB SETTING
	$('#settings input[type=text]').change(function(){ epm_advanced_tab_setting_input_change(this); });
	$('#settings input[type=radio]').change(function(){ epm_advanced_tab_setting_input_change(this); });
	$('#settings select').change(function(){ epm_advanced_tab_setting_input_change(this); });


	//TAB OUT_MANAGER
	$('#epm_advanced_tab_oui_add_modal_btn_refresh').on('click', function() {
		
		var modal 		 = $("#epm_advanced_tab_oui_add_modal");
		var select_brand = $('#modal_form_new_oui_brand');
		epm_advanced_tab_oui_manager_new_list_brands(modal, select_brand);
	});

	$('#epm_advanced_tab_oui_add_modal')
	.on('show.bs.modal', function (event) {
		var modal 		 = $(this);
		var select_brand = $('#modal_form_new_oui_brand');

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
				waitingDialog.hide();
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

		epm_advanced_tab_oui_manager_new_list_brands(modal, select_brand, function(status, data) {
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
			return status;
		});

		// Prevent the opening of the modal until the ajax request is completed
		return false;
	}).on('hidden.bs.modal', function (event) {
		// Clear the input fields and select elements when the modal is closed
		var modal = $(this);
		modal.find('input[type="text"]').val('');
		modal.find('select').empty().selectpicker('refresh');
		modal.data('ajaxState', 'not-started');
    });

	$('#modal_form_new_oui_btn_add').on("click", function() {
		var data_ajax = { 
			'module'		: "endpointman",
			'module_sec'	: "epm_advanced",
			'module_tab'	: "oui_manager",
			'command'		: "oui_add",
			'new_oui_number': $("#modal_form_new_oui_number").val().trim(),
			'new_oui_brand'	: $("#modal_form_new_oui_brand").val().trim()
		};
		epm_gloabl_manager_ajax(data_ajax, function(status, data) {
			if (status === true)
			{
				fpbxToast(data.message, '', 'success');
				$("#epm_advanced_tab_oui_add_modal").modal('hide');
				$("#epm_advanced_tab_oui_grid").bootstrapTable('refresh');
			}
		});
	});

	$('#epm_advanced_tab_oui_refresh').on("click", function(){
		$("#epm_advanced_tab_oui_grid").bootstrapTable('refresh');
		fpbxToast(_("Refrash Success!"), '', 'success');
	});

	$(document).on("click", ".tab_oui_grid_remove_row", function() {
		var $button  = $(this);
		var $row 	 = $button.closest('tr');						// Find the parent row of the button (the <tr>)
		var $table 	 = $row.closest('table');						// Find the parent table of the button
		var rowIndex = $row.data('index');							// Get the row index from the 'data-index' attribute that Bootstrap Table uses
		var rowData  = $table.bootstrapTable('getData')[rowIndex]; 	// Get the row data using the index (this assumes the table uses Bootstrap Table)

		if (rowData && rowData.id)
		{
			fpbxConfirm(
				sprintf(_("Are you sure to delete OUI '%s' for the brand '%s'?"), rowData.oui, rowData.brand),
				_("YES"), _("NO"),
				function()
				{
					var data_ajax = { 
						'module'	 : "endpointman",
						'module_sec' : "epm_advanced",
						'module_tab' : "oui_manager",
						'command'	 : "oui_remove",
						'oui_remove' : rowData.id
					};
					epm_gloabl_manager_ajax(data_ajax, function(status, data) {
						if (status === true)
						{
							fpbxToast(data.message, '', 'success');
							$table.bootstrapTable('refresh');
						}
					});
				}
			);
		}
		else {
			fpbxToast(_("The row data is invalid!"), '', 'warning');
		}
	});

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
		// $('#poce_tree_files').jstree(true).refresh();

		// Create the editor CodeMirror if it does not exist
		if (cmeditor === null)
		{
			const options_cmeditor = {
				lineNumbers: true,				// show line numbers
				matchBrackets: true,			// highlight matching brackets
				mode: "xml",             		// set the mode to JavaScript or the mode of the editor
				readOnly: true,					// do not allow editing
				viewportMargin: Infinity,		// set the viewport margin
				scrollbarStyle: "simple",		// set the scrollbar style
				extraKeys: {
					"F11": function(cm) {
						cm.setOption("fullScreen", !cm.getOption("fullScreen"));
					},
					"Esc": function(cm) {
						if (cm.getOption("fullScreen")) cm.setOption("fullScreen", false);
					}
				}
			}
			cmeditor = new CodeMirror(document.getElementById("config_textarea"), options_cmeditor);
		}

		// Create the editor JSON if it does not exist
		if (jsoneditor === null)
		{
			const options_jsoneditor = {
				animation: 100,
				mode: 'tree',
				modes: ['code', 'form', 'text', 'tree', 'view', 'preview'], // allowed modes
				onModeChange: function (newMode, oldMode) {
					// console.log('Mode switched from', oldMode, 'to', newMode)
				}
			}
			jsoneditor = new JSONEditor(document.getElementById("config_jsoneditor"), options_jsoneditor)
		}
		epm_advanced_tab_poce_resize();
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






// INI: FUNCTION TAB POCE

function epm_advanced_tab_poce_resize() {
    var footer 	  = document.getElementById("footer");
	var footerTop = footer.getBoundingClientRect().top;
	var footerH   = footer.getBoundingClientRect().height;
	var boxSrc 	  = document.getElementById("poce_box_sec_source");
	var boxSrcTop = boxSrc.getBoundingClientRect().top;
	
	// Calculate the available height
	var availableHeight = footerTop - boxSrcTop;

	// Calculate the maximum height of the window
	var maxWindowHeight = window.innerHeight - boxSrcTop - footerH; 

	// If the available height is greater than the maximum height of the window, set the maximum height of the window
	if (availableHeight > maxWindowHeight) {
		availableHeight = maxWindowHeight;
	}

	// If the available height is less than 200, set the height to 200
	if (availableHeight < 200) {
	 	availableHeight = 200;
	}

	boxSrc.style.height = availableHeight + "px";
}

/**
 * Refresh the parent node of the node to the specified number of levels
 * 
 * @param {string|object} treeInstanceOrId - The tree instance or the id of the tree 
 * @param {string|object|null} startNode  - The node to start refreshing, default is the selected node. If null, the selected node is used and if there is no selected node, the all tree is refreshed
 * @param {string|number} levels - The number of levels to go up the tree, default is 2
 * @param {function} [beforeRefreshCallback] - (Optional) The callback function to call before refreshing the node
 * @param {function} [afterRefreshCallback] - (Optional) The callback function to call after refreshing the node
 * @returns {void} true if the node was refreshed, false if the node was not found
 * 
 * @example
 * epm_advanced_tab_poce_refresh_tree_nodes('#poce_tree_files');
 * epm_advanced_tab_poce_refresh_tree_nodes('#poce_tree_files', null, 2);
 * epm_advanced_tab_poce_refresh_tree_nodes('#poce_tree_files', null, 2, function(node) { console.log('Before Refresh: ' + node.id); }, function(node) { console.log('After Refresh: ' + node.id); });
 * epm_advanced_tab_poce_refresh_tree_nodes('#poce_tree_files', 'node_1', 2);
 * epm_advanced_tab_poce_refresh_tree_nodes('#poce_tree_files', 'node_1', 2, function(node) { console.log('Before Refresh: ' + node.id); }, function(node) { console.log('After Refresh: ' + node.id); });
 * 
 * @example 
 * var jstree = $('#poce_tree_files').jstree(true);
 * epm_advanced_tab_poce_refresh_tree_nodes(jstree, 'node_1', 2);
 * epm_advanced_tab_poce_refresh_tree_nodes(jstree, 'node_1', 2, function(node) { console.log('Before Refresh: ' + node.id); }, function(node) { console.log('After Refresh: ' + node.id); });
 * 
 */
function epm_advanced_tab_poce_refresh_tree_nodes(treeInstanceOrId, startNode = null , levels = 2, beforeRefreshCallback = null, afterRefreshCallback = null)
{
	var treeInstance;
	if (typeof treeInstanceOrId === 'string')
	{
        treeInstance = $(treeInstanceOrId).jstree(true);
    }
	else if (treeInstanceOrId && typeof treeInstanceOrId.refresh_node === 'function')
	{
        treeInstance = treeInstanceOrId;
    }
	else
	{
		return false;
	}
	
	if (startNode === null || startNode === undefined)
	{
		var selectedNodes = treeInstance.get_selected(true);
		if (selectedNodes.length > 0)
		{
            startNode = selectedNodes[0];
        }
		else
		{
			treeInstance.refresh();
            return true;
        }
	}
	if (typeof startNode === 'string' || typeof startNode === 'number')
	{
        startNode = treeInstance.get_node(startNode);
		if (!startNode || startNode.id === undefined)
		{
            return false;
        }
    }

    var currentNode = startNode;
    for (var i = 0; i < levels; i++)
	{
        if (!currentNode || currentNode.parent === "#")
		{
            break;	// Is the root node or there are no more parent nodes, stop the cycle
        }
        var parentNode = treeInstance.get_node(currentNode.parent);
		if (parentNode)
		{
            currentNode = parentNode;
        }
    }

	if (typeof beforeRefreshCallback === 'function') { beforeRefreshCallback(currentNode); }
	if (currentNode) 								 { treeInstance.refresh_node(currentNode); }
	if (typeof afterRefreshCallback === 'function')  { afterRefreshCallback(currentNode); }
	return true;
}

function epm_advanced_tab_poce_unselect(showImg = false)
{
	$('#poce_box_sec_source').removeClass('poce_box_sec_source_loading poce_box_sec_source_loaderr');
	$("#poce_file_name_path, #poce_NameProductSelect").text(_("No Selected"));

	var form = $('form[name=form_config_text_sec_button]');

	form.find('input, button').each(function() {
		$(this).prop('disabled', true);

		if ($(this).is(':text')) {
			$(this).val('');
		}
		if ($(this).is(':hidden')) {
			$(this).val('');
		}
	});
	form.find('input[name=datosok]').val("false");

	if (jsoneditor !== null)
	{
		jsoneditor.setMode('text');
		jsoneditor.set('');
	}
	$('#config_jsoneditor').hide();

	if (cmeditor !== null)
	{
		cmeditor.setValue('');
		cmeditor.setOption("readOnly", true);
		cmeditor.setOption("mode", "text/plain");
	}
	$('#config_textarea').hide();


	switch (showImg)
	{
		case "error":
			$('.poce_box_sec_source').addClass('poce_box_sec_source_loaderr');
			break;
		case "loading":
			$('.poce_box_sec_source').addClass('poce_box_sec_source_loading');
			break;
	}
}

function epm_advanced_tab_poce_edit_file(node, params)
{
	epm_advanced_tab_poce_unselect("loading");

	var msgErrInit = null;
	switch (true)
	{
		case (params.product == null || params.type == null || params.name_file == null || params.id_file == null):
		
			msgErrInit = "Params invalid!";
			break;

		case (cmeditor === null):
			msgErrInit = "Editor Code is null!";
			break;

		case (jsoneditor === null):
			msgErrInit = "JSON editor is null!";
			break;
	}
	if (msgErrInit !== null)
	{
		fpbxToast(msgErrInit, '', 'warning');
		epm_advanced_tab_poce_unselect("error");
		return false;
	}

	var id			= params.product;								// Number id product
	var type		= params.type;									// Types: file, template, custom
	// var type_file = params.type_file;							// Type file: raw, xml
	var name_file	= params.name_file;								// Name file: mac.cfg, aastra.cfg
	var id_file		= params.id_file;								// Id file in database or name file: aastra.cfg or 1121
	var parentNode	= $('#poce_tree_files').jstree('get_node', id);	// Parent node
	var form		= $('form[name=form_config_text_sec_button]');	// Form to data
	
	// waitingDialog.show();
	// $('.poce_box_sec_source').addClass('poce_box_sec_source_loading');
	// epm_advanced_tab_poce_unselect("loading");
	$.ajax({
		type: 'POST',
		url: window.FreePBX.ajaxurl,
		data: {
			'module'		: "endpointman",
			'module_sec'	: "epm_advanced",
			'module_tab'	: "poce",
			'command'		: "poce_select_file",
			'product_select': id,
			'file_id' 		: id_file,
			'file_name' 	: name_file,
			'type_file' 	: type
		},
		dataType: 'json',
		timeout: 60000,
		error: function(xhr, ajaxOptions, thrownError)
		{
			epm_advanced_tab_poce_unselect("error");
			fpbxToast( sprintf(_('ERROR AJAX (%s): %s'), xhr.status, thrownError), '', 'error');
			return false;
		},
		success: function(data)
		{
			if (data.status == true)
			{
				if (parentNode && parentNode.data)
				{
					$("#poce_NameProductSelect").text(parentNode.data.text);
				}
				else
				{
					$("#poce_NameProductSelect").text("???");
				}
				$("#poce_file_name_path").text(data.location);

				var filename = data.location.split('/').pop().toLowerCase();
				var cmmode = "text/plain";
				switch (true)
				{
					case filename.endsWith(".js"):
						cmmode = "javascript";
						break;

					case filename.endsWith(".json"):
						cmmode = "json";
						break;

					case filename.endsWith(".xml"):
						cmmode = "xml";
						break;
					
					case filename.endsWith(".php"):
						cmmode = "php";
						break;

					case filename.endsWith(".sql"):
						cmmode = "sql";
						break;
					
					case filename.endsWith(".cfg"):
					case filename.endsWith(".ini"):
						cmmode = "ini";
						break;
					
					case filename.endsWith(".yml"):
						cmmode = "yaml";
						break;
				}
				cmeditor.setOption("mode", cmmode);


				var save_as_off 	= true;
				var full_screen_off = true;
				var delete_off 		= true;
				var share_off 		= true;
				var save_off 		= true;

				switch(data.type)
				{
					case "file":
						//share_off 		= false; // Disable, because the provisioner.net is down
						save_off 		= false;
						save_as_off 	= false;
						full_screen_off = false;

						$('#config_textarea').show();
						cmeditor.setOption("readOnly", false);
						cmeditor.setValue(data.config_data ?? ' ');
						break;

					case "template":
						// save_as_off = false;

						$('#config_jsoneditor').show();
						jsoneditor.setMode('tree')
						jsoneditor.set(data.config_data ?? '')
						break;
					
					case "custom":
						//share_off 		= false; // Disable, because the provisioner.net is down
						delete_off 		= false;
						save_off 		= false;
						save_as_off 	= false;
						full_screen_off = false;

						$('#config_textarea').show();
						cmeditor.setValue(data.config_data ?? ' ');
						cmeditor.setOption("readOnly", false);
						break;
					
					default:
						break;
				}

				form.find('button[name=bt_source_full_screen]').prop('disabled', full_screen_off);
				form.find('button[name=button_share]').prop('disabled', share_off);
				form.find('button[name=button_delete]').prop('disabled', delete_off);
				form.find('button[name=button_save]').prop('disabled', save_off);
				form.find('button[name=button_save_as]').prop('disabled', save_as_off);
				form.find('input[name=save_as_name]').prop('disabled', save_as_off).val(data.save_as_name_value);

				Object.entries(data).forEach(function([key, value])
				{
					switch (key) {
						case 'type':
							form.find('input[name=type_file]').val(value);
							break;
						case 'sendidt':
							form.find('input[name=sendid]').val(value);
							break;
						case 'product_select':
							form.find('input[name=product_select]').val(value);
							break;
						case 'save_as_name_value':
							form.find('input[name=save_as_name]').val(value);
							break;
						case 'original_name':
							form.find('input[name=original_name]').val(value);
							break;
						case 'filename':
							form.find('input[name=filename]').val(value);
							break;
						case 'location':
							form.find('input[name=location]').val(value);
							break;
					}
				});
				form.find('input[name=datosok]').val("true");
				fpbxToast(data.txt.load_data_ok, '', 'success');
				return true;
			} 
			else
			{
				epm_advanced_tab_poce_unselect("error");
				fpbxToast(data.message, '', 'error');
				return false;
			}
		},
	});	
	setTimeout(function () {waitingDialog.hide();}, 500);
}

function epm_advanced_tab_poce_delete()
{
	var form = $('form[name=form_config_text_sec_button]');
	if (form.find('input[name=datosok]').val() === "false")
	{
		fpbxToast(_("The form is not ready!"), '', 'error');
		return false;
	}

	var file_name = form.find('input[name=filename]').val();

	fpbxConfirm(
		sprintf( _("Are you sure to delete the file [%s]?"), file_name),
		_("YES"), _("NO"),
		function()
		{
			var cfg_data = {
				'module'		: "endpointman",
				'module_sec'	: "epm_advanced",
				'module_tab'	: "poce",
				'command'		: "poce_delete_config_custom",
				'type_file' 	: form.find('input[name=type_file]').val(),
				'product_select': form.find('input[name=product_select]').val(),
				'sql_select'	: form.find('input[name=sendid]').val(),
			};

			$.ajax({
				type: 'POST',
				url: window.FreePBX.ajaxurl,
				data: cfg_data,
				dataType: 'json',
				timeout: 60000,
				error: function(xhr, ajaxOptions, thrownError) {
					fpbxToast( sprintf(_('ERROR AJAX (%s): %s'), xhr.status, thrownError), '', 'error');
					return false;
				},
				success: function(data) {
					if (data.status == true)
					{
						epm_advanced_tab_poce_unselect();
						fpbxToast(data.message, '', 'success');

						var treeInstance = $('#poce_tree_files').jstree(true);
						if (! epm_advanced_tab_poce_refresh_tree_nodes(treeInstance))
						{
							// If the node is not found, refresh the entire tree
							treeInstance.refresh();
						}
						return true;
					} 
					else
					{
						fpbxToast(data.message, '', 'error');
						return false;
					}
				},
			});
		}
	);
}

function epm_advanced_tab_poce_save(save_as = false)
{
	var form = $('form[name=form_config_text_sec_button]');
	if (form.find('input[name=datosok]').val() === "false")
	{
		fpbxToast(_("The form is not ready!"), '', 'error');
		return false;
	}
	else if (!$('#config_textarea').is(':visible') && !$('#config_jsoneditor').is(':visible'))
	{
		fpbxToast(_("The editor is not activated!"), '', 'error');
		return false;
	}
	else if ($('#config_textarea').is(':visible') && $('#config_jsoneditor').is(':visible'))
	{
		fpbxToast(_("The editor is duplicated!"), '', 'error');
		return false;
	}
	
	var type_file 		= form.find('input[name=type_file]').val();
	var product_select 	= form.find('input[name=product_select]').val();
	var iddb 			= form.find('input[name=sendid]').val();
	var filename_new 	= form.find('input[name=save_as_name]').val();
	var filename_src 	= form.find('input[name=original_name]').val();
	var filename_now 	= form.find('input[name=filename]').val();
	var stringConfirm	= "";

	switch (true)
	{
		case ($('#config_textarea').is(':visible')):
			var config_data = cmeditor.getValue();
			break;

		case ($('#config_jsoneditor').is(':visible')):
			// var config_data = JSON.stringify(jsoneditor.get(), null, 2); //Prey to the bug of the JSONEditor
			var config_data = jsoneditor.getText();
			break;

		default:
			var config_data = null;
	}

	if (save_as)
	{
		if (filename_new === "")
		{
			fpbxToast(_("The new file name is empty!"), '', 'error');
			return false;
		}
		else if (filename_new === filename_src || filename_new === filename_now )
		{
			fpbxToast(_("The new file name is the same as the original!"), '', 'error');
			return false;
		}
		stringConfirm = sprintf(_("Are you sure to create a new file '%s'?"), filename_new);
	}
	else
	{
		filename_new  = "";
		stringConfirm = sprintf(_("Are you sure to save your changes in '%s'? Will be overwritten irreversibly!"), filename_now);
	}

	fpbxConfirm(
		sprintf(stringConfirm),
		_("YES"), _("NO"),
		function()
		{
			var cfg_data = {
				'module'		: "endpointman",
				'module_sec'	: "epm_advanced",
				'module_tab'	: "poce",
				'command'		: save_as ? "poce_save_as_file" : "poce_save_file",
				'params'	: {
					'type_file' 	: type_file,
					'product_select': product_select,
					// 'iddb'			: iddb,
					'filename_new'	: filename_new,
					'filename_src'	: filename_src,
					'filename_now'	: filename_now,
					'config_data'	: config_data
				},
			};
			$.ajax({
				type: 'POST',
				url: window.FreePBX.ajaxurl,
				data: cfg_data,
				dataType: 'json',
				timeout: 60000,
				error: function(xhr, ajaxOptions, thrownError) {
					fpbxToast( sprintf(_('ERROR AJAX (%s): %s'), xhr.status, thrownError), '', 'error');
					return false;
				},
				success: function(data) {
					if (data.status == true)
					{
						fpbxToast(data.message, '', 'success');

						if (data.tree_reload ?? false)
						{
							// epm_advanced_tab_poce_unselect();

							var treeObj 	 = $('#poce_tree_files');
							var treeInstance = treeObj.jstree(true);

							if (data.tree_node_find && data.tree_node_find.trim() !== "")
							{
								// Is needed used ono to wait for the tree to be loaded before selecting the node
								treeObj.one('refresh_node.jstree', function ()
								{
									var treeInstanceOne = $(this).jstree(true);
									treeInstanceOne.select_node(data.tree_node_find);
								});
							}
							if (! epm_advanced_tab_poce_refresh_tree_nodes(treeInstance, null, 2, function() { epm_advanced_tab_poce_unselect(); }))
							{
								// If the node is not found, refresh the entire tree
								treeInstance.refresh();
							}
						}
						return true;
					} 
					else {
						fpbxToast(data.message, '', 'error');
						return false;
					}
				},
			});
		}
	);

}

function epm_advanced_tab_poce_share()
{
	var form = $('form[name=form_config_text_sec_button]');
	if (form.find('input[name=datosok]').val() === "false")
	{
		fpbxToast(_("The form is not ready!"), '', 'error');
		return false;
	}

	var type_file 		= form.find('input[name=type_file]').val();
	var product_select 	= form.find('input[name=product_select]').val();
	var iddb 			= form.find('input[name=sendid]').val();
	var filename_now	= form.find('input[name=filename]').val();
	var filename_src	= form.find('input[name=original_name]').val();

	var cfg_data = {
		'module'	: "endpointman",
		'module_sec': "epm_advanced",
		'module_tab': "poce",
		'command'	: "poce_share",
		'params'	: {
			'type_file' 	: type_file,
			'iddb' 			: iddb,
			'product_select': product_select,
			'filename_src'	: filename_src,
			'filename_now'	: filename_now
		}
	};

	fpbxToast("Sharing Info...", '', 'info');
	$.ajax({
		type: 'POST',
		url: window.FreePBX.ajaxurl,
		data: cfg_data,
		dataType: 'json',
		timeout: 60000,
		error: function(xhr, ajaxOptions, thrownError) {
			fpbxToast( sprintf(_('ERROR AJAX (%s): %s'), xhr.status, thrownError), '', 'error');
			return false;
		},
		success: function(data) {
			if (data.status == true)
			{
			    fpbxToast(data.message, '', 'success');
			} 
			else
			{
				fpbxToast(data.message, "", 'error');
				return false;
			}
		},
	});
}
// END: FUNCTION TAB POCE








// INI: FUNCTION TAB OUI MANAGER
function epm_advanced_tab_oui_manager_grid_actionFormatter(value, row, index)
{
	var html = sprintf('<button type="button" class="btn btn-primary action-btn tab_oui_grid_remove_row" %s><i class="fa fa-trash" aria-hidden="true"></i></button>', row.custom == 1 ? '' : 'disabled');

    // if (row.custom == 1)
	// {
    // 	html += sprintf('<a href="javascript:epm_advanced_tab_oui_manager_bt_del(%s)" class="delAction"><i class="fa fa-trash"></i></a>', value);
	// }
    // else
	// {
    // 	html += '<i class="fa fa-trash"></i>';
    // }
    return html;
}

function epm_advanced_tab_oui_manager_grid_customFormatter(value, row, index)
{
	var html = '<i class="fa %s"></i> %s';
    if (value == 1)
	{
    	html = sprintf(html, "fa-pencil-square-o", _("Custom"));
	}
    else
	{
		html = sprintf(html, "fa-lock", _("Required"));
    }
    return html;
}

function epm_advanced_tab_oui_manager_bt_del(id_del = null)
{
	if (id_del === "" || id_del === null || id_del === undefined)
	{
		fpbxToast(_('Missing ID!'), '', 'error');
		return false;
	}
	else if (isNaN(id_del))
	{
		fpbxToast(_('ID is not a number!'), '', 'error');
		return false;
	}
	fpbxConfirm(
		_('Are you sure you want to delete the OUI?'),
		_("YES"), _("NO"),
		function()
		{
			var data_ajax = {
				'module': 	  "endpointman",
				'module_sec': "epm_advanced",
				'module_tab': "oui_manager",
				'command': 	  "oui_del",
				'id_del': 	  id_del
			};
			epm_gloabl_manager_ajax(data_ajax, function(status, data) {
				if (status === true)
				{
					fpbxToast("OUI delete Success!", '', 'success');
					$("#epm_advanced_tab_oui_grid").bootstrapTable('refresh');
				}
			});
		}
	);
}

/**
 * Fetch the list of brands via AJAX and populate the select element in the modal.
 * 
 * @param {jQuery} modal - jQuery object representing the modal container.
 * @param {jQuery} select_brand - jQuery object representing the select element for brands.
 * @param {function} [callback] - Optional callback function to be called after the AJAX request.
 *                                 It receives two parameters: 
 *                                 - {boolean} status: Whether the request was successful or not.
 *                                 - {Object|null} data: The data returned from the server, or null on error.
 * @returns {boolean} Returns `false` if the `modal` or `select_brand` are invalid, otherwise performs the AJAX request.
 * 
 * @example
 * // Example 1: Basic usage with a modal and select element
 * var modal = $('#myModal');
 * var select_brand = modal.find('#modal_form_new_oui_brand');
 * epm_advanced_tab_oui_manager_new_list_brands(modal, select_brand, function(status, data) {
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
 * var select_brand = modal.find('#modal_form_new_oui_brand');
 * epm_advanced_tab_oui_manager_new_list_brands(modal, select_brand);
 */
function epm_advanced_tab_oui_manager_new_list_brands(modal, select_brand, callback)
{
	// Set callback to function if not defined
	callback = callback || function(status, data) { };

	// Check if the modal is valid
	if (typeof modal === 'undefined' || modal === null || modal === "" || modal === false)
	{
		callback(false, null);
		return false;
	}

	// Check if the select_brand is valid
	if (typeof select_brand === 'undefined' || select_brand === null || select_brand === "" || select_brand === false)
	{
		callback(false, null);
		return false;
	}

	modal.find('select').empty().val('').selectpicker('refresh');
	$.ajax({
		type: 'POST',
		url: window.FreePBX.ajaxurl,
		data: {
			'module'	: "endpointman",
			'module_sec': "epm_advanced",
			'module_tab': "oui_manager",
			'command'	: "oui_brands"
		},
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
				$.each(data.brands, function(index, value)
				{
					select_brand.append($('<option>', {
						value: value.id,
						text: value.name,
						selected: value.is_select
					}));
				});
				select_brand.selectpicker('refresh');
			}
			else
			{
				fpbxToast(data.message, '', 'error');
			}
			callback(data.status, data);
		}
	});
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