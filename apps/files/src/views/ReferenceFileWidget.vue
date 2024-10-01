<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div v-if="!accessible" class="widget-file widget-file--no-access">
		<span class="widget-file__image widget-file__image--icon">
			<FolderIcon v-if="isFolder" :size="88" />
			<FileIcon v-else :size="88" />
		</span>
		<span class="widget-file__details">
			<p class="widget-file__title">
				{{ t('files', 'File cannot be accessed') }}
			</p>
			<p class="widget-file__description">
				{{ t('files', 'The file could not be found or you do not have permissions to view it. Ask the sender to share it.') }}
			</p>
		</span>
	</div>

	<!-- Live preview if a handler is available -->
	<component :is="viewerHandler.component"
		v-else-if="interactive && viewerHandler && !failedViewer"
		:active="false /* prevent video from autoplaying */"
		:can-swipe="false"
		:can-zoom="false"
		:is-embedded="true"
		v-bind="viewerFile"
		:file-list="[viewerFile]"
		:is-full-screen="false"
		:is-sidebar-shown="false"
		class="widget-file widget-file--interactive"
		@error="failedViewer = true" />

	<!-- The file is accessible -->
	<a v-else
		class="widget-file widget-file--link"
		:href="richObject.link"
		target="_blank"
		@click="navigate">
		<span class="widget-file__image" :class="filePreviewClass" :style="filePreviewStyle">
			<template v-if="!previewUrl">
				<FolderIcon v-if="isFolder" :size="88" fill-color="var(--color-primary-element)" />
				<FileIcon v-else :size="88" />
			</template>
		</span>
		<span class="widget-file__details">
			<p class="widget-file__title">{{ richObject.name }}</p>
			<p class="widget-file__description">{{ fileSize }}<br>{{ fileMtime }}</p>
			<p class="widget-file__link">{{ filePath }}</p>
		</span>
	</a>
</template>

<script lang="ts">
import { defineComponent, type Component, type PropType } from 'vue'
import { generateRemoteUrl, generateUrl } from '@nextcloud/router'
import { getCurrentUser } from '@nextcloud/auth'
import { getFilePickerBuilder } from '@nextcloud/dialogs'
import { Node } from '@nextcloud/files'
import FileIcon from 'vue-material-design-icons/File.vue'
import FolderIcon from 'vue-material-design-icons/Folder.vue'
import path from 'path'

// see lib/private/Collaboration/Reference/File/FileReferenceProvider.php
type Ressource = {
	id: number
	name: string
	size: number
	path: string
	link: string
	mimetype: string
	mtime: number // as unix timestamp
	'preview-available': boolean
}

type ViewerHandler = {
	id: string
	group: string
	mimes: string[]
	component: Component
}

/**
 * Minimal mock of the legacy Viewer FileInfo
 * TODO: replace by Node object
 */
type ViewerFile = {
	filename: string // the path to the root folder
	basename: string // the file name
	lastmod: Date // the last modification date
	size: number // the file size in bytes
	type: string
	mime: string
	fileid: number
	failed: boolean
	loaded: boolean
	davPath: string
	source: string
}

export default defineComponent({
	name: 'ReferenceFileWidget',
	components: {
		FolderIcon,
		FileIcon,
	},
	props: {
		richObject: {
			type: Object as PropType<Ressource>,
			required: true,
		},
		accessible: {
			type: Boolean,
			default: true,
		},
		interactive: {
			type: Boolean,
			default: true,
		},
	},

	data() {
		return {
			previewUrl: null as string | null,
			failedViewer: false,
		}
	},

	computed: {
		availableViewerHandlers(): ViewerHandler[] {
			return (window?.OCA?.Viewer?.availableHandlers || []) as ViewerHandler[]
		},
		viewerHandler(): ViewerHandler | undefined {
			return this.availableViewerHandlers
				.find(handler => handler.mimes.includes(this.richObject.mimetype))
		},
		viewerFile(): ViewerFile {
			const davSource = generateRemoteUrl(`dav/files/${getCurrentUser()?.uid}/${this.richObject.path}`)
				.replace(/\/\/$/, '/')
			return {
				filename: this.richObject.path,
				basename: this.richObject.name,
				lastmod: new Date(this.richObject.mtime * 1000),
				size: this.richObject.size,
				type: 'file',
				mime: this.richObject.mimetype,
				fileid: this.richObject.id,
				failed: false,
				loaded: true,
				davPath: davSource,
				source: davSource,
			}
		},

		fileSize() {
			return window.OC.Util.humanFileSize(this.richObject.size)
		},
		fileMtime() {
			return window.OC.Util.relativeModifiedDate(this.richObject.mtime * 1000)
		},
		filePath() {
			return path.dirname(this.richObject.path)
		},
		filePreviewStyle() {
			if (this.previewUrl) {
				return {
					backgroundImage: 'url(' + this.previewUrl + ')',
				}
			}
			return {}
		},
		filePreviewClass() {
			if (this.previewUrl) {
				return 'widget-file__image--preview'
			}
			return 'widget-file__image--icon'

		},
		isFolder() {
			return this.richObject.mimetype === 'httpd/unix-directory'
		},
	},

	mounted() {
		if (this.richObject['preview-available']) {
			const previewUrl = generateUrl('/core/preview?fileId={fileId}&x=250&y=250', {
				fileId: this.richObject.id,
			})
			const img = new Image()
			img.onload = () => {
				this.previewUrl = previewUrl
			}
			img.onerror = err => {
				console.error('could not load recommendation preview', err)
			}
			img.src = previewUrl
		}
	},
	methods: {
		navigate(event) {
			if (this.isFolder) {
				event.stopPropagation()
				event.preventDefault()
				this.openFilePicker()
			} else if (window?.OCA?.Viewer?.mimetypes.indexOf(this.richObject.mimetype) !== -1 && !window?.OCA?.Viewer?.file) {
				event.stopPropagation()
				event.preventDefault()
				window?.OCA?.Viewer?.open({ path: this.richObject.path })
			}
		},

		openFilePicker() {
			const picker = getFilePickerBuilder(t('settings', 'Your files'))
				.allowDirectories(true)
				.setMultiSelect(false)
				.addButton({
					id: 'open',
					label: this.t('settings', 'Open in files'),
					callback(nodes: Node[]) {
						if (nodes[0]) {
							window.open(generateUrl('/f/{fileid}', {
								fileid: nodes[0].fileid,
							}))
						}
					},
					type: 'primary',
				})
				.disableNavigation()
				.startAt(this.richObject.path)
				.build()
			picker.pick()
		},
	},
})
</script>

<style lang="scss" scoped>
.widget-file {
	display: flex;
	flex-grow: 1;
	color: var(--color-main-text) !important;
	text-decoration: none !important;
	padding: 0 !important;

	&__image {
		width: 30%;
		min-width: 160px;
		max-width: 320px;
		background-position: center;
		background-size: cover;
		background-repeat: no-repeat;

		&--icon {
			min-width: 88px;
			max-width: 88px;
			padding: 12px;
			padding-inline-end: 0;
			display: flex;
			align-items: center;
			justify-content: center;
		}
	}

	&__title {
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
		font-weight: bold;
	}

	&__details {
		padding: 12px;
		flex-grow: 1;
		display: flex;
		flex-direction: column;

		p {
			margin: 0;
			padding: 0;
		}
	}

	&__description {
		overflow: hidden;
		text-overflow: ellipsis;
		display: -webkit-box;
		-webkit-line-clamp: 3;
		line-clamp: 3;
		-webkit-box-orient: vertical;
	}

	// No preview, standard link to ressource
	&--link {
		color: var(--color-text-maxcontrast);
	}

	&--interactive {
		position: relative;
		height: 400px;
		max-height: 50vh;
		margin: 0;
	}
}
</style>
