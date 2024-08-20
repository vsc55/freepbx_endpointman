<?php if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); } ?>

<br />
<div class="container-fluid">
    <div class="row">
        <div class="col-sm-9">


            <div class="section">
                <div class="row">
                    <div class="col-xs-4 text-center" id="select_product_list_files_config">
                        <div class="btn-group">
                            <button type="button" class="btn btn-secondary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="label label-default label-pill">0</span>
                                <?= _("Local File Configs") ?> <i class="fa fa-chevron-down"></i>
                            </button>
                            <div class="dropdown-menu">
                                <a class="dropdown-item disabled" href="#"><?= _('Emtry') ?></a>
                            </div>
                        </div>
                    </div>
                    <div class="col-xs-4 text-center" id="select_product_list_files_template_custom">
                        <div class="btn-group">
                            <button type="button" class="btn btn-secondary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="label label-default label-pill">0</span>
                                <?= ("Custom Template Files") ?> <i class="fa fa-chevron-down"></i>
                            </button>
                            <div class="dropdown-menu">
                                <a class="dropdown-item disabled" href="#"><?= _('Emtry') ?></a>
                            </div>
                        </div>
                    </div>
                    <div class="col-xs-4 text-center" id="select_product_list_files_user_config">
                        <div class="btn-group">
                            <button type="button" class="btn btn-secondary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="label label-default label-pill">0</span>
                                <?= _("User File Configs") ?> <i class="fa fa-chevron-down"></i>
                            </button>
                            <div class="dropdown-menu">
                                <a class="dropdown-item disabled" href="#"><?= _('Emtry') ?></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>



            <div class="section">
                <div class="section">
                    <div class="row">
                        <div class="col-md-12">
                            <label><?= _('Product:') ?></label> <code id="poce_NameProductSelect"><?= _("No Selected") ?></code>
                        </div>
                    </div>
                </div>
                <div class="section">
                    <div class="row">
                        <div class="col-xs-12">
                            <form method="post" action="" name="form_config_text_sec_button">
                                <input type="hidden" name="type_file" value="" />
                                <input type="hidden" name="sendid" value="" />
                                <input type="hidden" name="product_select" value="" />
                                <input type="hidden" name="original_name" value="" />
                                <input type="hidden" name="filename" value="" />
                                <input type="hidden" name="location" value="" />
                                <input type="hidden" name="datosok" value="false" />
                                
                                <div id="box_sec_source" class="row">
                                    <div class="col-xs-12">
                                        <button type="button" class='btn btn-default btn-sm pull-xs-right' name="bt_source_full_screen" onclick="epm_advanced_tab_poce_bt_acction(this);" disabled><i class="fa fa-arrows-alt"></i> <?= _('Full Screen F11') ?></button>
                                        <label class="control-label" for="config_textarea"><i class="fa fa-file-code-o" data-for="config_textarea"></i> <?= _("Content of the file:") ?></label> <code class='inline' id='poce_file_name_path'><?= _("No Selected") ?></code>
                                        <textarea name="config_textarea" id="config_textarea" rows="5" disabled></textarea>
                                        <i class='fa fa-exclamation-triangle'></i> <font style="font-size: 0.8em; font-style: italic;"><?= _("NOTE: Key F11 Full Screen, ESC Exit FullScreen.") ?></font>
                                    </div>
                                </div>
                                
                                <div id="box_bt_save" class="row">
                                    <div class="col-xs-9">
                                        <i class='fa fa-exclamation-triangle'></i> <font style="font-size: 0.8em; font-style: italic;"><?= _("NOTE: File may be over-written during next package update. We suggest also using the <b>Share</b> button below to improve the next release.") ?></font>
                                    </div>
                                    <div class="col-xs-3 text-right">
                                        <button type="button" class='btn btn-default' name="button_save" onclick="epm_advanced_tab_poce_bt_acction(this);" disabled><i class='fa fa-floppy-o'></i> <?= _('Save')?></button>
                                        <button type="button" class='btn btn-danger' name="button_delete" onclick="epm_advanced_tab_poce_bt_acction(this);" disabled><i class='fa fa-trash-o'></i> <?= _('Delete')?></button>
                                    </div>
                                </div>
                                
                                <div id="box_bt_save_as" class="row">
                                    <div class="col-xs-7">
                                        <i class='fa fa-exclamation-triangle'></i> <font style="font-size: 0.8em; font-style: italic;"><?= _("NOTE: File is permanently saved and not over-written during next package update.") ?></font>
                                    </div>
                                    <div class="col-xs-5 text-right">
                                        <div class="input-group">
                                            <input type="text" class="form-control" name="save_as_name" id="save_as_name" value="" placeholder="<?= _('Name File...') ?>" disabled>
                                            <span class="input-group-append">
                                                <button type="button" class='btn btn-default' name="button_save_as" onclick="epm_advanced_tab_poce_bt_acction(this);" disabled><i class='fa fa-floppy-o'></i> <?= _('Save As...') ?></button>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div id="box_bt_share" class="row">
                                    <div class="col-xs-9">
                                        <i class='fa fa-exclamation-triangle'></i> <font style="font-size: 0.8em; font-style: italic;"> <?= _("Upload this configuration file to the <b>Provisioner.net Team</b>. Files shared are confidential and help improve the quality of releases.") ?></font>
                                    </div>
                                    <div class="col-xs-3 text-right">
                                        <button type="button" class="btn btn-default" name="button_share" onclick="epm_advanced_tab_poce_bt_acction(this);" disabled><i class="fa fa-upload"></i> <?= _('Share') ?></button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        <div class="col-sm-3 bootnav">
            <div id="lista_brand_bootnav" class="list-group">
	            <a href='#' class='list-group-item bootnavloadingajax text-center'><i class='fa fa-spinner fa-spin'></i> <?= _('Loading...') ?></a>
            </div>
        </div>
    </div>
</div>