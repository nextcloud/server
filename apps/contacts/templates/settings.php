<form id="contacts">
	<fieldset class="personalblock">
		<strong><?php echo $l->t('Contacts'); ?></strong><br />
		<?php echo $l->t('CardDAV syncing addresses:'); ?>
		<dl>
		<dt><?php echo $l->t('Primary address (Kontact et al)'); ?></dt>
		<dd><code><?php echo OC_Helper::linkToAbsolute('contacts', 'carddav.php'); ?>/</code></dd>
		<dt><?php echo $l->t('iOS/OS X'); ?></dt>
		<dd><code><?php echo OC_Helper::linkToAbsolute('contacts', 'carddav.php'); ?>/principals/<?php echo OC_User::getUser(); ?></code>/</dd>
		</dl>
		Powered by <a href="http://geonames.org/" target="_blank">geonames.org webservice</a>
	</fieldset>
</form>
