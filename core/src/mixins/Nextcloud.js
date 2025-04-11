/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import L10n from '../OC/l10n.js'
import OC from '../OC/index.js'

export default {
	data() {
		return {
			OC,
		}
	},
	methods: {
		t: L10n.translate.bind(L10n),
		n: L10n.translatePlural.bind(L10n),
	},
}
