<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<!-- eslint-disable-next-line vue/no-v-html -->
	<div class="markdown" v-html="html" />
</template>

<script setup lang="ts">
import { toRef } from 'vue'
import { useMarkdown } from '../composables/useMarkdown'

const props = withDefaults(
	defineProps<{
		markdown: string
		minHeadingLevel?: 1|2|3|4|5|6
	}>(),
	{
		minHeadingLevel: 2,
	},
)

const { html } = useMarkdown(toRef(props, 'markdown'), toRef(props, 'minHeadingLevel'))
</script>

<style scoped lang="scss">
.markdown {
	:deep {
		ul {
			list-style: disc;
			padding-inline-start: 20px;
		}

		h3, h4, h5, h6 {
			font-weight: 600;
			line-height: 1.5;
			margin-top: 24px;
			margin-bottom: 12px;
			color: var(--color-main-text);
		}

		h3 {
			font-size: 20px;
		}

		h4 {
			font-size: 18px;
		}

		h5 {
			font-size: 17px;
		}

		h6 {
			font-size: var(--default-font-size);
		}
	}
}
</style>
