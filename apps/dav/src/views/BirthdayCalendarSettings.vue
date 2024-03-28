<!--
  - @copyright Copyright (c) 2022 Cédric Neukom <github@webguy.ch>
  - @author Cédric Neukom <github@webguy.ch>
  -
  - @license GNU AGPL version 3 or any later version
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
	<SettingsSection :title="$t('dav', 'Birthday Calendar')">
		<CheckboxRadioSwitch :checked.sync="enableBirthdayCalendar">
			{{ $t('dav', 'Enable birthday calendar') }}
		</CheckboxRadioSwitch>

		<div class="select-container">
			<label for="birthdayReminder">
				{{ $t('dav', 'Birthday reminder:') }}
			</label>
			<span class="time-zone-text">
				<Multiselect v-model="birthdayReminder"
					:options="birthdayReminderOptions"
					:disabled="!enableBirthdayCalendar"
					id="birthdayReminder"></Multiselect>
			</span>
		</div>

		<Button :disabled="saving"
			type="primary"
			@click="save">
			{{ $t('dav', 'Save') }}
		</Button>
	</SettingsSection>
</template>

<script>
import {
	showError,
	showSuccess,
} from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import Button from '@nextcloud/vue/dist/Components/Button'
import CheckboxRadioSwitch
	from '@nextcloud/vue/dist/Components/CheckboxRadioSwitch'
import SettingsSection from '@nextcloud/vue/dist/Components/SettingsSection'
import Multiselect from '@nextcloud/vue/dist/Components/Multiselect'
import {
	disableBirthdayCalendar,
	enableBirthdayCalendar,
	saveBirthdayReminder
} from '../service/BirthdayCalendarService'

export default {
	name: 'BirthdayCalendarSettings',
	components: {
		Button,
		CheckboxRadioSwitch,
		SettingsSection,
		Multiselect,
	},
	data() {
		const birthdayReminderValues = [
			'',
			'PT9H',
			'-PT15H',
			'-P1DT15H',
			'-P6DT15H',
		]

		const birthdayReminderOptions = [
			t('dav', 'None'),
			t('dav', 'Same day (9 AM)'),
			t('dav', '1 day before (9 AM)'),
			t('dav', '2 days before (9 AM)'),
			t('dav', '1 week before (9 AM)'),
		]

		const initialBirthdayCalendarEnabled = loadState('dav', 'userBirthdayCalendarEnabled')
		const initialBirthdayCalendarReminderOffset = loadState('dav', 'userBirthdayCalendarReminderOffset')

		return {
			saving: false,
			isBirthdayCalendarEnabled: initialBirthdayCalendarEnabled,
			enableBirthdayCalendar: initialBirthdayCalendarEnabled,
			birthdayReminder: birthdayReminderOptions[birthdayReminderValues.indexOf(initialBirthdayCalendarReminderOffset)],
			birthdayReminderOptions,
			birthdayReminderValues,
		}
	},
	methods: {
		async save() {
			try {
				this.saving = true

				await saveBirthdayReminder(this.birthdayReminderValues[this.birthdayReminderOptions.indexOf(this.birthdayReminder)])

				if (this.isBirthdayCalendarEnabled && !this.enableBirthdayCalendar) {
					await disableBirthdayCalendar()
				} else if (!this.isBirthdayCalendarEnabled && this.enableBirthdayCalendar) {
					await enableBirthdayCalendar()
				}
				this.isBirthdayCalendarEnabled = this.enableBirthdayCalendar

				showSuccess(t('dav', 'Saved birthday calendar settings'))
			} catch (e) {
				console.error('could birthday calendar settings', e)

				showError(t('dav', 'Failed to save birthday calendar settings'))
			} finally {
				this.saving = false
			}
		},
	},
}
</script>

<style lang="scss">
.select-container {
	padding: 12px 12px 12px 0;

	> label {
		padding-right: 22px;
		font-weight: bold;
	}
}
</style>
