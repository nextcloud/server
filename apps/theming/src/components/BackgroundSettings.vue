<!--
  - @copyright Copyright (c) 2020 Julius Härtl <jus@bitgrid.net>
  -
  - @author Christopher Ng <chrng8@gmail.com>
  - @author Greta Doci <gretadoci@gmail.com>
  - @author John Molakvoæ <skjnldsv@protonmail.com>
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
	<div class="background-selector" data-user-theming-background-settings>
		<!-- Custom background -->
		<button class="background background__filepicker"
			:class="{ 'icon-loading': loading === 'custom', 'background--active': backgroundImage === 'custom' }"
			:data-color-bright="invertTextColor(Theming.color)"
			data-user-theming-background-custom
			tabindex="0"
			@click="pickFile">
			{{ t('theming', 'Custom background') }}
			<ImageEdit v-if="backgroundImage !== 'custom'" :size="26" />
			<Check :size="44" />
		</button>

		<!-- Default background -->
		<button class="background background__default"
			:class="{ 'icon-loading': loading === 'default', 'background--active': backgroundImage === 'default' }"
			:data-color-bright="invertTextColor(Theming.defaultColor)"
			:style="{ '--border-color': Theming.defaultColor }"
			data-user-theming-background-default
			tabindex="0"
			@click="setDefault">
			{{ t('theming', 'Default background') }}
			<Check :size="44" />
		</button>

		<!-- Custom color picker -->
		<NcColorPicker v-model="Theming.color" @input="debouncePickColor">
			<button class="background background__color"
				:data-color="Theming.color"
				:data-color-bright="invertTextColor(Theming.color)"
				:style="{ backgroundColor: Theming.color, '--border-color': Theming.color}"
				data-user-theming-background-color
				tabindex="0">
				{{ t('theming', 'Change color') }}
			</button>
		</NcColorPicker>

		<!-- Remove background -->
		<button class="background background__delete"
			:class="{ 'background--active': isBackgroundDisabled }"
			data-user-theming-background-clear
			tabindex="0"
			@click="removeBackground">
			{{ t('theming', 'No background') }}
			<Close v-if="!isBackgroundDisabled" :size="32" />
			<Check :size="44" />
		</button>

		<!-- Background set selection -->
		<button v-for="shippedBackground in shippedBackgrounds"
			:key="shippedBackground.name"
			:title="shippedBackground.details.attribution"
			:aria-label="shippedBackground.details.attribution"
			:class="{ 'icon-loading': loading === shippedBackground.name, 'background--active': backgroundImage === shippedBackground.name }"
			:data-color-bright="shippedBackground.details.theming === 'dark'"
			:data-user-theming-background-shipped="shippedBackground.name"
			:style="{ backgroundImage: 'url(' + shippedBackground.preview + ')', '--border-color': shippedBackground.details.primary_color }"
			class="background background__shipped"
			tabindex="0"
			@click="setShipped(shippedBackground.name)">
			<Check :size="44" />
		</button>
	</div>
</template>

<script>
import { generateFilePath, generateRemoteUrl, generateUrl } from '@nextcloud/router'
import { loadState } from '@nextcloud/initial-state'
import axios from '@nextcloud/axios'
import Check from 'vue-material-design-icons/Check.vue'
import Close from 'vue-material-design-icons/Close.vue'
import ImageEdit from 'vue-material-design-icons/ImageEdit.vue'
import debounce from 'debounce'
import NcColorPicker from '@nextcloud/vue/dist/Components/NcColorPicker.js'
import Vibrant from 'node-vibrant'
import { Palette } from 'node-vibrant/lib/color.js'
import { getFilePickerBuilder } from '@nextcloud/dialogs'
import { getCurrentUser } from '@nextcloud/auth'

const backgroundImage = loadState('theming', 'backgroundImage')
const shippedBackgroundList = loadState('theming', 'shippedBackgrounds')
const themingDefaultBackground = loadState('theming', 'themingDefaultBackground')
const defaultShippedBackground = loadState('theming', 'defaultShippedBackground')

const prefixWithBaseUrl = (url) => generateFilePath('theming', '', 'img/background/') + url
const picker = getFilePickerBuilder(t('theming', 'Select a background from your files'))
	.setMultiSelect(false)
	.setType(1)
	.setMimeTypeFilter(['image/png', 'image/gif', 'image/jpeg', 'image/svg+xml', 'image/svg'])
	.build()

export default {
	name: 'BackgroundSettings',

	components: {
		Check,
		Close,
		ImageEdit,
		NcColorPicker,
	},

	data() {
		return {
			loading: false,
			Theming: loadState('theming', 'data', {}),

			// User background image and color settings
			backgroundImage,
		}
	},

	computed: {
		shippedBackgrounds() {
			return Object.keys(shippedBackgroundList)
				.map(fileName => {
					return {
						name: fileName,
						url: prefixWithBaseUrl(fileName),
						preview: prefixWithBaseUrl('preview/' + fileName),
						details: shippedBackgroundList[fileName],
					}
				})
				.filter(background => {
					// If the admin did not changed the global background
					// let's hide the default background to not show it twice
					if (!this.isGlobalBackgroundDeleted && !this.isGlobalBackgroundDefault) {
						return background.name !== defaultShippedBackground
					}
					return true
				})
		},

		isGlobalBackgroundDefault() {
			return !!themingDefaultBackground
		},

		isGlobalBackgroundDeleted() {
			return themingDefaultBackground === 'backgroundColor'
		},

		isBackgroundDisabled() {
			return this.backgroundImage === 'disabled'
			|| !this.backgroundImage
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

		/**
		 * Update local state
		 *
		 * @param {object} data destructuring object
		 * @param {string} data.backgroundColor background color value
		 * @param {string} data.backgroundImage background image value
		 * @param {string} data.version cache buster number
		 * @see https://github.com/nextcloud/server/blob/c78bd45c64d9695724fc44fe8453a88824b85f2f/apps/theming/lib/Controller/UserThemeController.php#L187-L191
		 */
		async update(data) {
			// Update state
			this.backgroundImage = data.backgroundImage
			this.Theming.color = data.backgroundColor

			// Notify parent and reload style
			this.$emit('update:background')
			this.loading = false
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

		async setFile(path, color = null) {
			this.loading = 'custom'
			const result = await axios.post(generateUrl('/apps/theming/background/custom'), { value: path, color })
			this.update(result.data)
		},

		async removeBackground() {
			this.loading = 'remove'
			const result = await axios.delete(generateUrl('/apps/theming/background/custom'))
			this.update(result.data)
		},

		async pickColor(event) {
			this.loading = 'color'
			const color = event?.target?.dataset?.color || this.Theming?.color || '#0082c9'
			const result = await axios.post(generateUrl('/apps/theming/background/color'), { color })
			this.update(result.data)
		},
		debouncePickColor: debounce(function(...args) {
			this.pickColor(...args)
		}, 200),

		async pickFile() {
			const path = await picker.pick()
			this.loading = 'custom'

			// Extract primary color from image
			let response = null
			let color = null
			try {
				const fileUrl = generateRemoteUrl('dav/files/' + getCurrentUser().uid + path)
				response = await axios.get(fileUrl, { responseType: 'blob' })
				const blobUrl = URL.createObjectURL(response.data)
				const palette = await this.getColorPaletteFromBlob(blobUrl)

				// DarkVibrant is accessible AND visually pleasing
				// Vibrant is not accessible enough and others are boring
				color = palette?.DarkVibrant?.hex
				this.setFile(path, color)

				// Log data
				console.debug('Extracted colour', color, 'from custom image', path, palette)
			} catch (error) {
				this.setFile(path)
				console.error('Unable to extract colour from custom image', { error, path, response, color })
			}
		},

		/**
		 * Extract a Vibrant color palette from a blob URL
		 *
		 * @param {string} blobUrl the blob URL
		 * @return {Promise<Palette>}
		 */
		getColorPaletteFromBlob(blobUrl) {
			return new Promise((resolve, reject) => {
				const vibrant = new Vibrant(blobUrl)
				vibrant.getPalette((error, palette) => {
					if (error) {
						reject(error)
					}
					resolve(palette)
				})
			})
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
		overflow: hidden;
		width: 176px;
		height: 96px;
		margin: 8px;
		text-align: center;
		border: 2px solid var(--color-main-background);
		border-radius: var(--border-radius-large);
		background-position: center center;
		background-size: cover;

		&__filepicker {
			&.background--active {
				color: white;
				background-image: var(--image-background);
			}
		}

		&__default {
			background-color: var(--color-primary-default);
			background-image: var(--image-background-plain, var(--image-background-default));
		}

		&__filepicker, &__default, &__color {
			border-color: var(--color-border);
		}

		&__color {
			color: var(--color-primary-text);
			background-color: var(--color-primary-default);
		}

		// Over a background image
		&__default,
		&__shipped {
			color: white;
		}

		// Text and svg icon dark on bright background
		&[data-color-bright] {
			color: black;
		}

		&--active,
		&:hover,
		&:focus {
			// Use theme color primary, see inline css variable in template
			border: 2px solid var(--border-color, var(--color-primary-element)) !important;
		}

		// Icon
		span {
			margin: 4px;
		}

		.check-icon {
			display: none;
		}

		&--active:not(.icon-loading) {
			.check-icon {
				// Show checkmark
				display: block !important;
			}
		}
	}
}

</style>
