<?php
/**
 * Copyright (c) 2012 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

OCP\User::checkLoggedIn();
OCP\App::checkAppEnabled('files_sharing_log');

OCP\App::setActiveNavigationEntry('files_sharing_log_index');

OCP\Util::addStyle('files_sharing_log', 'style');

$query = OCP\DB::prepare('SELECT * FROM `*PREFIX*sharing_log` WHERE `user_id` = ?');
$log = $query->execute(array(OCP\User::getUser()))->fetchAll();

$output = new OCP\Template('files_sharing_log', 'index', 'user');
$output->assign('log', $log);
$output->printPage();
