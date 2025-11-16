<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<section>
		<NcSettingsSection
			:name="t('theming', 'Appearance and accessibility settings')"
			class="theming">
			<!-- eslint-disable-next-line vue/no-v-html -->
			<p v-html="description" />
			<!-- eslint-disable-next-line vue/no-v-html -->
			<p v-html="descriptionDetail" />

			<ThemeList
				v-model="selectedMainThemes"
				:label="t('theming', 'Themes')"
				:themes="mainThemes" />

			<ThemeList
				v-model="selectedSupplementaryThemes"
				:label="t('theming', 'Supplementary themes')"
				:themes="supplementaryThemes"
				multiple />

			<ThemeList
				v-model="selectedFontThemes"
				:label="t('theming', 'Fonts')"
				:themes="fontThemes" />

			<h3>{{ t('theming', 'Misc accessibility options') }}</h3>
			<NcCheckboxRadioSwitch
				type="checkbox"
				:checked="enableBlurFilter === 'yes'"
				:indeterminate="enableBlurFilter === ''"
				@update:checked="changeEnableBlurFilter">
				{{ t('theming', 'Enable blur background filter (may increase GPU load)') }}
			</NcCheckboxRadioSwitch>
		</NcSettingsSection>

		<NcSettingsSection
			:name="t('theming', 'Primary color')"
			:description="isUserThemingDisabled
				? t('theming', 'Customization has been disabled by your administrator')
				: t('theming', 'Set a primary color to highlight important elements. The color used for elements such as primary buttons might differ a bit as it gets adjusted to fulfill accessibility requirements.')">
			<UserPrimaryColor
				v-if="!isUserThemingDisabled"
				ref="primaryColor"
				@refresh-styles="refreshGlobalStyles" />
		</NcSettingsSection>

		<NcSettingsSection
			class="background"
			:name="t('theming', 'Background and color')"
			:description="isUserThemingDisabled
				? t('theming', 'Customization has been disabled by your administrator')
				: t('theming', 'The background can be set to an image from the default set, a custom uploaded image, or a plain color.')">
			<BackgroundSettings
				v-if="!isUserThemingDisabled"
				class="background__grid"
				@update:background="refreshGlobalStyles" />
		</NcSettingsSection>

		<NcSettingsSection
			:name="t('theming', 'Keyboard shortcuts')"
			:description="t('theming', 'In some cases keyboard shortcuts can interfere with accessibility tools. In order to allow focusing on your tool correctly you can disable all keyboard shortcuts here. This will also disable all available shortcuts in apps.')">
			<NcCheckboxRadioSwitch
				class="theming__preview-toggle"
				:checked.sync="shortcutsDisabled"
				type="switch"
				@change="changeShortcutsDisabled">
				{{ t('theming', 'Disable all keyboard shortcuts') }}
			</NcCheckboxRadioSwitch>
		</NcSettingsSection>

		<UserAppMenuSection />
	</section>
</template>

<script>
import axios from '@nextcloud/axios'
import { loadState } from '@nextcloud/initial-state'
import { generateOcsUrl } from '@nextcloud/router'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import BackgroundSettings from './components/BackgroundSettings.vue'
import ThemeList from './components/ThemeList.vue'
import UserAppMenuSection from './components/UserAppMenuSection.vue'
import UserPrimaryColor from './components/UserPrimaryColor.vue'
import { refreshStyles } from './helpers/refreshStyles.js'
import { logger } from './logger.js'

const availableThemes = loadState('theming', 'themes', [])
const shortcutsDisabled = loadState('theming', 'shortcutsDisabled', false)
const enableBlurFilter = loadState('theming', 'enableBlurFilter', '')

const isUserThemingDisabled = loadState('theming', 'isUserThemingDisabled')

export default {
	name: 'UserTheming',

	components: {
		ThemeList,
		NcCheckboxRadioSwitch,
		NcSettingsSection,
		BackgroundSettings,
		UserAppMenuSection,
		UserPrimaryColor,
	},

	data() {
		if (availableThemes.every(({ type, enabled }) => type !== 1 || !enabled)) {
			availableThemes.find(({ id, type }) => id === 'default' && type === 1).enabled = true
		}

		return {
			availableThemes,

			// Admin defined configs
			shortcutsDisabled,
			isUserThemingDisabled,
			enableBlurFilter,
		}
	},

	computed: {
		mainThemes() {
			return this.availableThemes.filter((theme) => theme.type === 1)
		},

		fontThemes() {
			return this.availableThemes.filter((theme) => theme.type === 2)
		},

		supplementaryThemes() {
			return this.availableThemes.filter((theme) => theme.type === 3)
		},

		selectedMainThemes: {
			get() {
				return this.mainThemes.filter(({ enabled }) => enabled)
			},

			set(themes) {
				logger.debug('SETTING main', { themes })
				this.updateThemes(this.mainThemes, themes)
			},
		},

		selectedFontThemes: {
			get() {
				return this.fontThemes.filter(({ enabled }) => enabled)
			},

			set(themes) {
				this.updateThemes(this.fontThemes, themes)
			},
		},

		selectedSupplementaryThemes: {
			get() {
				return this.supplementaryThemes.filter(({ enabled }) => enabled)
			},

			set(themes) {
				this.updateThemes(this.supplementaryThemes, themes)
			},
		},

		description() {
			return t(
				'theming',
				'Universal access is very important to us. We follow web standards and check to make everything usable also without mouse, and assistive software such as screenreaders. We aim to be compliant with the {linkstart}Web Content Accessibility Guidelines{linkend} 2.1 on AA level, with the high contrast theme even on AAA level.',
				{
					linkstart: '<a target="_blank" href="https://www.w3.org/WAI/standards-guidelines/wcag/" rel="noreferrer nofollow">',
					linkend: '</a>',
				},
				{
					escape: false,
				},
			)
		},

		descriptionDetail() {
			return t(
				'theming',
				'If you find any issues, do not hesitate to report them on {issuetracker}our issue tracker{linkend}. And if you want to get involved, come join {designteam}our design team{linkend}!',
				{
					issuetracker: '<a target="_blank" href="https://github.com/nextcloud/server/issues/" rel="noreferrer nofollow">',
					designteam: '<a target="_blank" href="https://nextcloud.com/design" rel="noreferrer nofollow">',
					linkend: '</a>',
				},
				{
					escape: false,
				},
			)
		},
	},

	watch: {
		shortcutsDisabled(newState) {
			this.changeShortcutsDisabled(newState)
		},
	},

	methods: {
		t,

		// Refresh server-side generated theming CSS
		async refreshGlobalStyles() {
			await refreshStyles()
			this.$nextTick(() => this.$refs.primaryColor.reload())
		},

		updateThemes(allThemes, selectedThemes) {
			for (const theme of allThemes) {
				theme.enabled = selectedThemes.includes(theme)
			}
			this.updateBodyAttributes()
		},

		async changeShortcutsDisabled(newState) {
			if (newState) {
				await axios({
					url: generateOcsUrl('apps/provisioning_api/api/v1/config/users/{appId}/{configKey}', {
						appId: 'theming',
						configKey: 'shortcuts_disabled',
					}),
					data: {
						configValue: 'yes',
					},
					method: 'POST',
				})
			} else {
				await axios({
					url: generateOcsUrl('apps/provisioning_api/api/v1/config/users/{appId}/{configKey}', {
						appId: 'theming',
						configKey: 'shortcuts_disabled',
					}),
					method: 'DELETE',
				})
			}
		},

		async changeEnableBlurFilter() {
			this.enableBlurFilter = this.enableBlurFilter === 'no' ? 'yes' : 'no'
			await axios({
				url: generateOcsUrl('apps/provisioning_api/api/v1/config/users/{appId}/{configKey}', {
					appId: 'theming',
					configKey: 'force_enable_blur_filter',
				}),
				data: {
					configValue: this.enableBlurFilter,
				},
				method: 'POST',
			})
			// Refresh the styles
			this.$emit('update:background')
		},

		updateBodyAttributes() {
			const enabledThemesIDs = [
				...this.selectedMainThemes.map((theme) => theme.id),
				...this.selectedSupplementaryThemes.map((theme) => theme.id),
				...this.selectedFontThemes.map((theme) => theme.id),
			]

			this.mainThemes.forEach((theme) => {
				document.body.toggleAttribute(`data-theme-${theme.id}`, theme.enabled)
			})
			this.supplementaryThemes.forEach((theme) => {
				document.body.toggleAttribute(`data-theme-${theme.id}`, theme.enabled)
			})
			this.fontThemes.forEach((font) => {
				document.body.toggleAttribute(`data-theme-${font.id}`, font.enabled)
			})

			document.body.setAttribute('data-themes', enabledThemesIDs.join(','))
		},
	},
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
