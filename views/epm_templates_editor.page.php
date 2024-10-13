<?php if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); } ?>	

<?php if ($missing_data) : ?>
	<div class="card text-center alert-warning">
		<div class="card-header">
			<strong><?= _("Warning!") ?></strong>
		</div>
		<div class="card-body">
			<h5 class="card-title"><?= _("Welcome to Endpoint Manager") ?></h5>
			<p class="card-text"><?= _("No get ID or Custom, it is necessary to select some to continue.") ?></p>
		</div>
	</div>
<?php else : ?>
	
<?php
	//TODO: 
	$dtemplate = $epm->epm_templates->edit_template_display($id_template, $custom);

	echo load_view(__DIR__.'/epm_templates/editor.views.template.php', array('request' => $request, 'dtemplate' => $dtemplate ));
	echo load_view(__DIR__.'/epm_templates/editor.views.dialog.cfg.global.php', array('request' => $request));
	echo load_view(__DIR__.'/epm_templates/editor.views.dialog.edit.cfg.php', array('request' => $request, 'dtemplate' => $dtemplate ));

	unset ($dtemplate);
?>

<?php endif; ?>