import Share from '../models/Share.js'

export default {
	methods: {
		async openSharingDetails(shareRequestObject) {
			let share = {}
			// handle externalResults from OCA.Sharing.ShareSearch
			// TODO : Better name/interface for handler required
			// For example `externalAppCreateShareHook` with proper documentation
			if (shareRequestObject.handler) {
				if (this.suggestions) {
					shareRequestObject.suggestions = this.suggestions
					shareRequestObject.fileInfo = this.fileInfo
					shareRequestObject.query = this.query
				}
				share = await shareRequestObject.handler(shareRequestObject)
				share = new Share(share)
			} else {
				share = this.mapShareRequestToShareObject(shareRequestObject)
			}

			const shareDetails = {
				fileInfo: this.fileInfo,
				share,
			}

			this.$emit('open-sharing-details', shareDetails)
		},
		openShareDetailsForCustomSettings(share) {
			share.setCustomPermissions = true
			this.openSharingDetails(share)
		},
		mapShareRequestToShareObject(shareRequestObject) {

			if (shareRequestObject.id) {
				return shareRequestObject
			}

			const share = {
				attributes: [
					{
						enabled: true,
						key: 'download',
						scope: 'permissions',
					},
				],
				share_type: shareRequestObject.shareType,
				share_with: shareRequestObject.shareWith,
				is_no_user: shareRequestObject.isNoUser,
				user: shareRequestObject.shareWith,
				share_with_displayname: shareRequestObject.displayName,
				subtitle: shareRequestObject.subtitle,
				permissions: shareRequestObject.permissions,
				expiration: '',
			}

			return new Share(share)
		},
	},
}
