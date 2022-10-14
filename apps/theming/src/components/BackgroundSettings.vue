<!--
  - @copyright Copyright (c) 2020 Julius Härtl <jus@bitgrid.net>
  - @copyright Copyright (c) 2022 Greta Doci <gretadoci@gmail.com>
  -
  - @author Julius Härtl <jus@bitgrid.net>
  - @author Greta Doci <gretadoci@gmail.com>
  - @author Christopher Ng <chrng8@gmail.com>
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
		<!-- Custom background -->
		<button class="background filepicker"
			:class="{ active: background === 'custom' }"
			tabindex="0"
			@click="pickFile">
			{{ t('theming', 'Pick from Files') }}
		</button>

		<!-- Default background -->
		<button class="background default"
			tabindex="0"
			:class="{ 'icon-loading': loading === 'default', active: background === 'default' }"
			@click="setDefault">
			{{ t('theming', 'Default image') }}
		</button>

		<!-- Custom color picker -->
		<NcColorPicker v-model="Theming.color" @input="debouncePickColor">
			<button class="background color"
				:class="{ active: background === Theming.color}"
				tabindex="0"
				:data-color="Theming.color"
				:data-color-bright="invertTextColor(Theming.color)"
				:style="{ backgroundColor: Theming.color, color: invertTextColor(Theming.color) ? '#000000' : '#ffffff'}">
				{{ t('theming', 'Custom color') }}
			</button>
		</NcColorPicker>

		<!-- Default admin primary color -->
		<button class="background color"
			:class="{ active: background === Theming.defaultColor }"
			tabindex="0"
			:data-color="Theming.defaultColor"
			:data-color-bright="invertTextColor(Theming.defaultColor)"
			:style="{ color: invertTextColor(Theming.defaultColor) ? '#000000' : '#ffffff'}"
			@click="debouncePickColor">
			{{ t('theming', 'Plain background') }}
		</button>

		<!-- Background set selection -->
		<button v-for="shippedBackground in shippedBackgrounds"
			:key="shippedBackground.name"
			v-tooltip="shippedBackground.details.attribution"
			:class="{ 'icon-loading': loading === shippedBackground.name, active: background === shippedBackground.name }"
			tabindex="0"
			class="background"
			:data-color-bright="shippedBackground.details.theming === 'dark'"
			:style="{ 'background-image': 'url(' + shippedBackground.preview + ')' }"
			@click="setShipped(shippedBackground.name)" />
	</div>
</template>

<script>
import { generateUrl } from '@nextcloud/router'
import { getBackgroundUrl } from '../helpers/getBackgroundUrl.js'
import { loadState } from '@nextcloud/initial-state'
import { prefixWithBaseUrl } from '../helpers/prefixWithBaseUrl.js'
import axios from '@nextcloud/axios'
import debounce from 'debounce'
import NcColorPicker from '@nextcloud/vue/dist/Components/NcColorPicker'
import Tooltip from '@nextcloud/vue/dist/Directives/Tooltip'

const shippedBackgroundList = loadState('theming', 'shippedBackgrounds')

export default {
	name: 'BackgroundSettings',
	directives: {
		Tooltip,
	},

	components: {
		NcColorPicker,
	},

	props: {
		background: {
			type: String,
			default: 'default',
		},
		themingDefaultBackground: {
			type: String,
			default: '',
		},
	},

	data() {
		return {
			backgroundImage: generateUrl('/apps/theming/background') + '?v=' + Date.now(),
			loading: false,
			Theming: loadState('theming', 'data', {}),
		}
	},

	computed: {
		shippedBackgrounds() {
			return Object.keys(shippedBackgroundList).map(fileName => {
				return {
					name: fileName,
					url: prefixWithBaseUrl(fileName),
					preview: prefixWithBaseUrl('preview/' + fileName),
					details: shippedBackgroundList[fileName],
				}
			})
		},
	},

	methods: {
		/**
		 * Do we need to invert the text if color is too bright?
		 *
		 * @param {string} color the hex color
		 */
		invertTextColor(color) {
			return this.calculateLuma(color) > 0.6
		},

		/**
		 * Calculate luminance of provided hex color
		 *
		 * @param {string} color the hex color
		 */
		calculateLuma(color) {
			const [red, green, blue] = this.hexToRGB(color)
			return (0.2126 * red + 0.7152 * green + 0.0722 * blue) / 255
		},

		/**
		 * Convert hex color to RGB
		 *
		 * @param {string} hex the hex color
		 */
		hexToRGB(hex) {
			const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex)
			return result
				? [parseInt(result[1], 16), parseInt(result[2], 16), parseInt(result[3], 16)]
				: null
		},

		async update(data) {
			const background = data.type === 'custom' || data.type === 'default' ? data.type : data.value
			this.backgroundImage = getBackgroundUrl(background, data.version, this.themingDefaultBackground)
			if (data.type === 'color' || (data.type === 'default' && this.themingDefaultBackground === 'backgroundColor')) {
				this.$emit('update:background', data)
				this.loading = false
				return
			}
			const image = new Image()
			image.onload = () => {
				this.$emit('update:background', data)
				this.loading = false
			}
			image.src = this.backgroundImage
		},

		async setDefault() {
			this.loading = 'default'
			const result = await axios.post(generateUrl('/apps/theming/background/default'))
			this.update(result.data)
		},

		async setShipped(shipped) {
			this.loading = shipped
			const result = await axios.post(generateUrl('/apps/theming/background/shipped'), { value: shipped })
			this.update(result.data)
		},

		async setFile(path) {
			this.loading = 'custom'
			const result = await axios.post(generateUrl('/apps/theming/background/custom'), { value: path })
			this.update(result.data)
		},

		debouncePickColor: debounce(function() {
			this.pickColor(...arguments)
		}, 200),
		async pickColor(event) {
			this.loading = 'color'
			const color = event?.target?.dataset?.color || this.Theming?.color || '#0082c9'
			const result = await axios.post(generateUrl('/apps/theming/background/color'), { value: color })
			this.update(result.data)
		},

		pickFile() {
			window.OC.dialogs.filepicker(t('theming', 'Insert from {productName}', { productName: OC.theme.name }), (path, type) => {
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
		border: 2px solid var(--color-main-background);
		overflow: hidden;

		&.current {
			background-image: var(--color-background-dark);
		}

		&.filepicker, &.default, &.color {
			border-color: var(--color-border);
		}

		&.color {
			background-color: var(--color-primary-default);
			color: var(--color-primary-text);
		}

		&.active,
		&:hover,
		&:focus {
			border: 2px solid var(--color-primary);
		}

		&.active:not(.icon-loading) {
			&:after {
				background-image: var(--original-icon-checkmark-white);
				background-repeat: no-repeat;
				background-position: center;
				background-size: 44px;
				content: '';
				display: block;
				height: 100%;
			}

			&[data-color-bright]:after {
				background-image: var(--original-icon-checkmark-dark);
			}
		}
	}
}
</style>
