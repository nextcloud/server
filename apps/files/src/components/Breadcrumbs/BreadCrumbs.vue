<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcBreadcrumbs data-cy-files-content-breadcrumbs
		:aria-label="t('files', 'Current directory path')"
		class="files-list__breadcrumbs"
		:class="{ 'files-list__breadcrumbs--with-progress': wrapUploadProgressBar }"
		@dragover="onDragOver">
		<!-- Current path sections -->
		<BreadCrumb v-for="segment of pathSegments"
			:key="segment"
			:aria-description="segment === path ? t('files', 'Reload current folder') : undefined"
			:disable-drop="segment === path /* Disable drop if this is the current directory */"
			:title="segment === '/' ? t('files', 'Go to the root folder') : undefined"
			:path="segment">
			<template #icon v-if="segment === '/'">
				<NcIconSvgWrapper :size="20" :svg="viewIcon" />
			</template>
		</BreadCrumb>

		<!-- Forward the actions slot -->
		<template #actions>
			<slot name="actions" />
		</template>
	</NcBreadcrumbs>
</template>

<script lang="ts">
import { t } from '@nextcloud/l10n'
import { defineComponent } from 'vue'
import { useNavigation } from '../../composables/useNavigation'
import { useUploaderStore } from '../../store/uploader.ts'
import { useFileListWidth } from '../../composables/useFileListWidth.ts'

import BreadCrumb from './BreadCrumb.vue'
import HomeSvg from '@mdi/svg/svg/home.svg?raw'
import NcBreadcrumbs from '@nextcloud/vue/dist/Components/NcBreadcrumbs.js'
import NcIconSvgWrapper from '@nextcloud/vue/dist/Components/NcIconSvgWrapper.js'
import { useDragAndDropStore } from '../../store/dragging.ts'

export default defineComponent({
	name: 'BreadCrumbs',

	components: {
		BreadCrumb,
		NcBreadcrumbs,
		NcIconSvgWrapper,
	},

	props: {
		path: {
			type: String,
			default: '/',
		},
	},

	setup() {
		const { currentView } = useNavigation(true)
		const fileListWidth = useFileListWidth()
		const uploaderStore = useUploaderStore()
		const draggingStore = useDragAndDropStore()

		return {
			t,
			currentView,
			fileListWidth,
			draggingStore,
			uploaderStore,
		}
	},

	computed: {
		pathSegments(): string[] {
			const cumulativePath = (acc: string) => (value: string) => (acc = `${acc}/${value}`)
			// Generate a cumulative path for each path segment: ['/', '/foo', '/foo/bar', ...] etc
			return this.path
				.split('/')
				.map(cumulativePath('/'))
		},

		isUploadInProgress(): boolean {
			return this.uploaderStore.queue.length !== 0
		},

		// Hide breadcrumbs if an upload is ongoing
		wrapUploadProgressBar(): boolean {
			// if an upload is ongoing, and on small screens / mobile, then
			// show the progress bar for the upload below breadcrumbs
			return this.isUploadInProgress && this.fileListWidth < 512
		},

		// used to show the views icon for the first breadcrumb
		viewIcon(): string {
			return this.currentView?.icon ?? HomeSvg
		},

		isDraggingFiles() {
			return this.draggingStore.dragging.length > 0
		}
	},

	methods: {
		onClick(path: string) {
			if (path === this.$route.query.dir) {
				this.$emit('reload')
			}
		},

		/**
		 * Event handler for the drag-over event.
		 * Used to set the drop effect
		 * @param event The drag even
		 */
		onDragOver(event: DragEvent) {
			if (!event.dataTransfer) {
				return
			}

			if (this.isDraggingFiles && event.ctrlKey) {
				event.dataTransfer.dropEffect = 'copy'
			}
			event.dataTransfer.dropEffect = 'move'
		}
	},
})
</script>

<style lang="scss" scoped>
.files-list__breadcrumbs {
	// Take as much space as possible
	flex: 1 1 100% !important;
	width: 100%;
	height: 100%;
	margin-block: 0;
	margin-inline: 10px;
	min-width: 0;

	:deep() {
		a {
			cursor: pointer !important;
		}
	}

	&--with-progress {
		flex-direction: column !important;
		align-items: flex-start !important;
	}
}
</style>
