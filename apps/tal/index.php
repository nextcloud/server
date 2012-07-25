<?php
/**
 * Copyright (c) 2012 Thomas Tanghus <thomas@tanghus.net>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

// Check if we are a user
OCP\User::checkLoggedIn();
OCP\App::checkAppEnabled('tal');

OCP\Util::addscript('tal','tal');
OCP\Util::addscript('tal','modernizr');
OCP\Util::addStyle('tal','tal');

$sections = array(
		array('id' => 'intro', 'title' => 'Introduction'),
		array('id' => 'example-1', 'title' => 'A simple example'),
		array('id' => 'gotchas', 'title' => 'Caveats & Gotchas'),
		array('id' => 'ref', 'title' => 'References'),
		);
$page = isset($_GET['page'])?trim(strip_tags($_GET['page'])):$sections[0]['id'];

$tmpl = new OC_TALTemplate('tal', 'manual', 'user');
$tmpl->assign('application', 'TAL');
$tmpl->assign('page', $page);
$tmpl->assign('sections', $sections);
$tmpl->printPage();
?>
