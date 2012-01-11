<?php
/**
 * Copyright (c) 2011 Thomas Tanghus <thomas@tanghus.net>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

require_once('../../../lib/base.php');
$l10n = new OC_L10N('contacts');
OC_JSON::checkLoggedIn();
OC_JSON::checkAppEnabled('contacts');

$output = new OC_TEMPLATE("contacts", "part.messagebox");
$output -> printpage();
?>
