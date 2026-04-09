<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<component
		:is="action.element"
		:key="action.id"
		ref="actionElement"
		:share.prop="share"
		:node.prop="node"
		:on-save.prop="onSave" />
</template>

<script lang="ts" setup>
import type { INode } from '@nextcloud/files'
import type { IShare } from '@nextcloud/sharing'
import type { ISidebarAction } from '@nextcloud/sharing/ui'
import type { PropType } from 'vue'

import { ref, toRaw, watchEffect } from 'vue'

const props = defineProps({
	action: {
		type: Object as PropType<ISidebarAction>,
		required: true,
	},
	node: {
		type: Object as PropType<INode>,
		required: true,
	},
	share: {
		type: Object as PropType<IShare | undefined>,
		required: true,
	},
})

defineExpose({ save })

const actionElement = ref()
const savingCallback = ref()

watchEffect(() => {
	if (!actionElement.value) {
		return
	}

	// This seems to be only needed in Vue 2 as the .prop modifier does not really work on the vue 2 version of web components
	// TODO: Remove with Vue 3
	actionElement.value.node = toRaw(props.node)
	actionElement.value.onSave = onSave
	actionElement.value.share = toRaw(props.share)
})

/**
 * The share is reset thus save the state of the component.
 */
async function save() {
	await savingCallback.value?.()
}

/**
 * Vue does not allow to call methods on wrapped web components
 * so we need to pass it per callback.
 *
 * @param callback - The callback to be called on save
 */
function onSave(callback: () => Promise<void>) {
	savingCallback.value = callback
}
</script>
