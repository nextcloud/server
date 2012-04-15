<?php
OC_Util::addStyle('gallery', 'styles');
OC_Util::addScript('gallery', 'albums');
OC_Util::addScript('gallery', 'scanner');
OC_Util::addScript('gallery', 'album_cover');
OC_Util::addStyle('files', 'files');
OC_Util::addScript('files_imageviewer', 'jquery.mousewheel-3.0.4.pack');
OC_Util::addScript('files_imageviewer', 'jquery.fancybox-1.3.4.pack');
OC_Util::addStyle( 'files_imageviewer', 'jquery.fancybox-1.3.4' );
$l = OC_L10N::get('gallery');
?>
<script type="text/javascript">var gallery_scanning_root='<? echo OC_Preferences::getValue(OC_User::getUser(), 'gallery', 'root', '/'); ?>'; var gallery_default_order = '<? echo OC_Preferences::getValue(OC_User::getUser(), 'gallery', 'order', 'ASC'); ?>';</script>
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
