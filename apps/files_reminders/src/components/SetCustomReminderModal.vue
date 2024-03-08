<!--
  - @copyright 2023 Christopher Ng <chrng8@gmail.com>
  -
  - @author Christopher Ng <chrng8@gmail.com>
  -
  - @license AGPL-3.0-or-later
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program. If not, see <http://www.gnu.org/licenses/>.
  -
-->

<template>
	<NcModal v-if="opened"
		:out-transition="true"
		size="small"
		@close="onClose">
		<form class="custom-reminder-modal" @submit.prevent="setCustom">
			<h2 class="custom-reminder-modal__title">
				{{ title }}
			</h2>

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

			<!-- Buttons -->
			<div class="custom-reminder-modal__buttons">
				<!-- Cancel pick -->
				<NcButton @click="onClose">
					{{ t('files_reminders', 'Cancel') }}
				</NcButton>

				<!-- Set reminder -->
				<NcButton :disabled="!isValid" native-type="submit" type="primary">
					{{ t('files_reminders', 'Set reminder') }}
				</NcButton>
			</div>
		</form>
	</NcModal>
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
import NcModal from '@nextcloud/vue/dist/Components/NcModal.js'
import NcNoteCard from '@nextcloud/vue/dist/Components/NcNoteCard.js'

import { getDateString, getInitialCustomDueDate } from '../shared/utils.ts'
import { logger } from '../shared/logger.ts'
import { setReminder } from '../services/reminderService.ts'

export default Vue.extend({
	name: 'SetCustomReminderModal',

	components: {
		NcButton,
		NcDateTime,
		NcDateTimePickerNative,
		NcModal,
		NcNoteCard,
	},

	data() {
		return {
			node: undefined as Node | undefined,
			opened: false,
			isValid: true,

			customDueDate: getInitialCustomDueDate() as '' | Date,
			nowDate: new Date(),
		}
	},

	computed: {
		fileId(): number {
			return this.node.fileid
		},

		fileName(): string {
			return this.node.basename
		},

		title() {
			return t('files_reminders', 'Set reminder for "{fileName}"', { fileName: this.fileName })
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
		async open(node: Node): Promise<void> {
			this.node = node
			this.isValid = true
			this.opened = true
			this.customDueDate = getInitialCustomDueDate()
			this.nowDate = new Date()

			// Focus the input and show the picker after the animation
			setTimeout(() => {
				const input = document.getElementById('set-custom-reminder') as HTMLInputElement
				input.focus()
				input.showPicker()
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
	margin: 30px;

	&__title {
		font-size: 16px;
		line-height: 2em;
	}

	&__buttons {
		display: flex;
		justify-content: flex-end;
		margin-top: 30px;

		button {
			margin-left: 10px;
		}
	}
}
</style>
