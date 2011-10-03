<?php
OC_Util::addStyle('gallery', 'styles');
OC_Util::addScript('gallery', 'album_cover');
OC_Util::addScript( 'files_imageviewer', 'lightbox' );
OC_Util::addStyle( 'files_imageviewer', 'lightbox' );
?>

<div id="controls">
  <a href="?"><input type="button" value="Back" /></a><br/>
</div>
<div id="gallery_list">
<?php
foreach ($_['photos'] as $a) {
?>
<a onclick="javascript:viewImage('/','<?php echo $a; ?>');"><img src="ajax/thumbnail.php?img=<?php echo $a ?>"></a>
<?php
  }
?>

</div>
