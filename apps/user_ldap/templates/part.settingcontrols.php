<div class="ldapSettingControls">
	<button type="button" class="ldap_action_test_connection" name="ldap_action_test_connection">
		<?php p($l->t('Test Configuration'));?>
	</button>
	<a href="<?php p(\OC::$server->getURLGenerator()->linkToDocs('admin-ldap')); ?>"
		target="_blank">
		<img src="<?php print_unescaped(OCP\Util::imagePath('', 'actions/info.png')); ?>"
			style="height:1.75ex" />
		<?php p($l->t('Help'));?>
	</a>
</div>
