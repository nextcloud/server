<?php
/**
 * Copyright (c) 2012 Georg Ehrke <ownclouddev at georgswebsite dot de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

require_once('../../../lib/base.php');
OC_JSON::checkLoggedIn();
OC_Util::checkAppEnabled('contacts');
$l10n = new OC_L10N('contacts');
$tmpl = new OC_Template('contacts', 'part.import');
$tmpl->assign('path', $_POST['path']);
$tmpl->assign('filename', $_POST['filename']);
$tmpl->printpage();
?>
