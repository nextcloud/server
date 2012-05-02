<?php

OC_JSON::checkAppEnabled('files_versions');
OC_JSON::checkAdminUser();
if (OCP\Config::getSystemValue('versions', 'true')=='true') {
	OCP\Config::setSystemValue('versions', 'false');
} else {
	OCP\Config::setSystemValue('versions', 'true');
}

?>
