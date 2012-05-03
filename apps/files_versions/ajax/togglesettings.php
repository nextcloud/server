<?php

OCP\JSON::checkAppEnabled('files_versions');
OCP\JSON::checkAdminUser();
if (OCP\Config::getSystemValue('versions', 'true')=='true') {
	OCP\Config::setSystemValue('versions', 'false');
} else {
	OCP\Config::setSystemValue('versions', 'true');
}

?>
