<form id="contacts">
	<fieldset class="personalblock">
		<strong><?php echo $l->t('Contacts'); ?></strong><br />
		<?php echo $l->t('CardDAV syncing addresses:'); ?>
		<dl>
		<dt><b><?php echo $l->t('Primary address (Kontact et al)'); ?></b></dt>
		<dd><code><i><?php echo OC_Helper::linkToAbsolute('contacts', 'carddav.php'); ?>/</i></code></dd>
		<dt><b><?php echo $l->t('iOS/OS X'); ?></b></dt>
		<dd><code><i><?php echo OC_Helper::linkToAbsolute('contacts', 'carddav.php'); ?>/principals/<?php echo OC_User::getUser(); ?></i></code>/</dd>
		</dl>
		Powered by <a href="http://geonames.org/" target="_blank">geonames.org webservice</a>
	</fieldset>
</form>
