/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { translate as t } from '@nextcloud/l10n'
import { View, getNavigation } from '@nextcloud/files'
import { ShareType } from '@nextcloud/sharing'
import AccountClockSvg from '@mdi/svg/svg/account-clock.svg?raw'
import AccountGroupSvg from '@mdi/svg/svg/account-group.svg?raw'
import AccountPlusSvg from '@mdi/svg/svg/account-plus.svg?raw'
import AccountSvg from '@mdi/svg/svg/account.svg?raw'
import DeleteSvg from '@mdi/svg/svg/delete.svg?raw'
import FileUploadSvg from '@mdi/svg/svg/file-upload.svg?raw'
import LinkSvg from '@mdi/svg/svg/link.svg?raw'

import { getContents, isFileRequest } from '../services/SharingService'

export const sharesViewId = 'shareoverview'
export const sharedWithYouViewId = 'sharingin'
export const sharedWithOthersViewId = 'sharingout'
export const sharingByLinksViewId = 'sharinglinks'
export const deletedSharesViewId = 'deletedshares'
export const pendingSharesViewId = 'pendingshares'
export const fileRequestViewId = 'filerequest'

export default () => {
	const Navigation = getNavigation()
	Navigation.register(new View({
		id: sharesViewId,
		name: t('files_sharing', 'Shares'),
		caption: t('files_sharing', 'Overview of shared files.'),

		emptyTitle: t('files_sharing', 'No shares'),
		emptyCaption: t('files_sharing', 'Files and folders you shared or have been shared with you will show up here'),

		icon: AccountPlusSvg,
		order: 20,

		columns: [],

		getContents: () => getContents(),
	}))

	Navigation.register(new View({
		id: sharedWithYouViewId,
		name: t('files_sharing', 'Shared with you'),
		caption: t('files_sharing', 'List of files that are shared with you.'),

		emptyTitle: t('files_sharing', 'Nothing shared with you yet'),
		emptyCaption: t('files_sharing', 'Files and folders others shared with you will show up here'),

		icon: AccountSvg,
		order: 1,
		parent: sharesViewId,

		columns: [],

		getContents: () => getContents(true, false, false, false),
	}))

	Navigation.register(new View({
		id: sharedWithOthersViewId,
		name: t('files_sharing', 'Shared with others'),
		caption: t('files_sharing', 'List of files that you shared with others.'),

		emptyTitle: t('files_sharing', 'Nothing shared yet'),
		emptyCaption: t('files_sharing', 'Files and folders you shared will show up here'),

		icon: AccountGroupSvg,
		order: 2,
		parent: sharesViewId,

		columns: [],

		getContents: () => getContents(false, true, false, false),
	}))

	Navigation.register(new View({
		id: sharingByLinksViewId,
		name: t('files_sharing', 'Shared by link'),
		caption: t('files_sharing', 'List of files that are shared by link.'),

		emptyTitle: t('files_sharing', 'No shared links'),
		emptyCaption: t('files_sharing', 'Files and folders you shared by link will show up here'),

		icon: LinkSvg,
		order: 3,
		parent: sharesViewId,

		columns: [],

		getContents: () => getContents(false, true, false, false, [ShareType.Link]),
	}))

	Navigation.register(new View({
		id: fileRequestViewId,
		name: t('files_sharing', 'File requests'),
		caption: t('files_sharing', 'List of file requests.'),

		emptyTitle: t('files_sharing', 'No file requests'),
		emptyCaption: t('files_sharing', 'File requests you have created will show up here'),

		icon: FileUploadSvg,
		order: 4,
		parent: sharesViewId,

		columns: [],

		getContents: () => getContents(false, true, false, false, [ShareType.Link, ShareType.Email])
			.then(({ folder, contents }) => {
				return {
					folder,
					contents: contents.filter((node) => isFileRequest(node.attributes?.['share-attributes'] || [])),
				}
			}),
	}))

	Navigation.register(new View({
		id: deletedSharesViewId,
		name: t('files_sharing', 'Deleted shares'),
		caption: t('files_sharing', 'List of shares you left.'),

		emptyTitle: t('files_sharing', 'No deleted shares'),
		emptyCaption: t('files_sharing', 'Shares you have left will show up here'),

		icon: DeleteSvg,
		order: 5,
		parent: sharesViewId,

		columns: [],

		getContents: () => getContents(false, false, false, true),
	}))

	Navigation.register(new View({
		id: pendingSharesViewId,
		name: t('files_sharing', 'Pending shares'),
		caption: t('files_sharing', 'List of unapproved shares.'),

		emptyTitle: t('files_sharing', 'No pending shares'),
		emptyCaption: t('files_sharing', 'Shares you have received but not approved will show up here'),

		icon: AccountClockSvg,
		order: 6,
		parent: sharesViewId,

		columns: [],

		getContents: () => getContents(false, false, true, false),
	}))
}
