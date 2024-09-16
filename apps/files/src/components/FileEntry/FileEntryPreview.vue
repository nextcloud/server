<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<span class="files-list__row-icon">
		<template v-if="source.type === 'folder'">
			<FolderOpenIcon v-if="dragover" v-once />
			<template v-else>
				<FolderIcon v-once />
				<OverlayIcon :is="folderOverlay"
					v-if="folderOverlay"
					class="files-list__row-icon-overlay" />
			</template>
		</template>

		<!-- Decorative images, should not be aria documented -->
		<span v-else-if="previewUrl" class="files-list__row-icon-preview-container">
			<canvas v-if="hasBlurhash && (backgroundFailed === true || !backgroundLoaded)"
				ref="canvas"
				class="files-list__row-icon-blurhash"
				aria-hidden="true" />
			<img v-if="backgroundFailed !== true"
				ref="previewImg"
				alt=""
				class="files-list__row-icon-preview"
				:class="{'files-list__row-icon-preview--loaded': backgroundFailed === false}"
				loading="lazy"
				:src="previewUrl"
				@error="onBackgroundError"
				@load="onBackgroundLoad">
		</span>

		<FileIcon v-else v-once />

		<!-- Favorite icon -->
		<span v-if="isFavorite" class="files-list__row-icon-favorite">
			<FavoriteIcon v-once />
		</span>

		<OverlayIcon :is="fileOverlay"
			v-if="fileOverlay"
			class="files-list__row-icon-overlay files-list__row-icon-overlay--file" />
	</span>
</template>

<script lang="ts">
import type { PropType } from 'vue'
import type { UserConfig } from '../../types.ts'

import { Node, FileType } from '@nextcloud/files'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { ShareType } from '@nextcloud/sharing'
import { getSharingToken, isPublicShare } from '@nextcloud/sharing/public'
import { decode } from 'blurhash'
import { defineComponent } from 'vue'

import AccountGroupIcon from 'vue-material-design-icons/AccountGroup.vue'
import AccountPlusIcon from 'vue-material-design-icons/AccountPlus.vue'
import FileIcon from 'vue-material-design-icons/File.vue'
import FolderIcon from 'vue-material-design-icons/Folder.vue'
import FolderOpenIcon from 'vue-material-design-icons/FolderOpen.vue'
import KeyIcon from 'vue-material-design-icons/Key.vue'
import LinkIcon from 'vue-material-design-icons/Link.vue'
import NetworkIcon from 'vue-material-design-icons/Network.vue'
import TagIcon from 'vue-material-design-icons/Tag.vue'
import PlayCircleIcon from 'vue-material-design-icons/PlayCircle.vue'

import CollectivesIcon from './CollectivesIcon.vue'
import FavoriteIcon from './FavoriteIcon.vue'

import { isLivePhoto } from '../../services/LivePhotos'
import { useUserConfigStore } from '../../store/userconfig.ts'
import logger from '../../logger.ts'

export default defineComponent({
	name: 'FileEntryPreview',

	components: {
		AccountGroupIcon,
		AccountPlusIcon,
		CollectivesIcon,
		FavoriteIcon,
		FileIcon,
		FolderIcon,
		FolderOpenIcon,
		KeyIcon,
		LinkIcon,
		NetworkIcon,
		TagIcon,
	},

	props: {
		source: {
			type: Object as PropType<Node>,
			required: true,
		},
		dragover: {
			type: Boolean,
			default: false,
		},
		gridMode: {
			type: Boolean,
			default: false,
		},
	},

	setup() {
		const userConfigStore = useUserConfigStore()
		const isPublic = isPublicShare()
		const publicSharingToken = getSharingToken()

		return {
			userConfigStore,

			isPublic,
			publicSharingToken,
		}
	},

	data() {
		return {
			backgroundFailed: undefined as boolean | undefined,
			backgroundLoaded: false,
		}
	},

	computed: {
		isFavorite(): boolean {
			return this.source.attributes.favorite === 1
		},

		userConfig(): UserConfig {
			return this.userConfigStore.userConfig
		},
		cropPreviews(): boolean {
			return this.userConfig.crop_image_previews === true
		},

		previewUrl() {
			if (this.source.type === FileType.Folder) {
				return null
			}

			if (this.backgroundFailed === true) {
				return null
			}

			try {
				const previewUrl = this.source.attributes.previewUrl
					|| (this.isPublic
						? generateUrl('/apps/files_sharing/publicpreview/{token}?file={file}', {
							token: this.publicSharingToken,
							file: this.source.path,
						})
						: generateUrl('/core/preview?fileId={fileid}', {
							fileid: String(this.source.fileid),
						})
					)
				const url = new URL(window.location.origin + previewUrl)

				// Request tiny previews
				url.searchParams.set('x', this.gridMode ? '128' : '32')
				url.searchParams.set('y', this.gridMode ? '128' : '32')
				url.searchParams.set('mimeFallback', 'true')

				// Etag to force refresh preview on change
				const etag = this.source?.attributes?.etag || ''
				url.searchParams.set('v', etag.slice(0, 6))

				// Handle cropping
				url.searchParams.set('a', this.cropPreviews === true ? '0' : '1')
				return url.href
			} catch (e) {
				return null
			}
		},

		fileOverlay() {
			if (isLivePhoto(this.source)) {
				return PlayCircleIcon
			}

			return null
		},

		folderOverlay() {
			if (this.source.type !== FileType.Folder) {
				return null
			}

			// Encrypted folders
			if (this.source?.attributes?.['is-encrypted'] === 1) {
				return KeyIcon
			}

			// System tags
			if (this.source?.attributes?.['is-tag']) {
				return TagIcon
			}

			// Link and mail shared folders
			const shareTypes = Object.values(this.source?.attributes?.['share-types'] || {}).flat() as number[]
			if (shareTypes.some(type => type === ShareType.Link || type === ShareType.Email)) {
				return LinkIcon
			}

			// Shared folders
			if (shareTypes.length > 0) {
				return AccountPlusIcon
			}

			switch (this.source?.attributes?.['mount-type']) {
			case 'external':
			case 'external-session':
				return NetworkIcon
			case 'group':
				return AccountGroupIcon
			case 'collective':
				return CollectivesIcon
			case 'shared':
				return AccountPlusIcon
			}

			return null
		},

		hasBlurhash() {
			return this.source.attributes['metadata-blurhash'] !== undefined
		},
	},

	mounted() {
		if (this.hasBlurhash && this.$refs.canvas) {
			this.drawBlurhash()
		}
	},

	methods: {
		// Called from FileEntry
		reset() {
			// Reset background state to cancel any ongoing requests
			this.backgroundFailed = undefined
			this.backgroundLoaded = false
			const previewImg = this.$refs.previewImg as HTMLImageElement | undefined
			if (previewImg) {
				previewImg.src = ''
			}
		},

		onBackgroundLoad() {
			this.backgroundFailed = false
			this.backgroundLoaded = true
		},

		onBackgroundError(event) {
			// Do not fail if we just reset the background
			if (event.target?.src === '') {
				return
			}
			this.backgroundFailed = true
			this.backgroundLoaded = false
		},

		drawBlurhash() {
			const canvas = this.$refs.canvas as HTMLCanvasElement

			const width = canvas.width
			const height = canvas.height

			const pixels = decode(this.source.attributes['metadata-blurhash'], width, height)

			const ctx = canvas.getContext('2d')
			if (ctx === null) {
				logger.error('Cannot create context for blurhash canvas')
				return
			}

			const imageData = ctx.createImageData(width, height)
			imageData.data.set(pixels)
			ctx.putImageData(imageData, 0, 0)
		},

		t,
	},
})
</script>
