import { getCurrentUser } from '@nextcloud/auth'
import { ShareType } from '@nextcloud/sharing'

/**
 * Filter out existing shares from the provided shares search results
 *
 * @param {object[]} shares the array of shares object
 * @param {object} context the Vue object
 * @param {object[]} context.reshare reshare from SharingInput Vue instance
 * @param {object[]} context.shares shares from SharingInput Vue instance
 * @param {object[]} context.linkShares linkShares from SharingInput Vue instance
 * @return {object[]}
 */
export default (shares, context) => {
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
				if (context.reshare && share.value.shareWith === context.reshare.owner) {
					return arr
				}
			}

			// filter out existing mail shares
			if (share.value.shareType === ShareType.Email) {
				const emails = context.linkShares.map(elem => elem.shareWith)
				if (emails.indexOf(share.value.shareWith.trim()) !== -1) {
					return arr
				}
			} else { // filter out existing shares
				// creating an object of uid => type
				const sharesObj = context.shares.reduce((obj, elem) => {
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
};
