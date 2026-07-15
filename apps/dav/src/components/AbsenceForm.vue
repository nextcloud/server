<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<form :class="$style.absenceForm" @submit.prevent="saveForm">
		<div :class="$style.absenceForm__pickerContainer">
			<NcDateTimePickerNative
				id="absence-first-day"
				v-model="firstDay"
				:class="$style.absenceForm__picker"
				:label="t('dav', 'First day')"
				required />
			<NcDateTimePickerNative
				id="absence-last-day"
				v-model="lastDay"
				:class="$style.absenceForm__picker"
				:label="t('dav', 'Last day (inclusive)')"
				required />
		</div>
		<label for="replacement-search-input">{{ t('dav', 'Out of office replacement (optional)') }}</label>
		<NcSelectUsers
			v-model="replacementUser"
			inputId="replacement-search-input"
			:loading="searchLoading"
			:placeholder="t('dav', 'Name of the replacement')"
			:options="options"
			@search="asyncFind" />
		<NcTextField v-model="status" :label="t('dav', 'Short absence status')" :required="true" />
		<div :class="$style.absenceForm__longMessageContainer">
			<NcTextArea
				v-model="message"
				:inputClass="$style.absenceForm__longMessage"
				:label="t('dav', 'Long absence Message')"
				required
				resize="none"
				rows="6" />
		</div>

		<div :class="$style.absenceForm__actions">
			<NcButton
				:disabled="loading || !valid"
				variant="primary"
				type="submit">
				{{ t('dav', 'Save') }}
			</NcButton>
			<NcButton
				:disabled="loading || !valid"
				variant="error"
				@click="clearAbsence">
				{{ t('dav', 'Disable absence') }}
			</NcButton>
		</div>
	</form>
</template>

<script>
import { getCurrentUser } from '@nextcloud/auth'
import axios from '@nextcloud/axios'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { generateOcsUrl } from '@nextcloud/router'
import { ShareType } from '@nextcloud/sharing'
import debounce from 'debounce'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcDateTimePickerNative from '@nextcloud/vue/components/NcDateTimePickerNative'
import NcSelectUsers from '@nextcloud/vue/components/NcSelectUsers'
import NcTextArea from '@nextcloud/vue/components/NcTextArea'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import { logger } from '../service/logger.ts'
import { formatDateAsYMD } from '../utils/date.ts'

/**
 * Adjusts a date so `NcDateTimePickerNative` shows the same date
 * instead of shifting it to the browsers timezone.
 *
 * @param {Date} date - e.g., new Date("1987-12-01")
 * @return {Date}
 */
function inputAdjustDate(date) {
	// e.g., date === Mon Nov 30 1987 16:00:00 GMT-0800 (Pacific Standard Time)
	const timezoneOffsetMilliseconds = date.getTimezoneOffset() * 60 * 1000
	// e.g., Tue Dec 01 1987 00:00:00 GMT-0800 (Pacific Standard Time)
	const adjustedDate = new Date(date.getTime() + timezoneOffsetMilliseconds)
	// `NcDateTimePickerNative` will display this as 12/01/1987
	return adjustedDate
}

export default {
	name: 'AbsenceForm',
	components: {
		NcButton,
		NcTextField,
		NcTextArea,
		NcDateTimePickerNative,
		NcSelectUsers,
	},

	setup() {
		return { t }
	},

	data() {
		const { firstDay, lastDay, status, message, replacementUserId, replacementUserDisplayName } = loadState('dav', 'absence', {})
		const firstDayDate = firstDay ? new Date(firstDay) : new Date()
		const firstDayInputAdjusted = inputAdjustDate(firstDayDate)
		const lastDayInputAdjusted = lastDay ? inputAdjustDate(new Date(lastDay)) : null
		return {
			loading: false,
			status: status ?? '',
			message: message ?? '',
			firstDay: firstDayInputAdjusted,
			lastDay: lastDayInputAdjusted,
			replacementUserId,
			replacementUser: replacementUserId ? { user: replacementUserId, displayName: replacementUserDisplayName } : null,
			searchLoading: false,
			options: [],
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

		/**
		 * Format shares for the multiselect options
		 *
		 * @param {object} result select entry item
		 * @return {object}
		 */
		formatForMultiselect(result) {
			return {
				user: result.uuid || result.value.shareWith,
				displayName: result.name || result.label,
				subtitle: result.dsc | '',
			}
		},

		async asyncFind(query) {
			this.searchLoading = true
			await this.debounceGetSuggestions(query.trim())
		},

		/**
		 * Get suggestions
		 *
		 * @param {string} search the search query
		 */
		async getSuggestions(search) {
			const shareType = [
				ShareType.User,
			]

			let request
			try {
				request = await axios.get(generateOcsUrl('apps/files_sharing/api/v1/sharees'), {
					params: {
						format: 'json',
						itemType: 'file',
						search,
						shareType,
					},
				})
			} catch (error) {
				logger.error('Error fetching suggestions', { error })
				return
			}

			const data = request.data.ocs.data
			const exact = request.data.ocs.data.exact
			data.exact = [] // removing exact from general results
			const rawExactSuggestions = exact.users
			const rawSuggestions = data.users
			logger.info('AbsenceForm raw suggestions', { rawExactSuggestions, rawSuggestions })
			// remove invalid data and format to user-select layout
			const exactSuggestions = rawExactSuggestions
				.map((share) => this.formatForMultiselect(share))
			const suggestions = rawSuggestions
				.map((share) => this.formatForMultiselect(share))

			const allSuggestions = exactSuggestions.concat(suggestions)

			// Count occurrences of display names in order to provide a distinguishable description if needed
			const nameCounts = allSuggestions.reduce((nameCounts, result) => {
				if (!result.displayName) {
					return nameCounts
				}
				if (!nameCounts[result.displayName]) {
					nameCounts[result.displayName] = 0
				}
				nameCounts[result.displayName]++
				return nameCounts
			}, {})

			this.options = allSuggestions.map((item) => {
				// Make sure that items with duplicate displayName get the shareWith applied as a description
				if (nameCounts[item.displayName] > 1 && !item.desc) {
					return { ...item, desc: item.shareWithDisplayNameUnique }
				}
				return item
			})

			this.searchLoading = false
			logger.info('AbsenseForm suggestions', { options: this.options })
		},

		/**
		 * Debounce getSuggestions
		 *
		 * @param {[string]} args - The arguments
		 */
		debounceGetSuggestions: debounce(function(...args) {
			this.getSuggestions(...args)
		}, 300),

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
					replacementUserId: this.replacementUser?.user ?? null,
				})
				showSuccess(t('dav', 'Absence saved'))
			} catch (error) {
				showError(t('dav', 'Failed to save your absence settings'))
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
				showSuccess(t('dav', 'Absence cleared'))
			} catch (error) {
				showError(t('dav', 'Failed to clear your absence settings'))
				logger.error('Could not clear absence', { error })
			} finally {
				this.loading = false
			}
		},
	},
}
</script>

<style module>
.absenceForm {
	display: flex;
	flex-direction: column;
	gap: 5px;
}

.absenceForm__pickerContainer {
	display: flex;
	gap: 10px;
	width: 100%;
}

.absenceForm__picker {
	flex: 1 auto;

	:global(.native-datetime-picker--input) {
		margin-bottom: 0;
	}
}

.absenceForm__longMessage {
	height: calc(var(--default-line-height) * 6 * var(--font-size-small));
}

.absenceForm__longMessageContainer {
	height: calc(var(--default-line-height) * 6 * var(--font-size-small) + var(--default-grid-baseline) * 2);
	display: flex;
	flex-direction: column;
	justify-content: start;
}

.absenceForm__actions {
	display: flex;
	gap: 5px;
}
</style>
