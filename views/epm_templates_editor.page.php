<?php if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); } ?>	

<?php if(empty($idsel) || empty($custom)) : ?>

	<div class="alert alert-warning" role="alert">
		<strong><?= _("Warning!")?> </strong><?= _("No select ID o Custom!") ?>
	</div>

<?php else : ?>
	
<?php
	//TODO: 
	$dtemplate = $epm->epm_templates->edit_template_display($idsel, $custom);

	echo load_view(__DIR__.'/epm_templates/editor.views.template.php', array('request' => $request, 'dtemplate' => $dtemplate ));
	echo load_view(__DIR__.'/epm_templates/editor.views.dialog.cfg.global.php', array('request' => $request));
	echo load_view(__DIR__.'/epm_templates/editor.views.dialog.edit.cfg.php', array('request' => $request, 'dtemplate' => $dtemplate ));

	unset ($dtemplate);
?>

<?php endif; ?>