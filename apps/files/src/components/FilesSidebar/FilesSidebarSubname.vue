<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { IFolder, INode } from '@nextcloud/files'

import { mdiStar } from '@mdi/js'
import { formatFileSize } from '@nextcloud/files'
import { t } from '@nextcloud/l10n'
import { computed } from 'vue'
import NcDateTime from '@nextcloud/vue/components/NcDateTime'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcUserBubble from '@nextcloud/vue/components/NcUserBubble'

const props = defineProps<{
	folder: IFolder
	node: INode
}>()

const isFavourited = computed(() => props.node.attributes.favorite === 1)
const showLocation = computed(() => props.folder.path !== props.node.dirname)
const size = computed(() => formatFileSize(props.node.size ?? 0))
</script>

<template>
	<div :class="$style.filesSidebarSubname">
		<div v-if="showLocation" :class="$style.filesSidebarSubname__path">
			<span :class="$style.filesSidebarSubname__label">{{ t('files', 'Location') }}:</span>
			{{ node.path }}
		</div>

		<div :class="$style.filesSidebarSubname__metadata">
			<NcIconSvgWrapper
				v-if="isFavourited"
				inline
				:path="mdiStar"
				:name="t('files', 'Favorite')" />

			<span>{{ size }}</span>

			<span v-if="node.mtime">
				<span :class="$style.filesSidebarSubname__separator">•</span>
				<NcDateTime :timestamp="node.mtime" />
			</span>

			<template v-if="node.owner">
				<span :class="$style.filesSidebarSubname__separator">•</span>
				<NcUserBubble
					:class="$style.filesSidebarSubname__userBubble"
					:title="t('files', 'Owner')"
					:user="node.owner"
					:display-name="node.attributes['owner-display-name']" />
			</template>
		</div>
	</div>
</template>

<style module>
.filesSidebarSubname {
	display: flex;
	flex-direction: column;
	gap: 4px;
}

.filesSidebarSubname__path {
	display: flex;
	flex-direction: column;
	width: 100%;
}

.filesSidebarSubname__label {
	color: var(--color-text-maxcontrast);
	font-weight: bold;
}

.filesSidebarSubname__metadata {
	display: flex;
	align-items: center;
	flex-wrap: wrap;
	gap: 0 8px;
}

.filesSidebarSubname__separator {
	display: inline-block;
	font-weight: bold !important;
}

.filesSidebarSubname__userBubble {
	display: inline-flex !important;
}
</style>
