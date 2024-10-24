<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcListItem class="version"
		:force-display-actions="true"
		:actions-aria-label="t('files_versions', 'Actions for version from {versionHumanExplicitDate}', { versionHumanExplicitDate })"
		:data-files-versions-version="version.fileVersion"
		@click="click">
		<!-- Icon -->
		<template #icon>
			<div v-if="!(loadPreview || previewLoaded)" class="version__image" />
			<img v-else-if="(isCurrent || version.hasPreview) && !previewErrored"
				:src="version.previewUrl"
				alt=""
				decoding="async"
				fetchpriority="low"
				loading="lazy"
				class="version__image"
				@load="previewLoaded = true"
				@error="previewErrored = true">
			<div v-else
				class="version__image">
				<ImageOffOutline :size="20" />
			</div>
		</template>

		<!-- author -->
		<template #name>
			<div class="version__info">
				<div v-if="versionLabel"
					class="version__info__label"
					:title="versionLabel">
					{{ versionLabel }}
				</div>
				<div v-if="versionAuthor" class="version__info">
					<span v-if="versionLabel">•</span>
					<NcAvatar class="avatar"
						:user="version.author"
						:size="16"
						:disable-menu="true"
						:disable-tooltip="true"
						:show-user-status="false" />
					<div>{{ versionAuthor }}</div>
				</div>
			</div>
		</template>

		<!-- Version file size as subline -->
		<template #subname>
			<div class="version__info version__info__subline">
				<NcDateTime class="version__info__date"
					relative-time="short"
					:timestamp="version.mtime" />
				<!-- Separate dot to improve alignement -->
				<span>•</span>
				<span>{{ humanReadableSize }}</span>
			</div>
		</template>

		<!-- Actions -->
		<template #actions>
			<NcActionButton v-if="enableLabeling && hasUpdatePermissions"
				data-cy-files-versions-version-action="label"
				:close-after-click="true"
				@click="labelUpdate">
				<template #icon>
					<Pencil :size="22" />
				</template>
				{{ version.label === '' ? t('files_versions', 'Name this version') : t('files_versions', 'Edit version name') }}
			</NcActionButton>
			<NcActionButton v-if="!isCurrent && canView && canCompare"
				data-cy-files-versions-version-action="compare"
				:close-after-click="true"
				@click="compareVersion">
				<template #icon>
					<FileCompare :size="22" />
				</template>
				{{ t('files_versions', 'Compare to current version') }}
			</NcActionButton>
			<NcActionButton v-if="!isCurrent && hasUpdatePermissions"
				data-cy-files-versions-version-action="restore"
				:close-after-click="true"
				@click="restoreVersion">
				<template #icon>
					<BackupRestore :size="22" />
				</template>
				{{ t('files_versions', 'Restore version') }}
			</NcActionButton>
			<NcActionLink v-if="isDownloadable"
				data-cy-files-versions-version-action="download"
				:href="downloadURL"
				:close-after-click="true"
				:download="downloadURL">
				<template #icon>
					<Download :size="22" />
				</template>
				{{ t('files_versions', 'Download version') }}
			</NcActionLink>
			<NcActionButton v-if="!isCurrent && enableDeletion && hasDeletePermissions"
				data-cy-files-versions-version-action="delete"
				:close-after-click="true"
				@click="deleteVersion">
				<template #icon>
					<Delete :size="22" />
				</template>
				{{ t('files_versions', 'Delete version') }}
			</NcActionButton>
		</template>
	</NcListItem>
</template>
<script lang="ts">
import type { PropType } from 'vue'
import type { Version } from '../utils/versions'

import { defineComponent } from 'vue'

import BackupRestore from 'vue-material-design-icons/BackupRestore.vue'
import Delete from 'vue-material-design-icons/Delete.vue'
import Download from 'vue-material-design-icons/Download.vue'
import FileCompare from 'vue-material-design-icons/FileCompare.vue'
import ImageOffOutline from 'vue-material-design-icons/ImageOffOutline.vue'
import Pencil from 'vue-material-design-icons/Pencil.vue'

import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton.js'
import NcActionLink from '@nextcloud/vue/dist/Components/NcActionLink.js'
import NcAvatar from '@nextcloud/vue/dist/Components/NcAvatar.js'
import NcDateTime from '@nextcloud/vue/dist/Components/NcDateTime.js'
import NcListItem from '@nextcloud/vue/dist/Components/NcListItem.js'
import Tooltip from '@nextcloud/vue/dist/Directives/Tooltip.js'

import moment from '@nextcloud/moment'
import { getRootUrl, generateOcsUrl } from '@nextcloud/router'
import { joinPaths } from '@nextcloud/paths'
import { loadState } from '@nextcloud/initial-state'
import { Permission, formatFileSize } from '@nextcloud/files'
import { translate as t } from '@nextcloud/l10n'
import axios from '@nextcloud/axios'

const hasPermission = (permissions: number, permission: number): boolean => (permissions & permission) !== 0

export default defineComponent({
	name: 'Version',

	components: {
		NcActionLink,
		NcActionButton,
		NcAvatar,
		NcDateTime,
		NcListItem,
		BackupRestore,
		Download,
		FileCompare,
		Pencil,
		Delete,
		ImageOffOutline,
	},

	directives: {
		tooltip: Tooltip,
	},

	props: {
		version: {
			type: Object as PropType<Version>,
			required: true,
		},
		fileInfo: {
			type: Object,
			required: true,
		},
		isCurrent: {
			type: Boolean,
			default: false,
		},
		isFirstVersion: {
			type: Boolean,
			default: false,
		},
		loadPreview: {
			type: Boolean,
			default: false,
		},
		canView: {
			type: Boolean,
			default: false,
		},
		canCompare: {
			type: Boolean,
			default: false,
		},
	},

	emits: ['click', 'compare', 'restore', 'delete', 'label-update-request'],

	data() {
		return {
			previewLoaded: false,
			previewErrored: false,
			capabilities: loadState('core', 'capabilities', { files: { version_labeling: false, version_deletion: false } }),
			versionAuthor: '',
		}
	},

	computed: {
		humanReadableSize() {
			return formatFileSize(this.version.size)
		},

		versionLabel(): string {
			const label = this.version.label ?? ''

			if (this.isCurrent) {
				if (label === '') {
					return t('files_versions', 'Current version')
				} else {
					return `${label} (${t('files_versions', 'Current version')})`
				}
			}

			if (this.isFirstVersion && label === '') {
				return t('files_versions', 'Initial version')
			}

			return label
		},

		versionHumanExplicitDate(): string {
			return moment(this.version.mtime).format('LLLL')
		},

		downloadURL(): string {
			if (this.isCurrent) {
				return getRootUrl() + joinPaths('/remote.php/webdav', this.fileInfo.path, this.fileInfo.name)
			} else {
				return getRootUrl() + this.version.url
			}
		},

		enableLabeling(): boolean {
			return this.capabilities.files.version_labeling === true
		},

		enableDeletion(): boolean {
			return this.capabilities.files.version_deletion === true
		},

		hasDeletePermissions(): boolean {
			return hasPermission(this.fileInfo.permissions, Permission.DELETE)
		},

		hasUpdatePermissions(): boolean {
			return hasPermission(this.fileInfo.permissions, Permission.UPDATE)
		},

		isDownloadable(): boolean {
			if ((this.fileInfo.permissions & Permission.READ) === 0) {
				return false
			}

			// If the mount type is a share, ensure it got download permissions.
			if (this.fileInfo.mountType === 'shared') {
				const downloadAttribute = this.fileInfo.shareAttributes
					.find((attribute) => attribute.scope === 'permissions' && attribute.key === 'download') || {}
				// If the download attribute is set to false, the file is not downloadable
				if (downloadAttribute?.value === false) {
					return false
				}
			}

			return true
		},
	},

	created() {
		this.fetchDisplayName()
	},

	methods: {
		labelUpdate() {
			this.$emit('label-update-request')
		},

		restoreVersion() {
			this.$emit('restore', this.version)
		},

		async deleteVersion() {
			// Let @nc-vue properly remove the popover before we delete the version.
			// This prevents @nc-vue from throwing a error.
			await this.$nextTick()
			await this.$nextTick()
			this.$emit('delete', this.version)
		},

		async fetchDisplayName() {
			// check to make sure that we have a valid author - in case database did not migrate, null author, etc.
			if (this.version.author) {
				try {
					const { data } = await axios.get(generateOcsUrl(`/cloud/users/${this.version.author}`))
					this.versionAuthor = data.ocs.data.displayname
				} catch (e) {
					// Promise got rejected - default to null author to not try to load author profile
					this.versionAuthor = null
				}
			}
		},

		click() {
			if (!this.canView) {
				window.location = this.downloadURL
				return
			}
			this.$emit('click', { version: this.version })
		},

		compareVersion() {
			if (!this.canView) {
				throw new Error('Cannot compare version of this file')
			}
			this.$emit('compare', { version: this.version })
		},

		t,
	},
})
</script>

<style scoped lang="scss">
.version {
	display: flex;
	flex-direction: row;

	&__info {
		display: flex;
		flex-direction: row;
		align-items: center;
		gap: 0.5rem;
		color: var(--color-main-text);
		font-weight: 500;

		&__label {
			font-weight: 700;
			// Fix overflow on narrow screens
			overflow: hidden;
			text-overflow: ellipsis;
		}

		&__date {
			// Fix overflow on narrow screens
			overflow: hidden;
			text-overflow: ellipsis;
		}

		&__subline {
			color: var(--color-text-maxcontrast)
		}
	}

	&__image {
		width: 3rem;
		height: 3rem;
		border: 1px solid var(--color-border);
		border-radius: var(--border-radius-large);

		// Useful to display no preview icon.
		display: flex;
		justify-content: center;
		color: var(--color-text-light);
	}
}
</style>
