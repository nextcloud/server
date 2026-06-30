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

		<ThemeList
			:label="t('theming', 'Themes')"
			:themes="mainThemes"
			:default="defaultTheme"
			@updated="updateBodyAttributes" />

		<ThemeList
			:label="t('theming', 'Supplementary themes')"
			:themes="supplementaryThemes"
			multiple
			@updated="updateBodyAttributes" />

		<ThemeList
			:label="t('theming', 'Fonts')"
			:themes="fontThemes"
			@updated="updateBodyAttributes" />

		<h3>{{ t('theming', 'Misc accessibility options') }}</h3>
		<NcCheckboxRadioSwitch
			type="checkbox"
			:modelValue="enableBlurFilter === 'yes'"
			:indeterminate="enableBlurFilter === ''"
			@update:modelValue="changeEnableBlurFilter">
			{{ t('theming', 'Enable blur background filter (may increase GPU load)') }}
		</NcCheckboxRadioSwitch>
	</NcSettingsSection>

	<NcNoteCard v-if="isUserThemingDisabled" type="info">
		{{ t('theming', 'Customization has been disabled by your administrator') }}
	</NcNoteCard>

	<template v-else>
		<UserSectionPrimaryColor ref="primaryColor" @refreshStyles="refreshGlobalStyles" />
		<UserSectionBackground @refreshStyles="refreshGlobalStyles" />
	</template>

	<UserSectionHotkeys />
	<UserSectionAppMenu />
</template>

<script setup lang="ts">
import type { ITheme } from '../components/ThemeListItem.vue'

import axios from '@nextcloud/axios'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { generateOcsUrl } from '@nextcloud/router'
import { computed, nextTick, onBeforeMount, ref, useTemplateRef } from 'vue'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import ThemeList from '../components/ThemeList.vue'
import UserSectionAppMenu from '../components/UserSectionAppMenu.vue'
import UserSectionBackground from '../components/UserSectionBackground.vue'
import UserSectionHotkeys from '../components/UserSectionHotkeys.vue'
import UserSectionPrimaryColor from '../components/UserSectionPrimaryColor.vue'
import { refreshStyles } from '../utils/refreshStyles.js'

const isUserThemingDisabled = loadState('theming', 'isUserThemingDisabled')

const enableBlurFilter = ref(loadState('theming', 'enableBlurFilter', ''))

const availableThemes = ref(loadState<ITheme[]>('theming', 'themes', []))
for (const theme of availableThemes.value) {
	theme.enabled = theme.enabled || false
}

const mainThemes = computed(() => availableThemes.value.filter((theme) => theme.type === 1))
const fontThemes = computed(() => availableThemes.value.filter((theme) => theme.type === 2))
const supplementaryThemes = computed(() => availableThemes.value.filter((theme) => theme.type === 3))
const defaultTheme = computed(() => mainThemes.value.find((theme) => theme.id === 'default'))
onBeforeMount(() => {
	if (availableThemes.value.every(({ type, enabled }) => type !== 1 || !enabled)) {
		if (defaultTheme.value) {
			defaultTheme.value.enabled = true
		}
	}
})

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
}

.background {
	&__grid {
		margin-top: 30px;
	}
}
</style>
