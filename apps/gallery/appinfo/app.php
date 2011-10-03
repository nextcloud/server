<?php
OC_App::register(array(
  'order' => 20,
  'id' => 'gallery',
  'name' => 'Gallery'));

OC_App::addNavigationEntry( array(
 'id' => 'gallery_index',
 'order' => 20,
 'href' => OC_Helper::linkTo('gallery', 'index.php'),
 'icon' => OC_Helper::linkTo('', 'core/img/filetypes/image.png'),
 'name' => 'Gallery'));

 class OC_GallerySearchProvider extends OC_Search_Provider{
	function search($query){
		$stmt = OC_DB::prepare('SELECT * FROM *PREFIX*gallery_albums WHERE uid_owner = ? AND album_name LIKE ?');
		$result = $stmt->execute(array(OC_User::getUser(),'%'.$query.'%'));
		$results=array();
		while($row=$result->fetchRow()){
			$results[]=new OC_Search_Result($row['album_name'],'',OC_Helper::linkTo( 'apps/gallery', 'index.php?view='.$row['album_name']),'Galleries');
		}
		return $results;
	}
}

new OC_GallerySearchProvider();
?>
