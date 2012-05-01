<?php

OC_JSON::checkAppEnabled('files_versions');
OC_JSON::checkAdminUser();
if (OC_Config::getValue('versions', 'true')=='true') {
	OC_Config::setValue('versions', 'false');
} else {
	OC_Config::setValue('versions', 'true');
}

?>
