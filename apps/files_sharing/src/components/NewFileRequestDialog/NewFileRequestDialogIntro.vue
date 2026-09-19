<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div>
		<!-- Request label -->
		<fieldset class="file-request-dialog__label" data-cy-file-request-dialog-fieldset="label">
			<legend>
				<label class="file-request-dialog__legend-label" for="file-request-dialog-label">
					{{ t('files_sharing', 'What are you requesting?') }}
				</label>
			</legend>
			<NcTextField
				id="file-request-dialog-label"
				:model-value="label"
				:disabled="disabled"
				:label="t('files_sharing', 'Request subject')"
				:placeholder="t('files_sharing', 'Birthday party photos, History assignment…')"
				:required="false"
				name="label"
				@update:value="$emit('update:label', $event)" />
		</fieldset>

		<!-- Request destination -->
		<fieldset class="file-request-dialog__destination" data-cy-file-request-dialog-fieldset="destination">
			<legend>
				<label class="file-request-dialog__legend-label" for="file-request-dialog-destination">
					{{ t('files_sharing', 'Where should these files go?') }}
				</label>
			</legend>
			<NcTextField
				id="file-request-dialog-destination"
				:model-value="destination"
				:disabled="disabled"
				:label="t('files_sharing', 'Upload destination')"
				:minlength="2/* cannot share root */"
				:placeholder="t('files_sharing', 'Select a destination')"
				:readonly="false /* cannot validate a readonly input */"
				:required="true /* cannot be empty */"
				:show-trailing-button="destination !== context.path"
				trailing-button-icon="undo"
				:trailing-button-label="t('files_sharing', 'Revert to default')"
				name="destination"
				aria-haspopup="dialog"
				@click="onPickDestination"
				@keydown="onDestinationKeydown"
				@paste.prevent.stop
				@trailing-button-click="$emit('update:destination', '')">
				<IconFolder :size="18" />
			</NcTextField>

			<p class="file-request-dialog__info">
				<IconLock :size="18" class="file-request-dialog__info-icon" />
				{{ t('files_sharing', 'The uploaded files are visible only to you unless you choose to share them.') }}
			</p>
		</fieldset>

		<!-- Request note -->
		<fieldset class="file-request-dialog__note" data-cy-file-request-dialog-fieldset="note">
			<legend>
				<label class="file-request-dialog__legend-label" for="file-request-dialog-note">
					{{ t('files_sharing', 'Add a note') }}
				</label>
			</legend>
			<NcTextArea
				id="file-request-dialog-note"
				:model-value="note"
				:disabled="disabled"
				:label="t('files_sharing', 'Note for recipient')"
				:placeholder="t('files_sharing', 'Add a note to help people understand what you are requesting.')"
				:required="false"
				name="note"
				@update:value="$emit('update:note', $event)" />

			<p class="file-request-dialog__info">
				<IconInfo :size="18" class="file-request-dialog__info-icon" />
				{{ t('files_sharing', 'You can add links, date or any other information that will help the recipient understand what you are requesting.') }}
			</p>
		</fieldset>
	</div>
</template>

<script lang="ts">
import type { Folder, INode } from '@nextcloud/files'
import type { PropType } from 'vue'

import { getFilePickerBuilder } from '@nextcloud/dialogs'
import { Permission } from '@nextcloud/files'
import { t } from '@nextcloud/l10n'
import { defineComponent } from 'vue'
import NcTextArea from '@nextcloud/vue/components/NcTextArea'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import IconFolder from 'vue-material-design-icons/Folder.vue'
import IconInfo from 'vue-material-design-icons/InformationOutline.vue'
import IconLock from 'vue-material-design-icons/Lock.vue'

export default defineComponent({
	name: 'NewFileRequestDialogIntro',

	components: {
		IconFolder,
		IconInfo,
		IconLock,
		NcTextArea,
		NcTextField,
	},

	props: {
		disabled: {
			type: Boolean,
			required: false,
			default: false,
		},

		context: {
			type: Object as PropType<Folder>,
			required: true,
		},

		label: {
			type: String,
			required: true,
		},

		destination: {
			type: String,
			required: true,
		},

		note: {
			type: String,
			required: true,
		},
	},

	emits: [
		'update:destination',
		'update:label',
		'update:note',
	],

	setup() {
		return {
			t,
		}
	},

	methods: {
		onDestinationKeydown(event: KeyboardEvent) {
			if (event.key === 'Enter' || event.key === ' ') {
				event.preventDefault()
				this.onPickDestination()
				return
			}

			// Destination is chosen in the picker, not by editing this field
			if (event.ctrlKey || event.metaKey || event.altKey) {
				return
			}
			if (event.key.length === 1 || event.key === 'Backspace' || event.key === 'Delete') {
				event.preventDefault()
			}
		},

		onPickDestination() {
			const filepicker = getFilePickerBuilder(t('files_sharing', 'Select a destination'))
				.addMimeTypeFilter('httpd/unix-directory')
				.allowDirectories(true)
				.addButton({
					label: t('files_sharing', 'Select'),
					callback: this.onPickedDestination,
				})
				.setFilter((node) => node.path !== '/')
				.setCanPick((node) => Boolean(node.permissions & Permission.SHARE))
				.startAt(this.destination)
				.build()
			try {
				filepicker.pick()
			} catch {
				// ignore cancel
			}
		},

		onPickedDestination(nodes: INode[]) {
			const node = nodes[0]
			if (node) {
				this.$emit('update:destination', node.path)
			}
		},
	},
})
</script>

<style scoped>
.file-request-dialog__legend-label {
	font: inherit;
	color: inherit;
	cursor: pointer;
}

.file-request-dialog__destination :deep(.input-field__input) {
	cursor: pointer;
}

.file-request-dialog__note :deep(textarea) {
	width: 100% !important;
	min-height: 80px;
}
</style>
