<template>
	<NcBreadcrumb v-files-drop.prevent="/* We do not set '.stop' to let the wrapping file list unset the dragging state */
			{
				enabled: canDrop,
				targetFolder
			}"
		class="files-bread-crumb"
		dir="auto"
		:disable-drop="!canDrop"
		exact
		:force-icon-text="fileListWidth > 486"
		:name="displayName"
		:title="ariaDescription || title"
		:to="to"
		@click.native="$emit('click', path)">
		<slot name="icon" />
	</NcBreadcrumb>
</template>

<script setup lang="ts">
import type { IFolder } from '@nextcloud/files'
import type { Location } from 'vue-router'
import { emit } from '@nextcloud/event-bus'
import { Permission } from '@nextcloud/files'
import { basename } from 'path'
import { useRoute } from 'vue-router/composables'
import { computed, ref, watchEffect } from 'vue'
import { useFileListWidth } from '../../composables/useFileListWidth'
import { useNavigation } from '../../composables/useNavigation'
import { usePathsStore } from '../../store/paths'
import { useFilesStore } from '../../store/files'

import NcBreadcrumb from '@nextcloud/vue/dist/Components/NcBreadcrumb.js'
import vFilesDrop from '../../directives/vFilesDrop.ts'

const props = defineProps({
	/** Accessible description of the breadcrumb */
	ariaDescription: {
		type: String,
		default: '',
	},
	/** Disable dropping on this breadcrumb */
	disableDrop: {
		type: Boolean,
		default: false,
	},
	/** Path of this breadcrumb */
	path: {
		type: String,
		required: true,
	},
	/** Native HTML title to show on the breadcrumb */
	title: {
		type: String,
		default: '',
	},
})

const route = useRoute()
const fileListWidth = useFileListWidth()
const { currentView, views } = useNavigation(true)
const fileStore = useFilesStore()
const pathStore = usePathsStore()

/**
 * The target folder this breadcrumb represents
 */
const targetFolder = ref<IFolder>()
watchEffect(() => {
	const folderSource = pathStore.getPath(currentView.value.id, props.path)
	if (folderSource) {
		targetFolder.value = fileStore.getNode(folderSource) as IFolder
	} else {
		currentView.value.getContents(props.path)
			.then(({ folder }) => {
				targetFolder.value = folder as IFolder
				// If this is a proper node we cache it for later usage
				if (folder.isDavRessource) {
					emit('files:node:created', folder)
				}
			})
	}
})

/**
 * Displayname of the folder this breadcrumb represents
 */
const displayName = computed(() => targetFolder.value?.displayname || basename(props.path))

/**
 * Drop is not disabled and folder has create permissions
 */
const canDrop = computed(() => {
	return !props.disableDrop
		&& targetFolder.value
		&& Boolean(targetFolder.value.permissions & Permission.CREATE)
})

/**
 * The vue-router target of this breadcrumb
 */
const to = computed<Location>(() => {
	// First check if this is a special route from a files view
	const matchedView = views.value.find((view) => view.params?.view === currentView.value?.id && view.params?.dir === props.path)
	if (matchedView) {
		const { fileid, view, ...query } = matchedView.params!
		return {
			name: route.name,
			params: {
				fileid,
				view,
			},
			query,
		} as Location
	}
	// Else this is a plain node view
	return {
		name: route.name,
		params: {
			view: currentView.value?.id,
			fileid: targetFolder.value?.isDavRessource
				? String(targetFolder.value.fileid)
				: undefined,
		},
		query: {
			dir: props.path,
		},
	} as Location
})
</script>
