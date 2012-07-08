<?php
/**
 * Copyright (c) 2012 Georg Ehrke <ownclouddev at georgswebsite dot de>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

OCP\JSON::checkLoggedIn();
OCP\App::checkAppEnabled('contacts');
$upload_max_filesize = OCP\Util::computerFileSize(ini_get('upload_max_filesize'));
$post_max_size = OCP\Util::computerFileSize(ini_get('post_max_size'));
$maxUploadFilesize = min($upload_max_filesize, $post_max_size);

$freeSpace=OC_Filesystem::free_space('/');
$freeSpace=max($freeSpace,0);
$maxUploadFilesize = min($maxUploadFilesize ,$freeSpace);

$tmpl = new OCP\Template('contacts', 'part.importaddressbook');
$tmpl->assign('uploadMaxFilesize', $maxUploadFilesize);
$tmpl->assign('requesttoken', $_SERVER['HTTP_REQUESTTOKEN']);
$tmpl->assign('uploadMaxHumanFilesize', OCP\Util::humanFileSize($maxUploadFilesize));
$tmpl->printpage();
?>
