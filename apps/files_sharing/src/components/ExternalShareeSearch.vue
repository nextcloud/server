<!--
  - SPDX-FileLicenseText: 2019, 2024 Nextcloud GmbH and Nextcloud contributors, STRATO AG
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<!--
TODO: Initially copied from ShareInput.vue for differentiation of sharee seatch by internal/external

- externalResults (OCA.Sharing.ShareSearch.state) already dropped (not needed here)
- shareType is passed with the request
- inconsistent use of trim() on shareWith in reducer alreay fixed here
- Needs more refactoring esp. with filterOutExistingShares(), formatForMultiselect()
  - filterOutExistingShares() should be solved with filter(predicateFunctions) instead
  - formatForMultiselect() could/shoul be common to both components
  - shareTypeToIcon() does not need some types here, if not refactored out, drop them
-->

<!--
Search field to look up internal sharees (shares with other internal Nextcloud users)
-->

<template>
	<div class="sharing-search">
		<NcSelect ref="select"
			v-model="value"
			input-id="sharing-search-external-input"
			class="sharing-search-external__input"
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
				{{ search ? noResultText : t('files_sharing', 'No recommendations. Start typing.') }}
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
import formatForMultiselect from '../utils/formatForMultiselect.js'
import Share from '../models/Share.ts'
import ShareRequests from '../mixins/ShareRequests.js'
import ShareDetails from '../mixins/ShareDetails.js'
import { ShareType } from '@nextcloud/sharing'
import { external as externalShareTypes, externalAllowed } from '../utils/ShareTypes.js';

export default {
	name: 'InternalShareeSearch',

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
		inputPlaceholder() {
			if (!this.canReshare) {
				return t('files_sharing', 'Resharing is not allowed')
			}

			if (getCapabilities().files_sharing.public.enabled !== true) {
				return t('files_sharing', 'Federated Cloud ID …')
			}

			return t('files_sharing', 'Email, or Federated Cloud ID …')
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
		 */
		async getSuggestions(search) {
			this.loading = true

			const lookup = getCapabilities().files_sharing.sharee.query_lookup_default === true;

			let request = null
			try {
				request = await axios.get(generateOcsUrl('apps/files_sharing/api/v1/sharees'), {
					params: {
						format: 'json',
						itemType: this.fileInfo.type === 'dir' ? 'folder' : 'file',
						search,
						lookup,
						perPage: this.config.maxAutocompleteResults,
						shareType: externalAllowed,
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
			const rawExactSuggestions = Object.values(exact).flat()
			const rawSuggestions = Object.values(data).flat()

			const shouldAlwaysShowUnique = this.config.shouldAlwaysShowUnique

			// remove invalid data and format to user-select layout
			const exactSuggestions = this.filterOutExistingShares(rawExactSuggestions)
				.map(share => formatForMultiselect(share, shouldAlwaysShowUnique))
				// sort by type so we can get user&groups first...
				.sort((a, b) => a.shareType - b.shareType)
			const suggestions = this.filterOutExistingShares(rawSuggestions)
				.map(share => formatForMultiselect(share, shouldAlwaysShowUnique))
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

			const allSuggestions = exactSuggestions.concat(suggestions).concat(lookupEntry)

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
						shareType: externalAllowed
					},
				})
			} catch (error) {
				console.error('Error fetching external share recommendations', error)
				return
			}

			const shouldAlwaysShowUnique = this.config.shouldAlwaysShowUnique

			const rawRecommendations = Object.values(request.data.ocs.data.exact).flat()

			// remove invalid data and format to user-select layout
			this.recommendations = this.filterOutExistingShares(rawRecommendations)
				.map(share => formatForMultiselect(share, shouldAlwaysShowUnique));

			this.loading = false
			console.info('external recommendations', this.recommendations)
		},

		/**
		 * Filter out existing shares from
		 * the provided shares search results
		 *
		 * @param {object[]} shares the array of shares object
		 * @return {object[]}
		 */
		filterOutExistingShares(shares) {
			console.log("external: filterOutExistingShares()", shares);
			return shares.reduce((arr, share) => {
				// only check proper objects
				if (typeof share !== 'object') {
					return arr
				}

				const shareType = share.value.shareType

				try {
					// Here we care only about external share types
					if (!externalShareTypes.includes(shareType)) {
						return arr
					}

					// filter out existing mail shares
					if (shareType === ShareType.Email) {
						const emails = this.linkShares.map(elem => elem.shareWith)
						if (emails.indexOf(share.value.shareWith.trim()) !== -1) {
							return arr
						}
					} else { // filter out existing shares
						console.log("non-email share with (shareWith): ", this.shares)
						// creating an object of uid => type
						const sharesObj = this.shares.reduce((obj, elem) => {
							obj[elem.shareWith.trim()] = elem.type
							return obj
						}, {})

						// if shareWith is the same and the share type too, ignore it
						const key = share.value.shareWith.trim()
						if (key in sharesObj
							&& sharesObj[key] === shareType) {
							return arr
						}
					}

					// ALL GOOD
					// let's add the suggestion
					arr.push(share)
				} catch (e) {
					return arr
				}
				return arr
			}, [])
		},
	},
}
</script>

<style lang="scss">
.sharing-search {
	display: flex;
	flex-direction: column;
	margin-bottom: 4px;

	label[for="sharing-search-external-input"] {
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
