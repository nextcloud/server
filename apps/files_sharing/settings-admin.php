<?php
/**
 * Copyright (c) 2011 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

\OC_Util::checkAdminUser();

if (\OC_Util::getTheme()) {
	$mailTemplatePath = \OC::$SERVERROOT . '/themes/' . OC_Util::getTheme() . '/core/templates/mail.php';
}

if (!isset($mailTemplatePath) || !file_exists($mailTemplatePath) ) {
	$mailTemplatePath = \OC::$SERVERROOT . '/core/templates/mail.php';
}

if (file_exists($mailTemplatePath)) {
	$mailTemplate = file_get_contents($mailTemplatePath);
} else {
	//log no mail template found
}


\OCP\Util::addStyle('files_sharing', 'settings-admin');
\OCP\Util::addScript('files_sharing', 'settings-admin');
//\OCP\Util::addScript('settings', 'personal');

$themes = array('default');

if ($handle = opendir(\OC::$SERVERROOT.'/themes')) {
	while (false !== ($entry = readdir($handle))) {
		if ($entry != '.' && $entry != '..') {
			if (is_dir(\OC::$SERVERROOT.'/themes/'.$entry)) {
				$themes[] = $entry;
			}
		}
	}
	closedir($handle);
}

$editableTemplates = \OCA\Files_Sharing\MailTemplate::getEditableTemplates();

$tmpl = new OCP\Template('files_sharing', 'settings-admin');
$tmpl->assign('themes', $themes);
$tmpl->assign('editableTemplates', $editableTemplates);


//\OCP\Util::addscript('files_settings', 'settings');
//\OCP\Util::addscript('core', 'multiselect');

return $tmpl->fetchPage();
