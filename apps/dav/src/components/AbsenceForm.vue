<!--
  - @copyright Copyright (c) 2023 Richard Steinmetz <richard@steinmetz.cloud>
  -
  - @author Richard Steinmetz <richard@steinmetz.cloud>
  -
  - @license AGPL-3.0-or-later
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU General Public License as published by
  - the Free Software Foundation, either version 3 of the License, or
  - (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU General Public License for more details.
  -
  - You should have received a copy of the GNU General Public License
  - along with this program.  If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template>
	<div class="absence">
		<div class="absence__dates">
			<NcDateTimePickerNative id="absence-first-day"
				v-model="firstDay"
				:label="$t('dav', 'First day')"
				class="absence__dates__picker" />
			<NcDateTimePickerNative id="absence-last-day"
				v-model="lastDay"
				:label="$t('dav', 'Last day (inclusive)')"
				class="absence__dates__picker" />
		</div>
		<NcTextField :value.sync="status" :label="$t('dav', 'Short absence status')" />
		<NcTextArea :value.sync="message" :label="$t('dav', 'Long absence Message')" />

		<div class="absence__buttons">
			<NcButton :disabled="loading || !valid"
				type="primary"
				@click="saveForm">
				{{ $t('dav', 'Save') }}
			</NcButton>
			<NcButton :disabled="loading || !valid"
				type="error"
				@click="clearAbsence">
				{{ $t('dav', 'Disable absence') }}
			</NcButton>
		</div>
	</div>
</template>

<script>
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcTextField from '@nextcloud/vue/dist/Components/NcTextField.js'
import NcTextArea from '@nextcloud/vue/dist/Components/NcTextArea.js'
import NcDateTimePickerNative from '@nextcloud/vue/dist/Components/NcDateTimePickerNative.js'
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { formatDateAsYMD } from '../utils/date.js'
import { loadState } from '@nextcloud/initial-state'
import { showError } from '@nextcloud/dialogs'

export default {
	name: 'AbsenceForm',
	components: {
		NcButton,
		NcTextField,
		NcTextArea,
		NcDateTimePickerNative,
	},
	data() {
		const { firstDay, lastDay, status, message } = loadState('dav', 'absence', {})

		return {
			loading: false,
			status: status ?? '',
			message: message ?? '',
			firstDay: firstDay ? new Date(firstDay) : new Date(),
			lastDay: lastDay ? new Date(lastDay) : null,
		}
	},
	computed: {
		/**
		 * @return {boolean}
		 */
		valid() {
			return !!this.firstDay
				&& !!this.lastDay
				&& !!this.status
				&& this.lastDay > this.firstDay
		},
	},
	methods: {
		resetForm() {
			this.status = ''
			this.message = ''
			this.firstDay = new Date()
			this.lastDay = null
		},
		async saveForm() {
			if (!this.valid) {
				return
			}

			this.loading = true
			try {
				await axios.post(generateUrl('/apps/dav/settings/absence'), {
					firstDay: formatDateAsYMD(this.firstDay),
					lastDay: formatDateAsYMD(this.lastDay),
					status: this.status,
					message: this.message,
				})
			} catch (error) {
				showError(this.$t('dav', 'Failed to save your absence settings'))
			} finally {
				this.loading = false
			}
		},
		async clearAbsence() {
			this.loading = true
			try {
				await axios.delete(generateUrl('/apps/dav/settings/absence'))
				this.resetForm()
			} catch (error) {
				showError(this.$t('dav', 'Failed to clear your absence settings'))
			} finally {
				this.loading = false
			}
		},
	},
}
</script>

<style lang="scss" scoped>
.absence {
	display: flex;
	flex-direction: column;
	gap: 5px;

	&__dates {
		display: flex;
		gap: 10px;
		width: 100%;

		&__picker {
			flex: 1 auto;

			::v-deep .native-datetime-picker--input {
				margin-bottom: 0;
			}
		}
	}

	&__buttons {
		display: flex;
		gap: 5px;
	}
}
</style>
