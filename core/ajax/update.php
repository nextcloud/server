<?php
set_time_limit(0);
$RUNTIME_NOAPPS = true;
require_once '../../lib/base.php';

if (OC::checkUpgrade(false)) {
	$updateEventSource = new OC_EventSource();
	$updateEventSource->send('success', 'Turned on maintenance mode');
	// Check if the .htaccess is existing - this is needed for upgrades from really old ownCloud versions
	if (isset($_SERVER['SERVER_SOFTWARE']) && strstr($_SERVER['SERVER_SOFTWARE'], 'Apache')) {
		if (!OC_Util::ishtaccessworking()) {
			if (!file_exists(OC::$SERVERROOT . '/data/.htaccess')) {
				OC_Setup::protectDataDirectory();
			}
		}
	}
	$result = OC_DB::updateDbFromStructure(OC::$SERVERROOT.'/db_structure.xml');
	if (!$result) {
		$updateEventSource->send('failure', 'Error updating database');
		$updateEventSource->close();
		die();
	}
	$updateEventSource->send('success', 'Updated database');
	$minimizerCSS = new OC_Minimizer_CSS();
	$minimizerCSS->clearCache();
	$minimizerJS = new OC_Minimizer_JS();
	$minimizerJS->clearCache();
	OC_Config::setValue('version', implode('.', OC_Util::getVersion()));
	OC_App::checkAppsRequirements();
	// load all apps to also upgrade enabled apps
	OC_App::loadApps();
	OC_Config::setValue('maintenance', false);
	$updateEventSource->send('success', 'Turned off maintenance mode');
	$updateEventSource->send('done', 'done');
	$updateEventSource->close();
}