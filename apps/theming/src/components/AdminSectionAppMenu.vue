<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { INavigationEntry } from '../../../../core/src/types/navigation.ts'
import type { AdminThemingParameters } from '../types.d.ts'

import axios from '@nextcloud/axios'
import { showError } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { ref, useId, watch } from 'vue'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import AppOrderSelector from './AppOrderSelector.vue'
import { logger } from '../utils/logger.ts'

const idGlobalDefaultApp = useId()
const { defaultApps } = loadState<AdminThemingParameters>('theming', 'adminThemingParameters')

/**
 * All enabled apps which can be navigated
 */
const allApps = loadState<INavigationEntry[]>('core', 'apps')
	.map(({ id, name, icon }) => ({ label: name, id, icon }))

/**
 * Currently selected app, wrapps the setter
 */
const selectedApps = ref(defaultApps.map((id) => allApps.find((app) => app.id === id)!).filter(Boolean))
watch(selectedApps, async (value) => {
	try {
		await saveSetting('defaultApps', value.map((app) => app.id))
	} catch (error) {
		logger.error('Could not set global default apps', { error })
		showError(t('theming', 'Could not set global default apps'))
	}
})

const hasCustomDefaultApp = ref(defaultApps.length > 0)
watch(hasCustomDefaultApp, (checked) => {
	selectedApps.value = checked
		? allApps.filter((app) => ['dashboard', 'files'].includes(app.id))
		: []
})

/**
 * @param key - The setting key
 * @param value - The setting value
 */
async function saveSetting(key: string, value: unknown) {
	const url = generateUrl('/apps/theming/ajax/updateAppMenu')
	return await axios.put(url, {
		setting: key,
		value,
	})
}
</script>

<template>
	<NcSettingsSection :name="t('theming', 'Navigation bar settings')">
		<h3>{{ t('theming', 'Default app') }}</h3>
		<p class="info-note">
			{{ t('theming', 'The default app is the app that is e.g. opened after login or when the logo in the menu is clicked.') }}
		</p>

		<NcCheckboxRadioSwitch v-model="hasCustomDefaultApp" type="switch">
			{{ t('theming', 'Use custom default app') }}
		</NcCheckboxRadioSwitch>

		<section v-if="hasCustomDefaultApp" :aria-labelledby="idGlobalDefaultApp">
			<h4 :id="idGlobalDefaultApp">
				{{ t('theming', 'Global default app') }}
			</h4>
			<NcSelect
				v-model="selectedApps"
				keep-open
				multiple
				:placeholder="t('theming', 'Global default apps')"
				:options="allApps" />

			<h5>{{ t('theming', 'Default app priority') }}</h5>
			<p class="info-note">
				{{ t('theming', 'If an app is not enabled for a user, the next app with lower priority is used.') }}
			</p>
			<AppOrderSelector v-model="selectedApps" />
		</section>
	</NcSettingsSection>
</template>

<style scoped lang="scss">
h3, h4 {
	font-weight: bold;
}

h4, h5 {
	margin-block-start: 12px;
}

.info-note {
	color: var(--color-text-maxcontrast);
}
</style>
