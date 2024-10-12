<?php if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); } ?>

<br />
<div class="container-fluid">
    <div class="row">

        <div class="col-12 col-sm-12 col-md-12 col-lg-6 col-xl-4">
            <div class="section_tree">
                <div id="toolbar_poce_tree" class="d-flex flex-wrap toolbar" role="toolbar">

                <div class="d-flex align-items-center ms-md-1 mt-1 mb-1 toolbar-buttons">
                        <button id="poceReloadTree" class="btn btn-primary me-2" title="<?= _("Reload")?>">
                            <i class="fa fa-refresh" aria-hidden="true"></i>
                        </button>
                        <div class="btn-group" role="group">
                            <button id="poceExpandTree" class="btn btn-primary" title="<?= _("Expand All")?>">
                                <i class="fa fa-expand" aria-hidden="true"></i>
                            </button>
                            <button id="poceCollapseTree" class="btn btn-primary" title="<?= _("Collapse All")?>">
                                <i class="fa fa-compress" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>

                    <div class="flex-grow-1 d-flex align-items-center ms-md-1 mt-1 mb-1 toolbar-search">
                        <div class="input-group w-100">
                            <input id="poce_tree_files_search" type="text" class="form-control" placeholder="<?= _("Search...") ?>" aria-label="Search">
                            <div class="input-group-append">
                                <div class="input-group-text d-flex align-items-center">
                                    <label for="poce_tree_files_search_show_only" class="me-2 mb-0"><i class="fa fa-eye-slash" aria-hidden="true"></i></label>
                                    <input type="checkbox" id="poce_tree_files_search_show_only" aria-label="Show Only">
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <div id="poce_tree_files" class="jstree-container"></div>
            </div>
        </div>
        
        <div class="col-12 col-sm-12 col-md-12 col-lg-6 col-xl-8">
            <div class="card section_editor">
                <h6 class="card-header"><?= _('Product:') ?> <span id="poce_NameProductSelect"><?= _("No Selected") ?></span></h6>
                <div class="card-body">
                    <form method="post" action="" name="form_config_text_sec_button" id="form_config_text_sec_button">
                        <input type="hidden" name="type_file" value="" />
                        <input type="hidden" name="sendid" value="" />
                        <input type="hidden" name="product_select" value="" />
                        <input type="hidden" name="original_name" value="" />
                        <input type="hidden" name="filename" value="" />
                        <input type="hidden" name="location" value="" />
                        <input type="hidden" name="datosok" value="false" />
                    
                        <div id="toolbar_poce_files" class="d-flex flex-wrap toolbar" role="toolbar" style="gap: 1rem;">

                            <div class="flex-grow-1 d-flex align-items-center ms-md-1 mt-1 mb-1 toolbar-save-as">
                                <div class="input-group w-100">
                                    <div class="input-group-prepend">
                                        <label class="input-group-text" for="save_as_name"><?= _('File Name') ?></label>
                                    </div>
                                    <input type="text" class="form-control" name="save_as_name" id="save_as_name" value="" placeholder="<?= _('New Name File...') ?>">
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-secondary" id="tab_poce_bt_save_as" name="button_save_as" title="<?= _("File is permanently saved and not over-written during next package update.") ?>">
                                            <i class="fa fa-floppy-o"></i> <?= _('Save As...') ?>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex align-items-center ms-md-1 mt-1 mb-1 toolbar-buttons">
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-primary" id="tab_poce_bt_share" name="button_share" title="<?= _("Upload this configuration file to the 'Provisioner.net' Team. Files shared are confidential and help improve the quality of releases.") ?>">
                                        <i class="fa fa-upload"></i> <?= _('Share') ?>
                                    </button>
                                    <button type="button" class="btn btn-secondary" id="tab_poce_bt_save" name="button_save" title="<?= _("File may be over-written during next package update. We suggest also using the 'Share' button below to improve the next release.") ?>">
                                        <i class="fa fa-floppy-o"></i> <?= _('Save') ?>
                                    </button>
                                    <button type="button" class="btn btn-danger" id="tab_poce_bt_delete" name="button_delete">
                                        <i class="fa fa-trash-o"></i> <?= _('Delete') ?>
                                    </button>
                                </div>
                                <div class="btn-group" role="group">
                                    <button type="button" class='btn btn-default' id="tab_poce_bt_src_full_screen" name="bt_source_full_screen" title="<?= _("Key F11 Full Screen, ESC Exit FullScreen.") ?>">
                                        <i class="fa fa-arrows-alt"></i> <?= _('F11') ?>
                                    </button>
                                </div>
                            </div>
                        </div>            
                    </form>

                    <div id="poce_box_sec_source" class="poce_box_sec_source">
                        <div id="config_textarea" style="width: 100%; height: 100%;"></div>
                        <div id="config_jsoneditor" style="width: 100%; height: 100%;"></div>
                    </div>
                </div>
                <div class="card-footer text-muted">
                    <p class="card-text"><?= _("File:") ?> <span id="poce_file_name_path"><?= _("No Selected") ?></span></p>
                </div>
            </div>
        </div>
    </div>
</div>