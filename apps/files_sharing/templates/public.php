<?php
/** @var $l OC_L10N */
/** @var $_ array */

OCP\Util::addScript('files', 'file-upload');
OCP\Util::addStyle('files_sharing', 'public');
OCP\Util::addStyle('files_sharing', 'mobile');
OCP\Util::addScript('files_sharing', 'public');
OCP\Util::addScript('files', 'fileactions');
OCP\Util::addScript('files', 'fileactionsmenu');
OCP\Util::addScript('files', 'jquery.fileupload');

// JS required for folders
OCP\Util::addStyle('files', 'files');
OCP\Util::addStyle('files', 'upload');
OCP\Util::addScript('files', 'filesummary');
OCP\Util::addScript('files', 'breadcrumb');
OCP\Util::addScript('files', 'fileinfomodel');
OCP\Util::addScript('files', 'newfilemenu');
OCP\Util::addScript('files', 'files');
OCP\Util::addScript('files', 'filelist');
OCP\Util::addscript('files', 'keyboardshortcuts');
?>

<?php if ($_['previewSupported']): /* This enables preview images for links (e.g. on Facebook, Google+, ...)*/?>
	<link rel="image_src" href="<?php p(\OC::$server->getURLGenerator()->linkToRoute( 'core_ajax_public_preview', array('x' => $_['previewMaxX'], 'y' => $_['previewMaxY'], 'file' => $_['directory_path'], 't' => $_['dirToken']))); ?>" />
<?php endif; ?>

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
<input type="hidden" name="previewSupported" value="<?php p($_['previewSupported'] ? 'true' : 'false'); ?>" id="previewSupported">
<input type="hidden" name="mimetypeIcon" value="<?php p(\OC::$server->getMimeTypeDetector()->mimeTypeIcon($_['mimetype'])); ?>" id="mimetypeIcon">
<input type="hidden" name="filesize" value="<?php p($_['nonHumanFileSize']); ?>" id="filesize">
<input type="hidden" name="maxSizeAnimateGif" value="<?php p($_['maxSizeAnimateGif']); ?>" id="maxSizeAnimateGif">


<header><div id="header" class="<?php p((isset($_['folder']) ? 'share-folder' : 'share-file')) ?>">
		<a href="<?php print_unescaped(link_to('', 'index.php')); ?>"
		   title="" id="owncloud">
			<div class="logo-icon svg">
			</div>
		</a>

		<div class="header-appname-container">
			<h1 class="header-appname">
				<?php
					if(OC_Util::getEditionString() === '') {
						p($theme->getName());
					} else {
						print_unescaped($theme->getHTMLName());
					}
				?>
			</h1>
		</div>

		<div id="logo-claim" style="display:none;"><?php p($theme->getLogoClaim()); ?></div>
		<div class="header-right">
			<span id="details">
				<?php
				if ($_['server2serversharing']) {
					?>
					<span id="save" data-protected="<?php p($_['protected']) ?>"
						  data-owner-display-name="<?php p($_['displayName']) ?>" data-owner="<?php p($_['owner']) ?>" data-name="<?php p($_['filename']) ?>">
					<button id="save-button"><?php p($l->t('Add to your ownCloud')) ?></button>
					<form class="save-form hidden" action="#">
						<input type="text" id="remote_address" placeholder="example.com/owncloud"/>
						<button id="save-button-confirm" class="icon-confirm svg" disabled></button>
					</form>
				</span>
				<?php } ?>
				<a href="<?php p($_['downloadURL']); ?>" id="download" class="button">
					<img class="svg" alt="" src="<?php print_unescaped(image_path("core", "actions/download.svg")); ?>"/>
					<span id="download-text"><?php p($l->t('Download'))?></span>
				</a>
			</span>
		</div>
</div></header>
<div id="content-wrapper">
	<div id="content">
		<div id="preview">
			<?php if (isset($_['folder'])): ?>
				<?php print_unescaped($_['folder']); ?>
			<?php else: ?>
				<?php if ($_['previewEnabled'] && substr($_['mimetype'], 0, strpos($_['mimetype'], '/')) == 'video'): ?>
					<div id="imgframe">
						<video tabindex="0" controls="" preload="none" style="max-width: <?php p($_['previewMaxX']); ?>px; max-height: <?php p($_['previewMaxY']); ?>px">
							<source src="<?php p($_['downloadURL']); ?>" type="<?php p($_['mimetype']); ?>" />
						</video>
					</div>
				<?php else: ?>
					<!-- Preview frame is filled via JS to support SVG images for modern browsers -->
					<div id="imgframe"></div>
				<?php endif; ?>
				<div class="directDownload">
					<a href="<?php p($_['downloadURL']); ?>" id="downloadFile" class="button">
						<img class="svg" alt="" src="<?php print_unescaped(image_path("core", "actions/download.svg")); ?>"/>
						<?php p($l->t('Download %s', array($_['filename'])))?> (<?php p($_['fileSize']) ?>)
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
</div>
