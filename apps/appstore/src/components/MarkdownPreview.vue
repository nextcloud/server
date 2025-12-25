<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { useMarkdown } from '../composables/useMarkdown.ts'

const {
	text,
	minHeadingLevel = 1,
} = defineProps<{
	/**
	 * The markdown text to render
	 */
	text: string
	/**
	 * Limit the minimum heading level
	 */
	minHeadingLevel?: number
}>()

const renderMarkdown = useMarkdown(() => text, { minHeadingLevel })
</script>

<template>
	<!-- eslint-disable-next-line vue/no-v-html -->
	<div class="settings-markdown" v-html="renderMarkdown" />
</template>

<style scoped lang="scss">
.settings-markdown :deep {
	a {
		text-decoration: underline;
		&::after {
			content: '↗';
			padding-inline: calc(var(--default-grid-baseline) / 2);
		}
	}

	pre {
		white-space: pre;
		overflow-x: auto;
		background-color: var(--color-background-dark);
		border-radius: var(--border-radius);
		padding: 1em 1.3em;
		margin-bottom: 1em;
	}

	p code {
		background-color: var(--color-background-dark);
		border-radius: var(--border-radius);
		padding: .1em .3em;
	}

	li {
		position: relative;
	}

	ul, ol {
		padding-inline-start: 10px;
		margin-inline-start: 10px;
	}

	ul li {
		list-style-type: disc;
	}

	ul > li > ul > li {
		list-style-type: circle;
	}

	ul > li > ul > li ul li {
		list-style-type: square;
	}

	blockquote {
		padding-inline-start: 1em;
		border-inline-start: 4px solid var(--color-primary-element);
		color: var(--color-text-maxcontrast);
		margin-inline: 0;
	}
}
</style>
