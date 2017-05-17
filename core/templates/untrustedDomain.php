<?php /** @var $_ array */ ?>

<div class="error">
	<h2><?php p($l->t('Access through untrusted domain')); ?></h2>

	<p>
		<?php p($l->t('Please contact your administrator. If you are an administrator of this instance, configure the "trusted_domains" setting in config/config.php. An example configuration is provided in config/config.sample.php.')); ?>
	</p>
	<p>
		<?php p($l->t('Depending on your configuration, as an administrator you might also be able to use the button below to trust this domain.')); ?>
	</p>
	<p style="text-align:center;">
		<a href="<?php print_unescaped(\OC::$server->getURLGenerator()->getAbsoluteURL(\OCP\Util::linkToRoute('settings.AdminSettings.index'))); ?>?trustDomain=<?php p($_['domain']); ?>" class="button">
			<?php p($l->t('Add "%s" as trusted domain', array($_['domain']))); ?>
		</a>
	</p>
</div>
