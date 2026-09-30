/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { CheckPlugin, OperatorPlugin } from './types.ts'

import { t } from '@nextcloud/l10n'
import { createPinia, PiniaVuePlugin, setActivePinia } from 'pinia'
import Vue from 'vue'
import Settings from './components/Workflow.vue'
import ShippedChecks from './components/Checks/index.js'
import { useWorkflowStore } from './store.ts'

Vue.use(PiniaVuePlugin)

const pinia = createPinia()
// plugins may register before the app is mounted, so the store has to be usable
// outside of a component from here on
setActivePinia(pinia)

const store = useWorkflowStore()

/**
 * Public javascript api for apps to register custom plugins
 */
window.OCA.WorkflowEngine = {
	...window.OCA.WorkflowEngine,

	/**
	 * Register the value editor of a check.
	 *
	 * @param plugin - The plugin to register
	 */
	registerCheck(plugin: CheckPlugin): void {
		store.addPluginCheck(plugin)
	},

	/**
	 * Register the presentation of an operation.
	 *
	 * @param plugin - The plugin to register
	 */
	registerOperator(plugin: OperatorPlugin): void {
		store.addPluginOperator(plugin)
	},
}

ShippedChecks.forEach((checkPlugin: CheckPlugin) => window.OCA.WorkflowEngine.registerCheck(checkPlugin))

Vue.prototype.t = t

const View = Vue.extend(Settings)
const workflowengine = new View({ pinia })
workflowengine.$mount('#workflowengine')
