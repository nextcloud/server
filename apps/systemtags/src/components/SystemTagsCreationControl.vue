<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div id="system-tags-creation-control">
		<h4 class="inlineblock">
			{{ t('systemtags', 'System tag management') }}
		</h4>

		<p class="settings-hint">
			{{ t('systemtags', 'If enabled, only administrators can create and edit tags. Accounts can still assign and remove them from files.') }}
		</p>

		<NcCheckboxRadioSwitch
			v-model="systemTagsCreationRestrictedToAdmin"
			type="switch"
			@update:modelValue="updateSystemTagsDefault">
			{{ t('systemtags', 'Restrict tag creation and editing to administrators') }}
		</NcCheckboxRadioSwitch>
	</div>
</template>

<script lang="ts">
import { showError, showSuccess } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import logger from '../logger.ts'
import { updateSystemTagsAdminRestriction } from '../services/api.js'

export default {
	name: 'SystemTagsCreationControl',

	components: {
		NcCheckboxRadioSwitch,
	},

	setup() {
		return {
			t,
		}
	},

	data() {
		return {
			// By default, system tags creation is not restricted to admins
			systemTagsCreationRestrictedToAdmin: loadState('systemtags', 'restrictSystemTagsCreationToAdmin', false),
		}
	},

	methods: {
		async updateSystemTagsDefault(isRestricted: boolean) {
			try {
				const responseData = await updateSystemTagsAdminRestriction(isRestricted)
				logger.debug('updateSystemTagsDefault', responseData)
				this.handleResponse({
					isRestricted,
					status: responseData.ocs?.meta?.status,
				})
			} catch (e) {
				this.handleResponse({
					errorMessage: t('systemtags', 'Unable to update setting'),
					error: e,
				})
			}
		},

		handleResponse({ isRestricted, status, errorMessage, error }) {
			if (status === 'ok') {
				this.systemTagsCreationRestrictedToAdmin = isRestricted
				showSuccess(isRestricted
					? t('systemtags', 'System tag creation is now restricted to administrators')
					: t('systemtags', 'System tag creation is now allowed for everybody'))
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
