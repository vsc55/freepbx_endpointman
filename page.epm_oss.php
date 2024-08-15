<?php
global $active_modules;

if (!empty($active_modules['endpoint']['rawname'])) {
	if (FreePBX::Endpointman()->configmod->get("disable_endpoint_warning") !== "1") {
		include('page.epm_warning.php');  
	}
}
?>
<br>
<div class="alert alert-info" role="alert">
	<h3 class="alert-heading">Open Source Information</h3>
  	<p>OSS PBX End Point Manager is the community supported PBX Endpoint Manager for FreePBX.</p>
	<p>The front end WebUI is hosted at: <a href="https://github.com/FreePBX/endpointman" class="alert-link" rel="nofollow">https://github.com/FreePBX/endpointman</a><br>The back end configurator is hosted at: <a href="https://github.com/provisioner/Provisioner" class="alert-link" rel="nofollow">https://github.com/provisioner/Provisioner</a></p>
	<p>Pull Requests can be made to either of these and are encouraged.</p>
	<br>
  	<div class="alert alert-warning" role="alert">
		This is not the same at the commercial EPM and It is <strong>NOT</strong> supported by FreePBX or Sangoma Technologies inc. If you are looking for a Commercially supported endpoint manager please look into the Commercial Endpoint Manager by <span>Sangoma Technologies inc</span>
	</div>
</div>