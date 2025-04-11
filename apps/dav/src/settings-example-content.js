/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import Vue from 'vue'
import { translate } from '@nextcloud/l10n'
import ExampleContactSettings from './views/ExampleContactSettings.vue'

Vue.prototype.$t = translate

const View = Vue.extend(ExampleContactSettings);

(new View({})).$mount('#settings-example-content')
