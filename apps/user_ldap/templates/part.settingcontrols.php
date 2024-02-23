<div class="ldapSettingControls">
	<button type="button" class="ldap_action_test_connection" name="ldap_action_test_connection">
		<?php p($l->t('Test Configuration'));?>
	</button>
	<a href="<?php p(link_to_docs('admin-ldap')); ?>"
		target="_blank" rel="noreferrer noopener">
		<img src="<?php print_unescaped(image_path('core', 'actions/info.svg')); ?>"
			style="height:1.75ex" />
		<?php p($l->t('Help'));?>
	</a>
</div>
