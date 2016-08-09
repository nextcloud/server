<?php
/** @var OC_L10N $l */
/** @var array $_ */
script('federatedfilesharing', 'settings-personal');
style('federatedfilesharing', 'settings-personal');
if ($_['showShareIT']) {
	script('federatedfilesharing', '3rdparty/gs-share/gs-share');
	style('federatedfilesharing', '3rdparty/gs-share/style');
}
?>

<?php if ($_['outgoingServer2serverShareEnabled']): ?>
	<div id="fileSharingSettings" class="section">
		<h2><?php p($l->t('Federated Cloud')); ?></h2>

		<p>
			<?php p($l->t('Your Federated Cloud ID:')); ?>
			<strong id="cloudid"><?php p($_['cloudId']); ?></strong>
			<a class="clipboardButton icon icon-clippy" data-clipboard-target="#cloudid"></a>
		</p>

		<br>

		<?php if ($_['showShareIT']) {?>
		<p>
			<?php p($l->t('Share it:')); ?>
			<div class="gs-share">
			<button data-url="<?php p(urlencode($_['reference'])); ?>"
				data-title='<?php p(urlencode($_['message_without_URL'])); ?>'
				class='js-gs-share social-gnu'>
				GNU Social
			</button>
			</div>
			<button class="social-diaspora pop-up"
				data-url='https://sharetodiaspora.github.io/?title=<?php p($_['message_without_URL']); ?>&url=<?php p(urlencode($_['reference'])); ?>'>
				Diaspora
			</button>
			<button class="social-twitter pop-up"
				data-url='https://twitter.com/intent/tweet?text=<?php p(urlencode($_['message_with_URL'])); ?>'>
				Twitter
			</button>
			<button class="social-facebook pop-up"
				data-url='https://www.facebook.com/sharer/sharer.php?u=<?php p(urlencode($_['reference'])); ?>'>
				Facebook
			</button>
			<button class="social-googleplus pop-up"
				data-url='https://plus.google.com/share?url=<?php p(urlencode($_['reference'])); ?>'/>
				Google+
			</button>
			<button id="oca-files-sharing-add-to-your-website">
				<?php p($l->t('Add to your website')) ?>
			</button>
		</p>

		<div class="hidden" id="oca-files-sharing-add-to-your-website-expanded">
		<p style="margin: 10px 0">
			<a target="_blank" rel="noreferrer" href="<?php p($_['reference']); ?>"
				style="padding:10px;background-color:#0082c9;color:#fff;border-radius:3px;padding-left:4px;">
				<img src="<?php p($_['owncloud_logo_path']); ?>"
					style="width:50px;position:relative;top:8px;">
				<?php p($l->t('Share with me via Nextcloud')); ?>
			</a>
		</p>

		<p>
			<?php p($l->t('HTML Code:')); ?>
			<xmp><a target="_blank" rel="noreferrer" href="<?php p($_['reference']); ?>"
	style="padding:10px;background-color:#0082c9;color:#fff;border-radius:3px;padding-left:4px;">
	<img src="<?php  p(\OC::$server->getURLGenerator()->getAbsoluteURL($_['owncloud_logo_path'])); ?>"
		style="width:50px;position:relative;top:8px;">
	<?php p($l->t('Share with me via Nextcloud')); ?>

</a></xmp>
		</p>
		</div>
		<?php } ?>

	</div>
<?php endif; ?>
