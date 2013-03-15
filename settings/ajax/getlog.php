<?php
/**
 * Copyright (c) 2012, Robin Appelman <icewind1991@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

OC_JSON::checkAdminUser();

$count=(isset($_GET['count']))?$_GET['count']:50;
$offset=(isset($_GET['offset']))?$_GET['offset']:0;

$entries=OC_Log_Owncloud::getEntries($count, $offset);
$data = array();

OC_JSON::success(array(
	"data" => $entries,
	"remain"=>(count(OC_Log_Owncloud::getEntries(1, $offset + $offset)) != 0) ? true : false));
