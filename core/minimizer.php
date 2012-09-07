<?php
session_write_close();

OC_App::loadApps();

if ($service == 'core.css') {
	$minimizer = new OC_Minimizer_CSS();
	$files = OC_TemplateLayout::findStylesheetFiles(OC_Util::$core_styles);
	$minimizer->output($files, $service);
}
else if ($service == 'core.js') {
	$minimizer = new OC_Minimizer_JS();
	$files = OC_TemplateLayout::findJavascriptFiles(OC_Util::$core_scripts);
	$minimizer->output($files, $service);
}
