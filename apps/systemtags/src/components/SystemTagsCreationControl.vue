<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div id="system-tags-creation-control">
		<h4 class="inlineblock">
			{{ t('settings', 'System tag creation') }}
		</h4>

		<p class="settings-hint">
			{{ t('settings', 'If enabled, regular accounts will be restricted from creating new tags but will still be able to assign and remove them from their files.') }}
		</p>

		<NcCheckboxRadioSwitch type="switch"
			:checked.sync="systemTagsCreationRestrictedToAdmin"
			@update:checked="updateSystemTagsDefault">
			{{ t('settings', 'Restrict tag creation to admins only') }}
		</NcCheckboxRadioSwitch>
	</div>
</template>

<script lang="ts">
import { loadState } from '@nextcloud/initial-state'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import logger from '../logger.ts'
import { updateSystemTagsAdminRestriction } from '../services/api.js'

import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'

export default {
	name: 'SystemTagsCreationControl',

	components: {
		NcCheckboxRadioSwitch,
	},

	data() {
		return {
			// By default, system tags creation is not restricted to admins
			systemTagsCreationRestrictedToAdmin: loadState('settings', 'restrictSystemTagsCreationToAdmin', '0') === '1',
		}
	},
	methods: {
		t,
		async updateSystemTagsDefault(isRestricted: boolean) {
			try {
				const responseData = await updateSystemTagsAdminRestriction(isRestricted)
				console.debug('updateSystemTagsDefault', responseData)
				this.handleResponse({
					isRestricted,
					status: responseData.ocs?.meta?.status,
				})
			} catch (e) {
				this.handleResponse({
					errorMessage: t('settings', 'Unable to update setting'),
					error: e,
				})
			}
		},

		handleResponse({ isRestricted, status, errorMessage, error }) {
			if (status === 'ok') {
				this.systemTagsCreationRestrictedToAdmin = isRestricted
				showSuccess(isRestricted
					? t('settings', 'System tag creation is now restricted to administrators')
					: t('settings', 'System tag creation is now allowed for everybody'),
				)
				return
			}

			if (errorMessage) {
				showError(errorMessage)
				logger.error(errorMessage, error)
			}
		},
	},
}
</script>
