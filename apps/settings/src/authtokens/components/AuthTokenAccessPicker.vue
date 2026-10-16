<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { Area, Preset, Selection } from '../utils/accessLevel.ts'

import { t } from '@nextcloud/l10n'
import { computed } from 'vue'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcRadioGroup from '@nextcloud/vue/components/NcRadioGroup'
import { PRESET_AREAS } from '../utils/accessLevel.ts'

/** What the app password can reach */
const selection = defineModel<Selection>({ required: true })

defineProps<{
	/** Scopes the picker can't show, kept when saving */
	kept: string[]
}>()

const areaOptions: { area: Area, label: string }[] = [
	{ area: 'files', label: t('settings', 'Files') },
	{ area: 'calendar', label: t('settings', 'Calendar') },
	{ area: 'contacts', label: t('settings', 'Contacts') },
]

const preset = computed({
	get: () => selection.value.preset,
	set(value: string) {
		const name = value as Preset
		const areas = name === 'files' || name === 'calendarContacts' ? PRESET_AREAS[name] : selection.value.areas
		selection.value = { preset: name, areas }
	},
})

const areas = computed({
	get: () => selection.value.areas,
	set: (value: Area[]) => {
		selection.value = { preset: 'custom', areas: value }
	},
})
</script>

<template>
	<div class="access-picker">
		<NcRadioGroup v-model="preset" :label="t('settings', 'Access')">
			<NcCheckboxRadioSwitch value="all" :description="t('settings', 'Everything you can do, including your settings and any admin rights you have.')">
				{{ t('settings', 'All access') }}
			</NcCheckboxRadioSwitch>
			<NcCheckboxRadioSwitch value="files">
				{{ t('settings', 'Files only') }}
			</NcCheckboxRadioSwitch>
			<NcCheckboxRadioSwitch value="calendarContacts">
				{{ t('settings', 'Calendar and contacts') }}
			</NcCheckboxRadioSwitch>
			<NcCheckboxRadioSwitch value="custom" :description="t('settings', 'This app password can only reach what is ticked.')">
				{{ t('settings', 'Custom') }}
			</NcCheckboxRadioSwitch>
		</NcRadioGroup>

		<div
			v-if="preset === 'custom'"
			class="access-picker__areas"
			role="group"
			:aria-label="t('settings', 'Custom access')">
			<NcCheckboxRadioSwitch
				v-for="option in areaOptions"
				:key="option.area"
				v-model="areas"
				:value="option.area">
				{{ option.label }}
			</NcCheckboxRadioSwitch>
			<!-- The live region stays in the DOM, screen readers skip regions inserted with their text -->
			<p class="access-picker__hint" role="status">
				{{ areas.length === 0 ? t('settings', 'Choose at least one') : '' }}
			</p>
		</div>

		<p v-if="kept.length > 0" class="access-picker__hint">
			{{ t('settings', 'This app password also has access set outside of Settings. It stays when you save.') }}
		</p>
	</div>
</template>

<style lang="scss" scoped>
.access-picker {
	display: flex;
	flex-direction: column;
	gap: calc(2 * var(--default-grid-baseline));

	&__areas {
		padding-inline-start: calc(4 * var(--default-grid-baseline));
	}

	&__hint {
		color: var(--color-text-maxcontrast);
	}
}
</style>
