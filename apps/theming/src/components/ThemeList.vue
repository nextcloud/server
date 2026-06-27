<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { ITheme } from './ThemeListItem.vue'

import axios, { isAxiosError } from '@nextcloud/axios'
import { showError } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { generateOcsUrl } from '@nextcloud/router'
import { ref } from 'vue'
import ThemeListItem from './ThemeListItem.vue'
import { logger } from '../utils/logger.ts'

const props = defineProps<{
	/**
	 * Label of this list
	 */
	label: string

	/**
	 * Allow to select multiple themes
	 */
	multiple?: boolean

	/**
	 * The default theme if multiple is false
	 */
	default?: ITheme

	/**
	 * The list of available themes
	 */
	themes: ITheme[]
}>()

const emit = defineEmits<{
	updated: []
}>()

const name = 'themes-' + Math.random().toString(16).slice(6)
const enforcedTheme = loadState('theming', 'enforceTheme', '')

const loading = ref(false)

/**
 * Enable or disable a theme
 *
 * @param theme - The theme toggled
 * @param state - The new enabled state
 */
async function toggleTheme(theme: ITheme, state: boolean) {
	if (theme.id === enforcedTheme) {
		return
	}

	if (loading.value) {
		return
	}

	if (theme.enabled === state) {
		return
	}

	try {
		loading.value = true
		if (state === false) {
			await axios.delete(generateOcsUrl('apps/theming/api/v1/theme/{themeId}', { themeId: theme.id }))
			if (!props.multiple && props.default) {
				// If the theme was disabled, we need to enable the default theme
				const defaultTheme = props.themes.find((t) => t.id === props.default!.id)
				if (defaultTheme && !defaultTheme.enabled) {
					await axios.put(generateOcsUrl('apps/theming/api/v1/theme/{themeId}/enable', { themeId: defaultTheme.id }))
					defaultTheme.enabled = true
				}
			}
		} else {
			await axios.put(generateOcsUrl('apps/theming/api/v1/theme/{themeId}/enable', { themeId: theme.id }))
			if (!props.multiple) {
				const otherTheme = props.themes.find((t) => t.id !== theme.id && t.enabled)
				if (otherTheme) {
					await axios.delete(generateOcsUrl('apps/theming/api/v1/theme/{themeId}', { themeId: otherTheme.id }))
					otherTheme.enabled = false
				}
			}
		}
		theme.enabled = state
		emit('updated')
	} catch (error) {
		let message = ''
		if (isAxiosError(error) && error.response?.data.ocs?.meta?.message) {
			message = `${error.response.data.ocs.meta.message}. ${message}`
		}
		showError(t('theming', 'Failed to update theme.') + message)

		logger.error('Failed to update theme', { error })
	} finally {
		loading.value = false
	}
}
</script>

<template>
	<ul :aria-label="label" class="theme-list">
		<ThemeListItem
			v-for="theme in themes"
			:key="theme.id"
			:modelValue="theme.enabled"
			:enforced="theme.id === enforcedTheme"
			:loading
			:theme
			:type="multiple ? 'checkbox' : ($props.default ? 'radio' : 'switch')"
			:name
			@update:modelValue="toggleTheme(theme, $event)" />
	</ul>
</template>

<style scoped>
.theme-list {
	--gap: 30px;
	display: grid;
	margin-top: var(--gap);
	column-gap: var(--gap);
	row-gap: var(--gap);
}

@media (max-width: 1440px) {
	.theme-list {
		display: flex;
		flex-direction: column;
	}
}
</style>
