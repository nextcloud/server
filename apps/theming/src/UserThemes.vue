<!--
  - @copyright Copyright (c) 2020 Julius Härtl <jus@bitgrid.net>
  - @copyright Copyright (c) 2022 Greta Doci <gretadoci@gmail.com>
  -
  - @author Christopher Ng <chrng8@gmail.com>
  -
  - @license AGPL-3.0-or-later
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program. If not, see <http://www.gnu.org/licenses/>.
  -
-->

<template>
	<section>
		<NcSettingsSection :title="t('theming', 'Appearance and accessibility')"
			:limit-width="false"
			class="theming">
			<p v-html="description" />
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
		</NcSettingsSection>

		<NcSettingsSection :title="t('theming', 'Keyboard shortcuts')">
			<p>{{ t('theming', 'In some cases keyboard shortcuts can interfer with accessibility tools. In order to allow focusing on your tool correctly you can disable all keyboard shortcuts here. This will also disable all available shortcuts in apps.') }}</p>
			<NcCheckboxRadioSwitch class="theming__preview-toggle"
				:checked.sync="shortcutsDisabled"
				name="shortcuts_disabled"
				type="switch"
				@change="changeShortcutsDisabled">
				{{ t('theming', 'Disable all keyboard shortcuts') }}
			</NcCheckboxRadioSwitch>
		</NcSettingsSection>

		<NcSettingsSection :title="t('theming', 'Background')"
			class="background">
			<p>{{ t('theming', 'Set a custom background') }}</p>
			<BackgroundSettings class="background__grid"
				:background="background"
				:theming-default-background="themingDefaultBackground"
				@update:background="updateBackground" />
		</NcSettingsSection>
	</section>
</template>

<script>
import { generateOcsUrl } from '@nextcloud/router'
import { loadState } from '@nextcloud/initial-state'
import axios from '@nextcloud/axios'
import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch'
import NcSettingsSection from '@nextcloud/vue/dist/Components/NcSettingsSection'

import BackgroundSettings from './components/BackgroundSettings.vue'
import ItemPreview from './components/ItemPreview.vue'

const availableThemes = loadState('theming', 'themes', [])
const enforceTheme = loadState('theming', 'enforceTheme', '')
const shortcutsDisabled = loadState('theming', 'shortcutsDisabled', false)

const background = loadState('theming', 'background')
const backgroundVersion = loadState('theming', 'backgroundVersion')
const themingDefaultBackground = loadState('theming', 'themingDefaultBackground')
const shippedBackgroundList = loadState('theming', 'shippedBackgrounds')

console.debug('Available themes', availableThemes)

export default {
	name: 'UserThemes',
	components: {
		ItemPreview,
		NcCheckboxRadioSwitch,
		NcSettingsSection,
		BackgroundSettings,
	},

	data() {
		return {
			availableThemes,
			enforceTheme,
			shortcutsDisabled,
			background,
			backgroundVersion,
			themingDefaultBackground,
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
				'Universal access is very important to us. We follow web standards and check to make everything usable also without mouse, and assistive software such as screenreaders. We aim to be compliant with the {guidelines}Web Content Accessibility Guidelines{linkend} 2.1 on AA level, with the high contrast theme even on AAA level.'
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
				'If you find any issues, do not hesitate to report them on {issuetracker}our issue tracker{linkend}. And if you want to get involved, come join {designteam}our design team{linkend}!'
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

	mounted() {
		this.updateGlobalStyles()
	},

	watch: {
		shortcutsDisabled(newState) {
			this.changeShortcutsDisabled(newState)
		},
	},

	methods: {
		updateBackground(data) {
			this.background = (data.type === 'custom' || data.type === 'default') ? data.type : data.value
			this.backgroundVersion = data.version
			this.updateGlobalStyles()
			this.$emit('update:background')
		},
		updateGlobalStyles() {
			// Override primary-invert-if-bright and color-primary-text if background is set
			const isBackgroundBright = shippedBackgroundList[this.background]?.theming === 'dark'
			if (isBackgroundBright) {
				document.querySelector('#header').style.setProperty('--primary-invert-if-bright', 'invert(100%)')
				document.querySelector('#header').style.setProperty('--color-primary-text', '#000000')
				// document.body.removeAttribute('data-theme-dark')
				// document.body.setAttribute('data-theme-light', 'true')
			} else {
				document.querySelector('#header').style.setProperty('--primary-invert-if-bright', 'no')
				document.querySelector('#header').style.setProperty('--color-primary-text', '#ffffff')
				// document.body.removeAttribute('data-theme-light')
				// document.body.setAttribute('data-theme-dark', 'true')
			}
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
	&::v-deep a {
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
		grid-template-columns: 1fr 1fr;
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
