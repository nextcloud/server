<!--
  - @copyright Copyright (c) 2023 John Molakvoæ <skjnldsv@protonmail.com>
  -
  - @author John Molakvoæ <skjnldsv@protonmail.com>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program. If not, see <http://www.gnu.org/licenses/>.
  -
  -->
<template>
	<span class="files-list__row-icon">
		<template v-if="source.type === 'folder'">
			<FolderOpenIcon v-once v-if="dragover" />
			<template v-else>
				<FolderIcon v-once />
				<OverlayIcon :is="folderOverlay"
					v-if="folderOverlay"
					class="files-list__row-icon-overlay" />
			</template>
		</template>

		<!-- Decorative image, should not be aria documented -->
		<img v-else-if="previewUrl && backgroundFailed !== true"
			ref="previewImg"
			alt=""
			class="files-list__row-icon-preview"
			:class="{'files-list__row-icon-preview--loaded': backgroundFailed === false}"
			:src="previewUrl"
			@error="backgroundFailed = true"
			@load="backgroundFailed = false">

		<FileIcon v-once v-else />

		<!-- Favorite icon -->
		<span v-if="isFavorite"
			class="files-list__row-icon-favorite"
			:aria-label="t('files', 'Favorite')">
			<FavoriteIcon v-once />
		</span>
	</span>
</template>

<script lang="ts">
import type { UserConfig } from '../../types.ts'

import { File, Folder, Node, FileType } from '@nextcloud/files'
import { generateUrl } from '@nextcloud/router'
import { translate as t } from '@nextcloud/l10n'
import { Type as ShareType } from '@nextcloud/sharing'
import Vue, { PropType } from 'vue'

import AccountGroupIcon from 'vue-material-design-icons/AccountGroup.vue'
import AccountPlusIcon from 'vue-material-design-icons/AccountPlus.vue'
import FileIcon from 'vue-material-design-icons/File.vue'
import FolderIcon from 'vue-material-design-icons/Folder.vue'
import FolderOpenIcon from 'vue-material-design-icons/FolderOpen.vue'
import KeyIcon from 'vue-material-design-icons/Key.vue'
import LinkIcon from 'vue-material-design-icons/Link.vue'
import NetworkIcon from 'vue-material-design-icons/Network.vue'
import TagIcon from 'vue-material-design-icons/Tag.vue'

import { useUserConfigStore } from '../../store/userconfig.ts'
import FavoriteIcon from './FavoriteIcon.vue'

export default Vue.extend({
	name: 'FileEntryPreview',

	components: {
		AccountGroupIcon,
		AccountPlusIcon,
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
		return {
			userConfigStore,
		}
	},

	data() {
		return {
			backgroundFailed: undefined as boolean | undefined,
		}
	},

	computed: {
		fileid() {
			return this.source?.fileid?.toString?.()
		},
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
					|| generateUrl('/core/preview?fileId={fileid}', {
						fileid: this.fileid,
					})
				const url = new URL(window.location.origin + previewUrl)

				// Request tiny previews
				url.searchParams.set('x', this.gridMode ? '128' : '32')
				url.searchParams.set('y', this.gridMode ? '128' : '32')
				url.searchParams.set('mimeFallback', 'true')

				// Handle cropping
				url.searchParams.set('a', this.cropPreviews === true ? '0' : '1')
				return url.href
			} catch (e) {
				return null
			}
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
			if (shareTypes.some(type => type === ShareType.SHARE_TYPE_LINK || type === ShareType.SHARE_TYPE_EMAIL)) {
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
			}

			return null
		},
	},

	methods: {
		reset() {
			// Reset background state
			this.backgroundFailed = undefined
			if (this.$refs.previewImg) {
				this.$refs.previewImg.src = ''
			}
		},

		t,
	},
})
</script>
