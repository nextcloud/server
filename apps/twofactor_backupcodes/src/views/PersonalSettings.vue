<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { mdiRefresh } from '@mdi/js'
import { showError } from '@nextcloud/dialogs'
import { n, t } from '@nextcloud/l10n'
import { confirmPassword } from '@nextcloud/password-confirmation'
import { computed, ref } from 'vue'
import NcFormBox from '@nextcloud/vue/components/NcFormBox'
import NcFormBoxButton from '@nextcloud/vue/components/NcFormBoxButton'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import BackupCodesDialog from '../components/BackupCodesDialog.vue'
import { logger } from '../service/logger.ts'
import { useStore } from '../store/index.ts'

const store = useStore()

// TRANSLATORS {used} is the number of used backup codes and picks the plural form, {total} is the total number of codes
const usage = computed(() => n('twofactor_backupcodes', '{used} out of the {total} current codes has been used. Regenerating invalidates the old codes.', '{used} out of the {total} current codes have been used. Regenerating invalidates the old codes.', store.used, { used: store.used, total: store.total }))
const generatingCodes = ref(false)

/**
 * Generate new backup codes
 */
async function generateBackupCodes() {
	// Not disabled while generating, so focus stays on the row and returns there after the dialog
	if (generatingCodes.value) {
		return
	}
	await confirmPassword()
	generatingCodes.value = true

	try {
		await store.generate()
	} catch (error) {
		logger.error('Error generating backup codes', { error })
		showError(t('twofactor_backupcodes', 'An error occurred while generating your backup codes'))
	} finally {
		generatingCodes.value = false
	}
}
</script>

<template>
	<NcFormBox>
		<NcFormBoxButton
			:label="store.enabled ? t('twofactor_backupcodes', 'Regenerate backup codes') : t('twofactor_backupcodes', 'Generate backup codes')"
			:description="store.enabled ? usage : undefined"
			@click="generateBackupCodes">
			<template #icon>
				<NcLoadingIcon v-if="generatingCodes" />
				<NcIconSvgWrapper v-else :path="mdiRefresh" />
			</template>
		</NcFormBoxButton>

		<BackupCodesDialog
			v-if="store.codes.length > 0"
			:codes="store.codes"
			@close="store.codes = []" />
	</NcFormBox>
</template>
