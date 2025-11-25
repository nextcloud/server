<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<form class="absence" @submit.prevent="saveForm">
		<div class="absence__dates">
			<NcDateTimePickerNative
				id="absence-first-day"
				v-model="firstDay"
				:label="t('dav', 'First day')"
				class="absence__dates__picker"
				:required="true" />
			<NcDateTimePickerNative
				id="absence-last-day"
				v-model="lastDay"
				:label="t('dav', 'Last day (inclusive)')"
				class="absence__dates__picker"
				:required="true" />
		</div>
		<label for="replacement-search-input">{{ t('dav', 'Out of office replacement (optional)') }}</label>
		<NcSelectUsers
			v-model="replacementUser"
			input-id="replacement-search-input"
			:loading="searchLoading"
			:placeholder="t('dav', 'Name of the replacement')"
			:options="options"
			@search="asyncFind" />
		<NcTextField v-model="status" :label="t('dav', 'Short absence status')" :required="true" />
		<NcTextArea v-model="message" :label="t('dav', 'Long absence Message')" :required="true" />

		<div class="absence__buttons">
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
		return {
			loading: false,
			status: status ?? '',
			message: message ?? '',
			firstDay: firstDay ? new Date(firstDay) : new Date(),
			lastDay: lastDay ? new Date(lastDay) : null,
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

			let request = null
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

			:deep(.native-datetime-picker--input) {
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
