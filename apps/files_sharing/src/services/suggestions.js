import { generateOcsUrl } from '@nextcloud/router'
import { getCapabilities } from '@nextcloud/capabilities'
import { ShareType } from '@nextcloud/sharing'
import axios from '@nextcloud/axios'
import filterOutExistingShares from './existingSharesFilter.js'
import formatForMultiselect from '../utils/formatForMultiselect.js'

/**
 * Get suggestions
 *
 * @param {string} search the search query
 * @param {Object} fileInfo a file info object
 * @param {Object} context the object to pass to external share result condition functions
 * @param {Object} config UI config
 * @param {number} config.maxAutocompleteResults max results
 * @param {boolean} config.shouldAlwaysShowUnique show unique names
 */
export default async(search, fileInfo, context, config = { }) => {
	const lookup = getCapabilities().files_sharing.sharee.query_lookup_default === true

	const shareType = [
		ShareType.User,
		ShareType.Group,
		ShareType.Remote,
		ShareType.RemoteGroup,
		ShareType.Team,
		ShareType.Room,
		ShareType.Guest,
		ShareType.Deck,
		ShareType.ScienceMesh,
	]

	if (getCapabilities().files_sharing.public.enabled === true) {
		shareType.push(ShareType.Email)
	}

	const request = await axios.get(generateOcsUrl('apps/files_sharing/api/v1/sharees'), {
		params: {
			format: 'json',
			itemType: fileInfo.type === 'dir' ? 'folder' : 'file',
			search,
			lookup,
			perPage: config.maxAutocompleteResults ?? 3,
			shareType,
		},
	})

	const data = request.data.ocs.data
	const exact = request.data.ocs.data.exact
	data.exact = [] // removing exact from general results

	// flatten array of arrays
	const rawExactSuggestions = Object.values(exact).reduce((arr, elem) => arr.concat(elem), [])
	const rawSuggestions = Object.values(data).reduce((arr, elem) => arr.concat(elem), [])

	// TODO: context is the Vue object of SharingInput
	//
	// - it is misused here to pass reshare, shares, linkShares (left over to stop a refactoring rabbit hole)
	// - for API compatibility it's only required as parameter to result.condition()

	// remove invalid data and format to user-select layout
	const exactSuggestions = filterOutExistingShares(rawExactSuggestions, context)
		.map(share => formatForMultiselect(share, config.shouldAlwaysShowUnique ?? false))
		// sort by type so we can get user&groups first...
		.sort((a, b) => a.shareType - b.shareType)
	const suggestions = filterOutExistingShares(rawSuggestions, context)
		.map(share => formatForMultiselect(share, config.shouldAlwaysShowUnique ?? false))
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

	const rawExternalResults = OCA.Sharing.ShareSearch.state.results

	// if there is a condition specified, filter it
	const externalResults = rawExternalResults.filter(result => !result.condition || result.condition(context))

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

	return allSuggestions.map(item => {
		// Make sure that items with duplicate displayName get the shareWith applied as a description
		if (nameCounts[item.displayName] > 1 && !item.desc) {
			return { ...item, desc: item.shareWithDisplayNameUnique }
		}
		return item
	});
}
