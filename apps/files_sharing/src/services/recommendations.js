import { generateOcsUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import filterOutExistingShares from './existingSharesFilter.js'
import formatForMultiselect from '../utils/formatForMultiselect.js'

/**
 * Get share recommendations.
 *
 * @param {Object} fileInfo a file info object
 * @param {Object} context the object to pass to external share result condition functions
 * @param {Object} config the object to pass to external share result condition functions
 * @param {Object} config.shouldAlwaysShowUnique always show unique display names
 * @param {Array} array of recommendations returned by API and extended with results from other share providers
 * @return
 */
export default async (fileInfo, context, config = { }) => {
	const request = await axios.get(generateOcsUrl('apps/files_sharing/api/v1/sharees_recommended'), {
		params: {
			format: 'json',
			itemType: fileInfo.type,
		},
	})

	const rawExternalResults = OCA.Sharing.ShareSearch.state.results

	const externalResults = rawExternalResults.filter(result => !result.condition || result.condition(context))

	// flatten array of arrays
	const rawRecommendations = Object.values(request.data.ocs.data.exact)
		.reduce((arr, elem) => arr.concat(elem), [])

	// remove invalid data and format to user-select layout
	return filterOutExistingShares(rawRecommendations, context)
		.map(share => formatForMultiselect(share, config.shouldAlwaysShowUnique ?? false))
		.concat(externalResults)
}
