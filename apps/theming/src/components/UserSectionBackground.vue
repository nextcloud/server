<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { mdiCheck, mdiImageEditOutline, mdiPaletteOutline, mdiUndo } from '@mdi/js'
import axios from '@nextcloud/axios'
import { getFilePickerBuilder } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { generateFilePath, generateUrl } from '@nextcloud/router'
import { ref } from 'vue'
import NcColorPicker from '@nextcloud/vue/components/NcColorPicker'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import { getTextColor } from '../utils/color.ts'

interface IThemingDefaults {
	backgroundImage: string
	backgroundColor: string
	backgroundMime: string
	defaultShippedBackground: string
}

interface IThemingData {
	backgroundImage: string
	backgroundColor: string
	backgroundMime: string
}

interface ShippedBackground {
	attribution: string
	description: string
	attribution_url: string
	dark_variant: string
	background_color: string
	primary_color: string
}

const emit = defineEmits<{
	refreshStyles: []
}>()

const SHIPPED_BACKGROUNDS = loadState<Record<string, ShippedBackground>>('theming', 'shippedBackgrounds')
const THEMING_DEFAULTS = loadState<IThemingDefaults>('theming', 'themingDefaults')
const DEFAULT_BACKGROUND_IMAGE = `url('${THEMING_DEFAULTS.backgroundImage}')`

const loading = ref<false | 'custom' | 'color' | 'default' | keyof typeof SHIPPED_BACKGROUNDS>(false)
const currentTheming = ref(structuredClone(loadState<IThemingData>('theming', 'data')))
const currentBackgroundImage = ref(loadState<string>('theming', 'userBackgroundImage'))

const shippedBackgrounds = Object.keys(SHIPPED_BACKGROUNDS)
	.filter((background) => {
		// If the admin did not changed the global background
		// let's hide the default background to not show it twice
		return background !== THEMING_DEFAULTS.defaultShippedBackground
			|| THEMING_DEFAULTS.backgroundMime !== ''
	})
	.map((fileName) => {
		return {
			name: fileName,
			url: prefixWithBaseUrl(fileName),
			preview: prefixWithBaseUrl('preview/' + fileName),
			details: SHIPPED_BACKGROUNDS[fileName]!,
		}
	})

/**
 * Add the theming app prefix to the url
 *
 * @param url - The url to preix
 */
function prefixWithBaseUrl(url: string) {
	return generateFilePath('theming', '', 'img/background/') + url
}

/**
 * Update local state
 *
 * @param data - Destructuring object
 * @param data.backgroundColor - Background color value
 * @param data.backgroundImage - Background image value
 * @param data.version - Cache buster number
 * @see https://github.com/nextcloud/server/blob/c78bd45c64d9695724fc44fe8453a88824b85f2f/apps/theming/lib/Controller/UserThemeController.php#L187-L191
 */
async function update(data: { backgroundColor: string, backgroundImage: string, version: string }) {
	// Update state
	currentBackgroundImage.value = data.backgroundImage
	currentTheming.value.backgroundColor = data.backgroundColor

	// Notify parent and reload style
	emit('refreshStyles')
	loading.value = false
}

/**
 * Set background to default
 */
async function setDefault() {
	loading.value = 'default'
	const result = await axios.post(generateUrl('/apps/theming/background/default'))
	update(result.data)
}

/**
 * Set background to a shipped background
 *
 * @param shipped - The shipped background name
 */
async function setShipped(shipped: string) {
	loading.value = shipped
	const result = await axios.post(generateUrl('/apps/theming/background/shipped'), { value: shipped })
	update(result.data)
}

/**
 * Set background to a Nextcloud file
 *
 * @param path - Path to the file
 */
async function setFile(path: string) {
	loading.value = 'custom'
	const result = await axios.post(generateUrl('/apps/theming/background/custom'), { value: path })
	update(result.data)
}

/**
 * Set a plain color as background
 *
 * @param color - The hex color
 */
async function pickColor(color?: string) {
	if (!color) {
		return
	}

	loading.value = 'color'
	const { data } = await axios.post(generateUrl('/apps/theming/background/color'), { color: color || '#0082c9' })
	update(data)
}

/**
 * Open file picker to select a custom background
 */
async function pickFile() {
	await getFilePickerBuilder(t('theming', 'Select a background from your files'))
		.allowDirectories(false)
		.setFilter((node) => node.mime.startsWith('image/'))
		.setMultiSelect(false)
		.addButton({
			label: t('theming', 'Select background'),
			callback: ([node]) => {
				setFile(node!.path)
			},
			variant: 'primary',
		})
		.build()
		.pick()
}
</script>

<template>
	<NcSettingsSection
		class="background"
		:name="t('theming', 'Background and color')"
		:description="t('theming', 'The background can be set to an image from the default set, a custom uploaded image, or a plain color.')">
		<fieldset>
			<legend class="hidden-visually">
				{{ t('theming', 'Background and color') }}
			</legend>

			<div :class="$style.backgroundSelect">
				<!-- Custom background -->
				<button
					:aria-disabled="loading === 'custom'"
					:aria-pressed="currentBackgroundImage === 'custom'"
					:aria-label="t('theming', 'Custom background')"
					:title="t('theming', 'Custom background')"
					class="button-vue"
					:class="[$style.backgroundSelect__entry, $style.backgroundSelect__entryFilePicker]"
					@click="pickFile">
					<NcLoadingIcon v-if="loading === 'custom'" />
					<NcIconSvgWrapper v-else :path="currentBackgroundImage === 'custom' ? mdiCheck : mdiImageEditOutline" />
				</button>

				<!-- Custom color picker -->
				<NcColorPicker v-model="currentTheming.backgroundColor" @submit="pickColor">
					<button
						class="button-vue"
						:class="[$style.backgroundSelect__entry, $style.backgroundSelect__entryColor]"
						:aria-disabled="loading === 'color'"
						:aria-pressed="currentBackgroundImage === 'color'"
						:aria-label="t('theming', 'Plain background') /* TRANSLATORS: Background using a single color */"
						:title="t('theming', 'Plain background') /* TRANSLATORS: Background using a single color */"
						:style="{
							backgroundColor: currentTheming.backgroundColor,
							'--color-content': getTextColor(currentTheming.backgroundColor),
						}">
						<NcLoadingIcon v-if="loading === 'color'" />
						<NcIconSvgWrapper v-else :path="currentBackgroundImage === 'color' ? mdiCheck : mdiPaletteOutline" />
					</button>
				</NcColorPicker>

				<!-- Default background -->
				<button
					class="button-vue"
					:class="[$style.backgroundSelect__entry, $style.backgroundSelect__entryDefault]"
					:aria-disabled="loading === 'default'"
					:aria-pressed="currentBackgroundImage === 'default'"
					:aria-label="t('theming', 'Default background')"
					:title="t('theming', 'Default background')"
					:style="{
						'--color-content': getTextColor(THEMING_DEFAULTS.backgroundColor),
					}"
					@click="setDefault">
					<NcLoadingIcon v-if="loading === 'default'" />
					<NcIconSvgWrapper v-else :path="currentBackgroundImage === 'default' ? mdiCheck : mdiUndo" />
				</button>
			</div>

			<!-- Background set selection -->
			<fieldset :class="$style.backgroundSelect">
				<label class="hidden-visually">
					{{ t('theming', 'Default shipped background images') }}
				</label>
				<button
					v-for="shippedBackground in shippedBackgrounds"
					:key="shippedBackground.name"
					:title="shippedBackground.details.attribution"
					:aria-label="shippedBackground.details.description"
					:aria-pressed="currentBackgroundImage === shippedBackground.name"
					class="button-vue"
					:class="$style.backgroundSelect__entry"
					:style="{
						backgroundImage: 'url(' + shippedBackground.preview + ')',
					}"
					tabindex="0"
					@click="setShipped(shippedBackground.name)">
					<NcIconSvgWrapper
						v-if="currentBackgroundImage === shippedBackground.name"
						:class="$style.backgroundSelect__entryIcon"
						:path="mdiCheck" />
				</button>
			</fieldset>
		</fieldset>
	</NcSettingsSection>
</template>

<style module lang="css">
.backgroundSelect {
	display: flex;
	flex-wrap: wrap;
	justify-content: center;

	.backgroundSelect__entry {
		display: flex;
		flex-direction: column;
		justify-content: center;
		align-items: center;
		overflow: hidden;
		height: 96px;
		width: 168px;
		margin: var(--default-grid-baseline);
		text-align: center;
		word-wrap: break-word;
		hyphens: auto;
		border: 2px solid var(--color-main-background);
		border-radius: var(--border-radius-large);
		background-position: center center;
		background-size: cover;

		--color-content: var(--color-background-plain-text);
	}

	.backgroundSelect__entry:hover,
	.backgroundSelect__entry:focus {
		outline: 2px solid var(--color-main-text) !important;
		border-color: var(--color-main-background) !important;
	}

	.backgroundSelect__entry > *{
		color: var(--color-content);
		opacity: 1;
	}

	.backgroundSelect__entryColor {
		background-color: var(--color-background-plain);
	}

	.backgroundSelect__entryFilePicker {
		--color-content: var(--color-main-text);
		background-color: var(--color-background-dark);
	}

	.backgroundSelect__entryFilePicker[aria-pressed="true"] {
		--color-content: var(--color-background-plain-text);
		background-image: var(--image-background);
	}

	.backgroundSelect__entryDefault {
		background-image: linear-gradient(to bottom, rgba(23, 23, 23, 0.5), rgba(23, 23, 23, 0.5)), v-bind(DEFAULT_BACKGROUND_IMAGE);
	}
}
</style>
