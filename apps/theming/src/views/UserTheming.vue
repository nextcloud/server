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
	</NcSettingsSection>

	<NcNoteCard v-if="isUserThemingDisabled" type="info">
		{{ t('theming', 'Customization has been disabled by your administrator') }}
	</NcNoteCard>

	<template v-else>
		<UserSectionPrimaryColor ref="primaryColor" @refreshStyles="refreshGlobalStyles" />
		<UserSectionBackground @refreshStyles="refreshGlobalStyles" />
	</template>

	<UserSectionCompatibility />
	<UserSectionAppMenu />
</template>

<script setup lang="ts">
import type { ITheme } from '../components/ThemeListItem.vue'

import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { computed, nextTick, onBeforeMount, ref, useTemplateRef } from 'vue'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import ThemeList from '../components/ThemeList.vue'
import UserSectionAppMenu from '../components/UserSectionAppMenu.vue'
import UserSectionBackground from '../components/UserSectionBackground.vue'
import UserSectionCompatibility from '../components/UserSectionCompatibility.vue'
import UserSectionPrimaryColor from '../components/UserSectionPrimaryColor.vue'
import { refreshStyles } from '../utils/refreshStyles.js'

const isUserThemingDisabled = loadState('theming', 'isUserThemingDisabled')

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
