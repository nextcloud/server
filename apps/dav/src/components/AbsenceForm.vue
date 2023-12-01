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
	<form class="absence" @submit.prevent="saveForm">
		<div class="absence__dates">
			<NcDateTimePickerNative id="absence-first-day"
				v-model="firstDay"
				:label="$t('dav', 'First day')"
				class="absence__dates__picker"
				:required="true" />
			<NcDateTimePickerNative id="absence-last-day"
				v-model="lastDay"
				:label="$t('dav', 'Last day (inclusive)')"
				class="absence__dates__picker"
				:required="true" />
		</div>
		<NcTextField :value.sync="status" :label="$t('dav', 'Short absence status')" :required="true" />
		<NcTextArea :value.sync="message" :label="$t('dav', 'Long absence Message')" :required="true" />

		<div class="absence__buttons">
			<NcButton :disabled="loading || !valid"
				type="primary"
				native-type="submit">
				{{ $t('dav', 'Save') }}
			</NcButton>
			<NcButton :disabled="loading || !valid"
				type="error"
				@click="clearAbsence">
				{{ $t('dav', 'Disable absence') }}
			</NcButton>
		</div>
	</form>
</template>

<script>
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcTextField from '@nextcloud/vue/dist/Components/NcTextField.js'
import NcTextArea from '@nextcloud/vue/dist/Components/NcTextArea.js'
import NcDateTimePickerNative from '@nextcloud/vue/dist/Components/NcDateTimePickerNative.js'
import { generateOcsUrl } from '@nextcloud/router'
import { getCurrentUser } from '@nextcloud/auth'
import axios from '@nextcloud/axios'
import { formatDateAsYMD } from '../utils/date.js'
import { loadState } from '@nextcloud/initial-state'
import { showError, showSuccess } from '@nextcloud/dialogs'

import logger from '../service/logger.js'

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
			// Translate the two date objects to midnight for an accurate comparison
			const firstDay = new Date(this.firstDay?.getTime())
			const lastDay = new Date(this.lastDay?.getTime())
			firstDay?.setHours(0, 0, 0, 0)
			lastDay?.setHours(0, 0, 0, 0)

			return !!this.firstDay
				&& !!this.lastDay
				&& !!this.status
				&& !!this.message
				&& lastDay >= firstDay
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
				await axios.post(generateOcsUrl('/apps/dav/api/v1/outOfOffice/{userId}', { userId: getCurrentUser().uid }), {
					firstDay: formatDateAsYMD(this.firstDay),
					lastDay: formatDateAsYMD(this.lastDay),
					status: this.status,
					message: this.message,
				})
				showSuccess(this.$t('dav', 'Absence saved'))
			} catch (error) {
				showError(this.$t('dav', 'Failed to save your absence settings'))
				logger.error('Could not save absence', { error })
			} finally {
				this.loading = false
			}
		},
		async clearAbsence() {
			this.loading = true
			try {
				await axios.delete(generateOcsUrl('/apps/dav/api/v1/outOfOffice/{userId}', { userId: getCurrentUser().uid }))
				this.resetForm()
				showSuccess(this.$t('dav', 'Absence cleared'))
			} catch (error) {
				showError(this.$t('dav', 'Failed to clear your absence settings'))
				logger.error('Could not clear absence', { error })
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
