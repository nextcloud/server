/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getCSPNonce } from '@nextcloud/auth'
import Vue from 'vue'

import PublicPageMenu from './views/PublicPageMenu.vue'

__webpack_nonce__ = getCSPNonce()

const View = Vue.extend(PublicPageMenu)
const instance = new View()
instance.$mount('#public-page-menu')
