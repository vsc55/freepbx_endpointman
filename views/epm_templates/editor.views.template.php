<?php
	if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }
?>

<form action="config.php?display=epm_templates" method="post" id="epm_template_edit_form" name="epm_template_edit_form" class="fpbx-submit">
	<?php if (isset($_REQUEST['silent_mode'])) { echo '<input name="silent_mode" id="silent_mode" type="hidden" value="1">'; } ?>
	<input name="id" id="id" type="hidden" value="<?php echo $dtemplate['hidden_id']; ?>">
	<input name="custom" id="custom" type="hidden" value="<?php echo $dtemplate['hidden_custom'] ; ?>">
        <input name="command" type="hidden" value="save_template">
	<div class="">
		<div class="row">
			<div class="col-md-6">
	            <div class="row">
             		<div class="col-md-12">
						<div class="panel panel-default">
							<div class="panel-heading">
								<h3 class="panel-title"><i class="fa fa-info-circle fa-lg"></i> <?php echo _("Info Template")?></h3>
							</div>
							<div class="panel-body">
                                <table class="table">
                                    <tr>
                                        <td class="col-md-3"><b><?php echo _("Template Name:")?></b></td>
                                        <td class="col-md-9"><?php if ($request['custom'] != 0): ?>Custom Template: Extension <?php echo $dtemplate['ext']; ?><?php else: ?><?php echo $dtemplate['template_name']; ?><?php endif; ?></td>
                                    </tr>
                                    <tr>
                                        <td class="col-md-3"><b><?php echo _("Product Line:")?></b></td>
                                        <td class="col-md-9"><?php echo $dtemplate['product']; ?></td>
                                    </tr>
                                    <tr>
                                        <td class="col-md-3"><b><?php echo _("Clone of Model:")?></b></td>
                                    <?php if ($request['custom'] != 0): ?>
                                        <td class="col-md-9"><?php echo $dtemplate['model'] ?></td>
                                    <?php else: ?>
                                        <td class="col-md-9">
                                        <select class="form-control selectpicker show-tick" data-style="btn-primary" data-live-search-placeholder="Search" data-live-search="true" name="model_list" id="model_list" >
                                            <?php
                                            foreach($dtemplate['models_ava'] as $row) {
                                                echo '<option value="'.$row['value'].'" '.(!empty($row['selected']) ? "selected" : "").'>'.$row['text'].'</option>';
                                            }
                                            ?>
                                        </select>
										</td>
                                    </tr>
                                    <tr>
                                        <td class="col-md-3"><b><?php echo _("Display:")?></b></td>
                                        <td class="col-md-9">
                                            <select class="form-control selectpicker show-tick" data-style="btn-primary" data-live-search-placeholder="Search" data-live-search="true" name="area_list" id="area_list" onchange="epm_template_edit_select_area_list(this)">
                                                <option value=""></option>
                                                <?php 	
                                                foreach($dtemplate['area_ava'] as $row) {
                                                    echo '<option value="'.$row['value'].'" '.(!empty($row['selected']) ? "selected" : "").'>'.$row['text'].'</option>';
                                                }
                                                ?>
                                            </select>
                                            <strong><?php echo _('Line settings on this page')?></strong><i><font size="-2"> (Note: This is NOT the number of supported lines on the phone(s))</font></i>
                                        </td>
                                    <?php endif; ?>
                                    </tr>
                                </table>
							</div>
						</div>
					</div>
	           </div>
           </div>
			<div class="col-md-6">
            	<div class="row">
             		<div class="col-md-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h3 class="panel-title"><i class="fa  fa-file-code-o fa-lg"></i> <?php echo _("Settings")?></h3>
                            </div>
                            <div class="panel-body">
                                <button type="button" class="btn btn-primary btn-lg btn-block" data-toggle="modal" data-target="#CfgGlobalTemplate"><i class='fa fa-pencil-square-o'></i> <?php echo _('Edit Global Settings Overrides')?></button>
                            </div>
                        </div>
                	</div>
				</div>
                <div class="row">
             		<div class="col-md-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h3 class="panel-title"><i class="fa  fa-file-code-o fa-lg"></i> <?php echo _("Edit File Configurations")?></h3>
                            </div>
                            <div class="panel-body">
                                <?php 
								
								//$list_all_files = array();
                                //if ($dtemplate['alt'] != 0) {
								//	$list_all_files = array_merge($dtemplate['alt_configs'], $dtemplate['only_configs']);
								//}
								//else {
								//	$list_all_files = $dtemplate['only_configs'];
								//}
                                
								
								$list_all_files = FreePBX::Endpointman()->epm_templates->edit_template_display_files_list($_REQUEST['idsel'],$_REQUEST['custom']);
                                foreach($list_all_files as $row): 
								//config.php?display=epm_templates&subpage=&custom=0&idsel=41
								$row['idrefbox'] = $row['id']."_".$row['id_d']."_".$row['id_p'];
                                ?>
								<div class="col-md-12">
                                	<div class="input-group input-group-br files_edit_configs" name="boxselect_<?php echo $row['idrefbox']; ?>" id="boxselect_<?php echo $row['idrefbox']; ?>">
                                        <select 
                                        	class="form-control selectpicker show-tick" 
                                            data-url="ajax.php?module=endpointman&amp;module_sec=epm_templates&amp;module_tab=editor&amp;command=list_files_edit&amp;idsel=<?php echo $_REQUEST['idsel']; ?>&amp;custom=<?php echo $_REQUEST['custom']; ?>&amp;namefile=<?php echo $row['name']; ?>"
											data-cache="false"
                                            data-id = "id"
											data-label = "name"
                                            data-style = ""
											data-live-search-placeholder = "Search..."
											data-live-search = "true"
                                            name="config_files[<?php echo $row['id']; ?>]" 
                                            id="config_files[<?php echo $row['id']; ?>]">
                                        </select>
                                        <span class="input-group-append">
                                            <button class="btn btn-default" type="button" name="bt_boxselect_<?php echo $row['idrefbox']; ?>" id="bt_boxselect_<?php echo $row['idrefbox']; ?>"><i class='fa fa-files-o'>&nbsp;</i></button>
                                        </span>
                                    </div>
								</div>
                                <?php endforeach; ?> 
                            </div>
                        </div>
	                </div>
                </div>
    		</div>
		</div>
	</div>
	
    
    
    
    
    
    
    
    
    

	<?php /*           
	{if condition="$alt != 0"}
        {loop name="alt_configs"}
            <p><strong><?php echo _('Edit File Configurations for:')?></strong>
            <a href="#" onclick="return popitup('config.php?type=tool&display=epm_config&amp;quietmode=1&amp;handler=file&amp;file=popup.html.php&amp;module=endpointman&amp;pop_type=alt_cfg_edit', '{$value.name}')">
            <code>{$value.name}</code> <i class='icon-pencil blue' ALT='<?php echo _('Edit')?> {$value.name}'></i></a>
            <br>
			
			
            <strong><?php echo _('Select Alternative File Configurations for')?> <code>{$value.name}</code></strong>
            <select name="{$value.name}" id="altconfig_{$value.name}">';
            <option value="0_{$value.name}">{$value.name} (No Change)</option>';
            {loop name="value.list"}
                <option value="{$value.id}_{$value.name}" {if condition="isset($value.selected)"}selected{/if}>{$value.name}</option>';
            {/loop}
            </select>
            <br/>
        {/loop}
            <br/>
	{/if}
    
    {loop name="only_configs"}
        <strong><?php echo _('Edit File Configurations for:')?></strong>&nbsp;
        <a href="#" onclick="return popitup2('config.php?type=tool&display=epm_config&amp;quietmode=1&amp;handler=file&amp;file=popup.html.php&amp;module=endpointman&amp;pop_type=alt_cfg_edit', '{$value.name}')"><code>{$value.name}</code>&nbsp;<i class='icon-pencil blue' ALT='<?php echo _('Edit')?>'></i></a>
        <br/>
    {/loop}
	*/ ?>
    
    
    
    
    
    
    
    
    
    
	<div class="">
		<div class="row">
			<div class="col-md-12">
            
				<div class="panel panel-default">
					<div class="panel-heading">
						<h3 class="panel-title"><i class="fa  fa-code"></i> <?php echo _("Example of Variables allowed in boxes")?></h3>
					</div>
					<div class="panel-body">
						<ul class='nobullets'>
							<li><code class='inline'>{$username.line.1}</code> <?php echo _('Device\'s Username (usually the extension)')?></li>
							<li><code class='inline'>{$displayname.line.1}</code> <?php echo _('Device\'s Description in FreePBX (Usually the Full Name)')?></li>
							<li><code class='inline'>{$server_host.line.1}</code> <?php echo _('Server IP For Line 1')?></li>
							<li><code class='inline'>{$server_port.line.1}</code> <?php echo _('Server Port For Line 1')?></li>
							<li><code class='inline'>{$mac}</code> <?php echo _('Device\'s Mac Address')?></li>
							<li><code class='inline'>{$model}</code> <?php echo _('Device\'s Model')?></li>
						</ul>
					</div>
				</div>
                
			</div>
		</div>
	</div>
	
	
	
	
	
    
	<div class="">
		<div class="row">
			<div class="col-md-12">
				<div id="main-slider" class="liquid-slider">
				<?php foreach($dtemplate['template_editor'] as $row) : ?> <!-- INI foreach de tabs -->
					<div>
                        <h2 class="title"><?php echo $row['title'];?></h2>
                    	<?php 
						foreach($row['data'] as $srow) 	//INI foreach objetos de cada tab
						{
							switch ($srow['type']) {
								case 'input':
									//INI INPUT-TEXT
									?>
                                    <div class="element-container">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="row">
                                                    <div class="form-group">
                                                        <div class="col-md-3">
                                                            <label class="control-label" for="<?php echo $srow['key']; ?>">
                                                            <?php 
                                                            if (! isset($srow['tooltip'])) { echo $srow['description']; }
                                                            else { echo '<a href="#" class="info">'.$srow['description'].'<span>'.$srow['tooltip'].'</span></a>'; }
                                                            ?>
                                                            </label>
                                                            <i class="fa fa-question-circle fpbx-help-icon" data-for="<?php echo $srow['key']; ?>"></i>
                                                        </div>
                                                        <div class="col-md-9">
															<input type="text" class="form-control" id="<?php echo $srow['key']; ?>" name="<?php echo $srow['key']; ?>" placeholder="" value="<?php echo $srow['value']; ?>" size="<?php echo (isset($srow['max_chars']) ? $srow['max_chars'] : "90" ); ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <span class="help-block fpbx-help-block" id="<?php echo $srow['key']; ?>-help">Texto ayuda ("<?php echo $srow['key']; ?>")!</span>
                                            </div>
                                        </div>
                                    </div>
                                    <?php
									//END INPUT-TEXT
									break;
									
								case 'textarea':
									//INI TEXTAREA
									?>
                                     <div class="element-container">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="row">
                                                    <div class="form-group">
                                                        <div class="col-md-3">
                                                            <label class="control-label" for="<?php echo $srow['key']; ?>">
                                                            <?php 
                                                            if (! isset($srow['tooltip'])) { echo $srow['description']; }
                                                            else { echo '<a href="#" class="info">'.$srow['description'].'<span>'.$srow['tooltip'].'</span></a>'; }
                                                            ?>
                                                            </label>
                                                            <i class="fa fa-question-circle fpbx-help-icon" data-for="<?php echo $srow['key']; ?>"></i>
                                                        </div>
                                                        <div class="col-md-9">
	                      									<textarea class="form-control" id="<?php echo $srow['key']; ?>" name="<?php echo $srow['key']; ?>" rows="<?php echo (isset($srow['rows']) ? $srow['rows'] : "2" ); ?>" cols="<?php echo (isset($srow['cols']) ? $srow['cols'] : "20" ); ?>"><?php echo $srow['value']; ?></textarea>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <span class="help-block fpbx-help-block" id="<?php echo $srow['key']; ?>-help">Texto ayuda ("<?php echo $srow['key']; ?>")!</span>
                                            </div>
                                        </div>
                                    </div>
                                    <?php
									//END TEXTAREA
									break;
									
								case 'radio':
									//INI RADIO
									?>
                                     <div class="element-container">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="row">
                                                    <div class="form-group">
                                                        <div class="col-md-12">
                                                            <label class="control-label" for="<?php echo $srow['key']; ?>">
                                                            <?php 
                                                            if (! isset($srow['tooltip'])) { echo $srow['description']; }
                                                            else { echo '<a href="#" class="info">'.$srow['description'].'<span>'.$srow['tooltip'].'</span></a>'; }
                                                            ?>
                                                            </label>
                                                            <i class="fa fa-question-circle fpbx-help-icon" data-for="<?php echo $srow['key']; ?>"></i>
                                                            <div class="radioset pull-xs-right">
                                                                <?php
																$z=0;
                                                                foreach($srow['data'] as $lrow) {
                                                                    /*
                                                                    echo '[<label>';
                                                                    echo (! isset($lrow['tooltip']) ? $lrow['description'] : '<a href="#" class="info">'.$lrow['description'].'<span>'.$lrow['tooltip'].'</span></a>').':';
                                                                    //echo '<input type="radio" name="'.$lrow['key'].'" id="'.$lrow['key'].'" value="'.$lrow['value'].'" '.(array_key_exists('checked', $lrow['value']) ? $lrow['checked'] : '').' >';
                                                                    echo '<input type="radio" name="'.$lrow['key'].'" id="'.$lrow['key'].'" value="'.$lrow['value'].'" >';
                                                                    echo '</label>]';
                                                                    
                                                                    <input type="radio" class="form-control" id="addtocdrno" name="addtocdr" value="0" <?php echo ($addtocdr == '1' ? '' : 'CHECKED'); ?>>
                                                                    <label for="addtocdrno"><?php echo _("No")?></label>
                                                                    */
                                                                    echo '<input type="radio" class="form-control" id="'.$lrow['key']. $z . '" name="'.$srow['key'].'" value="'.$lrow['value'].'" '.(array_key_exists('checked',$lrow)? 'checked' : '').' >';
                                                                    echo '<label for="'.$lrow['key']. $z .'">'.(! isset($lrow['tooltip']) ? $lrow['description'] : '<a href="#" class="info">'.$lrow['description'].'<span>'.$lrow['tooltip'].'</span></a>').'</label>';
                                                                $z++;
																}
                                                                ?>
    
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <span class="help-block fpbx-help-block" id="<?php echo $srow['key']; ?>-help">Texto ayuda ("<?php echo $srow['key']; ?>")!</span>
                                            </div>
                                        </div>
                                    </div>
                                    <?php
									//END RAIDO
									break;
									
								case 'list':
									//INI LIST
									?>
                                    <div class="element-container">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="row">
                                                    <div class="form-group">
                                                        <div class="col-md-3">
                                                            <label class="control-label" for="<?php echo $srow['key']; ?>">
                                                            <?php 
                                                            if (! isset($srow['tooltip'])) { echo $srow['description']; }
                                                            else { echo '<a href="#" class="info">'.$srow['description'].'<span>'.$srow['tooltip'].'</span></a>'; }
                                                            ?>
                                                            </label>
                                                            <i class="fa fa-question-circle fpbx-help-icon" data-for="<?php echo $srow['key']; ?>"></i>
                                                        </div>
                                                        <div class="col-md-9">
                                                        	<?php
	                    									echo '<select name="'.$srow['key'].'" id="'.$srow['key'].'" class="form-control selectpicker show-tick" data-style="" data-live-search-placeholder="Search" data-live-search="true">';
															foreach($srow['data'] as $lrow) 
															{
																//echo '<option value="'.$lrow['value'].'" '.(array_key_exists('selected',$lrow['selected'])? $lrow['value'] : '').' >'.$lrow['description'].'</option>';
																//echo '<option value="'.$lrow['value'].'" '.($lrow['value'] == $lrow['selected'] ? 'selected' : '').' >'.$lrow['description'].'</option>';
																echo '<option value="'.$lrow['value'].'" '.(array_key_exists('selected',$lrow)? 'selected' : '').' >'.$lrow['description'].'</option>';
															}
															echo '</select>';
															?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <span class="help-block fpbx-help-block" id="<?php echo $srow['key']; ?>-help">Texto ayuda ("<?php echo $srow['key']; ?>")!</span>
                                            </div>
                                        </div>
                                    </div>
                                    <?php
									//END LIST
									break;
									
									
								case 'checkbox':
									//INI CHEBOX
									//PENDIENTE UPATEAR.....
									?>
                                    <div class="element-container">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="row">
                                                    <div class="form-group">
                                                        <div class="col-md-3">
                                                            <label class="control-label" for="<?php echo $srow['key']; ?>">
                                                            <?php 
                                                            if (! isset($srow['tooltip'])) { echo $srow['description']; }
                                                            else { echo '<a href="#" class="info">'.$srow['description'].'<span>'.$srow['tooltip'].'</span></a>'; }
                                                            ?>
                                                            </label>
                                                            <i class="fa fa-question-circle fpbx-help-icon" data-for="<?php echo $srow['key']; ?>"></i>
                                                        </div>
                                                        <div class="col-md-9">
															<input type="checkbox" class="form-control" name="<?php echo $srow['key']; ?>" id="<?php echo $srow['key']; ?>" value="<?php echo $srow['value']; ?>">';
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <span class="help-block fpbx-help-block" id="<?php echo $srow['key']; ?>-help">Texto ayuda ("<?php echo $srow['key']; ?>")!</span>
                                            </div>
                                        </div>
                                    </div>
                                    <?php
									//END CHEKBOX
									break;
									
								case 'break':
									//INI BREAK
									?>
                                     <div class="element-container">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="row">
                                                    <div class="form-group">
                                                        <div class="col-md-12">&nbsp;</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php
									//END BREAK
									break;
										
								case 'group':
									//INI group
									?>
                                     <div class="element-container">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="row">
                                                    <div class="form-group">
                                                        <div class="col-md-12">
                                                        	<hr>
															<?php echo '<h3>'.(! isset($srow['tooltip']) ? $srow['description'] : '<a href="#" class="info">'.$srow['description'].'<span>'.$srow['tooltip'].'</span></a>').'</h3><br />'; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php
									//END group
									break;
											
								case 'header':
									//INI HEADER
									?>
                                     <div class="element-container">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="row">
                                                    <div class="form-group">
                                                        <div class="col-md-12">
                                                        	<?php echo '<strong>'.(! isset($srow['tooltip']) ? $srow['description'] : '<a href="#" class="info">'.$srow['description'].'<span>'.$srow['tooltip'].'</span></a>').'</strong><br/>'; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php
									//END HEADER
									break;
							}
							
							if (isset($srow['aried'])) {
								//INI ARIED
								?>
								 <div class="element-container">
									<div class="row">
										<div class="col-md-12">
											<div class="row">
												<div class="form-group">
													<div class="col-md-12">
														<?php echo '<label><input type="checkbox" name="ari_'.$srow['ari']['key'].'" '.(isset($srow['ari']['checked']) ? $srow['ari']['checked'] : '' ).' >End User Editable (<a href="http://projects.colsolgrp.net/documents/29" target="_blank">Through ARI Module</a>)</label>'; ?>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
								<?php
								//END ARIEND
							}
							
						} //END foreach objetos de cada tabs
						?>
	                    </div>
					<?php endforeach; ?> <!-- END foreach tabs -->
				</div>
    		</div>
	    </div>
    </div>
    
</form>
<?php 

return;



















/*
	<label>Reboot Phone(s) <input type='checkbox' name='epm_reboot'></label>
	<br />
	<button type="submit" name="button_save_template"><i class='icon-save blue'></i> <?php echo _('Save Template');?></button>


	
	<div class="coda-slider-wrapper">
		<div class="coda-slider preload" id="coda-slider-9">
		<?php 
		foreach($dtemplate['template_editor'] as $row) {
		?>
			<div class="panel">
				<div class="panel-wrapper">
					<h2 class="title"><?php echo $row['title'];?></h2>
					
					
					<table width="100%" border="0" cellspacing="0" cellpadding="0">
						<?php
						foreach($row['data'] as $srow) {
							echo "<tr>";
							switch ($srow['type']) {
								case 'input':
									echo '<td nowrap>'.(! isset($srow['tooltip']) ? $srow['description'] : '<a href="#" class="info">'.$srow['description'].'<span>'.$srow['tooltip'].'</span></a>').':</td><td nowrap><input type="text" name="'.$srow['key'].'" id="'.$srow['key'].'" value="'.$srow['value'].'" size="'.(isset($srow['max_chars']) ? $srow['max_chars'] : "90" ).'">';
									break;
									
								case 'textarea':
									echo '<td nowrap>'.(! isset($srow['tooltip']) ? $srow['description'] : '<a href="#" class="info">'.$srow['description'].'<span>'.$srow['tooltip'].'</span></a>').':</td><td nowrap><textarea rows="'.(isset($srow['rows']) ? $srow['rows'] : "2" ).'" cols="'.(isset($srow['cols']) ? $srow['cols'] : "20" ).'" name="'.$srow['key'].'" id="'.$srow['key'].'">'.$srow['value'].'</textarea>';
									break;
									
								case 'radio':
									echo '<td nowrap>'.(! isset($srow['tooltip']) ? $srow['description'] : '<a href="#" class="info">'.$srow['description'].'<span>'.$srow['tooltip'].'</span></a>').':</td><td nowrap>';
									foreach($srow['data'] as $lrow) {
										echo '[<label>';
										echo (! isset($lrow['tooltip']) ? $lrow['description'] : '<a href="#" class="info">'.$lrow['description'].'<span>'.$lrow['tooltip'].'</span></a>').':';
										//echo '<input type="radio" name="'.$lrow['key'].'" id="'.$lrow['key'].'" value="'.$lrow['value'].'" '.(array_key_exists('checked', $lrow['value']) ? $lrow['checked'] : '').' >';
										echo '<input type="radio" name="'.$lrow['key'].'" id="'.$lrow['key'].'" value="'.$lrow['value'].'" >';
										echo '</label>]';
									}
									break;
									
								case 'list':
									echo '<td nowrap>'.(! isset($srow['tooltip']) ? $srow['description'] : '<a href="#" class="info">'.$srow['description'].'<span>'.$srow['tooltip'].'</span></a>').':</td><td nowrap>';
									echo '<select name="'.$srow['key'].'" id="'.$srow['key'].'">';
									foreach($srow['data'] as $lrow) 
									{
										//echo '<option value="'.$lrow['value'].'" '.(array_key_exists('selected',$lrow['selected'])? $lrow['value'] : '').' >'.$lrow['description'].'</option>';
										echo '<option value="'.$lrow['value'].'" >'.$lrow['description'].'</option>';
									}
									echo '</select>';
									break;
									
									
								case 'checkbox':
									echo '<td nowrap>'.(! isset($srow['tooltip']) ? $srow['description'] : '<a href="#" class="info">'.$srow['description'].'<span>'.$srow['tooltip'].'</span></a>').':</td><td nowrap><input type="checkbox" name="'.$srow['key'].'" id="'.$srow['key'].'" value="'.$srow['value'].'">';
									break;
									
								case 'break':
									echo '<td nowrap colspan="2">&nbsp;';
									break;
										
								case 'group':
									echo '<td nowrap colspan="2"><hr><h3>'.(! isset($srow['tooltip']) ? $srow['description'] : '<a href="#" class="info">'.$srow['description'].'<span>'.$srow['tooltip'].'</span></a>').'</H3>';
									break;
											
								case 'header':
									echo '<td nowrap colspan="2"><strong>'.(! isset($srow['tooltip']) ? $srow['description'] : '<a href="#" class="info">'.$srow['description'].'<span>'.$srow['tooltip'].'</span></a>').'</strong>';
									break;
							}
							
							if (isset($srow['aried'])) {
								echo '<label><input type="checkbox" name="ari_'.$srow['ari']['key'].'" '.(isset($srow['ari']['checked']) ? $srow['ari']['checked'] : '' ).' >End User Editable (<a href="http://projects.colsolgrp.net/documents/29" target="_blank">Through ARI Module</a>)</label></td>';
							}
							else {
								echo "</td>";
							}
							echo "</tr>";
						}
						?>
					</table>
					
					
				</div>
			</div>
		<?php	
		}
		?>
		</div><!-- .coda-slider -->
	</div><!-- .coda-slider-wrapper -->

<?php


/*

 elseif((isset($_REQUEST['button_save_template'])) AND (isset($_REQUEST['custom']))) {
 	$endpoint->save_template($_REQUEST['id'],$_REQUEST['custom'],$_REQUEST);
 	$default_display = TRUE;
 	if(empty($endpoint->error)) {
 		$endpoint->message['general'] = _('Saved');
 	}
 }

/*

if($default_display) {
	$sql = 'SELECT endpointman_template_list.*, endpointman_product_list.short_name as model_class, endpointman_model_list.model as model_clone, endpointman_model_list.enabled FROM endpointman_template_list, endpointman_model_list, endpointman_product_list WHERE endpointman_model_list.hidden = 0 AND endpointman_template_list.model_id = endpointman_model_list.id AND endpointman_template_list.product_id = endpointman_product_list.id';
 	$template_list =& $endpoint->eda->sql($sql, 'getAll', \PDO::FETCH_ASSOC);
 	$i = 0;
 	$row_out = array();
 	foreach($template_list as $row) {
 		$row_out[$i] = $row;
 		$row_out[$i]['custom'] = 0;
 		if(!$row['enabled']) {
 			$row_out[$i]['model_clone'] = $row_out[$i]['model_clone']."<i>(Disabled)</i>";
 		}
 		$i++;
	}
	$sql = 'SELECT endpointman_mac_list.mac, endpointman_mac_list.id, endpointman_mac_list.model, endpointman_model_list.model as model_clone, endpointman_product_list.short_name as model_class FROM endpointman_mac_list, endpointman_model_list, endpointman_product_list WHERE  endpointman_product_list.id = endpointman_model_list.product_id AND endpointman_mac_list.global_custom_cfg_data IS NOT NULL AND endpointman_model_list.id = endpointman_mac_list.model AND endpointman_mac_list.template_id = 0';
	$template_list =& $db->getAll($sql, array(), \PDO::FETCH_ASSOC);
	foreach($template_list as $row) {
		$sql = 'SELECT  description , line FROM  endpointman_line_list WHERE  mac_id ='. $row['id'].' ORDER BY line ASC';
		$line_list =& $db->getAll($sql, array(), \PDO::FETCH_ASSOC);
		$description = "";
		$c = 0;
		foreach($line_list as $line_row) {
			if($c > 0) {
				$description .= ", ";
			}
			$description .= $line_row['description'];
			$c++;
		}
		$row_out[$i] = $row;
		$row_out[$i]['custom'] = 1;
		$row_out[$i]['name'] = $row['mac'] . "-(" .$description.")";
		$i++;
	}
	
 	$sql = "SELECT DISTINCT endpointman_product_list.* FROM endpointman_product_list, endpointman_model_list WHERE endpointman_product_list.id = endpointman_model_list.product_id AND endpointman_model_list.hidden = 0 AND endpointman_model_list.enabled = 1 AND endpointman_product_list.hidden != 1 AND endpointman_product_list.cfg_dir !=  ''";
 	$template_list =& $db->getAll($sql, array(), \PDO::FETCH_ASSOC);
 	$i = 1;
 	$class_row[0]['value'] = 0;
 	$class_row[0]['text'] = "";
 	foreach($template_list as $row) {
 		$class_row[$i]['value'] = $row['id'];
 		$class_row[$i]['text'] = $row['short_name'];
 		$i++;
 	}
 	$endpoint->tpl->assign("amp_conf_serial", "1");
 	$endpoint->tpl->assign("templates_list", $row_out);
 	$endpoint->tpl->assign("class_list", $class_row);
 	$endpoint->tpl->assign("no_add", $no_add);
 	$endpoint->tpl->assign("debug", $debug);
 }
 */
?>

<script language="javascript" type="text/javascript">
    $().ready(function() {
        $('#coda-slider-9').codaSlider({
            dynamicArrows: false,
            continuous: false
        });
    });
    function Reload() {
        window.location.reload();
    }
        function popitup(url, name) {
            newwindow=window.open(url + '&custom=' + document.getElementById('custom').value + '&tid=' + document.getElementById('id').value + '&value=' + document.getElementById('altconfig_'+ name).value + '&rand=' + new Date().getTime(),'name','height=710,width=800,scrollbars=yes,location=no');
                if (window.focus) {newwindow.focus()}
                return false;
        }
        function popitup2(url, name) {
            newwindow=window.open(url + '&custom=' + document.getElementById('custom').value + '&tid=' + document.getElementById('id').value + '&value=0_' + name + '&rand=' + new Date().getTime(),'name','height=700,width=800,scrollbars=yes,location=no');
                if (window.focus) {newwindow.focus()}
                return false;
        }
		
        function popitup3(url) {
            newwindow=window.open(url + '&custom=' + document.getElementById('custom').value + '&tid=' + document.getElementById('id').value + '&value=0_' + name + '&rand=' + new Date().getTime(),'name','height=700,width=800,scrollbars=yes,location=no');
                if (window.focus) {newwindow.focus()}
                return false;
        }
</script>
