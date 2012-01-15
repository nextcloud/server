<?php
OC_Util::addStyle('gallery', 'styles');
OC_Util::addScript('gallery', 'albums');
OC_Util::addScript('gallery', 'album_cover');
$l = new OC_L10N('gallery');
?>

<div id="notification"><div id="gallery_notification_text">Creating thumbnails</div></div>
<div id="controls">
  <input type="button" value="<?php echo $l->t('Rescan');?>" onclick="javascript:scanForAlbums();" />
  <br/>
</div>
<div id="gallery_list">
</div>
