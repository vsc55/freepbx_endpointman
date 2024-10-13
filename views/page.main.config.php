<?php if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); } ?>

<?= $endpoint_warn ?>
<div class="container-fluid" id="epm_config">
	<h1><?= _("End Point Configuration Manager")?></h1>
	<h2><?= _("Package Manager")?></h2>
    <div class="display full-border">
        <div class="row">
            <div class="col-sm-12">
                <div class="fpbx-container">
                    
                    <nav class="navbar navbar-light bg-info">
                        <span class="navbar-brand  mb-0 h1">
                            <button type="button" class="navbar-btn btn btn-default" id="button_check_for_updates" name="button_check_for_updates" disabled="false"><i class="fa fa-refresh"></i> <?= _("Check for Update") ?></button>
                        </span>
                        <form class="d-flex" role="search">
                            <input class="form-control mr-2" type="search" id="search" aria-label="<?= _("Search") ?>" placeholder="<?= _("Search Model...") ?>" />
                            <button class="btn btn-outline-success" type="button"><i class="fa fa-search" aria-hidden="true"></i></button>
                        </form>
                    </nav>
                    <br>

                    <div class="alert alert-info" role="alert">
                        <h3><?= _("Hidden Packages") ?></h3>
                        <div class="input-group mb-3">
                            <div class="input-group-prepend">
                                <label class="input-group-text" for="epm_config_manager_select_hidens"><?= _("Hidden Brands:") ?></label>
                            </div>
                            <select 
                                data-url="ajax.php?module=endpointman&amp;module_sec=epm_config&amp;module_tab=manager&amp;command=list_brand_model_hide"
                                data-cache="false"
                                data-id = ""
                                data-label = ""
                                data-selected-text-format="count > 4"
                                data-size="10"
                                data-style="" 
                                data-live-search-placeholder="<?= _("Search") ?>"" 
                                data-live-search="true" 
                                data-done-button="true"
                                class="selectpicker show-tick form-control form-control"
                                id="epm_config_manager_select_hidens" 
                                name="epm_config_manager_select_hidens"
                                multiple>
                            </select>
                            <div class="input-group-append" style="padding-left: 10px;">
                                <span class="help">
                                    <i class="fa fa-question-circle"></i>
                                    <span style="display: none;"><?php echo _("This list contains all the Brands <br /> that have been hidden. Clicking on any of them will unhide them.") ?></span>
                                </span>
                            </div>   
                        </div>                        
                    </div>

                    <div class="panel panel-primary"> 
                        <div class="panel-heading"> 
                            <h3 class="panel-title"><?= _("List Packages Manager") ?></h3>
                        </div> 
                        <div class="panel-body">
                            <!-- INI PANEL-BODY -->    
                            <ul class="list-group" id="epm_config_manager_list_loading">
                                <li class="list-group-item text-center bg-info"><i class="fa fa-spinner fa-pulse"></i>&nbsp; <?= _("Loading...")?></li>
                            </ul>
                            <div id="epm_config_manager_all_list_box"></div>
                            <!-- END PANEL-BODY -->
                        </div> 
                    </div> 

                </div>
            </div>
        </div>
    </div>
</div>