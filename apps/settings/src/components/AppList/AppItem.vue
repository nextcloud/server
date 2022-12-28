<!--
  - @copyright Copyright (c) 2018 Julius Härtl <jus@bitgrid.net>
  -
  - @author Julius Härtl <jus@bitgrid.net>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program. If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template>
	<div class="section" :class="{ selected: isSelected }" @click="showAppDetails">
		<div class="app-image app-image-icon" @click="showAppDetails">
			<div v-if="(listView && !app.preview) || (!listView && !screenshotLoaded)" class="icon-settings-dark" />

			<svg v-else-if="listView && app.preview"
				width="32"
				height="32"
				viewBox="0 0 32 32">
				<image x="0"
					y="0"
					width="32"
					height="32"
					preserveAspectRatio="xMinYMin meet"
					:xlink:href="app.preview"
					class="app-icon" />
			</svg>

			<img v-if="!listView && app.screenshot && screenshotLoaded" :src="app.screenshot" width="100%">
		</div>
		<div class="app-name" @click="showAppDetails">
			{{ app.name }}
		</div>
		<div v-if="!listView" class="app-summary">
			{{ app.summary }}
		</div>
		<div v-if="listView" class="app-version">
			<span v-if="app.version">{{ app.version }}</span>
			<span v-else-if="app.appstoreData.releases[0].version">{{ app.appstoreData.releases[0].version }}</span>
		</div>

		<div class="app-level">
			<span v-if="app.level === 300"
				:title="t('settings', 'This app is supported via your current Nextcloud subscription.')"
				:aria-label="t('settings', 'This app is supported via your current Nextcloud subscription.')"
				class="supported icon-checkmark-color">
				{{ t('settings', 'Supported') }}</span>
			<span v-if="app.level === 200"
				:title="t('settings', 'Featured apps are developed by and within the community. They offer central functionality and are ready for production use.')"
				:aria-label="t('settings', 'Featured apps are developed by and within the community. They offer central functionality and are ready for production use.')"
				class="official icon-checkmark">
				{{ t('settings', 'Featured') }}</span>
			<AppScore v-if="hasRating && !listView" :score="app.score" />
		</div>

		<div class="actions">
			<div v-if="app.error" class="warning">
				{{ app.error }}
			</div>
			<div v-if="isLoading" class="icon icon-loading-small" />
			<NcButton v-if="app.update"
				type="primary"
				:disabled="installing || isLoading"
				@click.stop="update(app.id)">
				{{ t('settings', 'Update to {update}', {update:app.update}) }}
			</NcButton>
			<NcButton v-if="app.canUnInstall"
				class="uninstall"
				type="tertiary"
				:disabled="installing || isLoading"
				@click.stop="remove(app.id)">
				{{ t('settings', 'Remove') }}
			</NcButton>
			<NcButton v-if="app.active"
				:disabled="installing || isLoading"
				@click.stop="disable(app.id)">
				{{ t('settings','Disable') }}
			</NcButton>
			<NcButton v-if="!app.active && (app.canInstall || app.isCompatible)"
				:title="enableButtonTooltip"
				:aria-label="enableButtonTooltip"
				type="primary"
				:disabled="!app.canInstall || installing || isLoading"
				@click.stop="enable(app.id)">
				{{ enableButtonText }}
			</NcButton>
			<NcButton v-else-if="!app.active"
				:title="forceEnableButtonTooltip"
				:aria-label="forceEnableButtonTooltip"
				type="secondary"
				:disabled="installing || isLoading"
				@click.stop="forceEnable(app.id)">
				{{ forceEnableButtonText }}
			</NcButton>
		</div>
	</div>
</template>

<script>
import AppScore from './AppScore.vue'
import AppManagement from '../../mixins/AppManagement.js'
import SvgFilterMixin from '../SvgFilterMixin.vue'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'

export default {
	name: 'AppItem',
	components: {
		AppScore,
		NcButton,
	},
	mixins: [AppManagement, SvgFilterMixin],
	props: {
		app: {},
		category: {},
		listView: {
			type: Boolean,
			default: true,
		},
	},
	data() {
		return {
			isSelected: false,
			scrolled: false,
			screenshotLoaded: false,
		}
	},
	computed: {
		hasRating() {
			return this.app.appstoreData && this.app.appstoreData.ratingNumOverall > 5
		},
	},
	watch: {
		'$route.params.id'(id) {
			this.isSelected = (this.app.id === id)
		},
	},
	mounted() {
		this.isSelected = (this.app.id === this.$route.params.id)
		if (this.app.releases && this.app.screenshot) {
			const image = new Image()
			image.onload = (e) => {
				this.screenshotLoaded = true
			}
			image.src = this.app.screenshot
		}
	},
	watchers: {

	},
	methods: {
		async showAppDetails(event) {
			if (event.currentTarget.tagName === 'INPUT' || event.currentTarget.tagName === 'A') {
				return
			}
			try {
				await this.$router.push({
					name: 'apps-details',
					params: { category: this.category, id: this.app.id },
				})
			} catch (e) {
				// we already view this app
			}
		},
		prefix(prefix, content) {
			return prefix + '_' + content
		},
	},
}
</script>

<style scoped>
	.app-icon {
		filter: var(--background-invert-if-bright);
	}
	.actions {
		display: flex !important;
		gap: 8px;
		flex-wrap: wrap;
		justify-content: end;
	}
</style>
