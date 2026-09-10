/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getCSPNonce } from '@nextcloud/auth'
import axios from '@nextcloud/axios'
import { addPasswordConfirmationInterceptors } from '@nextcloud/password-confirmation'
import Vue from 'vue'
import RecommendedApps from './components/setup/RecommendedApps.vue'
import logger from './logger.js'

addPasswordConfirmationInterceptors(axios)

__webpack_nonce__ = getCSPNonce()

const View = Vue.extend(RecommendedApps)
new View().$mount('#recommended-apps')

logger.debug('recommended apps view rendered')
