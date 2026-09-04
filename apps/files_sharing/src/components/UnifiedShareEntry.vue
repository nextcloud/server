<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<ul class="unified-share">
		<!-- Single-recipient share: one row -->
		<SharingEntrySimple
			v-if="isSingle"
			:title="recipients[0].display_name"
			:subtitle="recipientPermissionLabel(share, recipients[0])">
			<template #avatar>
				<NcAvatar
					:size="32"
					:isNoUser="isNoUserRecipient(recipients[0])"
					:user="isNoUserRecipient(recipients[0]) ? undefined : recipients[0].value"
					:displayName="recipients[0].display_name" />
			</template>
			<NcActionButton :aria-label="t('files_sharing', 'Edit share')" @click="openEditDialog">
				<template #icon>
					<PencilIcon :size="20" />
				</template>
				{{ t('files_sharing', 'Edit share') }}
			</NcActionButton>
			<NcActionButton :aria-label="t('files_sharing', 'Delete share')" @click="confirmDeleteShare">
				<template #icon>
					<DeleteIcon :size="20" />
				</template>
				{{ t('files_sharing', 'Delete share') }}
			</NcActionButton>
		</SharingEntrySimple>

		<!-- Multi-recipient share: collapsible group -->
		<template v-else>
			<SharingEntrySimple
				:title="summaryTitle"
				:subtitle="reshareLine"
				:aria-expanded="expanded">
				<template #avatar>
					<!-- Collapsed: show the stack; expanded: avatars live in the list -->
					<AvatarStack v-if="!expanded" :recipients="recipients" />
				</template>
				<template #action>
					<NcButton
						variant="tertiary"
						:aria-label="t('files_sharing', 'Toggle recipients')"
						:aria-expanded="expanded"
						@click="expanded = !expanded">
						<template #icon>
							<ChevronDownIcon v-if="expanded" :size="20" />
							<ChevronRightIcon v-else :size="20" />
						</template>
					</NcButton>
				</template>
				<NcActionButton :aria-label="t('files_sharing', 'Edit share')" @click="openEditDialog">
					<template #icon>
						<PencilIcon :size="20" />
					</template>
					{{ t('files_sharing', 'Edit share') }}
				</NcActionButton>
				<NcActionButton :aria-label="t('files_sharing', 'Delete share')" @click="confirmDeleteShare">
					<template #icon>
						<DeleteIcon :size="20" />
					</template>
					{{ t('files_sharing', 'Delete share') }}
				</NcActionButton>
			</SharingEntrySimple>

			<SharingEntrySimple
				v-for="recipient in recipients"
				v-show="expanded"
				:key="recipient.class + recipient.value"
				class="unified-share__recipient"
				:title="recipient.display_name"
				:subtitle="recipientPermissionLabel(share, recipient)"
				forceMenu>
				<template #avatar>
					<NcAvatar
						:size="32"
						:isNoUser="isNoUserRecipient(recipient)"
						:user="isNoUserRecipient(recipient) ? undefined : recipient.value"
						:displayName="recipient.display_name" />
				</template>
				<NcActionButton :aria-label="t('files_sharing', 'Remove participant')" @click="removeOne(recipient)">
					<template #icon>
						<DeleteIcon :size="20" />
					</template>
					{{ t('files_sharing', 'Remove participant') }}
				</NcActionButton>
			</SharingEntrySimple>
		</template>
	</ul>
</template>

<script setup lang="ts">
import type { SharingRecipient, SharingShare } from '../types/unifiedSharing.ts'

import { DialogBuilder } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import { computed, ref } from 'vue'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcAvatar from '@nextcloud/vue/components/NcAvatar'
import NcButton from '@nextcloud/vue/components/NcButton'
import ChevronDownIcon from 'vue-material-design-icons/ChevronDown.vue'
import ChevronRightIcon from 'vue-material-design-icons/ChevronRight.vue'
import DeleteIcon from 'vue-material-design-icons/Delete.vue'
import PencilIcon from 'vue-material-design-icons/Pencil.vue'
import AvatarStack from './AvatarStack.vue'
import SharingEntrySimple from './SharingEntrySimple.vue'
import { isNoUserRecipient, recipientPermissionLabel, recipientSummary, reshareSubtitle } from '../lib/unifiedSharing.ts'
import logger from '../services/logger.ts'
import { openShareEditDialog } from '../services/SharingDialog.ts'
import { deleteShare, removeRecipient } from '../services/unifiedShares.ts'

const props = defineProps<{
	share: SharingShare
	fileInfo: { node?: unknown }
}>()

const emit = defineEmits<{
	(e: 'refresh'): void
}>()

// Expanded by default; collapse is optional (e.g. long lists).
const expanded = ref(true)

const recipients = computed(() => props.share.recipients)
const isSingle = computed(() => recipients.value.length === 1)
const summaryTitle = computed(() => recipientSummary(recipients.value))
const reshareLine = computed(() => reshareSubtitle(props.share))

/**
 * Show a confirmation dialog and resolve to the user's choice.
 *
 * @param text The confirmation prompt
 */
async function confirm(text: string): Promise<boolean> {
	let confirmed = false
	const dialog = (new DialogBuilder())
		.setName(t('files_sharing', 'Delete share'))
		.setText(text)
		.setButtons([
			{
				label: t('files_sharing', 'Cancel'),
				variant: 'secondary',
				callback: () => {},
			},
			{
				label: t('files_sharing', 'Delete'),
				variant: 'error',
				callback: () => {
					confirmed = true
				},
			},
		])
		.build()
	try {
		await dialog.show()
	} catch (error) {
		logger.debug('Delete confirmation dialog closed', { error })
	}
	return confirmed
}

/**
 * Open the unified sharing dialog to edit this share.
 */
async function openEditDialog(): Promise<void> {
	try {
		await openShareEditDialog(props.share.id, props.fileInfo.node)
	} catch (error) {
		logger.error('Failed to open the sharing dialog', { error })
	} finally {
		// Refresh even if the dialog errored, it may still have applied
		// changes (recipients, permissions) before closing.
		emit('refresh')
	}
}

/**
 * Ask for confirmation, then delete the whole share.
 */
async function confirmDeleteShare(): Promise<void> {
	if (!await confirm(t('files_sharing', 'Are you sure you want to delete this share? This operation cannot be undone.'))) {
		return
	}
	try {
		await deleteShare(props.share.id)
		emit('refresh')
	} catch (error) {
		logger.error('Failed to delete share', { error })
	}
}

/**
 * Remove a single recipient from the share.
 *
 * @param recipient The recipient to remove
 */
async function removeOne(recipient: SharingRecipient): Promise<void> {
	try {
		await removeRecipient(props.share.id, recipient.class, recipient.value, recipient.instance)
		emit('refresh')
	} catch (error) {
		logger.error('Failed to remove recipient', { error })
	}
}
</script>

<style lang="scss" scoped>
.unified-share {
	// Unify every share row to 52px.
	:deep(.sharing-entry) {
		min-height: 52px;
	}

	&__recipient {
		padding-inline-start: 24px;
	}
}
</style>
