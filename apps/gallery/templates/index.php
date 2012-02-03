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
    <input type="button" id="g-scan-button" value="<?php echo $l->t('Rescan');?>" onclick="javascript:scanForAlbums();" />
  </div>
  <div id="g-settings">
    <input type="button" id="g-settings-button" value="<?php echo $l->t('Settings');?>" onclick="javascript:settings();"/>
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

<div id="g-dialog-settings" title="<?php echo $l->t('Settings');?>" style="display:none">
	<form>
    <fieldset><?php $root = OC_Appconfig::getValue('gallery', 'root', '/'); $order = OC_Appconfig::getValue('gallery', 'order', 'ASC');?>
    <label for="name"><?php echo $l->t('Scanning root');?></label>
    <input type="text" name="g-scanning-root" id="g-scanning-root" class="text ui-widget-content ui-corner-all" value="<?php echo $root;?>" /><br/>

    <label for="sort"><?php echo $l->t('Default sorting'); ?></label>
    <select id="g-display-order">
      <option value="ASC"<?php echo $order=='ASC'?'selected':'';?>><?php echo $l->t('Ascending'); ?></option>
      <option value="DESC"<?php echo $order=='DESC'?'selected':'';?>><?php echo $l->t('Descending'); ?></option>
    </select><br/>
<!--
    <label for="sort"><?php echo $l->t('Thumbnails size'); ?></label>
    <select>
      <option value="100">100px</option>
      <option value="150">150px</option>
      <option value="200">200px</option>
      </select>
      -->
	</fieldset>
	</form>
</div>

