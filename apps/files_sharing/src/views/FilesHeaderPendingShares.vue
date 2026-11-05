<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<ul
		v-if="shares.length > 0"
		class="pending-shares"
		:aria-label="t('files_sharing', 'Pending shares')">
		<li v-for="share in shares" :key="share.source" class="pending-shares__share">
			<div class="pending-shares__file">
				<NcIconSvgWrapper
					v-if="isFolder(share)"
					class="pending-shares__icon pending-shares__icon--folder"
					:path="mdiFolder"
					:size="32" />
				<img
					v-else
					class="pending-shares__icon"
					:src="getIconUrl(share)"
					alt="">
				<div class="pending-shares__text">
					<span class="pending-shares__name">
						{{ getBasename(share) }}<span class="pending-shares__extension">{{ getExtension(share) }}</span>
					</span>
					<span class="pending-shares__details">
						{{ t('files_sharing', 'Shared by {owner}', { owner: share.attributes['owner-display-name'] }, { escape: false }) }}
					</span>
				</div>
			</div>
			<NcButton
				:disabled="busy.includes(share.source)"
				@click="accept(share)">
				<template v-if="busy.includes(share.source)" #icon>
					<NcLoadingIcon :size="20" />
				</template>
				{{ isFolder(share) ? t('files_sharing', 'Accept folder') : t('files_sharing', 'Accept file') }}
			</NcButton>
			<NcActions
				:aria-label="t('files_sharing', 'Actions for {name}', { name: share.displayname }, { escape: false })"
				:disabled="busy.includes(share.source)"
				forceMenu>
				<NcActionText v-if="share.size !== undefined" class="pending-shares__info">
					<template #icon>
						<NcIconSvgWrapper :path="mdiChartPieOutline" />
					</template>
					{{ formatFileSize(share.size, true) }}
				</NcActionText>
				<NcActionText v-if="share.mtime" class="pending-shares__info">
					<template #icon>
						<NcIconSvgWrapper :path="mdiClockOutline" />
					</template>
					{{ formatRelativeTime(share.mtime) }}
				</NcActionText>
				<NcActionButton
					v-if="canRejectShare(share)"
					closeAfterClick
					@click="reject(share)">
					<template #icon>
						<NcIconSvgWrapper :path="mdiClose" />
					</template>
					{{ isFolder(share) ? t('files_sharing', 'Decline folder') : t('files_sharing', 'Decline file') }}
				</NcActionButton>
			</NcActions>
		</li>
	</ul>
</template>

<script setup lang="ts">
import type { INode } from '@nextcloud/files'

import { mdiChartPieOutline, mdiClockOutline, mdiClose, mdiFolder } from '@mdi/js'
import { showError } from '@nextcloud/dialogs'
import { emit } from '@nextcloud/event-bus'
import { FileType, formatFileSize } from '@nextcloud/files'
import { formatRelativeTime, t } from '@nextcloud/l10n'
import { ref } from 'vue'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcActions from '@nextcloud/vue/components/NcActions'
import NcActionText from '@nextcloud/vue/components/NcActionText'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import logger from '../services/logger.ts'
import { acceptShare, canRejectShare, rejectShare } from '../services/PendingSharesService.ts'
import { getContents } from '../services/SharingService.ts'

const shares = ref<INode[]>([])
const busy = ref<string[]>([])

/**
 * Fetch the pending shares
 */
async function update() {
	try {
		const { contents } = await getContents(false, false, true, false)
		shares.value = contents
	} catch (error) {
		logger.error('Could not fetch pending shares', { error })
	}
}

/**
 * Get the mime type icon of a share
 *
 * @param share The share node
 */
function getIconUrl(share: INode): string {
	return (window.OC as { MimeType: { getIconUrl: (mime: string) => string } }).MimeType.getIconUrl(share.mime)
}

/**
 * Whether a share is a folder
 *
 * @param share The share node
 */
function isFolder(share: INode): boolean {
	return share.type === FileType.Folder
}

/**
 * Get the file extension of a share, empty for folders
 *
 * @param share The share node
 */
function getExtension(share: INode): string {
	return share.type === FileType.File ? (share.extension ?? '') : ''
}

/**
 * Get the name of a share without its extension
 *
 * @param share The share node
 */
function getBasename(share: INode): string {
	const extension = getExtension(share)
	return extension && share.displayname.endsWith(extension)
		? share.displayname.slice(0, -extension.length)
		: share.displayname
}

/**
 * Run a share request and remove the share from the list on success
 *
 * @param share The share node
 * @param request The request to run
 * @param errorMessage The message to show on error
 */
async function handle<T>(share: INode, request: (share: INode) => Promise<T>, errorMessage: string): Promise<T | undefined> {
	busy.value = [...busy.value, share.source]
	try {
		const result = await request(share)
		shares.value = shares.value.filter((node) => node.source !== share.source)
		return result
	} catch (error) {
		logger.error(errorMessage, { error, share })
		showError(errorMessage)
	} finally {
		busy.value = busy.value.filter((source) => source !== share.source)
	}
}

/**
 * Accept a share, then open the folder or show the file details
 *
 * @param share The share node
 */
async function accept(share: INode) {
	const node = await handle(share, acceptShare, isFolder(share)
		? t('files_sharing', 'Could not accept folder')
		: t('files_sharing', 'Could not accept file'))
	if (!node) {
		return
	}

	emit('files:node:created', node)
	if (node.type === FileType.Folder) {
		window.OCP.Files.Router.goToRoute(null, { view: 'files' }, { dir: node.path })
		return
	}
	// Highlight the file and show its details
	window.OCP.Files.Router.goToRoute(
		null,
		{ view: 'files', fileid: String(node.fileid) },
		{ dir: node.dirname, opendetails: '' },
	)
}

/**
 * Reject a share
 *
 * @param share The share node
 */
async function reject(share: INode) {
	await handle(share, rejectShare, isFolder(share)
		? t('files_sharing', 'Could not decline folder')
		: t('files_sharing', 'Could not decline file'))
}

defineExpose({ update })
</script>

<style scoped lang="scss">
.pending-shares {
	display: grid;
	grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
	// Center the larger icon on the file list icon, so the text aligns with the file names
	--pending-shares-icon-size: 32px;
	--pending-shares-icon-offset: calc((var(--pending-shares-icon-size) - var(--icon-preview-size)) / 2);
	gap: var(--default-grid-baseline) calc(3 * var(--default-grid-baseline));
	margin-block-start: var(--default-grid-baseline);
	margin-inline: calc(var(--row-height) - var(--pending-shares-icon-offset)) var(--row-height);

	&__share {
		display: flex;
		align-items: center;
		gap: calc(2 * var(--default-grid-baseline));
		min-width: 0;
		// Same as the recommended files tiles
		padding-block: 5px;
	}

	&__file {
		display: flex;
		flex: 1;
		align-items: center;
		// The file names have an inline padding for their focus outline
		gap: calc(var(--checkbox-padding) - var(--pending-shares-icon-offset) + 2 * var(--default-grid-baseline));
		min-width: 0;
	}

	&__share &__icon {
		flex-shrink: 0;
		width: var(--pending-shares-icon-size);
		min-width: var(--pending-shares-icon-size);
		height: var(--pending-shares-icon-size);
		min-height: var(--pending-shares-icon-size);
	}

	&__icon--folder {
		color: var(--color-primary-element);
	}

	&__text {
		display: flex;
		flex: 1;
		flex-direction: column;
		min-width: 0;
	}

	&__name,
	&__details {
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
	}

	&__extension,
	&__details {
		color: var(--color-text-maxcontrast);
	}
}

// Read-only info entries in the actions menu
.pending-shares__info.action {
	&:hover {
		background-color: transparent;
	}

	:deep(.action-text) {
		color: var(--color-text-maxcontrast);
		cursor: default;
	}
}

@media screen and (max-width: 512px) {
	.pending-shares {
		margin-inline: var(--default-grid-baseline);
	}
}
</style>
