<template>
	<div id="accessibility" class="section">
		<h2>{{t('accessibility', 'Accessibility')}}</h2>
		<p v-html="description" />
		<p v-html="descriptionDetail" />

		<div class="preview-list">
			<preview v-for="preview in themes" :preview="preview"
				 :key="preview.id" :selected="selected.theme"
				 v-on:select="selectTheme"></preview>
			<preview v-for="preview in fonts" :preview="preview"
				 :key="preview.id" :selected="selected.font"
				 v-on:select="selectFont"></preview>
		</div>
	</div>
</template>

<script>
import preview from './components/itemPreview';
import axios from 'nextcloud-axios';

export default {
	name: 'Accessibility',
	components: { preview },
	beforeMount() {
		// importing server data into the app
		const serverDataElmt = document.getElementById('serverData');
		if (serverDataElmt !== null) {
			this.serverData = JSON.parse(
				document.getElementById('serverData').dataset.server
			);
		}
	},
	data() {
		return {
			serverData: []
		};
	},
	computed: {
		themes() {
			return this.serverData.themes;
		},
		fonts() {
			return this.serverData.fonts;
		},
		selected() {
			return {
				theme: this.serverData.theme,
				font: this.serverData.font
			};
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
	methods: {
		selectTheme(id) {
			this.selectItem('theme', id);
		},
		selectFont(id) {
			this.selectItem('font', id);
		},

		/**
		 * Commit a change and force reload css
		 * Fetching the file again will trigger the server update
		 *
		 * @param {string} type type of the change (font or theme)
		 * @param {string} id the data of the change
		 */
		selectItem(type, id) {
			axios.post(
					OC.linkToOCS('apps/accessibility/api/v1/config', 2) + type,
					{ value: id }
				)
				.then(response => {
					this.serverData[type] = id;

					// Remove old link
					let link = document.querySelector('link[rel=stylesheet][href*=accessibility][href*=user-]');
					if (!link) {
						// insert new css
						let link = document.createElement('link');
						link.rel = 'stylesheet';
						link.href = OC.generateUrl('/apps/accessibility/css/user-style.css') + '?v=' + new Date().getTime();
						document.head.appendChild(link);
					} else {
						// compare arrays
						if (
							JSON.stringify(Object.values(this.selected)) ===
							JSON.stringify([false, false])
						) {
							// if nothing is selected, blindly remove the css
							link.remove();
						} else {
							// force update
							link.href =
								link.href.split('?')[0] +
								'?v=' +
								new Date().getTime();
						}
					}
				})
				.catch(err => {
					console.log(err, err.response);
					OC.Notification.showTemporary(t('accessibility', err.response.data.ocs.meta.message + '. Unable to apply the setting.'));
				});
		}
	}
};
</script>
