<div class="ldapWizardControls">
	<button id="ldap_action_back" name="ldap_action_back" class="invisible">
		<?php p($l->t('Back'));?>
	</button>
	<button id="ldap_action_continue" name="ldap_action_continue">
		<?php p($l->t('Continue'));?>
	</button>
	<a href="<?php p($theme->getDocBaseUrl()); ?>/server/5.0/admin_manual/auth_ldap.html"
		target="_blank">
		<img src="<?php print_unescaped(OCP\Util::imagePath('', 'actions/info.png')); ?>"
			style="height:1.75ex" />
		<?php p($l->t('Help'));?>
	</a>
</div>