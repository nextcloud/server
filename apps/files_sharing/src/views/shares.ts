/**
 * @copyright Copyright (c) 2023 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
import type NavigationService from '../../../files/src/services/Navigation'
import type { Navigation } from '../../../files/src/services/Navigation'

import { translate as t } from '@nextcloud/l10n'
import AccountClockSvg from '@mdi/svg/svg/account-clock.svg?raw'
import AccountGroupSvg from '@mdi/svg/svg/account-group.svg?raw'
import AccountSvg from '@mdi/svg/svg/account.svg?raw'
import DeleteSvg from '@mdi/svg/svg/delete.svg?raw'
import LinkSvg from '@mdi/svg/svg/link.svg?raw'
import ShareVariantSvg from '@mdi/svg/svg/share-variant.svg?raw'

import { getContents } from '../services/SharingService'

export const sharesViewId = 'shareoverview'
export const deletedSharesViewId = 'deletedshares'
export const pendingSharesViewId = 'pendingshares'

export default () => {
	const Navigation = window.OCP.Files.Navigation as NavigationService
	Navigation.register({
		id: sharesViewId,
		name: t('files_sharing', 'Shares'),
		caption: t('files_sharing', 'Overview of shared files.'),

		icon: ShareVariantSvg,
		order: 20,

		columns: [],

		getContents: () => getContents(),
	} as Navigation)

	Navigation.register({
		id: 'sharingin',
		name: t('files_sharing', 'Shared with you'),
		caption: t('files_sharing', 'List of files that are shared with you.'),

		icon: AccountSvg,
		order: 1,
		parent: sharesViewId,

		columns: [],

		getContents: () => getContents(true, false, false, false),
	} as Navigation)

	Navigation.register({
		id: 'sharingout',
		name: t('files_sharing', 'Shared with others'),
		caption: t('files_sharing', 'List of files that you shared with others.'),

		icon: AccountGroupSvg,
		order: 2,
		parent: sharesViewId,

		columns: [],

		getContents: () => getContents(false, true, false, false),
	} as Navigation)

	Navigation.register({
		id: 'sharinglinks',
		name: t('files_sharing', 'Shared by link'),
		caption: t('files_sharing', 'List of files that are shared by link.'),

		icon: LinkSvg,
		order: 3,
		parent: sharesViewId,

		columns: [],

		getContents: () => getContents(false, true, false, false, [window.OC.Share.SHARE_TYPE_LINK]),
	} as Navigation)

	Navigation.register({
		id: deletedSharesViewId,
		name: t('files_sharing', 'Deleted shares'),
		caption: t('files_sharing', 'List of shares that you removed yourself from.'),

		icon: DeleteSvg,
		order: 4,
		parent: sharesViewId,

		columns: [],

		getContents: () => getContents(false, false, false, true),
	} as Navigation)

	Navigation.register({
		id: 'pendingshares',
		name: t('files_sharing', 'Pending shares'),
		caption: t('files_sharing', 'List of unapproved shares.'),

		icon: AccountClockSvg,
		order: 5,
		parent: sharesViewId,

		columns: [],

		getContents: () => getContents(false, false, true, false),
	} as Navigation)
}
