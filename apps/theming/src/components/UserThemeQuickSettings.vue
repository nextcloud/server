<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { ITheme } from './ThemeListItem.vue'

import axios, { isAxiosError } from '@nextcloud/axios'
import { showError } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { generateOcsUrl } from '@nextcloud/router'
import { computed, ref } from 'vue'
import NcFormGroup from '@nextcloud/vue/components/NcFormGroup'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import NcRadioGroup from '@nextcloud/vue/components/NcRadioGroup'
import NcRadioGroupButton from '@nextcloud/vue/components/NcRadioGroupButton'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import ThemeList from './ThemeList.vue'
import { logger } from '../utils/logger.ts'

const availableThemes = ref(loadState<ITheme[]>('theming', 'themes', []))
for (const theme of availableThemes.value) {
	theme.enabled = theme.enabled || false
}

const mainThemes = computed(() => availableThemes.value.filter((theme) => theme.type === 1))
const fontThemes = computed(() => availableThemes.value.filter((theme) => theme.type === 2))
const supplementaryThemes = computed(() => availableThemes.value.filter((theme) => theme.type === 3))

if (mainThemes.value.every((theme) => !theme.enabled)) {
	const defaultTheme = mainThemes.value.find((theme) => theme.id === 'default')
	if (defaultTheme) {
		defaultTheme.enabled = true
	}
}

const selectedMainThemeId = ref(mainThemes.value.find((theme) => theme.enabled)?.id ?? 'default')
const loadingTheme = ref(false)
const enforcedTheme = loadState('theming', 'enforceTheme', '')
const defaultMainTheme = computed(() => mainThemes.value.find((theme) => theme.id === 'default'))

const description = t(
	'theming',
	'Universal access is very important to us. We follow web standards and check to make everything usable also without mouse, and assistive software such as screenreaders. We aim to be compliant with the {linkstart}Web Content Accessibility Guidelines{linkend} 2.1 on AA level, with the high contrast theme even on AAA level.',
	{
		linkstart: '<a target="_blank" href="https://www.w3.org/WAI/standards-guidelines/wcag/" rel="noreferrer nofollow">',
		linkend: '</a>',
	},
	{ escape: false },
)

const descriptionDetail = t(
	'theming',
	'If you find any issues, do not hesitate to report them on {issuetracker}our issue tracker{linkend}. And if you want to get involved, come join {designteam}our design team{linkend}!',
	{
		issuetracker: '<a target="_blank" href="https://github.com/nextcloud/server/issues/" rel="noreferrer nofollow">',
		designteam: '<a target="_blank" href="https://nextcloud.com/design" rel="noreferrer nofollow">',
		linkend: '</a>',
	},
	{ escape: false },
)

/**
 * Enable or disable a theme.
 * When multiple is false, enabling one theme disables the other enabled theme.
 *
 * @param theme - The theme toggled
 * @param state - The new enabled state
 * @param themes - Themes in the same list
 * @param multiple - Allow several themes from this list to stay enabled
 * @param defaultTheme - Theme enabled when the last single-select theme is turned off
 */
async function toggleTheme(theme: ITheme, state: boolean, themes: ITheme[], multiple = false, defaultTheme?: ITheme) {
	if (enforcedTheme !== '' || loadingTheme.value || theme.enabled === state) {
		return
	}

	try {
		loadingTheme.value = true
		if (state === false) {
			await axios.delete(generateOcsUrl('apps/theming/api/v1/theme/{themeId}', { themeId: theme.id }))
			if (!multiple && defaultTheme) {
				// If the theme was disabled, we need to enable the default theme
				const fallbackTheme = themes.find((item) => item.id === defaultTheme.id)
				if (fallbackTheme && !fallbackTheme.enabled) {
					await axios.put(generateOcsUrl('apps/theming/api/v1/theme/{themeId}/enable', { themeId: fallbackTheme.id }))
					fallbackTheme.enabled = true
				}
			}
		} else {
			await axios.put(generateOcsUrl('apps/theming/api/v1/theme/{themeId}/enable', { themeId: theme.id }))
			if (!multiple) {
				const otherTheme = themes.find((item) => item.id !== theme.id && item.enabled)
				if (otherTheme) {
					await axios.delete(generateOcsUrl('apps/theming/api/v1/theme/{themeId}', { themeId: otherTheme.id }))
					otherTheme.enabled = false
				}
			}
		}
		theme.enabled = state
		updateBodyAttributes()
	} catch (error) {
		let message = ''
		if (isAxiosError(error) && error.response?.data.ocs?.meta?.message) {
			message = `${error.response.data.ocs.meta.message}. ${message}`
		}
		showError(t('theming', 'Failed to update theme.') + message)
		logger.error('Failed to update theme', { error })
	} finally {
		loadingTheme.value = false
	}
}

/**
 * Enable the selected main theme. Only one main theme can be active.
 *
 * @param themeId - Id of the main theme to enable
 */
async function selectMainTheme(themeId: string) {
	if (enforcedTheme !== '') {
		return
	}

	const theme = mainThemes.value.find((item) => item.id === themeId)
	if (!theme) {
		return
	}

	await toggleTheme(theme, true, mainThemes.value, false, defaultMainTheme.value)
	selectedMainThemeId.value = mainThemes.value.find((item) => item.enabled)?.id ?? selectedMainThemeId.value
}

/**
 * Update the body attributes to reflect the selected themes
 */
function updateBodyAttributes() {
	const enabledThemesIDs = [
		...mainThemes.value
			.filter((theme) => theme.enabled)
			.map((theme) => theme.id),
		...supplementaryThemes.value
			.filter((theme) => theme.enabled)
			.map((theme) => theme.id),
		...fontThemes.value
			.filter((font) => font.enabled)
			.map((font) => font.id),
	]

	mainThemes.value.forEach((theme) => {
		document.body.toggleAttribute(`data-theme-${theme.id}`, theme.enabled)
	})
	supplementaryThemes.value.forEach((theme) => {
		document.body.toggleAttribute(`data-theme-${theme.id}`, theme.enabled)
	})
	fontThemes.value.forEach((font) => {
		document.body.toggleAttribute(`data-theme-${font.id}`, font.enabled)
	})

	document.body.setAttribute('data-themes', enabledThemesIDs.join(','))
}
</script>

<template>
	<NcSettingsSection
		:name="t('theming', 'Appearance and accessibility settings')"
		class="theme-quick-settings">
		<!-- eslint-disable-next-line vue/no-v-html -->
		<p v-html="description" />
		<!-- eslint-disable-next-line vue/no-v-html -->
		<p v-html="descriptionDetail" />

		<NcRadioGroup
			:modelValue="selectedMainThemeId"
			:label="t('theming', 'Color schema')"
			class="theme-quick-settings_section"
			@update:modelValue="selectMainTheme">
			<NcRadioGroupButton
				v-for="theme in mainThemes"
				:key="theme.id"
				:label="theme.title"
				:value="theme.id"
				:loading="loadingTheme"
				:disabled="enforcedTheme !== ''" />
		</NcRadioGroup>
		<NcFormGroup
			:label="t('theming', 'Accessibility')"
			class="theme-quick-settings_section">
			<ThemeList
				:label="t('theming', 'Supplementary themes')"
				:themes="supplementaryThemes"
				:loading="loadingTheme"
				@toggle="(theme, state) => toggleTheme(theme, state, supplementaryThemes, true)" />
			<ThemeList
				:label="t('theming', 'Fonts')"
				:themes="fontThemes"
				:loading="loadingTheme"
				@toggle="(theme, state) => toggleTheme(theme, state, fontThemes)" />
		</NcFormGroup>
		<NcNoteCard
			v-if="enforcedTheme !== ''"
			type="warning"
			class="theme-quick-settings_section">
			{{ t('theming', 'Theme selection is enforced') }}
		</NcNoteCard>
	</NcSettingsSection>
</template>

<style lang="scss" scoped>
.theme-quick-settings {
	p {
		max-width: 800px;
	}

	:deep(a) {
		font-weight: bold;

		&:hover,
		&:focus {
			text-decoration: underline;
		}
	}

	&_section {
		margin-top: calc(5 * var(--default-grid-baseline));
	}
}
</style>
