<?php
/** @var \OCP\IL10N $l */
/** @var array $_ */
script('federatedfilesharing', 'settings-personal');
style('federatedfilesharing', 'settings-personal');
?>

<div id="fileSharingSettings" class="section">
	<h2 data-anchor-name="federated-cloud"><?php p($l->t('Federated Cloud')); ?></h2>
	<a target="_blank" rel="noreferrer noopener" class="icon-info svg"
	title="<?php p($l->t('Open documentation'));?>"
	href="<?php p(link_to_docs('user-sharing-federated')); ?>"></a>
	<p class="settings-hint"><?php p($l->t('You can share with anyone who uses a Nextcloud server or other Open Cloud Mesh (OCM) compatible servers and services! Just put their Federated Cloud ID in the share dialog. It looks like person@cloud.example.com')); ?></p>

	<p>
		<?php p($l->t('Your Federated Cloud ID:')); ?>
		<strong id="cloudid"><?php p($_['cloudId']); ?></strong>
		<a class="clipboardButton icon icon-clippy" data-clipboard-target="#cloudid"></a>
	</p>

	<br>

	<p>
		<?php p($l->t('Share it so your friends can share files with you:')); ?><br>
		<button class="social-facebook pop-up"
				data-url='https://www.facebook.com/sharer/sharer.php?u=<?php p(urlencode($_['reference'])); ?>'>
			Facebook
		</button>
		<button class="social-twitter pop-up"
			data-url='https://twitter.com/intent/tweet?text=<?php p(urlencode($_['message_with_URL'])); ?>'>
			Twitter
		</button>
		<button class="social-diaspora pop-up"
				data-url='https://sharetodiaspora.github.io/?title=<?php p($_['message_without_URL']); ?>&url=<?php p(urlencode($_['reference'])); ?>'>
			Diaspora
		</button>
		<button id="oca-files-sharing-add-to-your-website">
			<?php p($l->t('Add to your website')) ?>
		</button>
	</p>

	<div class="hidden" id="oca-files-sharing-add-to-your-website-expanded">
	<p style="margin: 10px 0">
		<a target="_blank" rel="noreferrer noopener" href="<?php p($_['reference']); ?>"
			style="padding:10px;background-color:<?php p($_['color']); ?>;color:<?php p($_['textColor']); ?>;border-radius:3px;padding-left:4px;">
			<span style="background-image:url(<?php p(\OC::$server->getURLGenerator()->getAbsoluteURL($_['logoPath'])); ?>);width:50px;height:30px;position:relative;top:8px;background-size:contain;display:inline-block;background-repeat:no-repeat; background-position: center center;"></span>
			<?php p($l->t('Share with me via Nextcloud')); ?>
		</a>
	</p>

	<p>
		<?php p($l->t('HTML Code:')); ?>
		<xmp><a target="_blank" rel="noreferrer noopener" href="<?php p($_['reference']); ?>" style="padding:10px;background-color:<?php p($_['color']); ?>;color:<?php p($_['textColor']); ?>;border-radius:3px;padding-left:4px;">
<span style="background-image:url(<?php p(\OC::$server->getURLGenerator()->getAbsoluteURL($_['logoPath'])); ?>);width:50px;height:30px;position:relative;top:8px;background-size:contain;display:inline-block;background-repeat:no-repeat; background-position: center center;"></span>
<?php p($l->t('Share with me via Nextcloud')); ?></a></xmp>
	</p>
	</div>

</div>

