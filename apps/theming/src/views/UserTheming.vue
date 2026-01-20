<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcSettingsSection
		:name="t('theming', 'Appearance and accessibility settings')"
		class="theming">
		<!-- eslint-disable-next-line vue/no-v-html -->
		<p v-html="description" />
		<!-- eslint-disable-next-line vue/no-v-html -->
		<p v-html="descriptionDetail" />

		<div class="theming__preview-list">
			<ThemePreviewItem
				v-for="theme in themes"
				:key="theme.id"
				:enforced="theme.id === enforceTheme"
				:selected="selectedTheme.id === theme.id"
				:theme="theme"
				:unique="themes.length === 1"
				type="theme"
				@update:selected="changeTheme(theme.id, $event)" />
		</div>

		<div class="theming__preview-list">
			<ThemePreviewItem
				v-for="theme in fonts"
				:key="theme.id"
				:selected="theme.enabled"
				:theme="theme"
				:unique="fonts.length === 1"
				type="font"
				@update:selected="changeFont(theme.id, $event)" />
		</div>

		<h3>{{ t('theming', 'Misc accessibility options') }}</h3>
		<NcCheckboxRadioSwitch
			type="checkbox"
			:model-value="enableBlurFilter === 'yes'"
			:indeterminate="enableBlurFilter === ''"
			@update:model-value="changeEnableBlurFilter">
			{{ t('theming', 'Enable blur background filter (may increase GPU load)') }}
		</NcCheckboxRadioSwitch>
	</NcSettingsSection>

	<NcNoteCard v-if="isUserThemingDisabled" type="info">
		{{ t('theming', 'Customization has been disabled by your administrator') }}
	</NcNoteCard>

	<template v-else>
		<UserSectionPrimaryColor ref="primaryColor" @refresh-styles="refreshGlobalStyles" />
		<UserSectionBackground @refresh-styles="refreshGlobalStyles" />
	</template>

	<UserSectionHotkeys />
	<UserSectionAppMenu />
</template>

<script setup lang="ts">
import type { ITheme } from '../components/ThemePreviewItem.vue'

import axios, { isAxiosError } from '@nextcloud/axios'
import { showError } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { generateOcsUrl } from '@nextcloud/router'
import { computed, nextTick, ref, useTemplateRef } from 'vue'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import ThemePreviewItem from '../components/ThemePreviewItem.vue'
import UserSectionAppMenu from '../components/UserSectionAppMenu.vue'
import UserSectionBackground from '../components/UserSectionBackground.vue'
import UserSectionHotkeys from '../components/UserSectionHotkeys.vue'
import UserSectionPrimaryColor from '../components/UserSectionPrimaryColor.vue'
import { logger } from '../utils/logger.ts'
import { refreshStyles } from '../utils/refreshStyles.js'

const enforceTheme = loadState('theming', 'enforceTheme', '')
const isUserThemingDisabled = loadState('theming', 'isUserThemingDisabled')

const enableBlurFilter = ref(loadState('theming', 'enableBlurFilter', ''))

const availableThemes = loadState<ITheme[]>('theming', 'themes', [])
const themes = ref(availableThemes.filter((theme) => theme.type === 1))
const fonts = ref(availableThemes.filter((theme) => theme.type === 2))
const selectedTheme = computed(() => themes.value.find((theme) => theme.enabled)
	|| themes.value[0]!)

const primaryColorSection = useTemplateRef('primaryColor')

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
 * Refresh server-side generated theming CSS
 */
async function refreshGlobalStyles() {
	await refreshStyles()
	nextTick(() => primaryColorSection.value?.reload())
}

/**
 * Handle theme change
 *
 * @param id - The theme ID to change
 * @param enabled - The theme state
 */
function changeTheme(id: string, enabled: boolean) {
	// Reset selected and select new one
	themes.value.forEach((theme) => {
		if (theme.id === id && enabled) {
			theme.enabled = true
			return
		}
		theme.enabled = false
	})

	updateBodyAttributes()
	selectItem(enabled, id)
}

/**
 * Handle font change
 *
 * @param id - The font ID to change
 * @param enabled - The font state
 */
function changeFont(id: string, enabled: boolean) {
	// Reset selected and select new one
	fonts.value.forEach((font) => {
		if (font.id === id && enabled) {
			font.enabled = true
			return
		}
		font.enabled = false
	})

	updateBodyAttributes()
	selectItem(enabled, id)
}

/**
 * Handle blur filter change
 */
async function changeEnableBlurFilter() {
	enableBlurFilter.value = enableBlurFilter.value === 'no' ? 'yes' : 'no'
	await axios({
		url: generateOcsUrl('apps/provisioning_api/api/v1/config/users/{appId}/{configKey}', {
			appId: 'theming',
			configKey: 'force_enable_blur_filter',
		}),
		data: {
			configValue: enableBlurFilter.value,
		},
		method: 'POST',
	})
	// Refresh the styles
	refreshStyles()
}

/**
 *
 */
function updateBodyAttributes() {
	const enabledThemesIDs = themes.value.filter((theme) => theme.enabled === true).map((theme) => theme.id)
	const enabledFontsIDs = fonts.value.filter((font) => font.enabled === true).map((font) => font.id)

	themes.value.forEach((theme) => {
		document.body.toggleAttribute(`data-theme-${theme.id}`, theme.enabled)
	})
	fonts.value.forEach((font) => {
		document.body.toggleAttribute(`data-theme-${font.id}`, font.enabled)
	})

	document.body.setAttribute('data-themes', [...enabledThemesIDs, ...enabledFontsIDs].join(','))
}

/**
 * Commit a change and force reload css
 * Fetching the file again will trigger the server update
 *
 * @param enabled - The theme state
 * @param themeId - The theme ID to change
 */
async function selectItem(enabled: boolean, themeId: string) {
	try {
		if (enabled) {
			await axios({
				url: generateOcsUrl('apps/theming/api/v1/theme/{themeId}/enable', { themeId }),
				method: 'PUT',
			})
		} else {
			await axios({
				url: generateOcsUrl('apps/theming/api/v1/theme/{themeId}', { themeId }),
				method: 'DELETE',
			})
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

<style lang="scss" scoped>
.theming {
	// Limit width of settings sections for readability
	p {
		max-width: 800px;
	}

	// Proper highlight for links and focus feedback
	:deep(a) {
		font-weight: bold;

		&:hover,
		&:focus {
			text-decoration: underline;
		}
	}

	&__preview-list {
		--gap: 30px;
		display: grid;
		margin-top: var(--gap);
		column-gap: var(--gap);
		row-gap: var(--gap);
	}
}

.background {
	&__grid {
		margin-top: 30px;
	}
}

@media (max-width: 1440px) {
	.theming__preview-list {
		display: flex;
		flex-direction: column;
	}
}
</style>
