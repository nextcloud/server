<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { showError, showSuccess } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { ref } from 'vue'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import logger from '../logger.ts'
import { updateSystemTagsAdminRestriction } from '../services/api.ts'

// By default, system tags creation is not restricted to admins
const systemTagsCreationRestrictedToAdmin = ref(loadState('systemtags', 'restrictSystemTagsCreationToAdmin', false))

/**
 * Update system tags admin restriction setting
 *
 * @param isRestricted - True if system tags creation should be restricted to admins
 */
async function updateSystemTagsDefault(isRestricted: boolean) {
	try {
		const responseData = await updateSystemTagsAdminRestriction(isRestricted)
		logger.debug('updateSystemTagsDefault', { responseData })
		handleResponse({
			isRestricted,
			status: responseData.ocs?.meta?.status,
		})
	} catch (e) {
		handleResponse({
			errorMessage: t('systemtags', 'Unable to update setting'),
			error: e,
		})
	}
}

/**
 * Handle response from updating system tags admin restriction
 *
 * @param context - The response context
 * @param context.isRestricted - Whether system tags creation is restricted to admins
 * @param context.status - The response status
 * @param context.errorMessage - The error message, if any
 * @param context.error - The error object, if any
 */
function handleResponse({ isRestricted, status, errorMessage, error }: {
	isRestricted?: boolean
	status?: string
	errorMessage?: string
	error?: unknown
}) {
	if (status === 'ok') {
		systemTagsCreationRestrictedToAdmin.value = !!isRestricted
		showSuccess(isRestricted
			? t('systemtags', 'System tag creation is now restricted to administrators')
			: t('systemtags', 'System tag creation is now allowed for everybody'))
		return
	}

	if (errorMessage) {
		showError(errorMessage)
		logger.error(errorMessage, { error })
	}
}
</script>

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
