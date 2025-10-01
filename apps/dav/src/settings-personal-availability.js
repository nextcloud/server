/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { t } from '@nextcloud/l10n'
import Vue from 'vue'
import Availability from './views/Availability.vue'

Vue.prototype.$t = t

const View = Vue.extend(Availability);

(new View({})).$mount('#settings-personal-availability')
