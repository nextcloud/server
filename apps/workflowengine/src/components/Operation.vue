<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div
		ref="operationElement"
		:class="[$style.actions__item, { [$style.colored]: colored }]">
		<div
			:class="[$style.icon, operation.iconClass]"
			:style="{ backgroundImage: operation.iconClass ? '' : `url(${operation.icon})` }" />
		<div :class="$style.actions__item__description">
			<h3>{{ operation.name }}</h3>
			<small>{{ operation.description }}</small>
			<NcButton v-if="colored">
				{{ t('workflowengine', 'Add new flow') }}
			</NcButton>
		</div>
		<div :class="$style.actions__item_options">
			<slot />
		</div>
	</div>
</template>

<script setup lang="ts">
/* eslint vue/multi-word-component-names: "warn" */

import type { OperationCard } from '../types.ts'

import { t } from '@nextcloud/l10n'
import { computed, nextTick, ref, watch } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import { contrastingTextColor, DEFAULT_TEXT_COLOR, iconFilterFor, knownTextColor } from '../helpers/contrast.ts'

const props = defineProps<{
	operation: OperationCard
	colored?: boolean
}>()

const operationElement = ref<HTMLDivElement>()
const color = ref(DEFAULT_TEXT_COLOR)
const backgroundColor = computed(() => props.colored ? (props.operation.color || 'var(--color-primary-element)') : 'transparent')

watch(backgroundColor, async () => {
	const known = knownTextColor(backgroundColor.value)
	if (known !== null) {
		color.value = known
		return
	}

	let resolved = backgroundColor.value
	if (!resolved.startsWith('#')) {
		// only the browser knows what a custom property resolves to
		await nextTick()
		resolved = window.getComputedStyle(operationElement.value!).backgroundColor
	}
	color.value = contrastingTextColor(resolved)
}, { immediate: true })

/** Filter to apply to the icon to make it accessible on the given background color. */
const iconFilter = computed(() => iconFilterFor(color.value))
</script>

<style module lang="scss">
@use "./../styles/operation.scss" as *;

.actions__item {
	color: v-bind('color');
	background-color: v-bind('backgroundColor');

	h3 {
		color: v-bind('color');
	}

	.icon {
		filter: v-bind('iconFilter');
	}
}
</style>
