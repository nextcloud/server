<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { IDialogButton } from '@nextcloud/dialogs'

import { t } from '@nextcloud/l10n'
import { textExistingFilesNotEncrypted } from './sharedTexts.ts'
import NcDialog from '@nextcloud/vue/dist/Components/NcDialog.js'
import NcNoteCard from '@nextcloud/vue/dist/Components/NcNoteCard.js'

const emit = defineEmits<{
	(e: 'close', encrypt: boolean): void
}>()

const buttons: IDialogButton[] = [
	{
		label: t('settings', 'Cancel encryption'),
		// @ts-expect-error Needs to be fixed in the dialogs library - value is allowed but missing from the types
		type: 'tertiary',
		callback: () => emit('close', false),
	},
	{
		label: t('settings', 'Enable encryption'),
		type: 'error',
		callback: () => emit('close', true),
	},
]

/**
 * When closed we need to emit the close event
 * @param isOpen open state of the dialog
 */
function onUpdateOpen(isOpen: boolean) {
	if (!isOpen) {
		emit('close', false)
	}
}
</script>

<template>
	<NcDialog :buttons="buttons"
		:name="t('settings', 'Confirm enabling encryption')"
		size="normal"
		@update:open="onUpdateOpen">
		<NcNoteCard type="warning">
			<p>
				{{ t('settings', 'Please read carefully before activating server-side encryption:') }}
				<ul>
					<li>
						{{ t('settings', 'Once encryption is enabled, all files uploaded to the server from that point forward will be encrypted at rest on the server. It will only be possible to disable encryption at a later date if the active encryption module supports that function, and all pre-conditions (e.g. setting a recover key) are met.') }}
					</li>
					<li>
						{{ t('settings', 'Encryption alone does not guarantee security of the system. Please see documentation for more information about how the encryption app works, and the supported use cases.') }}
					</li>
					<li>
						{{ t('settings', 'Be aware that encryption always increases the file size.') }}
					</li>
					<li>
						{{ t('settings', 'It is always good to create regular backups of your data, in case of encryption make sure to backup the encryption keys along with your data.') }}
					</li>
					<li>
						{{ textExistingFilesNotEncrypted }}
						{{ t('settings', 'Refer to the admin documentation on how to manually also encrypt existing files.') }}
					</li>
				</ul>
			</p>
		</NcNoteCard>
		<p>
			{{ t('settings', 'This is the final warning: Do you really want to enable encryption?') }}
		</p>
	</NcDialog>
</template>

<style scoped>
li {
	list-style-type: initial;
	margin-inline-start: 1rem;
	padding: 0.25rem 0;
}

p + p,
div + p {
	margin-block: 0.75rem;
}
</style>
