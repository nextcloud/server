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
				<NcIconSvgWrapper :svg="arrowLeftSvg" />
			</template>
			{{ t('files_reminders', 'Back') }}
		</NcActionButton>
		<NcActionButton v-if="Boolean(dueDate)"
			:aria-label="clearAriaLabel"
			@click="clear">
			<template #icon>
				<NcIconSvgWrapper :svg="clearSvg" />
			</template>
			{{ t('files_reminders', 'Clear reminder') }} — {{ getDateString(dueDate) }}
		</NcActionButton>
		<NcActionSeparator />
		<NcActionButton v-for="({ icon, label, ariaLabel, dateString, action }) in options"
			:key="label"
			:aria-label="ariaLabel"
			@click="action">
			<template #icon>
				<NcIconSvgWrapper :svg="icon" />
			</template>
			{{ label }} — {{ dateString }}
		</NcActionButton>
	</NcActions>
</template>

<script lang="ts">
import Vue, { type PropType } from 'vue'
import { translate as t } from '@nextcloud/l10n'
import { showError, showSuccess } from '@nextcloud/dialogs'

import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton.js'
import NcActions from '@nextcloud/vue/dist/Components/NcActions.js'
import NcActionSeparator from '@nextcloud/vue/dist/Components/NcActionSeparator.js'
import NcIconSvgWrapper from '@nextcloud/vue/dist/Components/NcIconSvgWrapper.js'

import arrowLeftSvg from '@mdi/svg/svg/arrow-left.svg?raw'
import clearSvg from '@mdi/svg/svg/close-circle-outline.svg?raw'
import laterTodaySvg from '@mdi/svg/svg/update.svg?raw'
import tomorrowSvg from '@mdi/svg/svg/chevron-right.svg?raw'
import thisWeekendSvg from '@mdi/svg/svg/calendar-weekend.svg?raw'
import nextWeekSvg from '@mdi/svg/svg/chevron-double-right.svg?raw'

import { clearReminder, setReminder } from '../services/reminderService.js'
import {
	DateTimePreset,
	getDateString,
	getDateTime,
	getVerboseDateString,
} from '../shared/utils.js'
import { logger } from '../shared/logger.js'

import type { FileAttributes } from '../shared/types.js'

interface ReminderOption {
	dateTimePreset: DateTimePreset
	icon: string
	label: string
	ariaLabel: string
	dateString?: string
	action?: () => Promise<void>
}

const laterToday: ReminderOption = {
	dateTimePreset: DateTimePreset.LaterToday,
	icon: laterTodaySvg,
	label: t('files_reminders', 'Later today'),
	ariaLabel: t('files_reminders', 'Set reminder for later today'),
}

const tomorrow: ReminderOption = {
	dateTimePreset: DateTimePreset.Tomorrow,
	icon: tomorrowSvg,
	label: t('files_reminders', 'Tomorrow'),
	ariaLabel: t('files_reminders', 'Set reminder for tomorrow'),
}

const thisWeekend: ReminderOption = {
	dateTimePreset: DateTimePreset.ThisWeekend,
	icon: thisWeekendSvg,
	label: t('files_reminders', 'This weekend'),
	ariaLabel: t('files_reminders', 'Set reminder for this weekend'),
}

const nextWeek: ReminderOption = {
	dateTimePreset: DateTimePreset.NextWeek,
	icon: nextWeekSvg,
	label: t('files_reminders', 'Next week'),
	ariaLabel: t('files_reminders', 'Set reminder for next week'),
}

export default Vue.extend({
	name: 'SetReminderActions',

	components: {
		NcActionButton,
		NcActions,
		NcActionSeparator,
		NcIconSvgWrapper,
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
			arrowLeftSvg,
			clearSvg,
			open: true,
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
			return `${t('files_reminders', 'Clear reminder')} — ${getVerboseDateString(this.dueDate as Date)}`
		},

		options(): ReminderOption[] {
			const computeOption = (option: ReminderOption) => {
				const dateTime = getDateTime(option.dateTimePreset)
				return {
					...option,
					ariaLabel: `${option.ariaLabel} — ${getVerboseDateString(dateTime)}`,
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
