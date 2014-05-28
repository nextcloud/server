<?php /** @var $l OC_L10N */ ?>
<div id="notification-container">
	<div id="notification" style="display: none;"></div>
</div>

<input type="hidden" id="filesApp" name="filesApp" value="1">
<input type="hidden" id="isPublic" name="isPublic" value="1">
<input type="hidden" name="dir" value="<?php p($_['dir']) ?>" id="dir">
<input type="hidden" name="downloadURL" value="<?php p($_['downloadURL']) ?>" id="downloadURL">
<input type="hidden" name="sharingToken" value="<?php p($_['sharingToken']) ?>" id="sharingToken">
<input type="hidden" name="filename" value="<?php p($_['filename']) ?>" id="filename">
<input type="hidden" name="mimetype" value="<?php p($_['mimetype']) ?>" id="mimetype">
<header><div id="header" class="<?php p((isset($_['folder']) ? 'share-folder' : 'share-file')) ?>">
		<a href="<?php print_unescaped(link_to('', 'index.php')); ?>" title="" id="owncloud"><img class="svg"
		                                                                                          src="<?php print_unescaped(image_path('', 'logo-wide.svg')); ?>" alt="<?php p($theme->getName()); ?>" /></a>
		<div id="logo-claim" style="display:none;"><?php p($theme->getLogoClaim()); ?></div>
		<div class="header-right">
			<?php if ($_['showDownloadButton']): ?>
			<a href="<?php p($_['downloadURL']); ?>" id="download" class="button">
				<img class="svg" alt="" src="<?php print_unescaped(OCP\image_path("core", "actions/download.svg")); ?>"/>
				<?php p($l->t('Download'))?>
			</a>
			<?php endif ?>
		</div>
</div></header>
<div id="content">
	<div id="preview">
		<?php if (isset($_['folder'])): ?>
			<?php print_unescaped($_['folder']); ?>
		<?php else: ?>
			<?php if (substr($_['mimetype'], 0, strpos($_['mimetype'], '/')) == 'image'): ?>
				<div id="imgframe">
				</div>
			<?php elseif (substr($_['mimetype'], 0, strpos($_['mimetype'], '/')) == 'video'): ?>
				<div id="imgframe">
					<video tabindex="0" controls="" autoplay="">
						<source src="<?php p($_['downloadURL']); ?>" type="<?php p($_['mimetype']); ?>" />
					</video>
				</div>
			<?php else: ?>
				<div id="imgframe">
					<?php $size = \OC\Preview::isMimeSupported($_['mimetype']) ? 500 : 128 ?>
					<img
						src="<?php p(OCP\Util::linkToRoute( 'core_ajax_public_preview', array('x' => $size, 'y' => $size, 'file' => $_['directory_path'], 't' => $_['dirToken']))); ?>"
						class="publicpreview"
						alt="" />
				</div>
			<?php endif; ?>
			<div class="directDownload">
				<a href="<?php p($_['downloadURL']); ?>" id="download" class="button">
					<img class="svg" alt="" src="<?php print_unescaped(OCP\image_path("core", "actions/download.svg")); ?>"/>
					<?php p($l->t('Download %s', array($_['filename'])))?>
				</a>
			</div>
			<div class="directLink">
				<label for="directLink"><?php p($l->t('Direct link')) ?></label>
				<input id="directLink" type="text" readonly value="<?php p($_['downloadURL']); ?>">
			</div>
		<?php endif; ?>
	</div>

</div>
<footer>
	<p class="info">
		<?php print_unescaped($theme->getLongFooter()); ?>
	</p>
</footer>
