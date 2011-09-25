<?
OC_Util::addStyle('gallery', 'styles');
OC_Util::addScript('gallery', 'album_cover');
?>

<div id="controls">
  <input type="button" value="New album" onclick="javascript:createNewAlbum();" />
  <input type="button" value="Rescan" onclick="javascript:scanForAlbums();" /><br/>
</div>
<div id="gallery_list">
<?
require_once('base.php');
foreach ($_['r'] as $r) {
?>
  <div class="gallery_album_box">
    <a href="?view=<? echo $r['album_name']; ?>"><img class="gallery_album_cover"></a>
    <h1><? echo $r['album_name']; ?></h1>
  </div>
<?
}
?>
</div>
