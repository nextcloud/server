/**
 * @copyright Copyright (c) 2024 Ferdinand Thiessen <opensource@fthiessen.de>
 *
 * @author Ferdinand Thiessen <opensource@fthiessen.de>
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
import type { PropType } from 'vue'
import type { IAppDiscoverElement } from '../../constants/AppDiscoverTypes.ts'

import { APP_DISCOVER_KNOWN_TYPES } from '../../constants/AppDiscoverTypes.ts'

/**
 * Common Props for all app discover types
 */
export const commonAppDiscoverProps = {
	type: {
		type: String as PropType<IAppDiscoverElement['type']>,
		required: true,
		validator: (v: unknown) => typeof v === 'string' && APP_DISCOVER_KNOWN_TYPES.includes(v as never),
	},

	id: {
		type: String as PropType<IAppDiscoverElement['id']>,
		required: true,
	},

	date: {
		type: Number as PropType<IAppDiscoverElement['date']>,
		required: false,
		default: undefined,
	},

	expiryDate: {
		type: Number as PropType<IAppDiscoverElement['expiryDate']>,
		required: false,
		default: undefined,
	},

	headline: {
		type: Object as PropType<IAppDiscoverElement['headline']>,
		required: false,
		default: () => null,
	},

	link: {
		type: String as PropType<IAppDiscoverElement['link']>,
		required: false,
		default: () => null,
	},
} as const
