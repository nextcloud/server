<?php
/**
 * Copyright (c) 2011 Craig Roberts craig0990@googlemail.com
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 */
require_once('../../../lib/base.php');

OC_JSON::checkLoggedIn();
// Fetch current commit (or HEAD if not yet set)
$head = OC_Preferences::getValue(OC_User::getUser(), 'files_versioning', 'head', 'HEAD');
OC_JSON::encodedPrint(array("head" => $head));
