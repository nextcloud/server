/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import axios from '@nextcloud/axios'
import { addPasswordConfirmationInterceptors } from '@nextcloud/password-confirmation'
import { createPinia } from 'pinia'
import { createApp } from 'vue'
import UserManagement from './views/UserManagement.vue'
import router from './router/index.ts'
import { useUsersStore } from './store/users.ts'

addPasswordConfirmationInterceptors(axios)

const pinia = createPinia()

// Migrate legacy local storage settings to the database
useUsersStore(pinia).migrateLocalStorage()

const app = createApp(UserManagement)
	.use(pinia)
	.use(router)
// the first navigation is async, mount once the selected group is known
router.isReady().then(() => app.mount('#content'))
