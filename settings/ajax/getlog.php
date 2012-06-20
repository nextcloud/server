<?php
/**
 * Copyright (c) 2012, Robin Appelman <icewind1991@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

// Init owncloud
require_once('../../lib/base.php');

OC_JSON::checkAdminUser();

$count=(isset($_GET['count']))?$_GET['count']:50;
$offset=(isset($_GET['offset']))?$_GET['offset']:0;

$entries=OC_Log_Owncloud::getEntries($count,$offset);
OC_JSON::success(array("data" => OC_Util::sanitizeHTML($entries)));
