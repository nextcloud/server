<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="background-selector" data-user-theming-background-settings>
		<!-- Custom background -->
		<button :aria-pressed="backgroundImage === 'custom'"
			:class="{
				'icon-loading': loading === 'custom',
				'background background__filepicker': true,
				'background--active': backgroundImage === 'custom'
			}"
			data-user-theming-background-custom
			tabindex="0"
			@click="pickFile">
			{{ t('theming', 'Custom background') }}
			<ImageEdit v-if="backgroundImage !== 'custom'" :size="20" />
			<Check :size="44" />
		</button>

		<!-- Custom color picker -->
		<NcColorPicker v-model="Theming.backgroundColor" @update:value="debouncePickColor">
			<button :class="{
					'icon-loading': loading === 'color',
					'background background__color': true,
					'background--active': backgroundImage === 'color'
				}"
				:aria-pressed="backgroundImage === 'color'"
				:data-color="Theming.backgroundColor"
				:data-color-bright="invertTextColor(Theming.backgroundColor)"
				:style="{ backgroundColor: Theming.backgroundColor, '--border-color': Theming.backgroundColor}"
				data-user-theming-background-color
				tabindex="0"
				@click="backgroundImage !== 'color' && debouncePickColor(Theming.backgroundColor)">
				{{ t('theming', 'Plain background') /* TRANSLATORS: Background using a single color */ }}
				<ColorPalette v-if="backgroundImage !== 'color'" :size="20" />
				<Check :size="44" />
			</button>
		</NcColorPicker>

		<!-- Default background -->
		<button :aria-pressed="backgroundImage === 'default'"
			:class="{
				'icon-loading': loading === 'default',
				'background background__default': true,
				'background--active': backgroundImage === 'default'
			}"
			:data-color-bright="invertTextColor(Theming.defaultBackgroundColor)"
			:style="{ '--border-color': Theming.defaultBackgroundColor }"
			data-user-theming-background-default
			tabindex="0"
			@click="setDefault">
			{{ t('theming', 'Default background') }}
			<Check :size="44" />
		</button>

		<!-- Background set selection -->
		<button v-for="shippedBackground in shippedBackgrounds"
			:key="shippedBackground.name"
			:title="shippedBackground.details.attribution"
			:aria-label="shippedBackground.details.description"
			:aria-pressed="backgroundImage === shippedBackground.name"
			:class="{
				'background background__shipped': true,
				'icon-loading': loading === shippedBackground.name,
				'background--active': backgroundImage === shippedBackground.name
			}"
			:data-color-bright="invertTextColor(shippedBackground.details.background_color)"
			:data-user-theming-background-shipped="shippedBackground.name"
			:style="{ backgroundImage: 'url(' + shippedBackground.preview + ')', '--border-color': shippedBackground.details.primary_color }"
			tabindex="0"
			@click="setShipped(shippedBackground.name)">
			<Check :size="44" />
		</button>
	</div>
</template>

<script>
import { generateFilePath, generateUrl } from '@nextcloud/router'
import { getFilePickerBuilder, showError } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import axios from '@nextcloud/axios'
import debounce from 'debounce'
import NcColorPicker from '@nextcloud/vue/dist/Components/NcColorPicker.js'

import Check from 'vue-material-design-icons/Check.vue'
import ImageEdit from 'vue-material-design-icons/ImageEdit.vue'
import ColorPalette from 'vue-material-design-icons/Palette.vue'

const shippedBackgroundList = loadState('theming', 'shippedBackgrounds')
const backgroundImage = loadState('theming', 'userBackgroundImage')
const {
	backgroundImage: defaultBackgroundImage,
	// backgroundColor: defaultBackgroundColor,
	backgroundMime: defaultBackgroundMime,
	defaultShippedBackground,
} = loadState('theming', 'themingDefaults')

const prefixWithBaseUrl = (url) => generateFilePath('theming', '', 'img/background/') + url

export default {
	name: 'BackgroundSettings',

	components: {
		Check,
		ColorPalette,
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
				.filter((background) => {
					// If the admin did not changed the global background
					// let's hide the default background to not show it twice
					return background !== defaultShippedBackground || !this.isGlobalBackgroundDefault
				})
				.map((fileName) => {
					return {
						name: fileName,
						url: prefixWithBaseUrl(fileName),
						preview: prefixWithBaseUrl('preview/' + fileName),
						details: shippedBackgroundList[fileName],
					}
				})
		},

		isGlobalBackgroundDefault() {
			return defaultBackgroundMime === ''
		},

		isGlobalBackgroundDeleted() {
			return defaultBackgroundMime === 'backgroundColor'
		},

		cssDefaultBackgroundImage() {
			return `url('${defaultBackgroundImage}')`
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
			this.Theming.backgroundColor = data.backgroundColor

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

		async setFile(path) {
			this.loading = 'custom'
			const result = await axios.post(generateUrl('/apps/theming/background/custom'), { value: path })
			this.update(result.data)
		},

		async removeBackground() {
			this.loading = 'remove'
			const result = await axios.delete(generateUrl('/apps/theming/background/custom'))
			this.update(result.data)
		},

		async pickColor(color) {
			this.loading = 'color'
			const { data } = await axios.post(generateUrl('/apps/theming/background/color'), { color: color || '#0082c9' })
			this.update(data)
		},

		debouncePickColor: debounce(function(...args) {
			this.pickColor(...args)
		}, 1000),

		pickFile() {
			const picker = getFilePickerBuilder(t('theming', 'Select a background from your files'))
				.allowDirectories(false)
				.setMimeTypeFilter(['image/png', 'image/gif', 'image/jpeg', 'image/svg+xml', 'image/svg'])
				.setMultiSelect(false)
				.addButton({
					id: 'select',
					label: t('theming', 'Select background'),
					callback: (nodes) => {
						this.applyFile(nodes[0]?.path)
					},
					type: 'primary',
				})
				.build()
			picker.pick()
		},

		async applyFile(path) {
			if (!path || typeof path !== 'string' || path.trim().length === 0 || path === '/') {
				console.error('No valid background have been selected', { path })
				showError(t('theming', 'No background has been selected'))
				return
			}

			this.loading = 'custom'
			this.setFile(path)
		},
	},
}
</script>

<style scoped lang="scss">
.background-selector {
	display: flex;
	flex-wrap: wrap;
	justify-content: center;

	.background-color {
		display: flex;
		justify-content: center;
		align-items: center;
		width: 176px;
		height: 96px;
		margin: 8px;
		border-radius: var(--border-radius-large);
		background-color: var(--color-primary);
	}

	.background {
		overflow: hidden;
		width: 176px;
		height: 96px;
		margin: 8px;
		text-align: center;
		word-wrap: break-word;
		hyphens: auto;
		border: 2px solid var(--color-main-background);
		border-radius: var(--border-radius-large);
		background-position: center center;
		background-size: cover;

		&__filepicker {
			background-color: var(--color-background-dark);

			&.background--active {
				color: var(--color-background-plain-text);
				background-image: var(--image-background);
			}
		}

		&__default {
			background-color: var(--color-background-plain);
			background-image: linear-gradient(to bottom, rgba(23, 23, 23, 0.5), rgba(23, 23, 23, 0.5)), v-bind(cssDefaultBackgroundImage);
		}

		&__filepicker, &__default, &__color {
			border-color: var(--color-border);
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
			outline: 2px solid var(--color-main-text) !important;
			border-color: var(--color-main-background) !important;
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
