<?php

OCP\JSON::checkAppEnabled('files_versions');
OCP\JSON::checkAdminUser();
OCP\JSON::callCheck();
if (OCP\Config::getSystemValue('versions', 'true')=='true') {
	OCP\Config::setSystemValue('versions', 'false');
} else {
	OCP\Config::setSystemValue('versions', 'true');
}
