<?php
OC_Util::addStyle('gallery', 'styles');
OC_Util::addScript('gallery', 'albums');
OC_Util::addScript('gallery', 'album_cover');
$l = new OC_L10N('gallery');
?>

<div id="notification"><div id="gallery_notification_text">Creating thumbnails</div></div>
<div id="controls">
  <div id="scan">
    <div id="scanprogressbar"></div>
    <input type="button" value="<?php echo $l->t('Rescan');?>" onclick="javascript:scanForAlbums();" />
  </div>
</div>
<div id="gallery_list">
</div>

<div id="dialog-confirm" title="<?php echo $l->t('Remove confirmation');?>" style="display: none">
  <p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span><?php echo $l->t('Do you want to remove album');?> <span id="albumName"></span>?</p>
</div>

<div id="dialog-form" title="<?php echo $l->t('Change album name');?>" style="display:none">
	<form>
	<fieldset>
    <label for="name"><?php echo $l->t('New album name');?></label>
		<input type="text" name="name" id="name" class="text ui-widget-content ui-corner-all" />
	</fieldset>
	</form>
</div>

