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
			<span id="details"><?php p($l->t('shared by %s', array($_['displayName']))) ?></span>
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
					</li>
				</ul>
			<?php endif; ?>
			<div class="button">
				<a href="<?php p($_['downloadURL']); ?>" id="download">
					<img class="svg" alt="Download" src="<?php print_unescaped(OCP\image_path("core", "actions/download.svg")); ?>"/>
					<?php p($l->t('Download %s', array($_['filename'])))?>
				</a>
			</div>
			<div class="directLink">
				<label for="directLink"><?php p($l->t('Direct link')) ?></label>
				<input id="directLink" type="text" readonly value="<?php p($_['downloadURL']); ?>">
			</div>
		<?php endif; ?>
	</div>
	<footer>
		<p class="info">
			<?php print_unescaped($theme->getLongFooter()); ?>
		</p>
	</footer>
