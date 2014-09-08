<?php
OC_JSON::checkAdminUser();
OCP\JSON::callCheck();

$selectedGroups = isset($_POST["selectedGroups"]) ? json_decode($_POST["selectedGroups"]) : array();
$changedGroup = isset($_POST["changedGroup"]) ? $_POST["changedGroup"] : '';

if ($changedGroup !== '') {
	if(($key = array_search($changedGroup, $selectedGroups)) !== false) {
		unset($selectedGroups[$key]);
	} else {
		$selectedGroups[] = $changedGroup;
	}
} else {
	\OCP\Util::writeLog('core', 'Can not update list of excluded groups from sharing, parameter missing', \OCP\Util::WARN);
}

\OC_Appconfig::setValue('core', 'shareapi_exclude_groups_list', implode(',', $selectedGroups));
