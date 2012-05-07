<form id="contacts">
	<fieldset class="personalblock">
		<legend><?php echo $l->t('Contacts'); ?></legend>
		<?php echo $l->t('CardDAV syncing addresses'); ?> (<a href="http://owncloud.org/synchronisation/" target="_blank"><?php echo $l->t('more info'); ?></a>)
		<dl>
		<dt><?php echo $l->t('Primary address (Kontact et al)'); ?></dt>
		<dd><code><?php echo OCP\Util::linkToRemote('carddav'); ?></code></dd>
		<dt><?php echo $l->t('iOS/OS X'); ?></dt>
		<dd><code><?php echo OCP\Util::linkToRemote('carddav'); ?>principals/<?php echo OCP\USER::getUser(); ?></code>/</dd>
		</dl>
		Powered by <a href="http://geonames.org/" target="_blank">geonames.org webservice</a>
	</fieldset>
</form>
