<input type="hidden" name="dir" value="<?php echo $_['dir'] ?>" id="dir">
<input type="hidden" name="downloadURL" value="<?php echo $_['downloadURL'] ?>" id="downloadURL">
<input type="hidden" name="filename" value="<?php echo $_['filename'] ?>" id="filename">
<input type="hidden" name="mimetype" value="<?php echo $_['mimetype'] ?>" id="mimetype">
<header><div id="header">
	<a href="<?php echo link_to('', 'index.php'); ?>" title="" id="owncloud"><img class="svg" src="<?php echo image_path('', 'logo-wide.svg'); ?>" alt="ownCloud" /></a>
	<div class="header-right">
		<span id="details"><?php echo $_['details']; ?></span>
		<a href="<?php echo $_['downloadURL']; ?>" id="download"><img class="svg" alt="Download" src="<?php echo OCP\image_path("core", "actions/download.svg"); ?>" /><?php echo $l->t('Download')?></a>
	</div>
</div></header>
<div id="preview">
	<?php if (substr($_['mimetype'], 0 , strpos($_['mimetype'], '/')) == 'image'): ?>
		<img src="<?php echo $_['downloadURL']; ?>" />
	<?php endif; ?>
</div>
<footer><p class="info"><a href="http://owncloud.org/">ownCloud</a> &ndash; <?php echo $l->t('web services under your control'); ?></p></footer>