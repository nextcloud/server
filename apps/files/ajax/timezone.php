<?php
	// FIXME: this should start a secure session if forcessl is enabled
	// see lib/base.php for an example
	@session_start();
	$_SESSION['timezone'] = $_GET['time'];
?>
