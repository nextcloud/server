<?php

$minimizer = new OC_Minimizer_CSS();
$files = $minimizer->findFiles(OC_Util::$core_styles);
$minimizer->output($files);
