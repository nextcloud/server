<template>
	<SettingsSection class="theming" :title="t('themes', 'Appearance and accessibility')">
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
	</SettingsSection>
</template>

<script>
import { generateOcsUrl } from '@nextcloud/router'
import { loadState } from '@nextcloud/initial-state'
import axios from '@nextcloud/axios'
import SettingsSection from '@nextcloud/vue/dist/Components/SettingsSection'

import ItemPreview from './components/ItemPreview'

const availableThemes = loadState('theming', 'themes', [])
const enforceTheme = loadState('theming', 'enforceTheme', '')

console.debug('Available themes', availableThemes)

export default {
	name: 'UserThemes',
	components: {
		ItemPreview,
		SettingsSection,
	},

	data() {
		return {
			availableThemes,
			enforceTheme,
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
	methods: {
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

		updateBodyAttributes() {
			const enabledThemesIDs = this.themes.filter(theme => theme.enabled === true).map(theme => theme.id)
			this.themes.forEach(theme => {
				document.body.toggleAttribute(`data-theme-${theme.id}`, theme.enabled)
			})

			document.body.setAttribute('data-themes', enabledThemesIDs.join(','))
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

@media (max-width: 1440px) {
	.theming__preview-list {
		display: flex;
		flex-direction: column;
	}
}

</style>
