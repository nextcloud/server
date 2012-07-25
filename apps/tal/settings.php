<?php
/**
 * Copyright (c) 2012 Thomas Tanghus <thomas@tanghus.net>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

 
OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('tal');

$tmpl = new OC_TALTemplate('tal', 'settings');
return $tmpl->fetchPage();

?>
