<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<component :is="section.element" ref="sectionElement" :node.prop="node" />
</template>

<script lang="ts" setup>
import type { INode } from '@nextcloud/files'
import type { ISidebarSection } from '@nextcloud/sharing/ui'
import type { PropType } from 'vue'

import { ref, watchEffect } from 'vue'

const props = defineProps({
	node: {
		type: Object as PropType<INode>,
		required: true,
	},
	section: {
		type: Object as PropType<ISidebarSection>,
		required: true,
	},
})

// TOOD: Remove with Vue 3
const sectionElement = ref()
watchEffect(() => {
	if (sectionElement.value) {
		sectionElement.value.node = props.node
	}
})
</script>
