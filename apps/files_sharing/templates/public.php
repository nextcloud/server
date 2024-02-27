<?php
/** @var \OCP\IL10N $l */
/** @var array $_ */
?>
<div id="app-content">
<?php if ($_['previewSupported']): /* This enables preview images for links (e.g. on Facebook, Google+, ...)*/?>
	<link rel="image_src" href="<?php p($_['previewImage']); ?>" />
<?php endif; ?>

<input type="hidden" id="sharingUserId" value="<?php p($_['owner']) ?>">
<input type="hidden" id="filesApp" name="filesApp" value="1">
<input type="hidden" id="isPublic" name="isPublic" value="1">
<?php if (!$_['hideDownload']): ?>
	<input type="hidden" name="downloadURL" value="<?php p($_['downloadURL']) ?>" id="downloadURL">
<?php endif; ?>
<input type="hidden" name="previewURL" value="<?php p($_['previewURL']) ?>" id="previewURL">
<input type="hidden" name="sharingToken" value="<?php p($_['sharingToken']) ?>" id="sharingToken">
<input type="hidden" name="filename" value="<?php p($_['filename']) ?>" id="filename">
<input type="hidden" name="mimetype" value="<?php p($_['mimetype']) ?>" id="mimetype">
<input type="hidden" name="previewSupported" value="<?php p($_['previewSupported'] ? 'true' : 'false'); ?>" id="previewSupported">
<input type="hidden" name="mimetypeIcon" value="<?php p(\OC::$server->getMimeTypeDetector()->mimeTypeIcon($_['mimetype'])); ?>" id="mimetypeIcon">
<input type="hidden" name="hideDownload" value="<?php p($_['hideDownload'] ? 'true' : 'false'); ?>" id="hideDownload">
<input type="hidden" id="disclaimerText" value="<?php p($_['disclaimer']) ?>">
<?php
$upload_max_filesize = OC::$server->get(\bantu\IniGetWrapper\IniGetWrapper::class)->getBytes('upload_max_filesize');
$post_max_size = OC::$server->get(\bantu\IniGetWrapper\IniGetWrapper::class)->getBytes('post_max_size');
$maxUploadFilesize = min($upload_max_filesize, $post_max_size);
?>
<input type="hidden" name="maxFilesizeUpload" value="<?php p($maxUploadFilesize); ?>" id="maxFilesizeUpload">

<?php if (!isset($_['hideFileList']) || (isset($_['hideFileList']) && $_['hideFileList'] === false)): ?>
	<input type="hidden" name="filesize" value="<?php p($_['nonHumanFileSize']); ?>" id="filesize">
<?php endif; ?>
<input type="hidden" name="maxSizeAnimateGif" value="<?php p($_['maxSizeAnimateGif']); ?>" id="maxSizeAnimateGif">
<?php if (isset($_['note']) && $_['note'] !== '') : ?>
	<div id="notemenu" class="hidden">
		<div class="icon-comment-white menutoggle" tabindex="0" role="button" aria-haspopup="true" aria-controls="note-content" aria-expanded="false">
			<span class="hidden-visually"><?php p($l->t('Share note'))?></span>
		</div>
		<div id="note-content" class="menu" aria-label="Note content">
			<div class="content">
				<?php p($_['note']); ?>
			</div>
		</div>
	</div>
<?php endif; ?>

<?php if (!isset($_['hideFileList']) || (isset($_['hideFileList']) && $_['hideFileList'] === false)) { ?>
	<!-- ONLY if this is a folder, we show the grid toggle button -->
	<?php if (empty($_['dir']) === false) { ?>
		<input type="checkbox" class="hidden-visually" id="showgridview"
			<?php if ($_['showgridview']) { ?>checked="checked" <?php } ?>/>
		<label id="view-toggle" for="showgridview" class="button <?php p($_['showgridview'] ? 'icon-toggle-filelist' : 'icon-toggle-pictures') ?>"
			title="<?php p($_['showgridview'] ? $l->t('Show list view') : $l->t('Show grid view'))?>"></label>
	<?php } ?>

	<!-- files listing -->
	<div id="files-public-content">
		<div id="preview">
			<?php if (isset($_['folder'])): ?>
				<?php print_unescaped($_['folder']); ?>
			<?php else: ?>
				<!-- preview frame to open file in with viewer -->
				<div id="imgframe"></div>
				<?php if (isset($_['mimetype']) && str_starts_with($_['mimetype'], 'image')): ?>
					<div class="directDownload">
						<div>
							<?php p($_['filename'])?> (<?php p($_['fileSize']) ?>)
						</div>
						<?php if (!$_['hideDownload']) { ?>
							<a href="<?php p($_['downloadURL']); ?>" id="downloadFile" class="button">
								<span class="icon icon-download"></span>
								<?php p($l->t('Download'))?>
							</a>
						<?php } ?>
					</div>
				<?php elseif ($_['previewURL'] === $_['downloadURL'] && !$_['hideDownload']): ?>
					<div class="directDownload">
						<div>
							<?php p($_['filename'])?>&nbsp;(<?php p($_['fileSize']) ?>)
						</div>
						<a href="<?php p($_['downloadURL']); ?>" id="downloadFile" class="button">
							<span class="icon icon-download"></span>
							<?php p($l->t('Download'))?>
						</a>
					</div>
				<?php endif; ?>
			<?php endif; ?>
		</div>
	</div>
<?php } else { ?>
	<input type="hidden" id="upload-only-interface" value="1"/>
	<div id="public-upload">
		<div
				id="emptycontent"
				class="emptycontent <?php if (!empty($_['note'])) { ?>has-note<?php } ?>">
			<?php if ($_['shareOwner']) { ?>
				<div id="displayavatar"><div class="avatardiv"></div></div>
				<h2><?php p($l->t('Upload files to %s', [$_['shareOwner']])) ?></h2>
				<p><span class="icon-folder"></span> <?php p($_['filename']) ?></p>
			<?php } else { ?>
				<div id="displayavatar"><span class="icon-folder"></span></div>
				<h2><?php p($l->t('Upload files to %s', [$_['filename']])) ?></h2>
			<?php } ?>

			<?php if (empty($_['note']) === false) { ?>
				<h3><?php p($l->t('Note')); ?></h3>
				<p class="note"><?php p($_['note']); ?></p>
			<?php } ?>

			<input type="file" name="files[]" class="hidden" multiple>
			<a href="#" class="button icon-upload"><?php p($l->t('Select or drop files')) ?></a>
			<div id="drop-upload-progress-indicator" style="padding-top: 25px;" class="hidden"><span class="icon-loading-small"></span><?php p($l->t('Uploading files')) ?></div>
			<div id="drop-upload-done-indicator" style="padding-top: 25px;" class="hidden"><?php p($l->t('Uploaded files:')) ?></div>
			<ul id="drop-uploaded-files"></ul>

			<?php if ($_['disclaimer'] !== '') { ?>
				<div>
					<?php
						echo $l->t('By uploading files, you agree to the %1$sterms of service%2$s.', [
							'<span id="show-terms-dialog">', '</span>'
						]);
				?>
				</div>
			<?php } ?>
		</div>
	</div>
<?php } ?>

<?php if (!isset($_['hideFileList']) || (isset($_['hideFileList']) && $_['hideFileList'] !== true)): ?>
	<div class="hiddenuploadfield">
		<input type="file" id="file_upload_start" class="hiddenuploadfield" name="files[]"
			   data-url="<?php p(\OC::$server->getURLGenerator()->linkTo('files', 'ajax/upload.php')); ?>" />
	</div>
<?php endif; ?>
</div>
