<?php
OCP\Util::addStyle('gallery', 'styles');
OCP\Util::addscript('gallery', 'albums');
OCP\Util::addscript('gallery', 'album_cover');
OCP\Util::addscript('files_imageviewer', 'jquery.mousewheel-3.0.4.pack');
OCP\Util::addscript('files_imageviewer', 'jquery.fancybox-1.3.4.pack');
OCP\Util::addStyle( 'files_imageviewer', 'jquery.fancybox-1.3.4' );
$l = OC_L10N::get('gallery');
?>
<script type="text/javascript">
  $(document).ready(function() {
    $("a[rel=images]").fancybox({
    'titlePosition': 'inside'
    });
  });
</script>

<div id="controls">
  <a href="?"><input type="button" value="<?php echo $l->t('Back');?>" /></a>
<br/>
</div>

<div id="gallery_list" class="leftcontent">
</div>

<div id="gallery_images" class="rightcontent">
<?php
foreach ($_['photos'] as $a) {
?>
<a rel="images" href="../../files/download.php?file=<?php echo urlencode($a); ?>"><img src="ajax/thumbnail.php?img=<?php echo urlencode($a) ?>"></a>
<?php
  }
?>
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
