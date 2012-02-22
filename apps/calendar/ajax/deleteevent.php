<?php
/**
 * Copyright (c) 2011 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
require_once('../../../lib/base.php');

$l10n = new OC_L10N('calendar');

OC_JSON::checkLoggedIn();
OC_JSON::checkAppEnabled('calendar');

$id = $_POST['id'];
$event_object = OC_Calendar_App::getEventObject($id);
$result = OC_Calendar_Object::delete($id);
OC_JSON::success();
?> 
