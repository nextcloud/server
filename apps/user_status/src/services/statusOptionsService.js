/**
 * @copyright Copyright (c) 2020 Georg Ehrke
 *
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Jan C. Borchardt <hey@jancborchardt.net>
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

import { translate as t } from '@nextcloud/l10n'

/**
 * Returns a list of all user-definable statuses
 *
 * @return {object[]}
 */
const getAllStatusOptions = () => {
	return [{
		type: 'online',
		label: t('user_status', 'Online'),
	}, {
		type: 'away',
		label: t('user_status', 'Away'),
	}, {
		type: 'dnd',
		label: t('user_status', 'Do not disturb'),
		subline: t('user_status', 'Mute all notifications'),
	}, {
		type: 'invisible',
		label: t('user_status', 'Invisible'),
		subline: t('user_status', 'Appear offline'),
	}]
}

export {
	getAllStatusOptions,
}
