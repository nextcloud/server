<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
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
		<label for="replacement-search-input">{{ $t('dav', 'Out of office replacement (optional)') }}</label>
		<NcSelect ref="select"
			v-model="replacementUser"
			input-id="replacement-search-input"
			:loading="searchLoading"
			:placeholder="$t('dav', 'Name of the replacement')"
			:clear-search-on-blur="() => false"
			:user-select="true"
			:options="options"
			@search="asyncFind">
			<template #no-options="{ search }">
				{{ search ?$t('dav', 'No results.') : $t('dav', 'Start typing.') }}
			</template>
		</NcSelect>
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
import { getCurrentUser } from '@nextcloud/auth'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import { generateOcsUrl } from '@nextcloud/router'
import { ShareType } from '@nextcloud/sharing'
import { formatDateAsYMD } from '../utils/date.js'
import axios from '@nextcloud/axios'
import debounce from 'debounce'
import logger from '../service/logger.js'

import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcTextField from '@nextcloud/vue/dist/Components/NcTextField.js'
import NcTextArea from '@nextcloud/vue/dist/Components/NcTextArea.js'
import NcSelect from '@nextcloud/vue/dist/Components/NcSelect.js'
import NcDateTimePickerNative from '@nextcloud/vue/dist/Components/NcDateTimePickerNative.js'

export default {
	name: 'AbsenceForm',
	components: {
		NcButton,
		NcTextField,
		NcTextArea,
		NcDateTimePickerNative,
		NcSelect,
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
				console.error('Error fetching suggestions', error)
				return
			}

			const data = request.data.ocs.data
			const exact = request.data.ocs.data.exact
			data.exact = [] // removing exact from general results
			const rawExactSuggestions = exact.users
			const rawSuggestions = data.users
			console.info('rawExactSuggestions', rawExactSuggestions)
			console.info('rawSuggestions', rawSuggestions)
			// remove invalid data and format to user-select layout
			const exactSuggestions = rawExactSuggestions
				.map(share => this.formatForMultiselect(share))
			const suggestions = rawSuggestions
				.map(share => this.formatForMultiselect(share))

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

			this.options = allSuggestions.map(item => {
				// Make sure that items with duplicate displayName get the shareWith applied as a description
				if (nameCounts[item.displayName] > 1 && !item.desc) {
					return { ...item, desc: item.shareWithDisplayNameUnique }
				}
				return item
			})

			this.searchLoading = false
			console.info('suggestions', this.options)
		},

		/**
		 * Debounce getSuggestions
		 *
		 * @param {...*} args the arguments
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
					replacementUserDisplayName: this.replacementUser?.displayName ?? null,
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
