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

require_once('apps/media/lib_media.php');

if(OC_App::getCurrentApp()=='files'){
	OC_Util::addScript('media','files');
}

OC_App::register( array( 'order' => 3, 'id' => 'media', 'name' => 'Media' ));

OC_APP::addNavigationEntry( array( 'id' => 'media_index', 'order' => 2, 'href' => OC_HELPER::linkTo( 'media', 'index.php' ), 'icon' => OC_HELPER::imagePath( 'media', 'media.png' ), 'name' => 'Media' ));
OC_APP::addSettingsPage( array( 'id' => 'media_settings', 'order' => 5, 'href' => OC_HELPER::linkTo( 'media', 'settings.php' ), 'name' => 'Media', 'icon' => OC_HELPER::imagePath( 'media', 'media.png' )));

// add subnavigations
$entry = array(
	'id' => "media_playlist",
	'order'=>1,
	'href' => '#playlist',
	'name' => 'Playlist'
);
OC_APP::addNavigationSubEntry( "media_index", $entry);
$entry = array(
	'id' => "media_collection",
	'order'=>1,
	'href' => '#collection',
	'name' => 'Collection'
);
OC_APP::addNavigationSubEntry( "media_index", $entry);
$entry = array(
	'id' => "media_recent",
	'order'=>1,
	'href' => '#recent',
	'name' => 'Most Recent'
);
OC_APP::addNavigationSubEntry( "media_index", $entry);
$entry = array(
	'id' => "media_mostplayer",
	'order'=>1,
	'href' => '#mostplayed',
	'name' => 'Most Played'
);
OC_APP::addNavigationSubEntry( "media_index", $entry);
?>
