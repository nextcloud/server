/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Vue from 'vue'
import Vuex from 'vuex'
import store from './store.js'
import Settings from './components/Workflow.vue'
import ShippedChecks from './components/Checks/index.js'

/**
 * A plugin for displaying a custom value field for checks
 *
 * @typedef {object} CheckPlugin
 * @property {string} class - The PHP class name of the check
 * @property {Comparison[]} operators - A list of possible comparison operations running on the check
 * @property {Vue} component - A vue component to handle the rendering of options
 *  The component should handle the v-model directive properly,
 *  so it needs a value property to receive data and emit an input
 *  event once the data has changed
 * @property {Function} placeholder - Return a placeholder of no custom component is used
 * @property {Function} validate - validate a check if no custom component is used
 */

/**
 * A plugin for extending the admin page representation of an operator
 *
 * @typedef {object} OperatorPlugin
 * @property {string} id - The PHP class name of the check
 * @property {string} operation - Default value for the operation field
 * @property {string} color - Custom color code to be applied for the operator selector
 * @property {Vue} component - A vue component to handle the rendering of options
 *  The component should handle the v-model directive properly,
 *  so it needs a value property to receive data and emit an input
 *  event once the data has changed
 */

/**
 * @typedef {object} Comparison
 * @property {string} operator - value the comparison should have, e.g. !less, greater
 * @property {string} name - Translated readable text, e.g. less or equals
 */

/**
 * Public javascript api for apps to register custom plugins
 */
window.OCA.WorkflowEngine = Object.assign({}, OCA.WorkflowEngine, {

	/**
	 *
	 * @param {CheckPlugin} Plugin the plugin to register
	 */
	registerCheck(Plugin) {
		store.commit('addPluginCheck', Plugin)
	},
	/**
	 *
	 * @param {OperatorPlugin} Plugin the plugin to register
	 */
	registerOperator(Plugin) {
		store.commit('addPluginOperator', Plugin)
	},
})

// Register shipped checks
ShippedChecks.forEach((checkPlugin) => window.OCA.WorkflowEngine.registerCheck(checkPlugin))

Vue.use(Vuex)
Vue.prototype.t = t

const View = Vue.extend(Settings)
const workflowengine = new View({
	store,
})
workflowengine.$mount('#workflowengine')
