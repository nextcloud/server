<?php
/**
 * Copyright (c) 2011 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

$tmpl = new OCP\Template( 'files_encryption', 'settings');
$blackList=explode(',',OCP\Config::getAppValue('files_encryption','type_blacklist','jpg,png,jpeg,avi,mpg,mpeg,mkv,mp3,oga,ogv,ogg'));
$enabled=(OCP\Config::getAppValue('files_encryption','enable_encryption','true')=='true');
$tmpl->assign('blacklist',$blackList);
$tmpl->assign('encryption_enabled',$enabled);

OCP\Util::addscript('files_encryption','settings');
OCP\Util::addscript('core','multiselect');

return $tmpl->fetchPage();
