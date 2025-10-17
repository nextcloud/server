<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import axios, { isAxiosError } from '@nextcloud/axios'
import { showError } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { generateOcsUrl } from '@nextcloud/router'
import ThemeListItem from './ThemeListItem.vue'
import { logger } from '../logger.ts'

interface ITheme {
	id: string
	enabled: boolean
}

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
	 * The list of available themes
	 */
	themes: ITheme[]

	/**
	 * The selected themes
	 */
	modelValue: ITheme[]
}>()

const emit = defineEmits<{
	(e: 'update:model-value', v: ITheme[])
}>()

const name = 'themes-' + Math.random().toString(16).slice(6)
const enforcedTheme = loadState('theming', 'enforceTheme', '')

/**
 * @param theme - The theme to check if selected
 */
function isSelected(theme: ITheme) {
	return props.modelValue.includes(theme)
}

/**
 * @param theme - The theme to toggle
 */
async function toggleSelected(theme: ITheme) {
	logger.debug('Toggle theme ' + theme.id, { theme })
	try {
		if (isSelected(theme)) {
			await axios.delete(generateOcsUrl('apps/theming/api/v1/theme/{themeId}', { themeId: theme.id }))
			emit('update:model-value', props.modelValue.filter(({ id }) => id !== theme.id))
		} else {
			await axios.put(generateOcsUrl('apps/theming/api/v1/theme/{themeId}/enable', { themeId: theme.id }))
			if (props.multiple) {
				emit('update:model-value', [...props.modelValue, theme])
			} else {
				emit('update:model-value', [theme])
			}
		}
	} catch (error) {
		logger.error('theming: Unable to apply setting.', { error })
		let message = t('theming', 'Unable to apply the setting.')
		if (isAxiosError(error) && error.response?.data.ocs?.meta?.message) {
			message = `${error.response.data.ocs.meta.message}. ${message}`
		}
		showError(message)
	}
}
</script>

<script lang="ts">
// todo: remove with Vue 3
export default {
	model: {
		event: 'update:model-value',
		prop: 'model-value',
	},
}
</script>

<template>
	<ul :aria-label="label" class="theme-list">
		<ThemeListItem
			v-for="theme in themes"
			:key="theme.id"
			:enforced="theme.id === enforcedTheme"
			:name="name"
			:theme="theme"
			:is-switch="themes.length === 1 || multiple"
			@selected="toggleSelected(theme)" />
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
