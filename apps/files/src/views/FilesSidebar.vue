<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { emit } from '@nextcloud/event-bus'
import { t } from '@nextcloud/l10n'
import { ref, toRef, watch } from 'vue'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcAppSidebar from '@nextcloud/vue/components/NcAppSidebar'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import FilesSidebarSubname from '../components/FilesSidebar/FilesSidebarSubname.vue'
import FilesSidebarTab from '../components/FilesSidebar/FilesSidebarTab.vue'
import { usePreviewImage } from '../composables/usePreviewImage.ts'
import { useSidebarStore } from '../store/sidebar.ts'

const sidebar = useSidebarStore()
const previewUrl = usePreviewImage(toRef(sidebar, 'currentNode'), {
	crop: false,
	fallback: false,
	size: [512, 288],
})

const background = ref<string>()
watch(previewUrl, () => {
	background.value = undefined
	// only try the background if there is more than a mime icon
	if (previewUrl.value && !previewUrl.value.includes('/core/mimeicon')) {
		const image = new Image()
		image.onload = () => {
			background.value = previewUrl.value
		}
		image.src = previewUrl.value
	}
}, { immediate: true })

/**
 * Emitted when the sidebar is fully closed.
 * Trigger the event-bus event.
 */
function onClosed() {
	if (sidebar.isOpen) {
		// was opened again meanwhile
		return
	}
	sidebar.currentNode = undefined
	emit('files:sidebar:closed')
}

/**
 * Emitted when the sidebar is fully opened.
 * Trigger the event-bus event.
 */
function onOpened() {
	emit('files:sidebar:opened', sidebar.currentNode!)
}

/**
 * Emitted when the sidebar open state is toggled by the sidebar toggle button.
 * As we hide the open button this is only triggered when the user closes the sidebar.
 *
 * @param open - The new open state
 */
function onToggle(open: boolean) {
	if (!open) {
		sidebar.close()
	}
}
</script>

<template>
	<NcAppSidebar
		data-cy-sidebar
		force-menu
		:active.sync="sidebar.activeTab"
		:background="background"
		:empty="!sidebar.hasContext"
		:loading="!sidebar.hasContext"
		:name="sidebar.currentNode?.displayname ?? t('files', 'Loading …')"
		no-toggle
		:open="sidebar.isOpen"
		@closed="onClosed"
		@opened="onOpened"
		@update:open="onToggle">
		<template v-if="sidebar.currentNode" #subname>
			<FilesSidebarSubname :node="sidebar.currentNode" />
		</template>

		<!-- Actions menu -->
		<template v-if="sidebar.currentContext" #secondary-actions>
			<!-- we cannot use a sub component due to limitations of the NcActions component -->
			<NcActionButton
				v-for="action of sidebar.currentActions"
				:key="action.id"
				close-after-click
				@click="action.onClick(sidebar.currentContext)">
				<template #icon>
					<NcIconSvgWrapper :svg="action.iconSvgInline(sidebar.currentContext)" />
				</template>
				{{ action.displayName(sidebar.currentContext) }}
			</NcActionButton>
		</template>

		<!-- Description -->
		<!-- <template v-if="hasContext" #description>
			<FilesSidebarDescription />
		</template> -->

		<template v-if="sidebar.hasContext">
			<FilesSidebarTab
				v-for="tab in sidebar.currentTabs"
				:key="tab.id"
				:active="sidebar.activeTab === tab.id"
				:tab="tab" />
		</template>
	</NcAppSidebar>
</template>

<style lang="scss" scoped>
.app-sidebar {
	&--has-preview:deep {
		.app-sidebar-header__figure {
			background-size: cover;
		}

		&[data-mimetype="text/plain"],
		&[data-mimetype="text/markdown"] {
			.app-sidebar-header__figure {
				background-size: contain;
			}
		}
	}

	&--full {
		position: fixed !important;
		z-index: 2025 !important;
		top: 0 !important;
		height: 100% !important;
	}

	:deep {
		.app-sidebar-header__description {
			margin: 0 16px 4px 16px !important;
		}
	}

	.svg-icon {
		:deep(svg) {
			width: 20px;
			height: 20px;
			fill: currentColor;
		}
	}
}

.sidebar__description {
		display: flex;
		flex-direction: column;
		width: 100%;
		gap: 8px 0;
	}
</style>
