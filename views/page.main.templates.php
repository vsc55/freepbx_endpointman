<?php if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); } ?>

<?= $endpoint_warn ?>
<div class="container-fluid" id="epm_templates">
	<h1><?= _("End Point Configuration Manager") ?></h1>
	<?php foreach($tabs as $key => $page) : ?>
		<?php if (strtolower($subpage) == $key) : ?>
            <h2><?= $page['name'] ?></h2>
            <div class="display">
                <div class="row">
                    <div class="col-sm-12">
                        <div class="fpbx-container">
                            <div class="display <?= ($key == "editor") ? "full" : "no" ?>-border">
                                <?php if((!$product_list) && (!$mac_list)) : ?>

                                    <div class="alert alert-warning" role="alert">
                                        <strong><?= _("Warning!") ?></strong><br>
                                        <?= _("Welcome to Endpoint Manager. You have no products (Modules) installed, click <a href=\"config.php?display=epm_config\"><b>here</b></a> to install some") ?>
                                    </div>

                                <?php elseif(!$product_list) : ?>

                                    <div class="alert alert-warning" role="alert">';
                                        <strong><?= _("Warning!")?> </strong> <?= _("Thanks for upgrading to version 2.0! Please head on over to <a href=\"config.php?display=epm_config\">Brand Configurations/Setup</b></a> to setup and install phone configurations") ?>
                                    </div>

                                <?php else : ?>

                                    <?= $page['content'] ?>
                                    
                                <?php endif; ?>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>    
    <?php endforeach; ?>
</div>

<?php if ($subpage == "editor") : ?>
	<br />
    <br />
    <br />
<?php endif; ?>

<?php
if ($command == 'save_template')
{
    $epm->save_template($i, $custom, $request);
    if(empty($epm->error))
    {
        $epm->message['general'] = _('Saved');
    }
}