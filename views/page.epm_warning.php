<?php if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); } ?>

<div class="col-sm-12">
	<br />
	<div class="display full-border">
		<div class="content">
			<div class="alert alert-warning">
				<strong><?= _("Warning - Commercial Endpoint Manager detected!")?><br/></strong>
				<br/>
				<?= _("You have installed the Commercial Endpoint Manager and OSS Endpoint Manager together!")?><br/>
				<br/>
				<?= _("This is not recommended and can bring you in troubles with provisioning your phones!")?><br/>
				<br/>
				<?= _("Only use one kind of Endpoint Manager for each Brand, never use the same Brand with both Endpoint Managers and also never assign the same Extension to a second Device!")?>
				<?= _("If you need support for your Commercial Endpoint Manager we inform you here that Sangoma states clearly that running both Endpoint Managers together is officially not supported and Sangoma is allowed to reject your support request if you have both Endpoint Managers running!")?><br/>
				<br/>
			</div>
		</div>
	</div>
</div>
