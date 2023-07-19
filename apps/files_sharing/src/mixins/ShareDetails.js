import Share from '../models/Share.js'

export default {
	methods: {
		openSharingDetails(share) {
			const shareRequestObject = {
				fileInfo: this.fileInfo,
				share: this.mapShareRequestToShareObject(share),
			}
			this.$emit('open-sharing-details', shareRequestObject)
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
