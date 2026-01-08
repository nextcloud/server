<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import axios from '@nextcloud/axios'
import { showError } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { generateOcsUrl } from '@nextcloud/router'
import { colord } from 'colord'
import debounce from 'debounce'
import { computed, ref, useTemplateRef, watch } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcColorPicker from '@nextcloud/vue/components/NcColorPicker'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import IconColorPalette from 'vue-material-design-icons/PaletteOutline.vue'
import IconUndo from 'vue-material-design-icons/UndoVariant.vue'
import { logger } from '../utils/logger.ts'

const emit = defineEmits<{
	refreshStyles: []
}>()

defineExpose({ reload })

const { primaryColor: initialPrimaryColor, defaultPrimaryColor } = loadState('theming', 'data', { primaryColor: '#0082c9', defaultPrimaryColor: '#0082c9' })

const triggerElement = useTemplateRef('trigger')

const loading = ref(false)
const primaryColor = ref(initialPrimaryColor)
watch(primaryColor, debounce((newColor) => {
	onUpdate(newColor)
}, 1000))

const isDefaultPrimaryColor = computed(() => colord(primaryColor.value).isEqual(colord(defaultPrimaryColor)))

/**
 * Global styles are reloaded so we might need to update the current value
 */
function reload() {
	let newColor = window.getComputedStyle(triggerElement.value!).backgroundColor
	// sometimes the browser returns the color in the "rgb(255, 132, 234)" format
	const rgbMatch = newColor.replaceAll(/\s/g, '').match(/^rgba?\((\d+),(\d+),(\d+)/)
	if (rgbMatch) {
		newColor = `#${numberToHex(rgbMatch[1]!)}${numberToHex(rgbMatch[2]!)}${numberToHex(rgbMatch[3]!)}`
	}
	if (newColor.toLowerCase() !== primaryColor.value.toLowerCase()) {
		primaryColor.value = newColor
	}
}

/**
 * Reset primary color to default
 */
function onReset() {
	primaryColor.value = defaultPrimaryColor
	onUpdate(null)
}

/**
 * Handle saving the new primary color on the server
 *
 * @param value - The new value
 */
async function onUpdate(value: string | null) {
	loading.value = true
	const url = generateOcsUrl('apps/provisioning_api/api/v1/config/users/{appId}/{configKey}', {
		appId: 'theming',
		configKey: 'primary_color',
	})
	try {
		if (value) {
			await axios.post(url, {
				configValue: value,
			})
		} else {
			await axios.delete(url)
		}
		emit('refreshStyles')
	} catch (error) {
		logger.error('Could not update primary color', { error })
		showError(t('theming', 'Could not set primary color'))
	}
	loading.value = false
}

/**
 * @param numeric - Numeric string to convert to hex
 */
function numberToHex(numeric: string): string {
	const parsed = Number.parseInt(numeric)
	return parsed.toString(16).padStart(2, '0')
}
</script>

<template>
	<NcSettingsSection
		:name="t('theming', 'Primary color')"
		:description="t('theming', 'Set a primary color to highlight important elements. The color used for elements such as primary buttons might differ a bit as it gets adjusted to fulfill accessibility requirements.')">
		<div :class="$style.userPrimaryColor">
			<NcColorPicker
				v-model="primaryColor"
				data-user-theming-primary-color>
				<button
					ref="trigger"
					:class="$style.userPrimaryColor__trigger"
					:style="{ 'background-color': primaryColor }"
					data-user-theming-primary-color-trigger>
					{{ t('theming', 'Primary color') }}
					<NcLoadingIcon v-if="loading" />
					<IconColorPalette v-else :size="20" />
				</button>
			</NcColorPicker>
			<NcButton variant="tertiary" :disabled="isDefaultPrimaryColor" @click="onReset">
				<template #icon>
					<IconUndo :size="20" />
				</template>
				{{ t('theming', 'Reset primary color') }}
			</NcButton>
		</div>
	</NcSettingsSection>
</template>

<style module lang="scss">
.userPrimaryColor {
	display: flex;
	flex-direction: row;
	flex-wrap: wrap;
	gap: 12px;
}

.userPrimaryColor .userPrimaryColor__trigger {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	gap: 8px;
	margin: 0 !important;

	background-color: var(--color-primary);
	color: var(--color-primary-text);
	height: 96px;
	width: 168px;

	word-wrap: break-word;
	hyphens: auto;

	border: 2px solid var(--color-main-background);
	border-radius: var(--border-radius-large);

	&:active {
		background-color: var(--color-primary-hover) !important;
	}

	&:hover,
	&:focus,
	&:focus-visible {
		border-color: var(--color-main-background) !important;
		outline: 2px solid var(--color-main-text) !important;
	}
}
</style>
