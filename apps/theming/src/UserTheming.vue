<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<section>
		<NcSettingsSection :name="t('theming', 'Appearance and accessibility settings')"
			class="theming">
			<!-- eslint-disable-next-line vue/no-v-html -->
			<p v-html="description" />
			<!-- eslint-disable-next-line vue/no-v-html -->
			<p v-html="descriptionDetail" />

			<div class="theming__preview-list">
				<ItemPreview v-for="theme in themes"
					:key="theme.id"
					:enforced="theme.id === enforceTheme"
					:selected="selectedTheme.id === theme.id"
					:theme="theme"
					:unique="themes.length === 1"
					type="theme"
					@change="changeTheme" />
			</div>

			<div class="theming__preview-list">
				<ItemPreview v-for="theme in fonts"
					:key="theme.id"
					:selected="theme.enabled"
					:theme="theme"
					:unique="fonts.length === 1"
					type="font"
					@change="changeFont" />
			</div>

			<h3>{{ t('theming', 'Misc accessibility options') }}</h3>
			<NcCheckboxRadioSwitch type="checkbox"
				:checked="enableBlurFilter === 'yes'"
				:indeterminate="enableBlurFilter === ''"
				@update:checked="changeEnableBlurFilter">
				{{ t('theming', 'Enable blur background filter (may increase GPU load)') }}
			</NcCheckboxRadioSwitch>
		</NcSettingsSection>

		<NcSettingsSection :name="t('theming', 'Primary color')"
			:description="isUserThemingDisabled
				? t('theming', 'Customization has been disabled by your administrator')
				: t('theming', 'Set a primary color to highlight important elements. The color used for elements such as primary buttons might differ a bit as it gets adjusted to fulfill accessibility requirements.')">
			<UserPrimaryColor v-if="!isUserThemingDisabled"
				ref="primaryColor"
				@refresh-styles="refreshGlobalStyles" />
		</NcSettingsSection>

		<NcSettingsSection class="background"
			:name="t('theming', 'Background and color')"
			:description="isUserThemingDisabled
				? t('theming', 'Customization has been disabled by your administrator')
				: t('theming', 'The background can be set to an image from the default set, a custom uploaded image, or a plain color.')">
			<BackgroundSettings v-if="!isUserThemingDisabled"
				class="background__grid"
				@update:background="refreshGlobalStyles" />
		</NcSettingsSection>

		<NcSettingsSection :name="t('theming', 'Keyboard shortcuts')"
			:description="t('theming', 'In some cases keyboard shortcuts can interfere with accessibility tools. In order to allow focusing on your tool correctly you can disable all keyboard shortcuts here. This will also disable all available shortcuts in apps.')">
			<NcCheckboxRadioSwitch class="theming__preview-toggle"
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
import { generateOcsUrl } from '@nextcloud/router'
import { loadState } from '@nextcloud/initial-state'
import { refreshStyles } from './helpers/refreshStyles'

import axios from '@nextcloud/axios'

import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'
import NcSettingsSection from '@nextcloud/vue/dist/Components/NcSettingsSection.js'

import BackgroundSettings from './components/BackgroundSettings.vue'
import ItemPreview from './components/ItemPreview.vue'
import UserAppMenuSection from './components/UserAppMenuSection.vue'
import UserPrimaryColor from './components/UserPrimaryColor.vue'

const availableThemes = loadState('theming', 'themes', [])
const enforceTheme = loadState('theming', 'enforceTheme', '')
const shortcutsDisabled = loadState('theming', 'shortcutsDisabled', false)
const enableBlurFilter = loadState('theming', 'enableBlurFilter', '')

const isUserThemingDisabled = loadState('theming', 'isUserThemingDisabled')

export default {
	name: 'UserTheming',

	components: {
		ItemPreview,
		NcCheckboxRadioSwitch,
		NcSettingsSection,
		BackgroundSettings,
		UserAppMenuSection,
		UserPrimaryColor,
	},

	data() {
		return {
			availableThemes,

			// Admin defined configs
			enforceTheme,
			shortcutsDisabled,
			isUserThemingDisabled,

			enableBlurFilter,
		}
	},

	computed: {
		themes() {
			return this.availableThemes.filter(theme => theme.type === 1)
		},

		fonts() {
			return this.availableThemes.filter(theme => theme.type === 2)
		},

		// Selected theme, fallback on first (default) if none
		selectedTheme() {
			return this.themes.find(theme => theme.enabled === true) || this.themes[0]
		},

		description() {
			// using the `t` replace method escape html, we have to do it manually :/
			return t(
				'theming',
				'Universal access is very important to us. We follow web standards and check to make everything usable also without mouse, and assistive software such as screenreaders. We aim to be compliant with the {guidelines}Web Content Accessibility Guidelines{linkend} 2.1 on AA level, with the high contrast theme even on AAA level.',
			)
				.replace('{guidelines}', this.guidelinesLink)
				.replace('{linkend}', '</a>')
		},

		guidelinesLink() {
			return '<a target="_blank" href="https://www.w3.org/WAI/standards-guidelines/wcag/" rel="noreferrer nofollow">'
		},

		descriptionDetail() {
			return t(
				'theming',
				'If you find any issues, do not hesitate to report them on {issuetracker}our issue tracker{linkend}. And if you want to get involved, come join {designteam}our design team{linkend}!',
			)
				.replace('{issuetracker}', this.issuetrackerLink)
				.replace('{designteam}', this.designteamLink)
				.replace(/\{linkend\}/g, '</a>')
		},

		issuetrackerLink() {
			return '<a target="_blank" href="https://github.com/nextcloud/server/issues/" rel="noreferrer nofollow">'
		},

		designteamLink() {
			return '<a target="_blank" href="https://nextcloud.com/design" rel="noreferrer nofollow">'
		},
	},

	watch: {
		shortcutsDisabled(newState) {
			this.changeShortcutsDisabled(newState)
		},
	},

	methods: {
		// Refresh server-side generated theming CSS
		async refreshGlobalStyles() {
			await refreshStyles()
			this.$nextTick(() => this.$refs.primaryColor.reload())
		},

		changeTheme({ enabled, id }) {
			// Reset selected and select new one
			this.themes.forEach(theme => {
				if (theme.id === id && enabled) {
					theme.enabled = true
					return
				}
				theme.enabled = false
			})

			this.updateBodyAttributes()
			this.selectItem(enabled, id)
		},

		changeFont({ enabled, id }) {
			// Reset selected and select new one
			this.fonts.forEach(font => {
				if (font.id === id && enabled) {
					font.enabled = true
					return
				}
				font.enabled = false
			})

			this.updateBodyAttributes()
			this.selectItem(enabled, id)
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
			const enabledThemesIDs = this.themes.filter(theme => theme.enabled === true).map(theme => theme.id)
			const enabledFontsIDs = this.fonts.filter(font => font.enabled === true).map(font => font.id)

			this.themes.forEach(theme => {
				document.body.toggleAttribute(`data-theme-${theme.id}`, theme.enabled)
			})
			this.fonts.forEach(font => {
				document.body.toggleAttribute(`data-theme-${font.id}`, font.enabled)
			})

			document.body.setAttribute('data-themes', [...enabledThemesIDs, ...enabledFontsIDs].join(','))
		},

		/**
		 * Commit a change and force reload css
		 * Fetching the file again will trigger the server update
		 *
		 * @param {boolean} enabled the theme state
		 * @param {string} themeId the theme ID to change
		 */
		async selectItem(enabled, themeId) {
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

			} catch (err) {
				console.error(err, err.response)
				OC.Notification.showTemporary(t('theming', err.response.data.ocs.meta.message + '. Unable to apply the setting.'))
			}
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
