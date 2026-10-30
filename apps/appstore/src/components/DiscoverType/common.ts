/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { PropType } from 'vue'
import type { IAppDiscoverElement } from '../../apps-discover.d.ts'

import { APP_DISCOVER_KNOWN_TYPES } from '../../constants.ts'

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
