/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getCSPNonce } from '@nextcloud/auth'
import Vue from 'vue'
import PublicShareAuth from './views/PublicShareAuth.vue'

__webpack_nonce__ = getCSPNonce()

const View = Vue.extend(PublicShareAuth)
const app = new View()
app.$mount('#core-public-share-auth')
