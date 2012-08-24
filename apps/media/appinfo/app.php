<?php
/**
 * ownCloud - media plugin
 *
 * @author Robin Appelman
 * @copyright 2010 Robin Appelman icewind1991@gmail.com
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library.  If not, see <http://www.gnu.org/
 *
 */

$l=OC_L10N::get('media');

OC::$CLASSPATH['OC_MEDIA'] = 'media/lib_media.php';
OC::$CLASSPATH['OC_MediaSearchProvider'] = 'media/lib_media.php';
OC::$CLASSPATH['OC_MEDIA_COLLECTION'] = 'media/lib_collection.php';
OC::$CLASSPATH['OC_MEDIA_SCANNER'] = 'media/lib_scanner.php';

//we need to have the sha256 hash of passwords for ampache
OCP\Util::connectHook('OC_User','post_login','OC_MEDIA','loginListener');

//connect to the filesystem for auto updating
OCP\Util::connectHook('OC_Filesystem','post_write','OC_MEDIA','updateFile');

//listen for file deletions to clean the database if a song is deleted
OCP\Util::connectHook('OC_Filesystem','post_delete','OC_MEDIA','deleteFile');

//list for file moves to update the database
OCP\Util::connectHook('OC_Filesystem','post_rename','OC_MEDIA','moveFile');

OCP\Util::addscript('media','loader');
OCP\App::registerPersonal('media','settings');

OCP\App::addNavigationEntry(array('id' => 'media_index', 'order' => 2, 'href' => OCP\Util::linkTo('media', 'index.php'), 'icon' => OCP\Util::imagePath('core', 'places/music.svg'), 'name' => $l->t('Music')));

OC_Search::registerProvider('OC_MediaSearchProvider');
