<?php

/**
* ownCloud - gallery application
*
* @author Bartek Przybylski
* @copyright 2012 Bartek Przybylski bartek@alefzero.eu
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



OCP\User::checkLoggedIn();
OCP\App::checkAppEnabled('gallery');
OCP\App::setActiveNavigationEntry( 'gallery_index' );

OCP\Util::addStyle('files', 'files');
OCP\Util::addStyle('gallery', 'styles');
OCP\Util::addScript('gallery', 'pictures');
OCP\Util::addStyle( 'gallery', 'supersized' );
OCP\Util::addStyle( 'gallery', 'supersized.shutter' );
OCP\Util::addscript('gallery', 'slideshow');
OCP\Util::addscript('gallery', 'jquery.easing.min');
OCP\Util::addscript('gallery', 'supersized.3.2.7.min');
OCP\Util::addscript('gallery', 'supersized.shutter.min');

include('gallery/lib/tiles.php');

$root = !empty($_GET['root']) ? $_GET['root'] : '/';
$images = \OC_FileCache::searchByMime('image', null, '/'.\OCP\USER::getUser().'/files'.$root);
sort($images);

$tl = new \OC\Pictures\TilesLine();
$ts = new \OC\Pictures\TileStack(array(), '');
$previous_element = @$images[0];

$root_images = array();
$second_level_images = array();

$fallback_images = array(); // if the folder only cotains subfolders with images -> these are taken for the stack preview

for($i = 0; $i < count($images); $i++) {
	$prev_dir_arr = explode('/', $previous_element);
	$dir_arr = explode('/', $images[$i]);

	if(count($dir_arr) == 1) { // getting the images in this directory
		$root_images[] = $root.$images[$i];
	} else {
		if(strcmp($prev_dir_arr[0], $dir_arr[0]) != 0) { // if we entered a new directory
			if(count($second_level_images) == 0) { // if we don't have images in this directory
				if(count($fallback_images) != 0) { // but have fallback_images
					$tl->addTile(new \OC\Pictures\TileStack($fallback_images, $prev_dir_arr[0]));
					$fallback_images = array();
				}
			} else { // if we collected images for this directory
				$tl->addTile(new \OC\Pictures\TileStack($second_level_images, $prev_dir_arr[0]));
				$fallback_images = array();
				$second_level_images = array();
			}
		}
		if (count($dir_arr) == 2) { // These are the pics in our current subdir
			$second_level_images[] = $root.$images[$i];
			$fallback_images = array();
		} else { // These are images from the deeper directories
			if(count($second_level_images) == 0) {
				$fallback_images[] = $root.$images[$i];
			}
		}
		// have us a little something to compare against
		$previous_element = $images[$i];
	}
}

// if last element in the directory was a directory we don't want to miss it :)
if(count($second_level_images)>0) {
	$tl->addTile(new \OC\Pictures\TileStack($second_level_images, $prev_dir_arr[0]));
}

// if last element in the directory was a directory with no second_level_images we also don't want to miss it ...
if(count($fallback_images)>0) {
	$tl->addTile(new \OC\Pictures\TileStack($fallback_images, $prev_dir_arr[0]));
}

// and finally our images actually stored in the root folder
for($i = 0; $i<count($root_images); $i++) {
	$tl->addTile(new \OC\Pictures\TileSingle($root_images[$i]));
}

$tmpl = new OCP\Template( 'gallery', 'index', 'user' );
$tmpl->assign('root', $root, false);
$tmpl->assign('tl', $tl, false);
$tmpl->printPage();
?>
