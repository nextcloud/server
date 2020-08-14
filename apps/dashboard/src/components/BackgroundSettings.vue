<!--
  - @copyright Copyright (c) 2020 Julius Härtl <jus@bitgrid.net>
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
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program. If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template>
	<div class="background-selector">
		<div class="background default"
			:class="{ 'icon-loading': loading === 'default' }"
			@click="setDefault()">
			<div class="background--preview">
				Default
			</div>
		</div>
		<div class="background" @click="pickFile">
			<a>
				{{ t('dashboard', 'Pick an image from your files') }}
			</a>
		</div>
		<div v-for="background in shippedBackgrounds"
			:key="background.name"
			class="background"
			:class="{ 'icon-loading': loading === background.name }"
			@click="setShipped(background.name)">
			<div class="background--preview" :style="{ 'background-image': 'url(' + background.url + ')' }" />
		</div>
	</div>
</template>

<script>
import axios from '@nextcloud/axios'
import { generateUrl, generateFilePath } from '@nextcloud/router'
import { loadState } from '@nextcloud/initial-state'
import isMobile from '../mixins/isMobile'

const prefixWithBaseUrl = (url) => generateFilePath('dashboard', '', 'img/') + url
const shippedBackgroundList = loadState('dashboard', 'shippedBackgrounds')

const getBackgroundUrl = (background, time = 0) => {
	if (background === 'default') {
		if (window.OCA.Accessibility.theme === 'dark') {
			return !isMobile ? prefixWithBaseUrl('flickr-148302424@N05-36591009215.jpg?v=1') : prefixWithBaseUrl('flickr-148302424@N05-36591009215-mobile.jpg?v=1')
		}
		return !isMobile ? prefixWithBaseUrl('flickr-paszczak000-8715851521.jpg?v=1') : prefixWithBaseUrl('flickr-paszczak000-8715851521-mobile.jpg?v=1')
	} else if (background === 'custom') {
		return generateUrl('/apps/dashboard/background') + '?v=' + time
	}
	return prefixWithBaseUrl(background)
}

export default {
	name: 'BackgroundSettings',
	data() {
		return {
			backgroundImage: generateUrl('/apps/dashboard/background') + '?v=' + Date.now(),
			loading: false,
		}
	},
	computed: {
		shippedBackgrounds() {
			return shippedBackgroundList.map((item) => {
				return {
					name: item,
					url: prefixWithBaseUrl(item),
				}
			})
		},
	},
	methods: {
		async update(state) {
			const date = Date.now()
			this.backgroundImage = getBackgroundUrl(state, date)
			const image = new Image()
			image.onload = () => {
				this.$emit('updateBackground', state)
				this.loading = false
			}
			image.src = this.backgroundImage
		},
		async setDefault() {
			console.debug('SetDefault')
			await axios.post(generateUrl('/apps/dashboard/background'))
			this.update('default')
		},
		async setShipped(shipped) {
			this.loading = shipped
			await axios.post(generateUrl('/apps/dashboard/background'), { shipped })
			this.update(shipped)
		},
		async setUrl(url) {
			this.loading = true
			await axios.post(generateUrl('/apps/dashboard/background'), { url })
			this.update('custom')
		},
		async setFile(path) {
			this.loading = true
			await axios.post(generateUrl('/apps/dashboard/background'), { path })
			this.update('custom')
		},
		pickFile() {
			window.OC.dialogs.filepicker(t('dashboard', 'Insert from {productName}', { productName: OC.theme.name }), (path, type) => {
				if (type === OC.dialogs.FILEPICKER_TYPE_CHOOSE) {
					this.setFile(path)
				}
			}, false, ['image/png', 'image/gif', 'image/jpeg', 'image/svg'], true, OC.dialogs.FILEPICKER_TYPE_CHOOSE)
		},
	},
}
</script>

<style scoped lang="scss">

	.background-selector {
		display: flex;
		flex-wrap: wrap;

		.background {
			width: 140px;
			padding: 15px;
			border-radius: var(--border-radius);
			text-align: center;

			&.current {
				background-image: var(--color-background-dark);
			}

			&--preview {
				width: 140px;
				height: 90px;
				background-size: cover;
				background-position: center center;
			}

			&:hover {
				background-color: var(--color-background-hover);
			}
		}
	}

</style>
