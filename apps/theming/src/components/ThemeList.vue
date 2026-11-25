<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { ITheme } from './ThemeListItem.vue'

import { loadState } from '@nextcloud/initial-state'
import NcFormBox from '@nextcloud/vue/components/NcFormBox'
import NcFormBoxSwitch from '@nextcloud/vue/components/NcFormBoxSwitch'

defineProps<{
	/**
	 * Label of this list
	 */
	label: string

	/**
	 * The list of available themes
	 */
	themes: ITheme[]

	/**
	 * Disable every switch while a theme update is in progress
	 */
	loading?: boolean
}>()

const emit = defineEmits<{
	toggle: [theme: ITheme, state: boolean]
}>()

/** Font themes, such as the dyslexia font. */
const THEME_TYPE_FONT = 2

const enforcedTheme = loadState('theming', 'enforceTheme', '')

/**
 * Attributes that apply a font theme's own typeface to its name.
 *
 * @param theme - Theme being rendered
 */
function fontPreviewAttrs(theme: ITheme): Record<string, string> {
	if (theme.type !== THEME_TYPE_FONT) {
		return {}
	}
	return { [`data-theme-${theme.id}`]: '' }
}
</script>

<template>
	<NcFormBox
		:aria-label="label"
		class="theme-list">
		<NcFormBoxSwitch
			v-for="theme in themes"
			:key="theme.id"
			:modelValue="theme.enabled"
			:disabled="enforcedTheme !== ''"
			:loading="loading"
			@update:modelValue="emit('toggle', theme, $event)">
			<span
				:class="{ 'theme-list__font-name': theme.type === THEME_TYPE_FONT }"
				v-bind="fontPreviewAttrs(theme)">
				{{ theme.title }}
			</span>
		</NcFormBoxSwitch>
	</NcFormBox>
</template>

<style lang="scss" scoped>
.theme-list {
	gap: calc(2 * var(--default-grid-baseline));
}

.theme-list__font-name {
	font-family: var(--font-face);
}
</style>
