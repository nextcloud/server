/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Share from '../models/Share.ts'
import Config from '../services/ConfigService.ts'
import { ATOMIC_PERMISSIONS } from '../lib/SharePermissionsToolBox.js'
import logger from '../services/logger.ts'

export default {
	methods: {
		async openSharingDetails(shareRequestObject) {
			let share = {}
			// handle externalResults from OCA.Sharing.ShareSearch
			// TODO : Better name/interface for handler required
			// For example `externalAppCreateShareHook` with proper documentation
			if (shareRequestObject.handler) {
				const handlerInput = {}
				if (this.suggestions) {
					handlerInput.suggestions = this.suggestions
					handlerInput.fileInfo = this.fileInfo
					handlerInput.query = this.query
				}
				const externalShareRequestObject = await shareRequestObject.handler(handlerInput)
				share = this.mapShareRequestToShareObject(externalShareRequestObject)
			} else {
				share = this.mapShareRequestToShareObject(shareRequestObject)
			}

			if (this.fileInfo.type !== 'dir') {
				const originalPermissions = share.permissions
				const strippedPermissions = originalPermissions
					& ~ATOMIC_PERMISSIONS.CREATE
					& ~ATOMIC_PERMISSIONS.DELETE

				if (originalPermissions !== strippedPermissions) {
					logger.debug('Removed create/delete permissions from file share (only valid for folders)')
					share.permissions = strippedPermissions
				}
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
						value: true,
						key: 'download',
						scope: 'permissions',
					},
				],
				hideDownload: false,
				share_type: shareRequestObject.shareType,
				share_with: shareRequestObject.shareWith,
				is_no_user: shareRequestObject.isNoUser,
				user: shareRequestObject.shareWith,
				share_with_displayname: shareRequestObject.displayName,
				subtitle: shareRequestObject.subtitle,
				permissions: shareRequestObject.permissions ?? new Config().defaultPermissions,
				expiration: '',
			}

			return new Share(share)
		},
	},
}
