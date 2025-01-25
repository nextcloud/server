<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="sharing-search">
		<label class="hidden-visually" for="sharing-search-input">
			{{ isExternal ? t('files_sharing', 'Enter external recipients')
				: t('files_sharing', 'Search for internal recipients') }}
		</label>
		<NcSelect ref="select"
			v-model="value"
			input-id="sharing-search-input"
			class="sharing-search__input"
			:disabled="!canReshare"
			:loading="loading"
			:filterable="false"
			:placeholder="inputPlaceholder"
			:clear-search-on-blur="() => false"
			:user-select="true"
			:options="options"
			:label-outside="true"
			@search="asyncFind"
			@option:selected="onSelected">
			<template #no-options="{ search }">
				{{ search ? noResultText : placeholder }}
			</template>
		</NcSelect>
	</div>
</template>

<script>
import { generateOcsUrl } from '@nextcloud/router'
import { getCurrentUser } from '@nextcloud/auth'
import { getCapabilities } from '@nextcloud/capabilities'
import axios from '@nextcloud/axios'
import debounce from 'debounce'
import NcSelect from '@nextcloud/vue/dist/Components/NcSelect.js'

import Config from '../services/ConfigService.ts'
import Share from '../models/Share.ts'
import ShareRequests from '../mixins/ShareRequests.js'
import ShareDetails from '../mixins/ShareDetails.js'
import { ShareType } from '@nextcloud/sharing'

export default {
	name: 'SharingInput',

	components: {
		NcSelect,
	},

	mixins: [ShareRequests, ShareDetails],

	props: {
		shares: {
			type: Array,
			default: () => [],
			required: true,
		},
		linkShares: {
			type: Array,
			default: () => [],
			required: true,
		},
		fileInfo: {
			type: Object,
			default: () => {},
			required: true,
		},
		reshare: {
			type: Share,
			default: null,
		},
		canReshare: {
			type: Boolean,
			required: true,
		},
		isExternal: {
			type: Boolean,
			default: false,
		},
		placeholder: {
			type: String,
			default: '',
		},
	},

	data() {
		return {
			config: new Config(),
			loading: false,
			query: '',
			recommendations: [],
			ShareSearch: OCA.Sharing.ShareSearch.state,
			suggestions: [],
			value: null,
		}
	},

	computed: {
		/**
		 * Implement ShareSearch
		 * allows external appas to inject new
		 * results into the autocomplete dropdown
		 * Used for the guests app
		 *
		 * @return {Array}
		 */
		externalResults() {
			return this.ShareSearch.results
		},
		inputPlaceholder() {
			const allowRemoteSharing = this.config.isRemoteShareAllowed

			if (!this.canReshare) {
				return t('files_sharing', 'Resharing is not allowed')
			}
			if (this.placeholder) {
				return this.placeholder
			}

			// We can always search with email addresses for users too
			if (!allowRemoteSharing) {
				return t('files_sharing', 'Name or email …')
			}

			return t('files_sharing', 'Name, email, or Federated Cloud ID …')
		},

		isValidQuery() {
			return this.query && this.query.trim() !== '' && this.query.length > this.config.minSearchStringLength
		},

		options() {
			if (this.isValidQuery) {
				return this.suggestions
			}
			return this.recommendations
		},

		noResultText() {
			if (this.loading) {
				return t('files_sharing', 'Searching …')
			}
			return t('files_sharing', 'No elements found.')
		},
	},

	mounted() {
		this.getRecommendations()
	},

	methods: {
		onSelected(option) {
			this.value = null // Reset selected option
			this.openSharingDetails(option)
		},

		async asyncFind(query) {
			// save current query to check if we display
			// recommendations or search results
			this.query = query.trim()
			if (this.isValidQuery) {
				// start loading now to have proper ux feedback
				// during the debounce
				this.loading = true
				await this.debounceGetSuggestions(query)
			}
		},

		/**
		 * Get suggestions
		 *
		 * @param {string} search the search query
		 * @param {boolean} [lookup] search on lookup server
		 */
		async getSuggestions(search, lookup = false) {
			this.loading = true

			if (getCapabilities().files_sharing.sharee.query_lookup_default === true) {
				lookup = true
			}

			let shareType = []

			if (this.isExternal) {
				shareType.push(ShareType.Remote)
				shareType.push(ShareType.RemoteGroup)
			} else {
				// Merge shareType array
				shareType = shareType.concat([
					ShareType.User,
					ShareType.Group,
					ShareType.Team,
					ShareType.Room,
					ShareType.Guest,
					ShareType.Deck,
					ShareType.ScienceMesh,
				])

			}

			if (getCapabilities().files_sharing.public.enabled === true && this.isExternal) {
				shareType.push(ShareType.Email)
			}

			let request = null
			try {
				request = await axios.get(generateOcsUrl('apps/files_sharing/api/v1/sharees'), {
					params: {
						format: 'json',
						itemType: this.fileInfo.type === 'dir' ? 'folder' : 'file',
						search,
						lookup,
						perPage: this.config.maxAutocompleteResults,
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

			// flatten array of arrays
			const rawExactSuggestions = Object.values(exact).reduce((arr, elem) => arr.concat(elem), [])
			const rawSuggestions = Object.values(data).reduce((arr, elem) => arr.concat(elem), [])

			// remove invalid data and format to user-select layout
			const exactSuggestions = this.filterOutExistingShares(rawExactSuggestions)
				.map(share => this.formatForMultiselect(share))
				// sort by type so we can get user&groups first...
				.sort((a, b) => a.shareType - b.shareType)
			const suggestions = this.filterOutExistingShares(rawSuggestions)
				.map(share => this.formatForMultiselect(share))
				// sort by type so we can get user&groups first...
				.sort((a, b) => a.shareType - b.shareType)

			// lookup clickable entry
			// show if enabled and not already requested
			const lookupEntry = []
			if (data.lookupEnabled && !lookup) {
				lookupEntry.push({
					id: 'global-lookup',
					isNoUser: true,
					displayName: t('files_sharing', 'Search globally'),
					lookup: true,
				})
			}

			// if there is a condition specified, filter it
			const externalResults = this.externalResults.filter(result => !result.condition || result.condition(this))

			const allSuggestions = exactSuggestions.concat(suggestions).concat(externalResults).concat(lookupEntry)

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

			this.suggestions = allSuggestions.map(item => {
				// Make sure that items with duplicate displayName get the shareWith applied as a description
				if (nameCounts[item.displayName] > 1 && !item.desc) {
					return { ...item, desc: item.shareWithDisplayNameUnique }
				}
				return item
			})

			this.loading = false
			console.info('suggestions', this.suggestions)
		},

		/**
		 * Debounce getSuggestions
		 *
		 * @param {...*} args the arguments
		 */
		debounceGetSuggestions: debounce(function(...args) {
			this.getSuggestions(...args)
		}, 300),

		/**
		 * Get the sharing recommendations
		 */
		async getRecommendations() {
			this.loading = true

			let request = null
			try {
				request = await axios.get(generateOcsUrl('apps/files_sharing/api/v1/sharees_recommended'), {
					params: {
						format: 'json',
						itemType: this.fileInfo.type,
					},
				})
			} catch (error) {
				console.error('Error fetching recommendations', error)
				return
			}

			// Add external results from the OCA.Sharing.ShareSearch api
			const externalResults = this.externalResults.filter(result => !result.condition || result.condition(this))

			// flatten array of arrays
			const rawRecommendations = Object.values(request.data.ocs.data.exact)
				.reduce((arr, elem) => arr.concat(elem), [])

			// remove invalid data and format to user-select layout
			this.recommendations = this.filterOutExistingShares(rawRecommendations)
				.map(share => this.formatForMultiselect(share))
				.concat(externalResults)

			this.loading = false
			console.info('recommendations', this.recommendations)
		},

		/**
		 * Filter out existing shares from
		 * the provided shares search results
		 *
		 * @param {object[]} shares the array of shares object
		 * @return {object[]}
		 */
		filterOutExistingShares(shares) {
			return shares.reduce((arr, share) => {
				// only check proper objects
				if (typeof share !== 'object') {
					return arr
				}
				try {
					if (share.value.shareType === ShareType.User) {
						// filter out current user
						if (share.value.shareWith === getCurrentUser().uid) {
							return arr
						}

						// filter out the owner of the share
						if (this.reshare && share.value.shareWith === this.reshare.owner) {
							return arr
						}
					}

					// filter out existing mail shares
					if (share.value.shareType === ShareType.Email) {
						const emails = this.linkShares.map(elem => elem.shareWith)
						if (emails.indexOf(share.value.shareWith.trim()) !== -1) {
							return arr
						}
					} else { // filter out existing shares
						// creating an object of uid => type
						const sharesObj = this.shares.reduce((obj, elem) => {
							obj[elem.shareWith] = elem.type
							return obj
						}, {})

						// if shareWith is the same and the share type too, ignore it
						const key = share.value.shareWith.trim()
						if (key in sharesObj
							&& sharesObj[key] === share.value.shareType) {
							return arr
						}
					}

					// ALL GOOD
					// let's add the suggestion
					arr.push(share)
				} catch {
					return arr
				}
				return arr
			}, [])
		},

		/**
		 * Get the icon based on the share type
		 *
		 * @param {number} type the share type
		 * @return {string} the icon class
		 */
		shareTypeToIcon(type) {
			switch (type) {
			case ShareType.Guest:
				// default is a user, other icons are here to differentiate
				// themselves from it, so let's not display the user icon
				// case ShareType.Remote:
				// case ShareType.User:
				return {
					icon: 'icon-user',
					iconTitle: t('files_sharing', 'Guest'),
				}
			case ShareType.RemoteGroup:
			case ShareType.Group:
				return {
					icon: 'icon-group',
					iconTitle: t('files_sharing', 'Group'),
				}
			case ShareType.Email:
				return {
					icon: 'icon-mail',
					iconTitle: t('files_sharing', 'Email'),
				}
			case ShareType.Team:
				return {
					icon: 'icon-teams',
					iconTitle: t('files_sharing', 'Team'),
				}
			case ShareType.Room:
				return {
					icon: 'icon-room',
					iconTitle: t('files_sharing', 'Talk conversation'),
				}
			case ShareType.Deck:
				return {
					icon: 'icon-deck',
					iconTitle: t('files_sharing', 'Deck board'),
				}
			case ShareType.Sciencemesh:
				return {
					icon: 'icon-sciencemesh',
					iconTitle: t('files_sharing', 'ScienceMesh'),
				}
			default:
				return {}
			}
		},

		/**
		 * Format shares for the multiselect options
		 *
		 * @param {object} result select entry item
		 * @return {object}
		 */
		formatForMultiselect(result) {
			let subname
			if (result.value.shareType === ShareType.User && this.config.shouldAlwaysShowUnique) {
				subname = result.shareWithDisplayNameUnique ?? ''
			} else if ((result.value.shareType === ShareType.Remote
					|| result.value.shareType === ShareType.RemoteGroup
			) && result.value.server) {
				subname = t('files_sharing', 'on {server}', { server: result.value.server })
			} else if (result.value.shareType === ShareType.Email) {
				subname = result.value.shareWith
			} else {
				subname = result.shareWithDescription ?? ''
			}

			return {
				shareWith: result.value.shareWith,
				shareType: result.value.shareType,
				user: result.uuid || result.value.shareWith,
				isNoUser: result.value.shareType !== ShareType.User,
				displayName: result.name || result.label,
				subname,
				shareWithDisplayNameUnique: result.shareWithDisplayNameUnique || '',
				...this.shareTypeToIcon(result.value.shareType),
			}
		},
	},
}
</script>

<style lang="scss">
.sharing-search {
	display: flex;
	flex-direction: column;
	margin-bottom: 4px;

	label[for="sharing-search-input"] {
		margin-bottom: 2px;
	}

	&__input {
		width: 100%;
		margin: 10px 0;
	}
}

.vs__dropdown-menu {
	// properly style the lookup entry
	span[lookup] {
		.avatardiv {
			background-image: var(--icon-search-white);
			background-repeat: no-repeat;
			background-position: center;
			background-color: var(--color-text-maxcontrast) !important;
			.avatardiv__initials-wrapper {
				display: none;
			}
		}
	}
}
</style>
