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
	<NcActions class="actions-secondary-vue"
		:open.sync="open">
		<NcActionButton @click="$emit('back')">
			<template #icon>
				<ArrowLeft :size="20" />
			</template>
			{{ t('files_reminders', 'Back') }}
		</NcActionButton>

		<NcActionButton v-if="Boolean(dueDate)"
			:aria-label="clearAriaLabel"
			@click="clear">
			<template #icon>
				<CloseCircleOutline :size="20" />
			</template>
			{{ t('files_reminders', 'Clear reminder') }} – {{ getDateString(dueDate) }}
		</NcActionButton>

		<NcActionSeparator />

		<NcActionButton v-for="({ label, ariaLabel, dateString, action }) in options"
			:key="label"
			:aria-label="ariaLabel"
			@click="action">
			{{ label }} – {{ dateString }}
		</NcActionButton>

		<NcActionSeparator />

		<NcActionInput type="datetime-local"
			is-native-picker
			:min="now"
			v-model="customDueDate">
			<template #icon>
				<CalendarClock :size="20" />
			</template>
		</NcActionInput>

		<NcActionButton :aria-label="customAriaLabel"
			@click="setCustom">
			<template #icon>
				<Check :size="20" />
			</template>
			{{ t('files_reminders', 'Set custom reminder') }}
		</NcActionButton>
	</NcActions>
</template>

<script lang="ts">
import Vue, { type PropType } from 'vue'
import { translate as t } from '@nextcloud/l10n'
import { showError, showSuccess } from '@nextcloud/dialogs'

import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton.js'
import NcActionInput from '@nextcloud/vue/dist/Components/NcActionInput.js'
import NcActions from '@nextcloud/vue/dist/Components/NcActions.js'
import NcActionSeparator from '@nextcloud/vue/dist/Components/NcActionSeparator.js'

import ArrowLeft from 'vue-material-design-icons/ArrowLeft.vue'
import CalendarClock from 'vue-material-design-icons/CalendarClock.vue'
import Check from 'vue-material-design-icons/Check.vue'
import CloseCircleOutline from 'vue-material-design-icons/CloseCircleOutline.vue'

import { clearReminder, setReminder } from '../services/reminderService.ts'
import {
	DateTimePreset,
	getDateString,
	getDateTime,
	getInitialCustomDueDate,
	getVerboseDateString,
} from '../shared/utils.ts'
import { logger } from '../shared/logger.ts'

import type { FileAttributes } from '../shared/types.ts'

interface ReminderOption {
	dateTimePreset: DateTimePreset
	label: string
	ariaLabel: string
	dateString?: string
	action?: () => Promise<void>
}

const laterToday: ReminderOption = {
	dateTimePreset: DateTimePreset.LaterToday,
	label: t('files_reminders', 'Later today'),
	ariaLabel: t('files_reminders', 'Set reminder for later today'),
}

const tomorrow: ReminderOption = {
	dateTimePreset: DateTimePreset.Tomorrow,
	label: t('files_reminders', 'Tomorrow'),
	ariaLabel: t('files_reminders', 'Set reminder for tomorrow'),
}

const thisWeekend: ReminderOption = {
	dateTimePreset: DateTimePreset.ThisWeekend,
	label: t('files_reminders', 'This weekend'),
	ariaLabel: t('files_reminders', 'Set reminder for this weekend'),
}

const nextWeek: ReminderOption = {
	dateTimePreset: DateTimePreset.NextWeek,
	label: t('files_reminders', 'Next week'),
	ariaLabel: t('files_reminders', 'Set reminder for next week'),
}

export default Vue.extend({
	name: 'SetReminderActions',

	components: {
		ArrowLeft,
		CalendarClock,
		Check,
		CloseCircleOutline,
		NcActionButton,
		NcActionInput,
		NcActions,
		NcActionSeparator,
	},

	props: {
		file: {
			type: Object as PropType<FileAttributes>,
			required: true,
		},

		dueDate: {
			type: Date as PropType<null | Date>,
			default: null,
		},
	},

	data() {
		return {
			open: true,
			now: new Date(),
			customDueDate: getInitialCustomDueDate() as '' | Date,
		}
	},

	watch: {
		open(isOpen) {
			if (!isOpen) {
				this.$emit('close')
			}
		},
	},

	computed: {
		fileId(): number {
			return this.file.id
		},

		fileName(): string {
			return this.file.name
		},

		clearAriaLabel(): string {
			return `${t('files_reminders', 'Clear reminder')} – ${getVerboseDateString(this.dueDate as Date)}`
		},

		customAriaLabel(): null | string {
			if (this.customDueDate === '') {
				return null
			}
			return `${t('files_reminders', 'Set reminder at custom date & time')} – ${getVerboseDateString(this.customDueDate)}`
		},

		options(): ReminderOption[] {
			const computeOption = (option: ReminderOption) => {
				const dateTime = getDateTime(option.dateTimePreset)
				return {
					...option,
					ariaLabel: `${option.ariaLabel} – ${getVerboseDateString(dateTime)}`,
					dateString: getDateString(dateTime),
					action: () => this.set(dateTime),
				}
			}

			return [
				laterToday,
				tomorrow,
				thisWeekend,
				nextWeek,
			].map(computeOption)
		},
	},

	methods: {
		t,
		getDateString,

		async set(dueDate: Date): Promise<void> {
			try {
				await setReminder(this.fileId, dueDate)
				showSuccess(t('files_reminders', 'Reminder set for "{fileName}"', { fileName: this.fileName }))
				this.open = false
			} catch (error) {
				logger.error('Failed to set reminder', { error })
				showError(t('files_reminders', 'Failed to set reminder'))
			}
		},

		async setCustom(): Promise<void> {
			// Handle input cleared
			if (this.customDueDate === '') {
				showError(t('files_reminders', 'Please choose a valid date & time'))
				return
			}

			try {
				await setReminder(this.fileId, this.customDueDate)
				showSuccess(t('files_reminders', 'Reminder set for "{fileName}"', { fileName: this.fileName }))
				this.open = false
			} catch (error) {
				logger.error('Failed to set reminder', { error })
				showError(t('files_reminders', 'Failed to set reminder'))
			}
		},

		async clear(): Promise<void> {
			try {
				await clearReminder(this.fileId)
				showSuccess(t('files_reminders', 'Reminder cleared'))
				this.open = false
			} catch (error) {
				logger.error('Failed to clear reminder', { error })
				showError(t('files_reminders', 'Failed to clear reminder'))
			}
		},
	},
})
</script>

<style lang="scss" scoped>
.actions-secondary-vue {
	display: block !important;
	float: right !important;
	padding: 5px 0 0 4px !important;
	pointer-events: none !important; // prevent activation of file row
}
</style>
