<div class="ldapWizardControls">
	<span class="ldap_saving hidden"><?php p($l->t('Saving'));?> <img class="wizSpinner" src="<?php p(image_path('core', 'loading.gif')); ?>"/></span>
	<span class="ldap_config_state_indicator"></span> <span class="ldap_config_state_indicator_sign"></span>
	<button class="ldap_action_back invisible" name="ldap_action_back"
			type="button">
		<?php p($l->t('Back'));?>
	</button>
	<button class="ldap_action_continue" name="ldap_action_continue" type="button">
		<?php p($l->t('Continue'));?>
	</button>
	<a href="<?php p(\OC_Helper::linkToDocs('admin-ldap')); ?>"
		target="_blank">
		<img src="<?php print_unescaped(OCP\Util::imagePath('', 'actions/info.png')); ?>"
			style="height:1.75ex" />
		<span class="ldap_grey"><?php p($l->t('Help'));?></span>
	</a>
</div>
