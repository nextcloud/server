<?php

/**
* ownCloud - bookmarks plugin
*
* @author Arthur Schiwon
* @copyright 2011 Arthur Schiwon blizzz@arthur-schiwon.de
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

//no apps or filesystem
$RUNTIME_NOSETUPFS=true;

require_once('../../../lib/base.php');

// We send json data
header( 'Content-Type: application/jsonrequest' );

// Check if we are a user
if( !OC_User::isLoggedIn()){
	echo json_encode( array( 'status' => 'error', 'data' => array( 'message' => 'Authentication error' )));
	exit();
}

$metadata = array();

$url = urldecode($_GET["url"]);
//allow only http(s) and (s)ftp
$protocols = '/^[hs]{0,1}[tf]{0,1}tp[s]{0,1}\:\/\//i';
//if not (allowed) protocol is given, assume http
if(preg_match($protocols, $url) == 0) {
	$url = 'http://' . $url;
} 

$page = file_get_contents($url);
@preg_match( "/<title>(.*)<\/title>/si", $page, $match );
$metadata['title'] = htmlentities(strip_tags(@$match[1])); 

$meta = get_meta_tags($url);

if(array_key_exists('description', $meta)) {
	$metadata['description'] = $meta['description'];
}

echo json_encode( array( 'status' => 'success', 'data' => $metadata));
