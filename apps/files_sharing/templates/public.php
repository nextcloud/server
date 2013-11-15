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
<header><div id="header">
		<a href="<?php print_unescaped(link_to('', 'index.php')); ?>" title="" id="owncloud"><img class="svg"
		                                                                                          src="<?php print_unescaped(image_path('', 'logo-wide.svg')); ?>" alt="<?php p($theme->getName()); ?>" /></a>
		<div id="logo-claim" style="display:none;"><?php p($theme->getLogoClaim()); ?></div>
		<div class="header-right">
			<?php if (isset($_['folder'])): ?>
				<span id="details"><?php p($l->t('%s shared the folder %s with you',
						array($_['displayName'], $_['filename']))) ?></span>
			<?php else: ?>
				<span id="details"><?php p($l->t('%s shared the file %s with you',
						array($_['displayName'], $_['filename']))) ?></span>
			<?php endif; ?>


			<?php if (!isset($_['folder']) || $_['allowZipDownload']): ?>
				<a href="<?php p($_['downloadURL']); ?>" class="button" id="download"><img
						class="svg" alt="Download" src="<?php print_unescaped(OCP\image_path("core", "actions/download.svg")); ?>"
						/><span><?php p($l->t('Download'))?></span></a>
			<?php endif; ?>

			<?php if ($_['allowPublicUploadEnabled']):?>


			<input type="hidden" id="publicUploadRequestToken" name="requesttoken" value="<?php p($_['requesttoken']) ?>" />
			<input type="hidden" id="dirToken" name="dirToken" value="<?php p($_['dirToken']) ?>" />
			<input type="hidden" id="uploadMaxFilesize" name="uploadMaxFilesize" value="<?php p($_['uploadMaxFilesize']) ?>" />
			<input type="hidden" id="uploadMaxHumanFilesize" name="uploadMaxHumanFilesize" value="<?php p($_['uploadMaxHumanFilesize']) ?>" />
			<input type="hidden" id="directory_path" name="directory_path" value="<?php p($_['directory_path']) ?>" />
			<?php if($_['uploadMaxFilesize'] >= 0):?>
				<input type="hidden" name="MAX_FILE_SIZE" id="max_upload"
				       value="<?php p($_['uploadMaxFilesize']) ?>">
			<?php endif;?>


			<div id="data-upload-form" class="button" title="<?php p($l->t('Upload') . ' max. '.$_['uploadMaxHumanFilesize']) ?>">
				<input id="file_upload_start" type="file" name="files[]" data-url="<?php print_unescaped(OCP\Util::linkTo('files', 'ajax/upload.php')); ?>" multiple>
				<a href="#" id="publicUploadButtonMock" class="svg">
					<span><?php p($l->t('Upload'))?></span>
				</a>
			</div>

		</div>

		<div id="additional_controls" style="display:none">
			<div id="uploadprogresswrapper">
				<div id="uploadprogressbar"></div>
				<input id="cancel_upload_button" type="button" class="stop" style="display:none"
				       value="<?php p($l->t('Cancel upload'));?>"
					/>
			</div>




			<?php endif; ?>

		</div>
	</div></header>
<div id="content">
	<div id="preview">
		<?php if (isset($_['folder'])): ?>
			<?php print_unescaped($_['folder']); ?>
		<?php else: ?>
			<?php if (substr($_['mimetype'], 0, strpos($_['mimetype'], '/')) == 'image'): ?>
				<div id="imgframe">
					<img src="<?php p($_['downloadURL']); ?>" />
				</div>
			<?php elseif (substr($_['mimetype'], 0, strpos($_['mimetype'], '/')) == 'video'): ?>
				<div id="imgframe">
					<video tabindex="0" controls="" autoplay="">
						<source src="<?php p($_['downloadURL']); ?>" type="<?php p($_['mimetype']); ?>" />
					</video>
				</div>
			<?php elseif (\OC\Preview::isMimeSupported($_['mimetype'])): ?>
				<div id="imgframe">
					<img src="<?php p(OCP\Util::linkToRoute( 'core_ajax_public_preview', array('x' => 500, 'y' => 500, 'file' => urlencode($_['directory_path']), 't' => $_['dirToken']))); ?>" class="publicpreview"/>
				</div>
			<?php else: ?>
				<ul id="noPreview">
					<li class="error">
						<?php p($l->t('No preview available for').' '.$_['filename']); ?><br />
						<a href="<?php p($_['downloadURL']); ?>" id="download"><img class="svg" alt="Download"
						                                                            src="<?php print_unescaped(OCP\image_path("core", "actions/download.svg")); ?>"
								/><?php p($l->t('Download'))?></a>
					</li>
				</ul>
			<?php endif; ?>
			<div class="directLink"><label for="directLink"><?php p($l->t('Direct link')) ?></label><input id="directLink" type="text" readonly value="<?php p($_['downloadURL']); ?>"></input></div>
		<?php endif; ?>
	</div>
	<footer>
		<p class="info">
			<?php print_unescaped($theme->getLongFooter()); ?>
		</p>
	</footer>
