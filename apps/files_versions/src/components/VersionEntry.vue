<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcListItem
		class="version"
		:force-display-actions="true"
		:actions-aria-label="t('files_versions', 'Actions for version from {versionHumanExplicitDate}', { versionHumanExplicitDate })"
		:data-files-versions-version="version.fileVersion"
		@click="click">
		<!-- Icon -->
		<template #icon>
			<div v-if="!(loadPreview || previewLoaded)" class="version__image" />
			<img
				v-else-if="version.previewUrl && !previewErrored"
				:src="version.previewUrl"
				alt=""
				decoding="async"
				fetchpriority="low"
				loading="lazy"
				class="version__image"
				@load="previewLoaded = true"
				@error="previewErrored = true">
			<div
				v-else
				class="version__image">
				<ImageOffOutline :size="20" />
			</div>
		</template>

		<!-- author -->
		<template #name>
			<div class="version__info">
				<div
					v-if="versionLabel"
					class="version__info__label"
					data-cy-files-version-label
					:title="versionLabel">
					{{ versionLabel }}
				</div>
				<div
					v-if="versionAuthor"
					class="version__info"
					data-cy-files-version-author-name>
					<span v-if="versionLabel">•</span>
					<NcAvatar
						class="avatar"
						:user="version.author ?? undefined"
						:size="20"
						disable-menu
						disable-tooltip
						hide-status />
					<div
						class="version__info__author_name"
						:title="versionAuthor">
						{{ versionAuthor }}
					</div>
				</div>
			</div>
		</template>

		<!-- Version file size as subline -->
		<template #subname>
			<div class="version__info version__info__subline">
				<NcDateTime
					class="version__info__date"
					relative-time="short"
					:timestamp="version.mtime" />
				<!-- Separate dot to improve alignment -->
				<span>•</span>
				<span>{{ humanReadableSize }}</span>
			</div>
		</template>

		<!-- Actions -->
		<template #actions>
			<NcActionButton
				v-if="enableLabeling && hasUpdatePermissions"
				data-cy-files-versions-version-action="label"
				:close-after-click="true"
				@click="labelUpdate">
				<template #icon>
					<Pencil :size="22" />
				</template>
				{{ version.label === '' ? t('files_versions', 'Name this version') : t('files_versions', 'Edit version name') }}
			</NcActionButton>
			<NcActionButton
				v-if="!isCurrent && canView && canCompare"
				data-cy-files-versions-version-action="compare"
				:close-after-click="true"
				@click="compareVersion">
				<template #icon>
					<FileCompare :size="22" />
				</template>
				{{ t('files_versions', 'Compare to current version') }}
			</NcActionButton>
			<NcActionButton
				v-if="!isCurrent && hasUpdatePermissions"
				data-cy-files-versions-version-action="restore"
				:close-after-click="true"
				@click="restoreVersion">
				<template #icon>
					<BackupRestore :size="22" />
				</template>
				{{ t('files_versions', 'Restore version') }}
			</NcActionButton>
			<NcActionLink
				v-if="isDownloadable"
				data-cy-files-versions-version-action="download"
				:href="downloadURL"
				:close-after-click="true"
				:download="downloadURL">
				<template #icon>
					<Download :size="22" />
				</template>
				{{ t('files_versions', 'Download version') }}
			</NcActionLink>
			<NcActionButton
				v-if="!isCurrent && enableDeletion && hasDeletePermissions"
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

<script lang="ts" setup>
import type { PropType } from 'vue'
import type { LegacyFileInfo } from '../../../files/src/services/FileInfo.ts'
import type { Version } from '../utils/versions.ts'

import { getCurrentUser } from '@nextcloud/auth'
import { formatFileSize, Permission } from '@nextcloud/files'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import moment from '@nextcloud/moment'
import { join } from '@nextcloud/paths'
import { getRootUrl } from '@nextcloud/router'
import { computed, nextTick, ref } from 'vue'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcActionLink from '@nextcloud/vue/components/NcActionLink'
import NcAvatar from '@nextcloud/vue/components/NcAvatar'
import NcDateTime from '@nextcloud/vue/components/NcDateTime'
import NcListItem from '@nextcloud/vue/components/NcListItem'
import BackupRestore from 'vue-material-design-icons/BackupRestore.vue'
import FileCompare from 'vue-material-design-icons/FileCompare.vue'
import ImageOffOutline from 'vue-material-design-icons/ImageOffOutline.vue'
import Pencil from 'vue-material-design-icons/PencilOutline.vue'
import Delete from 'vue-material-design-icons/TrashCanOutline.vue'
import Download from 'vue-material-design-icons/TrayArrowDown.vue'

const props = defineProps({
	version: {
		type: Object as PropType<Version>,
		required: true,
	},

	fileInfo: {
		type: Object as PropType<LegacyFileInfo>,
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
})

const emit = defineEmits(['click', 'compare', 'restore', 'delete', 'label-update-request'])

const hasPermission = (permissions: number, permission: number): boolean => (permissions & permission) !== 0

const previewLoaded = ref(false)
const previewErrored = ref(false)
const capabilities = ref(loadState('core', 'capabilities', { files: { version_labeling: false, version_deletion: false } }))

const humanReadableSize = computed(() => {
	return formatFileSize(props.version.size)
})

const versionLabel = computed(() => {
	const label = props.version.label ?? ''

	if (props.isCurrent) {
		if (label === '') {
			return t('files_versions', 'Current version')
		} else {
			return `${label} (${t('files_versions', 'Current version')})`
		}
	}

	if (props.isFirstVersion && label === '') {
		return t('files_versions', 'Initial version')
	}

	return label
})

const versionAuthor = computed(() => {
	if (!props.version.author || !props.version.authorName) {
		return ''
	}

	if (props.version.author === getCurrentUser()?.uid) {
		return t('files_versions', 'You')
	}

	return props.version.authorName ?? props.version.author
})

const versionHumanExplicitDate = computed(() => {
	return moment(props.version.mtime).format('LLLL')
})

const downloadURL = computed(() => {
	if (props.isCurrent) {
		return getRootUrl() + join('/remote.php/webdav', props.fileInfo.path, props.fileInfo.name)
	} else {
		return getRootUrl() + props.version.url
	}
})

const enableLabeling = computed(() => {
	return capabilities.value.files.version_labeling === true
})

const enableDeletion = computed(() => {
	return capabilities.value.files.version_deletion === true
})

const hasDeletePermissions = computed(() => {
	return hasPermission(props.fileInfo.permissions, Permission.DELETE)
})

const hasUpdatePermissions = computed(() => {
	return hasPermission(props.fileInfo.permissions, Permission.UPDATE)
})

const isDownloadable = computed(() => {
	if ((props.fileInfo.permissions & Permission.READ) === 0) {
		return false
	}

	// If the mount type is a share, ensure it got download permissions.
	if (props.fileInfo.mountType === 'shared') {
		const downloadAttribute = props.fileInfo.shareAttributes
			.find((attribute) => attribute.scope === 'permissions' && attribute.key === 'download') || {}
		// If the download attribute is set to false, the file is not downloadable
		if (downloadAttribute?.value === false) {
			return false
		}
	}

	return true
})

/**
 *
 */
function labelUpdate() {
	emit('label-update-request')
}

/**
 *
 */
function restoreVersion() {
	emit('restore', props.version)
}

/**
 *
 */
async function deleteVersion() {
	// Let @nc-vue properly remove the popover before we delete the version.
	// This prevents @nc-vue from throwing a error.
	await nextTick()
	await nextTick()
	emit('delete', props.version)
}

/**
 *
 */
function click() {
	if (!props.canView) {
		window.location.href = downloadURL.value
		return
	}
	emit('click', { version: props.version })
}

/**
 *
 */
function compareVersion() {
	if (!props.canView) {
		throw new Error('Cannot compare version of this file')
	}
	emit('compare', { version: props.version })
}
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
		overflow: hidden;

		&__label {
			font-weight: 700;
			// Fix overflow on narrow screens
			overflow: hidden;
			text-overflow: ellipsis;
			min-width: 110px;
		}

		&__author_name {
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
		color: var(--color-main-text);
	}
}
</style>
