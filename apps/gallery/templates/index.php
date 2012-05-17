<?php
OCP\Util::addStyle('gallery', 'styles');
OCP\Util::addscript('gallery', 'albums');
OCP\Util::addscript('gallery', 'scanner');
OCP\Util::addscript('gallery', 'album_cover');
OCP\Util::addStyle('files', 'files');
OCP\Util::addscript('files_imageviewer', 'jquery.mousewheel-3.0.4.pack');
OCP\Util::addscript('files_imageviewer', 'jquery.fancybox-1.3.4.pack');
OCP\Util::addStyle( 'files_imageviewer', 'jquery.fancybox-1.3.4' );
$l = OC_L10N::get('gallery');
?>
<script type="text/javascript">var gallery_scanning_root='<?php echo OCP\Config::getUserValue(OCP\USER::getUser(), 'gallery', 'root', '/'); ?>'; var gallery_default_order = '<?php echo OCP\Config::getUserValue(OCP\USER::getUser(), 'gallery', 'order', 'ASC'); ?>';</script>
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
		<img src="<?php echo OCP\Util::linkTo('gallery', 'img/loading.gif'); ?>">
	</div>
</div>
<div id="gallery_list">
</div>
