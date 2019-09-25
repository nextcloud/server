import Vue from 'vue'
import Vuex from 'vuex'
import store from './store'
import Settings from './components/Workflow'
import ShippedChecks from './components/Checks'

/**
 * A plugin for displaying a custom value field for checks
 *
 * @typedef {Object} CheckPlugin
 * @property {string} class - The PHP class name of the check
 * @property {Comparison[]} operators - A list of possible comparison operations running on the check
 * @property {Vue} component - A vue component to handle the rendering of options
 * 	The component should handle the v-model directive properly,
 * 	so it needs a value property to receive data and emit an input
 * 	event once the data has changed
 * @property {callable} placeholder - Return a placeholder of no custom component is used
 * @property {callable} validate - validate a check if no custom component is used
 **/

/**
 * A plugin for extending the admin page repesentation of a operator
 *
 * @typedef {Object} OperatorPlugin
 * @property {string} id - The PHP class name of the check
 * @property {string} operation - Default value for the operation field
 * @property {string} color - Custom color code to be applied for the operator selector
 * @property {Vue} component - A vue component to handle the rendering of options
 * 	The component should handle the v-model directive properly,
 * 	so it needs a value property to receive data and emit an input
 * 	event once the data has changed
 */

/**
 * @typedef {Object} Comparison
 * @property {string} operator - value the comparison should have, e.g. !less, greater
 * @property {string} name - Translated readable text, e.g. less or equals
 **/

/**
 * Public javascript api for apps to register custom plugins
 */
window.OCA.WorkflowEngine = Object.assign({}, OCA.WorkflowEngine, {

	/**
	 *
	 * @param {CheckPlugin} Plugin the plugin to register
	 */
	registerCheck: function(Plugin) {
		store.commit('addPluginCheck', Plugin)
	},
	/**
	 *
	 * @param {OperatorPlugin} Plugin the plugin to register
	 */
	registerOperator: function(Plugin) {
		store.commit('addPluginOperator', Plugin)
	}
})

// Register shipped checks
ShippedChecks.forEach((checkPlugin) => window.OCA.WorkflowEngine.registerCheck(checkPlugin))

Vue.use(Vuex)
Vue.prototype.t = t

const View = Vue.extend(Settings)
new View({
	store
}).$mount('#workflowengine')
