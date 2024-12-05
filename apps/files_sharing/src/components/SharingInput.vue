<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="sharing-search">
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
import Share from '../models/Share.ts'
import ShareRequests from '../mixins/ShareRequests.js'
import ShareDetails from '../mixins/ShareDetails.js'
import { ShareType } from '@nextcloud/sharing'

import getRecommendations from '../services/recommendations.js';
import getSuggestions from '../services/suggestions.js';

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
	},

	data() {
		return {
			config: new Config(),
			loading: false,
			query: '',
			recommendations: [],
			suggestions: [],
			value: null,
		}
	},

	computed: {
		inputPlaceholder() {
			const allowRemoteSharing = this.config.isRemoteShareAllowed

			if (!this.canReshare) {
				return t('files_sharing', 'Resharing is not allowed')
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
		 */
		async getSuggestions(search) {
			this.loading = true

			try {
				this.suggestions = await getSuggestions(search, this.fileInfo, this, this.config)
				this.loading = false
				console.info('suggestions', this.suggestions)
			} catch (error) {
				console.error('Error fetching suggestions', error)
				return
			}
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

			try {
				this.recommendations = await getRecommendations(this.fileInfo, this, this.config);
				console.info('recommendations', this.recommendations)
			} catch (error) {
				console.error('Error fetching recommendations', error)
				return
			}

			this.loading = false
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
