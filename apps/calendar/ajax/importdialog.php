<?php
/**
 * Copyright (c) 2012 Georg Ehrke <ownclouddev at georgswebsite dot de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

require_once('../../../lib/base.php');
OC_JSON::checkLoggedIn();
OC_Util::checkAppEnabled('calendar');
$l10n = new OC_L10N('calendar');
$tmpl = new OC_Template('calendar', 'part.import');
$tmpl->assign('path', $_POST['path']);
$tmpl->assign('filename', $_POST['filename']);
$tmpl->printpage();
?>
