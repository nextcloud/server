<?
OC_Util::addStyle('gallery', 'styles');
OC_Util::addScript('gallery', 'albums');
OC_Util::addScript('gallery', 'album_cover');
?>

<div id="controls">
  <input type="button" value="New album" onclick="javascript:createNewAlbum();" />
  <input type="button" value="Rescan" onclick="javascript:scanForAlbums();" /><br/>
</div>
<div id="gallery_list">
</div>
