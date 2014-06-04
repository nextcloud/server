<?php
/**
 * Copyright (c) 2011 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

\OC_Util::checkAdminUser();

\OCP\Util::addStyle('files_sharing', 'settings-admin');
\OCP\Util::addScript('files_sharing', 'settings-admin');

$themes = \OCA\Files_Sharing\MailTemplate::getEditableThemes();
$editableTemplates = \OCA\Files_Sharing\MailTemplate::getEditableTemplates();

$tmpl = new OCP\Template('files_sharing', 'settings-admin');
$tmpl->assign('themes', $themes);
$tmpl->assign('editableTemplates', $editableTemplates);

return $tmpl->fetchPage();
