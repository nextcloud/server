<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="profile-picker">
		<div class="profile-picker__heading">
			<h2>
				{{ t('user_picker', 'Profile picker') }}
			</h2>
			<div class="input-wrapper">
				<NcSelect
					ref="profiles-search-input"
					v-model="selectedProfile"
					input-id="profiles-search"
					:loading="loading"
					:filterable="false"
					:placeholder="t('user_picker', 'Search for a user profile')"
					:clear-search-on-blur="() => false"
					:multiple="false"
					:options="options"
					label="displayName"
					@search="searchForProfile"
					@option:selecting="resolveResult">
					<template #no-options="{ search }">
						{{ search ? noResultText : t('user_picker', 'Search for a user profile. Start typing') }}
					</template>
				</NcSelect>
			</div>
			<NcEmptyContent class="empty-content">
				<template #icon>
					<AccountOutline :size="20" />
				</template>
			</NcEmptyContent>
		</div>
		<div class="profile-picker__footer">
			<NcButton
				v-if="selectedProfile !== null"
				variant="primary"
				:aria-label="t('user_picker', 'Insert selected user profile link')"
				:disabled="loading || selectedProfile === null"
				@click="submit">
				{{ t('user_picker', 'Insert') }}
				<template #icon>
					<ArrowRightIcon />
				</template>
			</NcButton>
		</div>
	</div>
</template>

<script>
import axios from '@nextcloud/axios'
import { generateOcsUrl, generateUrl } from '@nextcloud/router'
import debounce from 'debounce'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import AccountOutline from 'vue-material-design-icons/AccountOutline.vue'
import ArrowRightIcon from 'vue-material-design-icons/ArrowRight.vue'
import { logger } from '../utils/logger.ts'

export default {
	name: 'ProfilesCustomPicker',

	components: {
		NcSelect,
		NcButton,
		NcEmptyContent,
		AccountOutline,
		ArrowRightIcon,
	},

	props: {
		providerId: {
			type: String,
			required: true,
		},

		accessible: {
			type: Boolean,
			default: false,
		},
	},

	data() {
		return {
			searchQuery: '',
			loading: false,
			resultUrl: null,
			reference: null,
			profiles: [],
			selectedProfile: null,
			abortController: null,
		}
	},

	computed: {
		options() {
			if (this.searchQuery !== '') {
				return this.profiles
			}
			return []
		},

		noResultText() {
			return this.loading ? t('user_picker', 'Searching …') : t('user_picker', 'Not found')
		},
	},

	mounted() {
		this.focusOnInput()
	},

	methods: {
		focusOnInput() {
			this.$nextTick(() => {
				this.$refs['profiles-search-input'].$el.getElementsByTagName('input')[0]?.focus()
			})
		},

		async searchForProfile(query) {
			if (query.trim() === '' || query.trim().length < 3) {
				return
			}
			this.searchQuery = query.trim()
			this.loading = true
			await this.debounceFindProfiles(query)
		},

		debounceFindProfiles: debounce(function(...args) {
			this.findProfiles(...args)
		}, 300),

		async findProfiles(query) {
			const url = generateOcsUrl('core/autocomplete/get?search={searchQuery}&itemType=%20&itemId=%20&shareTypes[]=0&limit=20', { searchQuery: query })
			try {
				const res = await axios.get(url)
				this.profiles = res.data.ocs.data.map((userAutocomplete) => {
					return {
						user: userAutocomplete.id,
						displayName: userAutocomplete.label,
						icon: userAutocomplete.icon,
						subtitle: userAutocomplete.subline,
						isNoUser: userAutocomplete.source.startsWith('users'),
					}
				})
			} catch (error) {
				logger.error('user_picker: error while searching for users', { error })
			} finally {
				this.loading = false
			}
		},

		submit() {
			this.resultUrl = window.location.origin + generateUrl(`/u/${this.selectedProfile.user.trim().toLowerCase()}`, null, { noRewrite: true })
			this.$emit('submit', this.resultUrl)
			this.$el.dispatchEvent(new CustomEvent('submit', { detail: this.resultUrl, bubbles: true }))
		},

		async resolveResult(selectedItem) {
			this.loading = true
			this.abortController = new AbortController()
			this.selectedProfile = selectedItem
			this.resultUrl = window.location.origin + generateUrl(`/u/${this.selectedProfile.user.trim().toLowerCase()}`, null, { noRewrite: true })
			try {
				const res = await axios.get(generateOcsUrl('references/resolve', 2) + '?reference=' + encodeURIComponent(this.resultUrl), {
					signal: this.abortController.signal,
				})
				this.reference = res.data.ocs.data.references[this.resultUrl]
			} catch (error) {
				logger.error('user_picker: error resolving the user profile link', { error })
			} finally {
				this.loading = false
			}
		},

		clearSelection() {
			this.selectedProfile = null
			this.resultUrl = null
			this.reference = null
		},
	},
}
</script>

<style scoped lang="scss">
.profile-picker {
	width: 100%;
	min-height: 450px;
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: space-between;
	padding: 12px 16px 16px 16px;

	&__heading, .select {
		width: 100%;

		h2 {
			text-align: center;
		}
	}

	&__footer {
		width: 100%;
		display: flex;
		align-items: center;
		justify-content: end;
		margin-top: 12px;

		> * {
			margin-left: 4px;
		}
	}
}
</style>
