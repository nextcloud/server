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
import type NavigationService from '../../files/src/services/Navigation'

import { translate as t } from '@nextcloud/l10n'
import DeleteSvg from '@mdi/svg/svg/delete.svg?raw'

import getContents from './services/trashbin'

const Navigation = window.OCP.Files.Navigation as NavigationService
Navigation.register({
	id: 'trashbin',
	name: t('files_trashbin', 'Deleted files'),

	icon: DeleteSvg,
	order: 50,
	sticky: true,

	getContents,
})
