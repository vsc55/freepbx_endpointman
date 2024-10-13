<?php if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); } ?>

<?= $endpoint_warn ?>
<div class="container-fluid" id="epm_templates">
	<h1><?= _("End Point Configuration Manager") ?></h1>

	<?php foreach($tabs as $key => $page) : ?>
		<?php if (strtolower($subpage) != $key) { continue; } ?>

        <h2><?= $page['name'] ?></h2>
        <div class="display">
            <div class="row">
                <div class="col-sm-12">
                    <div class="fpbx-container">
                        <div class="display <?= ($key == "edit") ? "full" : "no" ?>-border">

                            <?php if ($main['warning']['no_modules_install']) : ?>

                                <div class="card text-center alert-warning">
                                    <div class="card-header">
                                        <strong><?= _("Warning!") ?></strong>
                                    </div>
                                    <div class="card-body">
                                        <h5 class="card-title"><?= _("Welcome to Endpoint Manager") ?></h5>
                                        <p class="card-text"><?= _("You do not have products (modules) installed, it is necessary to install some to continue.") ?></p>
                                        <br>
                                        <a href="config.php?display=epm_config" class="btn btn-primary"><?= _("Click Here to Install Some") ?></a>
                                    </div>
                                </div>

                            <?php elseif ($main['warning']['update_from_ver_previous_2']) : ?>

                                <div class="card text-center alert-warning">
                                    <div class="card-header">
                                        <strong><?= _("Warning!") ?></strong>
                                    </div>
                                    <div class="card-body">
                                        <p class="card-text">
                                            <?= _("Thanks for upgrading to new version! Please head on over to <b>Brand Configurations/Setup</b> to setup and install phone configurations.") ?>
                                        </p>
                                        <br>
                                        <a href="config.php?display=epm_config" class="btn btn-primary"><?= _("Click Here to Go Brand Configurations/Setup") ?></a>
                                    </div>
                                </div>

                            <?php else : ?>

                                <?= $page['content'] ?>
                                
                            <?php endif; ?>

                        </div>
                    </div>
                </div>
            </div>
        </div>
        
    <?php endforeach; ?>
</div>







<?php if ($subpage == "edit") : ?>
	<br />
    <br />
    <br />
<?php endif; ?>

<?php
/*
if ($command == 'save_template')
{
    $epm->save_template($i, $custom, $request);
    if(empty($epm->error))
    {
        $epm->message['general'] = _('Saved');
    }
}
*/