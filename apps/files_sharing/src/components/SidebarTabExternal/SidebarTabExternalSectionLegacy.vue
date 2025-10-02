<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="sharing-tab-external-section-legacy">
		<component :is="component" :file-info="fileInfo" />
	</div>
</template>

<script lang="ts" setup>
import {
	type Component, type PropType,

	computed,
} from 'vue'

const props = defineProps({
	fileInfo: {
		type: Object,
		required: true,
	},
	sectionCallback: {
		type: Function as PropType<(el: HTMLElement | undefined, fileInfo: unknown) => Component>,
		required: true,
	},
})

const component = computed(() => props.sectionCallback(undefined, props.fileInfo))
</script>

<style scoped>
.sharing-tab-external-section-legacy {
	width: 100%;
}
</style>
