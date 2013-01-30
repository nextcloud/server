<input type="hidden" name="dir" value="<?php echo $_['dir'] ?>" id="dir">
<input type="hidden" name="downloadURL" value="<?php echo $_['downloadURL'] ?>" id="downloadURL">
<input type="hidden" name="filename" value="<?php echo $_['filename'] ?>" id="filename">
<input type="hidden" name="mimetype" value="<?php echo $_['mimetype'] ?>" id="mimetype">
<header><div id="header">
	<a href="<?php echo link_to('', 'index.php'); ?>" title="" id="owncloud"><img class="svg" src="<?php echo image_path('', 'logo-wide.svg'); ?>" alt="ownCloud" /></a>
	<div class="header-right">
	<?php if (isset($_['folder'])): ?>
		<span id="details"><?php echo $l->t('%s shared the folder %s with you', array($_['displayName'], $_['filename'])) ?></span>
	<?php else: ?>
		<span id="details"><?php echo $l->t('%s shared the file %s with you', array($_['displayName'], $_['filename'])) ?></span>
	<?php endif; ?>
		<?php if (!isset($_['folder']) || $_['allowZipDownload']): ?>
			<a href="<?php echo $_['downloadURL']; ?>" class="button" id="download"><img class="svg" alt="Download" src="<?php echo OCP\image_path("core", "actions/download.svg"); ?>" /><?php echo $l->t('Download')?></a>
		<?php endif; ?>
	</div>
</div></header>
<div id="preview">
	<?php if (isset($_['folder'])): ?>
		<?php echo $_['folder']; ?>
	<?php else: ?>
		<?php if (substr($_['mimetype'], 0, strpos($_['mimetype'], '/')) == 'image'): ?>
			<div id="imgframe">
				<img src="<?php echo $_['downloadURL']; ?>" />
			</div>
		<?php endif; ?>
		<ul id="noPreview">
			<li class="error">
				<?php echo $l->t('No preview available for').' '.$_['filename']; ?><br />
				<a href="<?php echo $_['downloadURL']; ?>" id="download"><img class="svg" alt="Download" src="<?php echo OCP\image_path("core", "actions/download.svg"); ?>" /><?php echo $l->t('Download')?></a>
			</li>
		</ul>
	<?php endif; ?>
</div>
<footer><p class="info"><a href="http://owncloud.org/">ownCloud</a> &ndash; <?php echo $l->t('web services under your control'); ?></p></footer>
