<?php

/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2013-2017 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
?>
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
