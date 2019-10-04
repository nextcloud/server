<template>
	<div id="accessibility" class="section">
		<h2>{{ t('accessibility', 'Accessibility') }}</h2>
		<p v-html="description" />
		<p v-html="descriptionDetail" />

		<div class="preview-list">
			<ItemPreview :key="highcontrast.id"
				:preview="highcontrast"
				:selected="selected.highcontrast"
				@select="selectHighContrast" />
			<ItemPreview v-for="preview in themes"
				:key="preview.id"
				:preview="preview"
				:selected="selected.theme"
				@select="selectTheme" />
			<ItemPreview v-for="preview in fonts"
				:key="preview.id"
				:preview="preview"
				:selected="selected.font"
				@select="selectFont" />
		</div>
	</div>
</template>

<script>
import ItemPreview from './components/ItemPreview'
import axios from '@nextcloud/axios'

export default {
	name: 'Accessibility',
	components: { ItemPreview },
	data() {
		return {
			serverData: []
		}
	},
	computed: {
		themes() {
			return this.serverData.themes
		},
		highcontrast() {
			return this.serverData.highcontrast
		},
		fonts() {
			return this.serverData.fonts
		},
		selected() {
			return {
				theme: this.serverData.selected.theme,
				highcontrast: this.serverData.selected.highcontrast,
				font: this.serverData.selected.font
			}
		},
		description() {
			// using the `t` replace method escape html, we have to do it manually :/
			return t(
				'accessibility',
				`Universal access is very important to us. We follow web standards
				and check to make everything usable also without mouse,
				and assistive software such as screenreaders.
				We aim to be compliant with the {guidelines} 2.1 on AA level,
				with the high contrast theme even on AAA level.`
			)
				.replace('{guidelines}', this.guidelinesLink)
		},
		guidelinesLink() {
			return `<a target="_blank" href="https://www.w3.org/WAI/standards-guidelines/wcag/" rel="noreferrer nofollow">${t('accessibility', 'Web Content Accessibility Guidelines')}</a>`
		},
		descriptionDetail() {
			return t(
				'accessibility',
				`If you find any issues, don’t hesitate to report them on {issuetracker}.
				And if you want to get involved, come join {designteam}!`
			)
				.replace('{issuetracker}', this.issuetrackerLink)
				.replace('{designteam}', this.designteamLink)
		},
		issuetrackerLink() {
			return `<a target="_blank" href="https://github.com/nextcloud/server/issues/" rel="noreferrer nofollow">${t('accessibility', 'our issue tracker')}</a>`
		},
		designteamLink() {
			return `<a target="_blank" href="https://nextcloud.com/design" rel="noreferrer nofollow">${t('accessibility', 'our design team')}</a>`
		}
	},
	beforeMount() {
		// importing server data into the app
		const serverDataElmt = document.getElementById('serverData')
		if (serverDataElmt !== null) {
			this.serverData = JSON.parse(
				document.getElementById('serverData').dataset.server
			)
		}
	},
	methods: {
		selectHighContrast(id) {
			this.selectItem('highcontrast', id)
		},
		selectTheme(id, idSelectedBefore) {
			this.selectItem('theme', id)
			document.body.classList.remove(idSelectedBefore)
			if (id) {
				document.body.classList.add(id)
			}
		},
		selectFont(id) {
			this.selectItem('font', id)
		},

		/**
		 * Commit a change and force reload css
		 * Fetching the file again will trigger the server update
		 *
		 * @param {string} type type of the change (font, highcontrast or theme)
		 * @param {string} id the data of the change
		 */
		selectItem(type, id) {
			axios.post(OC.linkToOCS('apps/accessibility/api/v1/config', 2) + type, { value: id })
				.then(response => {
					this.serverData.selected[type] = id

					// Remove old link
					let link = document.querySelector('link[rel=stylesheet][href*=accessibility][href*=user-]')
					if (!link) {
						// insert new css
						let link = document.createElement('link')
						link.rel = 'stylesheet'
						link.href = OC.generateUrl('/apps/accessibility/css/user-style.css') + '?v=' + new Date().getTime()
						document.head.appendChild(link)
					} else {
						// compare arrays
						if (
							JSON.stringify(Object.values(this.selected))
							=== JSON.stringify([false, false])
						) {
							// if nothing is selected, blindly remove the css
							link.remove()
						} else {
							// force update
							link.href
								= link.href.split('?')[0]
								+ '?v='
								+ new Date().getTime()
						}
					}
				})
				.catch(err => {
					console.error(err, err.response)
					OC.Notification.showTemporary(t('accessibility', err.response.data.ocs.meta.message + '. Unable to apply the setting.'))
				})
		}
	}
}
</script>
