<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { t } from '@nextcloud/l10n'
import { computed } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import IconArrowULeftTop from 'vue-material-design-icons/ArrowULeftTop.vue'
import IconCheck from 'vue-material-design-icons/Check.vue'
import { getTextColor } from '../utils/color.ts'

const props = withDefaults(defineProps<{
	/**
	 * This item is the instance primary color.
	 * When another color is selected it offers a reset icon.
	 */
	isDefault?: boolean
	/**
	 * Background color of the button.
	 * Omitted for the custom color trigger until a color has been picked.
	 */
	color?: string
	/**
	 * Accessible name of this color
	 */
	name: string
	/**
	 * Whether this color is the one currently selected
	 */
	active?: boolean
	/**
	 * Replace the icon with a loading indicator
	 */
	loading?: boolean
}>(), {
	isDefault: false,
	active: false,
	loading: false,
})

const emit = defineEmits<{
	select: []
}>()

const label = computed(() => (props.isDefault && !props.active) ? t('theming', 'Reset primary color') : props.name)
const buttonStyle = computed(() => {
	if (!props.color) {
		return undefined
	}
	const textColor = getTextColor(props.color)
	return {
		'--color-primary-element': props.color,
		'--color-primary-element-hover': props.color,
		'--color-primary-element-text': textColor,
	}
})
</script>

<template>
	<NcButton
		class="primary-color-item"
		variant="primary"
		:style="buttonStyle"
		:aria-pressed="active ? 'true' : 'false'"
		:aria-label="label"
		:title="label"
		@click="emit('select')">
		<template #icon>
			<NcLoadingIcon v-if="loading && active" />
			<IconCheck v-else-if="active" :size="28" />
			<IconArrowULeftTop v-else-if="isDefault" :size="28" />
			<slot v-else />
		</template>
	</NcButton>
</template>

<style scoped lang="scss">
.primary-color-item {
	--color-primary-element: var(--color-primary-light);
	--color-primary-element-hover: var(--color-primary-light);
	--color-primary-element-text: var(--color-primary-light-text);
	--button-size: 64px !important;
	border-color: rgba(0, 0, 0, 0.1) !important;
}
</style>
