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
		<div v-if="loading">Loading</div>
		<div v-for="background in shippedBackgrounds"
			:key="background"
			class="background"
			@click="setUrl(background)">
			<img :src="background">
		</div>
		<div class="background" @click="pickFile">
			<a>
				{{ t('dashboard', 'Pick an image from your files') }}
			</a>
		</div>
	</div>
</template>

<script>
import axios from '@nextcloud/axios'
import { generateUrl, generateFilePath } from '@nextcloud/router'
import { loadState } from '@nextcloud/initial-state'

const prefixWithBaseUrl = (url) => generateFilePath('dashboard', '', 'img/') + url
const shippedBackgroundList = loadState('dashboard', 'shippedBackgrounds')

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
				return prefixWithBaseUrl(item)
			})
		},
	},
	methods: {
		async update() {
			const date = Date.now()
			this.backgroundImage = generateUrl('/apps/dashboard/background') + '?v=' + date
			const image = new Image()
			image.onload = () => {
				this.$emit('updateBackground', date)
				this.loading = false
			}
			image.src = this.backgroundImage
		},
		setDefault() {
			console.debug('SetDefault')
			this.update()
		},
		async setUrl(url) {
			this.loading = true
			console.debug('SetUrl ' + url)
			await axios.post(generateUrl('/apps/dashboard/background'), { url })
			this.update()
		},
		async setFile(path) {
			this.loading = true
			console.debug('SetFile ' + path)
			await axios.post(generateUrl('/apps/dashboard/background'), { path })
			this.update()
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

			& img {
				width: 140px;
			}

			&:hover {
				background-color: var(--color-background-hover);
			}
		}
	}

</style>
