<?php
/**
 * Copyright (c) 2011 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

$tmpl = new OC_Template( 'files_encryption', 'settings');
$blackList=explode(',',OC_Appconfig::getValue('files_encryption','type_blacklist','jpg,png,jpeg,avi,mpg,mpeg,mkv,mp3,oga,ogv,ogg'));
$enabled=(OC_Appconfig::getValue('files_encryption','enable_encryption','true')=='true');
$tmpl->assign('blacklist',$blackList);
$tmpl->assign('encryption_enabled',$enabled);

OC_Util::addScript('files_encryption','settings');
OC_Util::addScript('core','multiselect');

return $tmpl->fetchPage();
