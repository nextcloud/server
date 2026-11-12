<!--
 - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { IRichObjectParameters, ISetupCheck } from '../settings-types.ts'

import { mdiAlert, mdiClose, mdiInformation } from '@mdi/js'
import escapeHTML from 'escape-html'
import { computed } from 'vue'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'

const props = defineProps<{
	/** The setup check result to show */
	setupCheck: ISetupCheck
}>()

const leadingIcon = computed(() => {
	if (props.setupCheck.severity === 'error') {
		return mdiClose
	} else if (props.setupCheck.severity === 'warning') {
		return mdiAlert
	}
	return mdiInformation
})

const descriptionHtml = computed(() => parseRichObject(props.setupCheck.description, props.setupCheck.descriptionParameters))

/**
 * Simplified RichObject parsing and replacing.
 *
 * @param message - The message that may contain rich objects
 * @param parameters - The rich object parameters
 */
function parseRichObject(message: string, parameters?: IRichObjectParameters): string {
	if (!parameters) {
		return message
	}

	for (const [placeholder, parameter] of Object.entries(parameters)) {
		let replacement: string
		if (parameter.type === 'user') {
			replacement = `@${escapeHTML(parameter.name)}`
		} else if (parameter.type === 'file') {
			replacement = escapeHTML(parameter.path || parameter.name)
		} else if (parameter.type === 'highlight') {
			if (parameter.link) {
				replacement = '<a href="' + encodeURI(parameter.link) + '">' + escapeHTML(parameter.name) + '</a>'
			} else {
				replacement = '<em>' + escapeHTML(parameter.name) + '</em>'
			}
		} else {
			replacement = escapeHTML(parameter.name)
		}
		message = message.replaceAll('{' + placeholder + '}', replacement)
	}

	return message
}
</script>

<template>
	<li :class="[$style.item, $style[setupCheck.severity]]">
		<NcIconSvgWrapper :class="$style.icon" :path="leadingIcon" />
		<div :class="$style.wrapper">
			<div>
				{{ setupCheck.name }}
			</div>
			<!-- eslint-disable-next-line vue/no-v-html -->
			<div :class="$style.description" v-html="descriptionHtml" />
		</div>
	</li>
</template>

<style module>
.item {
	border-radius: var(--border-radius-element);
	display: flex;
	align-items: start;
	flex-direction: row;
}

.item:hover {
	background-color: var(--color-background-hover);
}

.wrapper {
	display: flex;
	flex-direction: column;
	/* align with icon */
	padding-top: calc((var(--default-clickable-area) - 1lh) / 2);
}

.description {
	color: var(--color-text-maxcontrast);
}

.icon {
	border-radius: calc(var(--default-clickable-area) / 2);
}

.error .icon {
	color: var(--color-element-error);
}

.warning .icon {
	color: var(--color-element-warning);
}

.info .icon {
	color: var(--color-element-info);
}
</style>
