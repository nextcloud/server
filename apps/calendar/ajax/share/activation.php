<?php
/**
 * Copyright (c) 2012 Georg Ehrke <ownclouddev@georgswebsite.de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
require_once('../../../../lib/base.php');
$id = strip_tags($_GET['id']);
$activation = strip_tags($_GET['activation']);
OC_Calendar_Share::set_active(OC_User::getUser(), $id, $activation);
OC_JSON::success();