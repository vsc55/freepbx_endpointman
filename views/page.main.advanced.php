<?php if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); } ?>

<?= $endpoint_warn ?>
<div class="container-fluid" id="epm_advanced">
	<h1><?= _("End Point Configuration Manager")?></h1>
	<h2><?= _("Advanced Settings")?></h2>
	<div class="row">
		<div class="col-sm-12">
			<div class="fpbx-container">
				<div class="display no-border">
					<div class="nav-container">
						<div class="scroller scroller-left"><i class="glyphicon glyphicon-chevron-left"></i></div>
						<div class="scroller scroller-right"><i class="glyphicon glyphicon-chevron-right"></i></div>
						<div class="wrapper">
							<ul class="nav nav-tabs list" role="tablist" id="list-tabs-epm_advanced">

								<?php foreach($tabs as $key => $page): ?>
									<li data-name="<?= $key ?>" class="change-tab <?= ($key == $subpage) ? 'active' : '' ?>">
										<a href="#<?= $key ?>" aria-controls="<?= $key ?>" role="tab" data-toggle="tab"><?= $page['name'] ?></a>
									</li>
								<?php endforeach; ?>

							</ul>
						</div>
					</div>
					<div class="tab-content display">

						<?php foreach($tabs as $key => $page): ?>
							<div id="<?= $key ?>" class="tab-pane <?= ($key == $subpage) ? 'active' : '' ?>">
								<?= $page['content'] ?>
							</div>
						<?php endforeach; ?>

					</div>
				</div>
			</div>
		</div>
	</div>
</div>