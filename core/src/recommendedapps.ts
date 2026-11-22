/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import axios from '@nextcloud/axios'
import { addPasswordConfirmationInterceptors } from '@nextcloud/password-confirmation'
import { createApp } from 'vue'
import RecommendedApps from './components/setup/RecommendedApps.vue'

addPasswordConfirmationInterceptors(axios)

createApp(RecommendedApps).mount('#recommended-apps')
