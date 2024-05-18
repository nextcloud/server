/**
 * @copyright Copyright (c) 2020 Georg Ehrke
 *
 * @author Georg Ehrke <oc.list@georgehrke.com>
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
 * Returns an array
 *
 * @return {object[]}
 */
const getAllClearAtOptions = () => {
	return [{
		label: t('user_status', 'Don\'t clear'),
		clearAt: null,
	}, {
		label: t('user_status', '30 minutes'),
		clearAt: {
			type: 'period',
			time: 1800,
		},
	}, {
		label: t('user_status', '1 hour'),
		clearAt: {
			type: 'period',
			time: 3600,
		},
	}, {
		label: t('user_status', '4 hours'),
		clearAt: {
			type: 'period',
			time: 14400,
		},
	}, {
		label: t('user_status', 'Today'),
		clearAt: {
			type: 'end-of',
			time: 'day',
		},
	}, {
		label: t('user_status', 'This week'),
		clearAt: {
			type: 'end-of',
			time: 'week',
		},
	}]
}

export {
	getAllClearAtOptions,
}
