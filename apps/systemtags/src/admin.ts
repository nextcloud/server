/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getCSPNonce } from '@nextcloud/auth'
import Vue from 'vue'

import SystemTagsSection from './views/SystemTagsSection.vue'

__webpack_nonce__ = getCSPNonce()

const SystemTagsSectionView = Vue.extend(SystemTagsSection)
new SystemTagsSectionView().$mount('#vue-admin-systemtags')
