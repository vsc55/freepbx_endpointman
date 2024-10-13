<?php if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); } ?>

<?= $endpoint_warn ?>
<div class="alert alert-info" role="alert">
	<h3 class="alert-heading"><?= _('Open Source Information') ?></h3>
  	<p><?= _('OSS PBX End Point Manager is the community supported PBX Endpoint Manager for FreePBX.') ?></p>
	<p><?= _('The front end WebUI is hosted at: <a href="https://github.com/FreePBX/endpointman" class="alert-link" rel="nofollow">https://github.com/FreePBX/endpointman</a><br>The back end configurator is hosted at: <a href="https://github.com/provisioner/Provisioner" class="alert-link" rel="nofollow">https://github.com/provisioner/Provisioner</a> ') ?></p>
	<p><?= _('Pull Requests can be made to either of these and are encouraged.') ?></p>
	<br>
  	<div class="alert alert-warning" role="alert">
		<?= _('This is not the same at the commercial EPM and It is <strong>NOT</strong> supported by FreePBX or Sangoma Technologies inc. If you are looking for a Commercially supported endpoint manager please look into the Commercial Endpoint Manager by <span>Sangoma Technologies inc</span>') ?>
	</div>
</div>