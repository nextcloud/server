<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { getCapabilities } from '@nextcloud/capabilities'
import { showError } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import { confirmPassword } from '@nextcloud/password-confirmation'
import { computed, ref } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import { logger } from '../service/logger.ts'
import { print } from '../service/PrintService.js'
import { useStore } from '../store/index.ts'

// eslint-disable-next-line @typescript-eslint/no-explicit-any
const instanceName = (getCapabilities() as any).theming.name ?? 'Nextcloud'

const store = useStore()
const generatingCodes = ref(false)

const hasCodes = computed(() => {
	return store.codes && store.codes.length > 0
})

const downloadFilename = instanceName + '-backup-codes.txt'
const downloadUrl = computed(() => {
	if (!hasCodes.value) {
		return ''
	}
	return 'data:text/plain,' + encodeURIComponent(store.codes.reduce((prev, code) => {
		return prev + code + '\n'
	}, ''))
})

/**
 * Generate new backup codes
 */
async function generateBackupCodes() {
	await confirmPassword()
	// Hide old codes
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

/**
 * Print the backup codes
 */
function printCodes() {
	print(!store.codes || store.codes.length === 0 ? [] : store.codes)
}
</script>

<template>
	<div :class="$style.backupcodesSettings">
		<NcButton
			v-if="!store.enabled"
			:disabled="generatingCodes"
			variant="primary"
			@click="generateBackupCodes">
			<template #icon>
				<NcLoadingIcon v-if="generatingCodes" />
			</template>
			{{ t('twofactor_backupcodes', 'Generate backup codes') }}
		</NcButton>
		<template v-else>
			<p>
				<template v-if="!hasCodes">
					{{ t('twofactor_backupcodes', 'Backup codes have been generated. {used} of {total} codes have been used.', { used: store.used, total: store.total }) }}
				</template>
				<template v-else>
					{{ t('twofactor_backupcodes', 'These are your backup codes. Please save and/or print them as you will not be able to read the codes again later.') }}
					<ul :aria-label="t('twofactor_backupcodes', 'List of backup codes')">
						<li
							v-for="code in store.codes"
							:key="code"
							:class="$style.backupcodesSettings__code">
							{{ code }}
						</li>
					</ul>
				</template>
			</p>
			<p :class="$style.backupcodesSettings__actions">
				<NcButton
					id="generate-backup-codes"
					variant="error"
					@click="generateBackupCodes">
					{{ t('twofactor_backupcodes', 'Regenerate backup codes') }}
				</NcButton>
				<template v-if="hasCodes">
					<NcButton @click="printCodes">
						{{ t('twofactor_backupcodes', 'Print backup codes') }}
					</NcButton>
					<NcButton
						:href="downloadUrl"
						:download="downloadFilename"
						variant="primary">
						{{ t('twofactor_backupcodes', 'Save backup codes') }}
					</NcButton>
				</template>
			</p>
			<p>
				<em>
					{{ t('twofactor_backupcodes', 'If you regenerate backup codes, you automatically invalidate old codes.') }}
				</em>
			</p>
		</template>
	</div>
</template>

<style module>
.backupcodesSettings {
	display: flex;
	flex-direction: column;
}

.backupcodesSettings__code {
	font-family: monospace;
	letter-spacing: 0.02em;
	font-size: 1.2em;
}

.backupcodesSettings__actions {
	display: flex;
	flex-wrap: wrap;
	gap: var(--default-grid-baseline);
}
</style>
