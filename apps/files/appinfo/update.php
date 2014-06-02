<?php

// this drops the keys below, because they aren't needed anymore
// core related
if (version_compare(\OC_Config::getValue('version', '0.0.0'), '7.0.0', '<')) {
	\OC_Config::deleteKey('allowZipDownload');
	\OC_Config::deleteKey('maxZipInputSize');
}
