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
		<a class="background filepicker"
			tabindex="0"
			@click="pickFile"
			@keyup.enter="pickFile"
			@keyup.space="pickFile">
			{{ t('dashboard', 'Pick from files') }}
		</a>
		<a class="background default"
			tabindex="0"
			:class="{ 'icon-loading': loading === 'default' }"
			@click="setDefault"
			@keyup.enter="setDefault"
			@keyup.space="setDefault">
			{{ t('dashboard', 'Default images') }}
		</a>
		<a class="background color"
			tabindex="0"
			@click="pickColor"
			@keyup.enter="pickColor"
			@keyup.space="pickColor">
			{{ t('dashboard', 'Plain background') }}
		</a>
		<a v-for="background in shippedBackgrounds"
			:key="background.name"
			v-tooltip="background.details.attribution"
			tabindex="0"
			class="background"
			:class="{ 'icon-loading': loading === background.name }"
			:style="{ 'background-image': 'url(' + background.url + ')' }"
			@click="setShipped(background.name)"
			@keyup.enter="setShipped(background.name)"
			@keyup.space="setShipped(background.name)" />
	</div>
</template>

<script>
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { loadState } from '@nextcloud/initial-state'
import getBackgroundUrl from './../helpers/getBackgroundUrl'
import prefixWithBaseUrl from './../helpers/prefixWithBaseUrl'
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
			return Object.keys(shippedBackgroundList).map((item) => {
				return {
					name: item,
					url: prefixWithBaseUrl(item),
					details: shippedBackgroundList[item],
				}
			})
		},
	},
	methods: {
		async update(data) {
			const background = data.type === 'custom' || data.type === 'default' ? data.type : data.value
			this.backgroundImage = getBackgroundUrl(background, data.version)
			if (data.type === 'color') {
				this.$emit('updateBackground', data)
				this.loading = false
				return
			}
			const image = new Image()
			image.onload = () => {
				this.$emit('updateBackground', data)
				this.loading = false
			}
			image.src = this.backgroundImage
		},
		async setDefault() {
			this.loading = 'default'
			const result = await axios.post(generateUrl('/apps/dashboard/background/default'))
			this.update(result.data)
		},
		async setShipped(shipped) {
			this.loading = shipped
			const result = await axios.post(generateUrl('/apps/dashboard/background/shipped'), { value: shipped })
			this.update(result.data)
		},
		async setFile(path) {
			this.loading = 'custom'
			const result = await axios.post(generateUrl('/apps/dashboard/background/custom'), { value: path })
			this.update(result.data)
		},
		async pickColor() {
			this.loading = 'color'
			const color = OCA && OCA.Theming ? OCA.Theming.color : '#0082c9'
			const result = await axios.post(generateUrl('/apps/dashboard/background/color'), { value: color })
			this.update(result.data)
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
		justify-content: center;

		.background {
			width: 176px;
            height: 96px;
            margin: 8px;
            background-size: cover;
            background-position: center center;
			text-align: center;
            border-radius: var(--border-radius-large);
			border-radius: var(--border-radius-large);
			border: 2px solid var(--color-main-background);
			overflow: hidden;

			&.current {
				background-image: var(--color-background-dark);
			}

			&.filepicker, &.default, &.color {
				border-color: var(--color-border);
				line-height: 96px;
			}

			&.color {
				background-color: var(--color-primary);
				color: var(--color-primary-text);
			}

			&:hover,
            &:focus {
                border: 2px solid var(--color-primary);
			}
		}
	}

</style>
