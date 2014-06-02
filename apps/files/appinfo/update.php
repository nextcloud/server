<?php

// this drops the keys below, because they aren't needed anymore
// core related
if (version_compare(\OCP\Config::getSystemValue('version', '0.0.0'), '7.0.0', '<')) {
	\OCP\Config::deleteSystemValue('allowZipDownload');
	\OCP\Config::deleteSystemValue('maxZipInputSize');
}
