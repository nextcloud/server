<?php
/**
 * Copyright (c) 2013 Sam Tuke <samtuke@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

$tmpl = new OCP\Template( 'files_encryption', 'settings-personal');

$blackList = explode( ',', \OCP\Config::getAppValue( 'files_encryption', 'type_blacklist', 'jpg,png,jpeg,avi,mpg,mpeg,mkv,mp3,oga,ogv,ogg' ) );

$tmpl->assign( 'blacklist', $blackList );

return $tmpl->fetchPage();

return null;
