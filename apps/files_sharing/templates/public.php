<?php
/** @var $l OC_L10N */
/** @var $_ array */
?>

<?php if ($_['previewSupported']): /* This enables preview images for links (e.g. on Facebook, Google+, ...)*/?>
	<link rel="image_src" href="<?php p(\OC::$server->getURLGenerator()->linkToRoute( 'core_ajax_public_preview', array('x' => $_['previewMaxX'], 'y' => $_['previewMaxY'], 'file' => $_['directory_path'], 't' => $_['dirToken']))); ?>" />
<?php endif; ?>

<div id="notification-container">
	<div id="notification" style="display: none;"></div>
</div>

<input type="hidden" id="sharingUserId" value="<?php p($_['owner']) ?>">
<input type="hidden" id="filesApp" name="filesApp" value="1">
<input type="hidden" id="isPublic" name="isPublic" value="1">
<input type="hidden" name="dir" value="<?php p($_['dir']) ?>" id="dir">
<input type="hidden" name="downloadURL" value="<?php p($_['downloadURL']) ?>" id="downloadURL">
<input type="hidden" name="sharingToken" value="<?php p($_['sharingToken']) ?>" id="sharingToken">
<input type="hidden" name="filename" value="<?php p($_['filename']) ?>" id="filename">
<input type="hidden" name="mimetype" value="<?php p($_['mimetype']) ?>" id="mimetype">
<input type="hidden" name="previewSupported" value="<?php p($_['previewSupported'] ? 'true' : 'false'); ?>" id="previewSupported">
<input type="hidden" name="mimetypeIcon" value="<?php p(\OC::$server->getMimeTypeDetector()->mimeTypeIcon($_['mimetype'])); ?>" id="mimetypeIcon">
<?php
$upload_max_filesize = OC::$server->getIniWrapper()->getBytes('upload_max_filesize');
$post_max_size = OC::$server->getIniWrapper()->getBytes('post_max_size');
$maxUploadFilesize = min($upload_max_filesize, $post_max_size);
?>
<input type="hidden" name="maxFilesizeUpload" value="<?php p($maxUploadFilesize); ?>" id="maxFilesizeUpload">

<?php if (!isset($_['hideFileList']) || (isset($_['hideFileList']) && $_['hideFileList'] === false)): ?>
	<input type="hidden" name="filesize" value="<?php p($_['nonHumanFileSize']); ?>" id="filesize">
<?php endif; ?>
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
				if (!isset($_['hideFileList']) || (isset($_['hideFileList']) && $_['hideFileList'] === false)) {
					if ($_['server2serversharing']) {
						?>
						<span id="save" data-protected="<?php p($_['protected']) ?>"
							  data-owner-display-name="<?php p($_['displayName']) ?>" data-owner="<?php p($_['owner']) ?>" data-name="<?php p($_['filename']) ?>">
						<button id="save-button"><?php p($l->t('Add to your Nextcloud')) ?></button>
						<form class="save-form hidden" action="#">
							<input type="text" id="remote_address" placeholder="user@yourNextcloud.org"/>
							<button id="save-button-confirm" class="icon-confirm svg" disabled></button>
						</form>
					</span>
					<?php } ?>
					<a href="<?php p($_['downloadURL']); ?>" id="download" class="button">
						<img class="svg" alt="" src="<?php print_unescaped(image_path("core", "actions/download.svg")); ?>"/>
						<span id="download-text"><?php p($l->t('Download'))?></span>
					</a>
				<?php } ?>
			</span>
		</div>
	</div></header>
<div id="content-wrapper">
	<?php if (!isset($_['hideFileList']) || (isset($_['hideFileList']) && $_['hideFileList'] === false)) { ?>
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
		<?php } else { ?>
		<input type="hidden" id="upload-only-interface" value="1"/>
			<div id="public-upload">
				<div id="emptycontent" class="">
					<div id="displayavatar"><div class="avatardiv"></div></div>
					<h2><?php p($l->t('Upload files to %s', [$_['shareOwner']])) ?></h2>
					<p><span class="icon-folder"></span> <?php p($_['filename']) ?></p>
					<input type="file" name="files[]" class="hidden" multiple>

					<a href="#" class="button icon-upload"><?php p($l->t('Select or drop files')) ?></a>
					<div id="drop-upload-progress-indicator" style="padding-top: 25px;" class="hidden"><?php p($l->t('Uploading files…')) ?></div>
					<div id="drop-upload-done-indicator" style="padding-top: 25px;" class="hidden"><?php p($l->t('Uploaded files:')) ?></div>
					<ul>
					</ul>
				</div>
			</div>
		<?php } ?>
<?php if (!isset($_['hideFileList']) || (isset($_['hideFileList']) && $_['hideFileList'] !== true)): ?>
	<input type="hidden" name="dir" id="dir" value="" />
	<div class="hiddenuploadfield">
	<input type="file" id="file_upload_start" class="hiddenuploadfield" name="files[]"
		data-url="<?php print_unescaped(OCP\Util::linkTo('files', 'ajax/upload.php')); ?>" />
	</div>
	<?php endif; ?>
	<footer>
		<p class="info">
			<?php print_unescaped($theme->getLongFooter()); ?>
		</p>
	</footer>
</div>
