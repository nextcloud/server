/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Vue from 'vue'
import { getRequestToken } from '@nextcloud/auth'

import SystemTagsSection from './views/SystemTagsSection.vue'

// @ts-expect-error __webpack_nonce__ is injected by webpack
__webpack_nonce__ = btoa(getRequestToken())

const SystemTagsSectionView = Vue.extend(SystemTagsSection)
new SystemTagsSectionView().$mount('#vue-admin-systemtags')
