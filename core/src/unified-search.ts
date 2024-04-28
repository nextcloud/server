/**
 * @copyright Copyright (c) 2024 Fon E. Noel NFEBE <opensource@nfebe.com>
 *
 * @author Fon E. Noel NFEBE <opensource@nfebe.com>
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

import { getLoggerBuilder } from '@nextcloud/logger'
import { getRequestToken } from '@nextcloud/auth'
import { translate as t, translatePlural as n } from '@nextcloud/l10n'
import { createPinia, PiniaVuePlugin } from 'pinia'
import Vue from 'vue'

import UnifiedSearch from './views/UnifiedSearch.vue'
import { useSearchStore } from '../src/store/unified-search-external-filters.js'

// eslint-disable-next-line camelcase
__webpack_nonce__ = btoa(getRequestToken())

const logger = getLoggerBuilder()
	.setApp('unified-search')
	.detectUser()
	.build()

Vue.mixin({
	data() {
		return {
			logger,
		}
	},
	methods: {
		t,
		n,
	},
})

// Define type structure for unified searc action
interface UnifiedSearchAction {
    id: string;
    appId: string;
    label: string;
    icon: string;
    callback: () => void;
}

// Register the add/register filter action API globally
window.OCA = window.OCA || {}
window.OCA.UnifiedSearch = {
	registerFilterAction: ({ id, appId, label, callback, icon }: UnifiedSearchAction) => {
		const searchStore = useSearchStore()
		searchStore.registerExternalFilter({ id, appId, label, callback, icon })
	},
}

Vue.use(PiniaVuePlugin)
const pinia = createPinia()

export default new Vue({
	el: '#unified-search',
	pinia,
	// eslint-disable-next-line vue/match-component-file-name
	name: 'UnifiedSearchRoot',
	render: h => h(UnifiedSearch),
})
