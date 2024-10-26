<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcDialog v-if="opened"
		:name="name"
		:out-transition="true"
		size="small"
		close-on-click-outside
		@closing="onClose">
		<form id="set-custom-reminder-form"
			class="custom-reminder-modal"
			@submit.prevent="setCustom">
			<NcDateTimePickerNative id="set-custom-reminder"
				v-model="customDueDate"
				:label="label"
				:min="nowDate"
				:required="true"
				type="datetime-local"
				@input="onInput" />

			<NcNoteCard v-if="isValid" type="info">
				{{ t('files_reminders', 'We will remind you of this file') }}
				<NcDateTime :timestamp="customDueDate" />
			</NcNoteCard>

			<NcNoteCard v-else type="error">
				{{ t('files_reminders', 'Please choose a valid date & time') }}
			</NcNoteCard>
		</form>
		<template #actions>
			<!-- Cancel pick -->
			<NcButton type="tertiary" @click="onClose">
				{{ t('files_reminders', 'Cancel') }}
			</NcButton>

			<!-- Clear reminder -->
			<NcButton v-if="hasDueDate" @click="clear">
				{{ t('files_reminders', 'Clear reminder') }}
			</NcButton>

			<!-- Set reminder -->
			<NcButton :disabled="!isValid"
				type="primary"
				form="set-custom-reminder-form"
				native-type="submit">
				{{ t('files_reminders', 'Set reminder') }}
			</NcButton>
		</template>
	</NcDialog>
</template>

<script lang="ts">
import Vue from 'vue'
import type { Node } from '@nextcloud/files'
import { emit } from '@nextcloud/event-bus'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'

import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcDateTime from '@nextcloud/vue/dist/Components/NcDateTime.js'
import NcDateTimePickerNative from '@nextcloud/vue/dist/Components/NcDateTimePickerNative.js'
import NcDialog from '@nextcloud/vue/dist/Components/NcDialog.js'
import NcNoteCard from '@nextcloud/vue/dist/Components/NcNoteCard.js'

import { getDateString, getInitialCustomDueDate } from '../shared/utils.ts'
import { logger } from '../shared/logger.ts'
import { clearReminder, setReminder } from '../services/reminderService.ts'

export default Vue.extend({
	name: 'SetCustomReminderModal',

	components: {
		NcButton,
		NcDateTime,
		NcDateTimePickerNative,
		NcDialog,
		NcNoteCard,
	},

	data() {
		return {
			node: undefined as Node | undefined,
			hasDueDate: false,
			opened: false,
			isValid: true,

			customDueDate: null as null | Date,
			nowDate: new Date(),
		}
	},

	computed: {
		fileId(): number|undefined {
			return this.node?.fileid
		},

		fileName(): string|undefined {
			return this.node?.basename
		},

		name() {
			return this.fileName ? t('files_reminders', 'Set reminder for "{fileName}"', { fileName: this.fileName }) : ''
		},

		label(): string {
			return t('files_reminders', 'Set reminder at custom date & time')
		},

		clearAriaLabel(): string {
			return t('files_reminders', 'Clear reminder')
		},
	},

	methods: {
		t,
		getDateString,

		/**
		 * Open the modal to set a custom reminder
		 * and reset the state.
		 * @param node The node to set a reminder for
		 */
		open(node: Node): void {
			const dueDate = node.attributes['reminder-due-date'] ? new Date(node.attributes['reminder-due-date']) : null

			this.node = node
			this.hasDueDate = Boolean(dueDate)
			this.isValid = true
			this.opened = true
			this.customDueDate = dueDate ?? getInitialCustomDueDate()
			this.nowDate = new Date()

			// Focus the input and show the picker after the animation
			setTimeout(() => {
				const input = document.getElementById('set-custom-reminder') as HTMLInputElement
				input.focus()
				if (!this.hasDueDate) {
					input.showPicker()
				}
			}, 300)
		},

		async setCustom(): Promise<void> {
			// Handle input cleared or invalid date
			if (!(this.customDueDate instanceof Date) || isNaN(this.customDueDate)) {
				showError(t('files_reminders', 'Please choose a valid date & time'))
				return
			}

			try {
				await setReminder(this.fileId, this.customDueDate)
				Vue.set(this.node.attributes, 'reminder-due-date', this.customDueDate.toISOString())
				emit('files:node:updated', this.node)
				showSuccess(t('files_reminders', 'Reminder set for "{fileName}"', { fileName: this.fileName }))
				this.onClose()
			} catch (error) {
				logger.error('Failed to set reminder', { error })
				showError(t('files_reminders', 'Failed to set reminder'))
			}
		},

		async clear(): Promise<void> {
			try {
				await clearReminder(this.fileId)
				Vue.set(this.node.attributes, 'reminder-due-date', '')
				emit('files:node:updated', this.node)
				showSuccess(t('files_reminders', 'Reminder cleared for "{fileName}"', { fileName: this.fileName }))
				this.onClose()
			} catch (error) {
				logger.error('Failed to clear reminder', { error })
				showError(t('files_reminders', 'Failed to clear reminder'))
			}
		},

		onClose(): void {
			this.opened = false
			this.$emit('close')
		},

		onInput(): void {
			const input = document.getElementById('set-custom-reminder') as HTMLInputElement
			this.isValid = input.checkValidity()
		},
	},
})
</script>

<style lang="scss" scoped>
.custom-reminder-modal {
	margin: 0 12px;
}
</style>
