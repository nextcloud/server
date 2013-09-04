<?php
session_write_close();

OC_App::loadApps();

if ($service == 'core.css') {
	$minimizer = new OC_Minimizer_CSS();
	$files = OC_TemplateLayout::findStylesheetFiles(OC_Util::$coreStyles);
	$minimizer->output($files, $service);
}
else if ($service == 'core.js') {
	$minimizer = new OC_Minimizer_JS();
	$files = OC_TemplateLayout::findJavascriptFiles(OC_Util::$coreScripts);
	$minimizer->output($files, $service);
}
