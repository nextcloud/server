<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { Area, Grant, Preset, Selection } from '../utils/accessLevel.ts'

import { t } from '@nextcloud/l10n'
import { computed } from 'vue'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcRadioGroup from '@nextcloud/vue/components/NcRadioGroup'
import { AREA_OPTIONS, areaState, PRESET_GRANTS, setGrant } from '../utils/accessLevel.ts'

/** What the app password can reach */
const selection = defineModel<Selection>({ required: true })

defineProps<{
	/** Scopes the picker can't show, kept when saving */
	kept: string[]
}>()

const labels: Record<Grant, string> = {
	files: t('settings', 'Files'),
	filesShare: t('settings', 'Can create shares and public links'),
	calendar: t('settings', 'Calendar'),
	calendarWrite: t('settings', 'Can edit calendars'),
	contacts: t('settings', 'Contacts'),
	contactsWrite: t('settings', 'Can edit contacts'),
}

const areas = Object.keys(AREA_OPTIONS) as Area[]

const preset = computed({
	get: () => selection.value.preset,
	set(value: string) {
		const name = value as Preset
		const grants = name === 'files' || name === 'calendarContacts' ? PRESET_GRANTS[name] : selection.value.grants
		selection.value = { preset: name, grants }
	},
})

/**
 * @param grant Area or sub-option to change
 * @param on Whether to tick it
 */
function toggle(grant: Grant, on: boolean) {
	selection.value = { preset: 'custom', grants: setGrant(selection.value.grants, grant, on) }
}
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
			<template v-for="area in areas" :key="area">
				<!-- A partly granted area shows as mixed; clicking it grants the whole area -->
				<NcCheckboxRadioSwitch
					:modelValue="areaState(selection.grants, area) === 'all'"
					:indeterminate="areaState(selection.grants, area) === 'partial'"
					@update:modelValue="toggle(area, $event)">
					{{ labels[area] }}
				</NcCheckboxRadioSwitch>
				<div class="access-picker__options">
					<NcCheckboxRadioSwitch
						v-for="option in AREA_OPTIONS[area]"
						:key="option"
						:modelValue="selection.grants.includes(option)"
						@update:modelValue="toggle(option, $event)">
						{{ labels[option] }}
					</NcCheckboxRadioSwitch>
				</div>
			</template>
			<!-- The live region stays in the DOM, screen readers skip regions inserted with their text -->
			<p class="access-picker__hint" role="status">
				{{ selection.grants.length === 0 ? t('settings', 'Choose at least one') : '' }}
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

	&__options {
		padding-inline-start: calc(8 * var(--default-grid-baseline));
	}

	&__hint {
		color: var(--color-text-maxcontrast);
	}
}
</style>
