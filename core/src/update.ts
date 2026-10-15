/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { loadState } from '@nextcloud/initial-state'
import { createApp, defineAsyncComponent } from 'vue'

const UpdaterAdmin = defineAsyncComponent(() => import('./views/UpdaterAdmin.vue'))
const UpdaterAdminCli = defineAsyncComponent(() => import('./views/UpdaterAdminCli.vue'))

const view = loadState('core', 'updaterView')
createApp(view === 'adminCli' ? UpdaterAdminCli : UpdaterAdmin).mount('#core-updater')
