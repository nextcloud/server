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
import debounce from 'debounce'
import { computed, ref, useTemplateRef, watch } from 'vue'
import NcColorPicker from '@nextcloud/vue/components/NcColorPicker'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import IconColorPalette from 'vue-material-design-icons/PaletteOutline.vue'
import UserPrimaryColorItem from './UserPrimaryColorItem.vue'
import { isSameColor } from '../utils/color.ts'
import { logger } from '../utils/logger.ts'

const emit = defineEmits<{
	refreshStyles: []
}>()

defineExpose({ reload })

const { primaryColor: initialPrimaryColor, defaultPrimaryColor } = loadState('theming', 'data', { primaryColor: '#0082c9', defaultPrimaryColor: '#0082c9' })

/** Preset colors shown next to the instance primary color. */
const PRESET_COLORS = [
	{ color: '#6A32F4', name: t('theming', 'Purple') },
	{ color: '#7B4E7E', name: t('theming', 'Plum') },
	{ color: '#8D465A', name: t('theming', 'Berry') },
	{ color: '#9C4236', name: t('theming', 'Red') },
	{ color: '#9F652F', name: t('theming', 'Brown') },
	{ color: '#316B26', name: t('theming', 'Green') },
	{ color: '#124C3B', name: t('theming', 'Dark green') },
	{ color: '#1D243D', name: t('theming', 'Navy') },
	{ color: '#4F6071', name: t('theming', 'Slate') },
]

const triggerElement = useTemplateRef('trigger')

const loading = ref(false)
const primaryColor = ref(initialPrimaryColor)
watch(primaryColor, debounce((newColor) => {
	onUpdate(newColor)
}, 1000))

const isDefaultPrimaryColor = computed(() => isSameColor(primaryColor.value, defaultPrimaryColor))
const isCustomColor = computed(() => !isDefaultPrimaryColor.value && !PRESET_COLORS.some((entry) => isSameColor(entry.color, primaryColor.value)))
const pickerBackground = ref<string | undefined>(isCustomColor.value ? primaryColor.value : undefined)

/**
 * Apply a color chosen in the picker and paint the palette button with it
 *
 * @param color - Hex color
 */
function onPickerUpdate(color?: string) {
	if (!color) {
		return
	}
	pickerBackground.value = color
	primaryColor.value = color
}

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
	if (isDefaultPrimaryColor.value) {
		return
	}
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
		<div class="primary-color-list">
			<UserPrimaryColorItem
				isDefault
				:active="isDefaultPrimaryColor"
				:color="defaultPrimaryColor"
				:name="t('theming', 'Primary color')"
				:loading="loading && isDefaultPrimaryColor"
				@select="onReset" />
			<UserPrimaryColorItem
				v-for="preset in PRESET_COLORS"
				:key="preset.color"
				:color="preset.color"
				:name="preset.name"
				:active="isSameColor(primaryColor, preset.color)"
				:loading="loading && isSameColor(primaryColor, preset.color)"
				@select="primaryColor = preset.color" />
			<NcColorPicker
				:modelValue="primaryColor"
				data-user-theming-primary-color
				@update:modelValue="onPickerUpdate">
				<UserPrimaryColorItem
					ref="trigger"
					:color="pickerBackground"
					:name="t('theming', 'Custom color')"
					:active="isCustomColor"
					:loading="loading && isCustomColor">
					<IconColorPalette :size="28" />
				</UserPrimaryColorItem>
			</NcColorPicker>
		</div>
	</NcSettingsSection>
</template>

<style scoped lang="scss">
.primary-color-list {
	display: flex;
	flex-direction: row;
	flex-wrap: wrap;
	align-items: center;
	gap: 8px;
}
</style>
