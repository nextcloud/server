/**
 * @copyright Copyright (c) 2019 Julius Härtl <jus@bitgrid.net>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Julius Härtl <jus@bitgrid.net>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

import Vue from 'vue'
import Vuex from 'vuex'
import store from './store'
import Settings from './components/Workflow'
import ShippedChecks from './components/Checks'
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
