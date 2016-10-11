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
	<a href="<?php p(link_to_docs('admin-ldap')); ?>"
		target="_blank" rel="noreferrer">
		<img src="<?php print_unescaped(image_path('', 'actions/info.svg')); ?>"
			style="height:1.75ex" />
		<span class="ldap_grey"><?php p($l->t('Help'));?></span>
	</a>
</div>
