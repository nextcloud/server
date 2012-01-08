<?php
OC::$CLASSPATH['OC_Gallery_Album'] = 'apps/gallery/lib/album.php';
OC::$CLASSPATH['OC_Gallery_Photo'] = 'apps/gallery/lib/photo.php';
OC::$CLASSPATH['OC_Gallery_Scanner'] = 'apps/gallery/lib/scanner.php';
OC::$CLASSPATH['OC_Gallery_Hooks_Handlers'] = 'apps/gallery/lib/hooks_handlers.php';

OC_App::register(array(
  'order' => 20,
  'id' => 'gallery',
  'name' => 'Gallery'));

OC_App::addNavigationEntry( array(
 'id' => 'gallery_index',
 'order' => 20,
 'href' => OC_Helper::linkTo('gallery', 'index.php'),
 'icon' => OC_Helper::imagePath('core', 'places/picture.svg'),
 'name' => 'Gallery'));

 class OC_GallerySearchProvider extends OC_Search_Provider{
	function search($query){
		$stmt = OC_DB::prepare('SELECT * FROM *PREFIX*gallery_albums WHERE uid_owner = ? AND album_name LIKE ?');
		$result = $stmt->execute(array(OC_User::getUser(),'%'.$query.'%'));
		$results=array();
		while($row=$result->fetchRow()){
			$results[]=new OC_Search_Result($row['album_name'],'',OC_Helper::linkTo('apps/gallery', 'index.php?view='.$row['album_name']),'Galleries');
		}
		return $results;
	}
}

new OC_GallerySearchProvider();

require_once('apps/gallery/lib/hooks_handlers.php');
?>
