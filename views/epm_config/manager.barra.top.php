<?php
	if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }
	//http://issues.freepbx.org/browse/FREEPBX-12816
?>

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