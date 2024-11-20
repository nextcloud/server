<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcSettingsSection :name="t('theming', 'Navigation bar settings')">
		<h3>{{ t('theming', 'Default app') }}</h3>
		<p class="info-note">
			{{ t('theming', 'The default app is the app that is e.g. opened after login or when the logo in the menu is clicked.') }}
		</p>

		<NcCheckboxRadioSwitch :checked.sync="hasCustomDefaultApp" type="switch" data-cy-switch-default-app="">
			{{ t('theming', 'Use custom default app') }}
		</NcCheckboxRadioSwitch>

		<template v-if="hasCustomDefaultApp">
			<h4>{{ t('theming', 'Global default app') }}</h4>
			<NcSelect v-model="selectedApps"
				:close-on-select="false"
				:placeholder="t('theming', 'Global default apps')"
				:options="allApps"
				:multiple="true" />
			<h5>{{ t('theming', 'Default app priority') }}</h5>
			<p class="info-note">
				{{ t('theming', 'If an app is not enabled for a user, the next app with lower priority is used.') }}
			</p>
			<AppOrderSelector :value.sync="selectedApps" />
		</template>
	</NcSettingsSection>
</template>

<script lang="ts">
import type { INavigationEntry } from '../../../../../core/src/types/navigation'

import { showError } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { computed, defineComponent } from 'vue'

import axios from '@nextcloud/axios'

import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'
import NcSelect from '@nextcloud/vue/dist/Components/NcSelect.js'
import NcSettingsSection from '@nextcloud/vue/dist/Components/NcSettingsSection.js'
import AppOrderSelector from '../AppOrderSelector.vue'

export default defineComponent({
	name: 'AppMenuSection',
	components: {
		AppOrderSelector,
		NcCheckboxRadioSwitch,
		NcSelect,
		NcSettingsSection,
	},
	props: {
		defaultApps: {
			type: Array,
			required: true,
		},
	},
	emits: {
		'update:defaultApps': (value: string[]) => Array.isArray(value) && value.every((id) => typeof id === 'string'),
	},
	setup(props, { emit }) {
		const hasCustomDefaultApp = computed({
			get: () => props.defaultApps.length > 0,
			set: (checked: boolean) => {
				if (checked) {
					emit('update:defaultApps', ['dashboard', 'files'])
				} else {
					selectedApps.value = []
				}
			},
		})

		/**
		 * All enabled apps which can be navigated
		 */
		const allApps = loadState<INavigationEntry[]>('core', 'apps')
			.map(({ id, name, icon }) => ({ label: name, id, icon }))

		/**
		 * Currently selected app, wrapps the setter
		 */
		const selectedApps = computed({
			get: () => props.defaultApps.map((id) => allApps.filter(app => app.id === id)[0]),
			set(value) {
				saveSetting('defaultApps', value.map(app => app.id))
					.then(() => emit('update:defaultApps', value.map(app => app.id)))
					.catch(() => showError(t('theming', 'Could not set global default apps')))
			},
		})

		const saveSetting = async (key: string, value: unknown) => {
			const url = generateUrl('/apps/theming/ajax/updateAppMenu')
			return await axios.put(url, {
				setting: key,
				value,
			})
		}

		return {
			allApps,
			selectedApps,
			hasCustomDefaultApp,

			t,
		}
	},
})
</script>

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
