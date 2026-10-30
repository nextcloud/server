/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { CheckPlugin, OperatorPlugin, WorkflowEngineApi } from './types.ts'

import { createPinia, setActivePinia } from 'pinia'
import { createApp } from 'vue'
import Workflow from './components/Workflow.vue'
import ShippedChecks from './components/Checks/index.ts'
import { useWorkflowStore } from './store.ts'

const pinia = createPinia()
// apps may register their plugins before the app is mounted, so the store has
// to be usable outside of a component from here on
setActivePinia(pinia)

const store = useWorkflowStore()

// every app declares `window.OCA` with its own members, so the part this app
// owns is narrowed here instead of in a global declaration
const OCA = window.OCA as unknown as { WorkflowEngine?: WorkflowEngineApi }

/**
 * Public javascript api for apps to register custom plugins
 */
OCA.WorkflowEngine = {
	...OCA.WorkflowEngine,

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

ShippedChecks.forEach((checkPlugin) => OCA.WorkflowEngine!.registerCheck(checkPlugin))

createApp(Workflow)
	.use(pinia)
	.mount('#workflowengine')
