<?php
OC_Util::addStyle('gallery', 'styles');
OC_Util::addScript('gallery', 'albums');
OC_Util::addScript('gallery', 'scanner');
OC_Util::addScript('gallery', 'album_cover');
OC_Util::addStyle('files', 'files');
OC_Util::addScript('files_imageviewer', 'jquery.mousewheel-3.0.4.pack');
OC_Util::addScript('files_imageviewer', 'jquery.fancybox-1.3.4.pack');
OC_Util::addStyle( 'files_imageviewer', 'jquery.fancybox-1.3.4' );
$l = new OC_L10N('gallery');
?>

<div id="controls">
	<div id="scan">
		<div id="scanprogressbar"></div>
		<input type="button" class="start" value="<?php echo $l->t('Rescan');?>" onclick="javascript:scanForAlbums();" />
    <input type="button" class="stop" style="display:none" value="<?php echo $l->t('Stop');?>" onclick="javascript:Scanner.stop();" />
    <input type="button" id="g-share-button" value="<?php echo $l->t('Share'); ?>" onclick="javascript:shareGallery();" />
		<input type="button" id="g-settings-button" value="<?php echo $l->t('Settings');?>" onclick="javascript:settings();"/>
	</div>
	<div id="g-album-navigation">
		<div class="crumb last" style="background-image:url('<?php echo OC::$WEBROOT;?>/core/img/breadcrumb.png')">
			<a href="javascript:returnToElement(0);">main</a>
		</div>
	</div>
	<div id="g-album-loading" class="crumb" style="display:none">
		<img src="img/loading.gif">
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
		<fieldset><?php $root = OC_Preferences::getValue(OC_User::getUser(), 'gallery', 'root', '/'); $order = OC_Preferences::getValue(OC_User::getUser(), 'gallery', 'order', 'ASC');?>
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

