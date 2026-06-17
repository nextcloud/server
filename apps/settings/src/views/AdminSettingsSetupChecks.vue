<!--
 - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { ISetupCheck } from '../settings-types.ts'

import { mdiCheck, mdiCloseCircleOutline, mdiReload } from '@mdi/js'
import axios from '@nextcloud/axios'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { computed, onMounted, ref } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import SettingsSetupChecksList from '../components/SettingsSetupChecks/SettingsSetupChecksList.vue'
import logger from '../logger.ts'

const {
	sectionDocsUrl,
	installationGuidesDocsUrl,
	loggingSectionUrl,
} = loadState<Record<string, string>>('settings', 'setup-checks-section')

const adminDocsHtml = t(
	'settings',
	'Please double check the {linkStartInstallationGuides}installation guides{linkEnd}, and check for any errors or warnings in the {linkStartLog}log{linkEnd}.',
	{
		linkEnd: ' ↗</a>',
		linkStartInstallationGuides: `<a target="_blank" rel="noreferrer noopener" href="${installationGuidesDocsUrl}">`,
		linkStartLog: `<a target="_blank" rel="noreferrer noopener" href="${loggingSectionUrl}">`,
	},
	{ escape: false },
)

const footerHtml = t(
	'settings',
	'Check the security of your {productName} over {linkStart}our security scan{linkEnd}.',
	{
		linkStart: '<a target="_blank" rel="noreferrer noopener" href="https://scan.nextcloud.com">',
		linkEnd: ' ↗</a>',
		productName: window.OC.theme.productName,
	},
	{ escape: false },
)

const loading = ref(true)
const loadingFailed = ref(false)
const setupChecks = ref<ISetupCheck[]>([])

const allTestsOk = computed(() => setupChecks.value.length === 0)
const hasErrors = computed(() => setupChecks.value.some(({ severity }) => severity === 'error'))
const hasWarnings = computed(() => setupChecks.value.some(({ severity }) => severity === 'warning'))

onMounted(loadSetupChecks)

/**
 * Load the setup checks from API.
 */
async function loadSetupChecks() {
	try {
		loading.value = true
		loadingFailed.value = false

		const { data } = await axios.get<Record<string, Record<string, ISetupCheck>>>(generateUrl('settings/ajax/checksetup'))
		setupChecks.value = Object.values(data)
			.map((mapping) => Object.values(mapping))
			.flat()
			.filter(({ severity }) => severity !== 'success')
	} catch (error) {
		loadingFailed.value = true
		logger.error('Failed to load setup checks', { error })
	} finally {
		loading.value = false
	}
}
</script>

<template>
	<NcSettingsSection
		id="security-warning"
		:name="t('settings', 'Security & setup warnings')"
		:description="t('settings', 'It is important for the security and performance of your instance that everything is configured correctly. To help you with that we are doing some automatic checks. Please see the linked documentation for more information.')"
		:doc-url="sectionDocsUrl">
		<NcEmptyContent v-if="loading" :name="t('settings', 'Checking your server …')">
			<template #icon>
				<NcLoadingIcon />
			</template>
		</NcEmptyContent>

		<NcEmptyContent v-else-if="loadingFailed" :name="t('settings', 'Failed to run setup checks')">
			<template #icon>
				<NcIconSvgWrapper :path="mdiCloseCircleOutline" />
			</template>
			<template #action>
				<NcButton variant="primary" @click="loadSetupChecks">
					<template #icon>
						<NcIconSvgWrapper :path="mdiReload" />
					</template>
					{{ t('settings', 'Try again') }}
				</NcButton>
			</template>
		</NcEmptyContent>

		<NcEmptyContent v-else-if="allTestsOk" :name="t('settings', 'All checks passed.')">
			<template #icon>
				<NcIconSvgWrapper :path="mdiCheck" />
			</template>
		</NcEmptyContent>

		<template v-else>
			<p v-if="hasErrors || hasWarnings" class="settings-security-warnings__result-hint">
				{{ hasErrors
					? t('settings', 'There are some errors regarding your setup.')
					: t('settings', 'There are some warnings regarding your setup.')
				}}
			</p>

			<SettingsSetupChecksList :setup-checks="setupChecks" severity="error" />
			<SettingsSetupChecksList :setup-checks="setupChecks" severity="warning" />
			<SettingsSetupChecksList :setup-checks="setupChecks" severity="info" />

			<!-- eslint-disable-next-line vue/no-v-html -->
			<p class="settings-security-warnings__hint" v-html="adminDocsHtml" />
		</template>

		<!-- eslint-disable-next-line vue/no-v-html -->
		<p class="settings-security-warnings__footer" v-html="footerHtml" />
	</NcSettingsSection>
</template>

<style scope lang="scss">
.settings-security-warnings {
	&__hint {
		margin-top: calc(2 * var(--default-grid-baseline));
	}

	&__footer {
		margin-top: calc(3 * var(--default-grid-baseline));
	}
}
</style>
