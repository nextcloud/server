<div class="ldapSettingControls">
	<input class="ldap_submit" value="<?php p($l->t('Save'));?>" type="submit">
	<button class="ldap_action_test_connection" name="ldap_action_test_connection">
		<?php p($l->t('Test Configuration'));?>
	</button>
	<a href="<?php p($theme->getDocBaseUrl()); ?>/server/7.0/go.php?to=admin-ldap"
		target="_blank">
		<img src="<?php print_unescaped(OCP\Util::imagePath('', 'actions/info.png')); ?>"
			style="height:1.75ex" />
		<?php p($l->t('Help'));?>
	</a>
</div>
