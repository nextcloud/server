<template>
	<div id="accessibility">
		<div id="themes" class="section">
			<h2>{{t('accessibility', 'Themes')}}</h2>
			<div class="themes-list preview-list">
				<preview v-for="preview in themes" :preview="preview"
						 :key="preview.id" :selected="selected.theme"
						 v-on:select="selectTheme"></preview>
			</div>
		</div>
		<div id="fonts" class="section">
			<h2>{{t('accessibility', 'Fonts')}}</h2>
			<div class="fonts-list preview-list">
				<preview v-for="preview in fonts" :preview="preview"
						 :key="preview.id" :selected="selected.font"
						 v-on:select="selectFont"></preview>
			</div>
		</div>
	</div>
</template>

<script>
import preview from './components/itemPreview';

export default {
	name: 'app',
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
		 * Commit a change
		 *
		 * @param {string} type type of the change (font or theme)
		 * @param {string} id the data of the change
		 */
		selectItem(type, id) {
			this.serverData[type] = id;
			console.log(type, id);
		}
	}
};
</script>
