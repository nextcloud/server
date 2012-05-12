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
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
* 
*/

OCP\App::checkAppEnabled('media');
 require_once(OC::$APPSROOT . '/apps/media/lib_collection.php');
 require_once(OC::$APPSROOT . '/apps/media/lib_ampache.php');

$arguments=$_POST;
if(!isset($_POST['action']) and isset($_GET['action'])){
	$arguments=$_GET;
}

foreach($arguments as &$argument){
	$argument=stripslashes($argument);
}
@ob_clean();
if(isset($arguments['action'])){
	OCP\Util::writeLog('media','ampache '.$arguments['action'].' request', OCP\Util::DEBUG);
	switch($arguments['action']){
		case 'songs':
			OC_MEDIA_AMPACHE::songs($arguments);
			break;
		case 'url_to_song':
			OC_MEDIA_AMPACHE::url_to_song($arguments);
			break;
		case 'play':
			OC_MEDIA_AMPACHE::play($arguments);
			break;
		case 'handshake':
			OC_MEDIA_AMPACHE::handshake($arguments);
			break;
		case 'ping':
			OC_MEDIA_AMPACHE::ping($arguments);
			break;
		case 'artists':
			OC_MEDIA_AMPACHE::artists($arguments);
			break;
		case 'artist_songs':
			OC_MEDIA_AMPACHE::artist_songs($arguments);
			break;
		case 'artist_albums':
			OC_MEDIA_AMPACHE::artist_albums($arguments);
			break;
		case 'albums':
			OC_MEDIA_AMPACHE::albums($arguments);
			break;
		case 'album_songs':
			OC_MEDIA_AMPACHE::album_songs($arguments);
			break;
		case 'search_songs':
			OC_MEDIA_AMPACHE::search_songs($arguments);
			break;
		case 'song':
			OC_MEDIA_AMPACHE::song($arguments);
			break;
	}
}

?> 
