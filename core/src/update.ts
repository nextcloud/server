/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getCSPNonce } from '@nextcloud/auth'
import { loadState } from '@nextcloud/initial-state'
import Vue, { defineAsyncComponent } from 'vue'

__webpack_nonce__ = getCSPNonce()

const UpdaterAdmin = defineAsyncComponent(() => import('./views/UpdaterAdmin.vue'))
const UpdaterAdminCli = defineAsyncComponent(() => import('./views/UpdaterAdminCli.vue'))

const view = loadState('core', 'updaterView')
const app = new Vue({
	name: 'NextcloudUpdater',
	render: (h) => view === 'adminCli' ? h(UpdaterAdminCli) : h(UpdaterAdmin),
})
app.$mount('#core-updater')
