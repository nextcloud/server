<input type="hidden" name="dir" value="<?php p($_['dir']) ?>" id="dir">
<input type="hidden" name="downloadURL" value="<?php p($_['downloadURL']) ?>" id="downloadURL">
<input type="hidden" name="filename" value="<?php p($_['filename']) ?>" id="filename">
<input type="hidden" name="mimetype" value="<?php p($_['mimetype']) ?>" id="mimetype">
<header><div id="header">
	<a href="<?php print_unescaped(link_to('', 'index.php')); ?>" title="" id="owncloud"><img class="svg"
		src="<?php print_unescaped(image_path('', 'logo-wide.svg')); ?>" alt="ownCloud" /></a>
	<div class="header-right">
	<?php if (isset($_['folder'])): ?>
		<span id="details"><?php p($l->t('%s shared the folder %s with you',
			array($_['displayName'], $_['fileTarget']))) ?></span>
	<?php else: ?>
		<span id="details"><?php p($l->t('%s shared the file %s with you',
			array($_['displayName'], $_['fileTarget']))) ?></span>
	<?php endif; ?>
		<?php if (!isset($_['folder']) || $_['allowZipDownload']): ?>
			<a href="<?php p($_['downloadURL']); ?>" class="button" id="download"><img
				class="svg" alt="Download" src="<?php print_unescaped(OCP\image_path("core", "actions/download.svg")); ?>"
				/><?php p($l->t('Download'))?></a>
		<?php endif; ?>
	</div>
</div></header>
<div id="preview">
	<?php if (isset($_['folder'])): ?>
		<?php print_unescaped($_['folder']); ?>
	<?php else: ?>
		<?php if (substr($_['mimetype'], 0, strpos($_['mimetype'], '/')) == 'image'): ?>
			<div id="imgframe">
				<img src="<?php p($_['downloadURL']); ?>" />
			</div>
		<?php endif; ?>
		<ul id="noPreview">
			<li class="error">
				<?php p($l->t('No preview available for').' '.$_['fileTarget']); ?><br />
				<a href="<?php p($_['downloadURL']); ?>" id="download"><img class="svg" alt="Download"
					src="<?php print_unescaped(OCP\image_path("core", "actions/download.svg")); ?>"
					/><?php p($l->t('Download'))?></a>
			</li>
		</ul>
	<?php endif; ?>
</div>
<footer><p class="info"><a href="http://owncloud.org/">ownCloud</a> &ndash;
<?php p($l->t('web services under your control')); ?></p></footer>
