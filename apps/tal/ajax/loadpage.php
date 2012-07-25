<?php
/**
 * Copyright (c) 2011 Thomas Tanghus <thomas@tanghus.net>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
 
OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('tal');

$id = isset($_GET['id'])?trim(strip_tags($_GET['id'])):'';

if($id) {
	$tmpl = new OC_TALTemplate('tal', 'sections');
	$tmpl->assign('id',$id);
	$page = $tmpl->fetchPage();
	OCP\JSON::success(array('data' => array('id'=>$id, 'page'=>$page)));
	exit();
} else {
	$l10n = new OC_L10N('tal');
	OCP\JSON::error(array('data' => array('message' => $l10n->t('Page name missing from request.'))));
}