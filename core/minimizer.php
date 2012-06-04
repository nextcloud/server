<?php

OC_App::loadApps();

if ($service == 'core.css'){
	$minimizer = new OC_Minimizer_CSS();
	$files = $minimizer->findFiles(OC_Util::$core_styles);
	$minimizer->output($files);
}
else if ($service == 'core.js'){
	$minimizer = new OC_Minimizer_JS();
	$files = $minimizer->findFiles(OC_Util::$core_scripts);
	$minimizer->output($files);
}
