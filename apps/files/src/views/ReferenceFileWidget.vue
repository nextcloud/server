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

	<!-- Live preview where the viewer can show the file, read-only, with a menu
	     to open it in the viewer. <nc-viewer-file> is a custom element, not a Vue
	     component: as a plain tag, Vue would try to resolve it and warn, as our
	     build has no `isCustomElement` for it. `:is` with a string renders the
	     element without, and `.prop` hands it the file as a DOM property -->
	<component
		:is="viewerFileTag"
		v-else-if="interactive && viewable && !failedViewer"
		:file.prop="node"
		class="widget-file widget-file--interactive"
		@errored="failedViewer = true" />

	<!-- The file is accessible -->
	<a
		v-else
		class="widget-file widget-file--link"
		:href="richObject.link"
		target="_blank"
		@click="navigate">
		<span class="widget-file__image" :class="filePreviewClass" :style="filePreviewStyle">
			<template v-if="!previewUrl">
				<FolderIcon v-if="isFolder" :size="88" fillColor="var(--color-primary-element)" />
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
import type { Node } from '@nextcloud/files'
import type { PropType } from 'vue'

import { getCurrentUser } from '@nextcloud/auth'
import { getFilePickerBuilder } from '@nextcloud/dialogs'
import { File, Permission } from '@nextcloud/files'
import { t } from '@nextcloud/l10n'
import { generateRemoteUrl, generateUrl } from '@nextcloud/router'
import { canView, getViewer } from '@nextcloud/viewer'
import path from 'path'
import { defineComponent } from 'vue'
import FileIcon from 'vue-material-design-icons/File.vue'
import FolderIcon from 'vue-material-design-icons/Folder.vue'
import { generateFileUrl } from '../utils/generateUrl.ts'
import { logger } from '../utils/logger.ts'

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
	permissions?: number
	'hide-download'?: 'yes' | 'no'
	'can-download'?: 'yes' | 'no'
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
			// The element @nextcloud/viewer defines to show a file anywhere
			viewerFileTag: 'nc-viewer-file',
			previewUrl: null as string | null,
			failedViewer: false,
		}
	},

	computed: {
		/**
		 * The file, as the viewer takes it. The server resolved the reference
		 * for this user: the path is in their folder, a file shared with them
		 * included. None for a guest, who has no folder
		 */
		node(): File | null {
			const uid = getCurrentUser()?.uid
			if (!uid || this.isFolder) {
				return null
			}
			return new File({
				id: this.richObject.id,
				source: generateRemoteUrl(`dav/files/${uid}/${this.richObject.path}`).replace(/\/\/$/, '/'),
				root: `/files/${uid}`,
				displayname: this.richObject.name,
				mime: this.richObject.mimetype,
				size: this.richObject.size,
				mtime: new Date(this.richObject.mtime * 1000),
				// Not known here, and not needed to show it
				owner: null,
				// Readable at least, or this widget would say it is not accessible
				permissions: this.richObject.permissions ?? Permission.READ,
				attributes: {
					hasPreview: this.richObject['preview-available'],
					// What a share allows, which the viewer reads as DAV gives it:
					// no download offered, and the preview asked for by hand
					'hide-download': this.richObject['hide-download'] === 'yes',
					...(this.richObject['can-download'] === 'no'
						? { 'share-attributes': JSON.stringify([{ scope: 'permissions', key: 'download', value: false }]) }
						: {}),
				},
			})
		},

		/** Whether the viewer has something to show the file with */
		viewable(): boolean {
			return this.node !== null && canView(this.node)
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
			img.onerror = (error) => {
				logger.error('could not load recommendation preview', { error })
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
			} else if (this.viewable) {
				event.stopPropagation()
				event.preventDefault()
				getViewer().open([this.node!], this.node!)
			}
		},

		openFilePicker() {
			const picker = getFilePickerBuilder(t('settings', 'Your files'))
				.allowDirectories(true)
				.setMultiSelect(false)
				.addButton({
					id: 'open',
					label: this.t('settings', 'Open in files'),
					callback([node]: Node[]) {
						if (node) {
							window.open(generateFileUrl(node.fileid!))
						}
					},
					variant: 'primary',
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
