/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Vue from 'vue'
import Vuex, { Store } from 'vuex'
import axios from '@nextcloud/axios'
import { confirmPassword } from '@nextcloud/password-confirmation'
import { loadState } from '@nextcloud/initial-state'
import { getApiUrl } from './helpers/api.js'

import '@nextcloud/password-confirmation/dist/style.css'

Vue.use(Vuex)

const store = new Store({
	state: {
		rules: [],
		scope: loadState('workflowengine', 'scope'),
		appstoreEnabled: loadState('workflowengine', 'appstoreenabled'),
		operations: loadState('workflowengine', 'operators'),

		plugins: Vue.observable({
			checks: {},
			operators: {},
		}),

		entities: loadState('workflowengine', 'entities'),
		events: loadState('workflowengine', 'entities')
			.map((entity) => entity.events.map(event => {
				return {
					id: `${entity.id}::${event.eventName}`,
					entity,
					...event,
				}
			})).flat(),
		checks: loadState('workflowengine', 'checks'),
	},
	mutations: {
		addRule(state, rule) {
			state.rules.push({ ...rule, valid: true })
		},
		updateRule(state, rule) {
			const index = state.rules.findIndex((item) => rule.id === item.id)
			const newRule = Object.assign({}, rule)
			Vue.set(state.rules, index, newRule)
		},
		removeRule(state, rule) {
			const index = state.rules.findIndex((item) => rule.id === item.id)
			state.rules.splice(index, 1)
		},
		addPluginCheck(state, plugin) {
			Vue.set(state.plugins.checks, plugin.class, plugin)
		},
		addPluginOperator(state, plugin) {
			plugin = Object.assign(
				{ color: 'var(--color-primary-element)' },
				plugin, state.operations[plugin.id] || {})
			if (typeof state.operations[plugin.id] !== 'undefined') {
				Vue.set(state.operations, plugin.id, plugin)
			}
		},
	},
	actions: {
		async fetchRules(context) {
			const { data } = await axios.get(getApiUrl(''))
			Object.values(data.ocs.data).flat().forEach((rule) => {
				context.commit('addRule', rule)
			})
		},
		async createNewRule(context, rule) {
			await confirmPassword()
			let entity = null
			let events = []
			if (rule.isComplex === false && rule.fixedEntity === '') {
				entity = context.state.entities.find((item) => rule.entities && rule.entities[0] === item.id)
				entity = entity || Object.values(context.state.entities)[0]
				events = [entity.events[0].eventName]
			}

			context.commit('addRule', {
				id: -(new Date().getTime()),
				class: rule.id,
				entity: entity ? entity.id : rule.fixedEntity,
				events,
				name: '', // unused in the new ui, there for legacy reasons
				checks: [
					{ class: null, operator: null, value: '' },
				],
				operation: rule.operation || '',
			})
		},
		updateRule(context, rule) {
			context.commit('updateRule', {
				...rule,
				events: typeof rule.events === 'string' ? JSON.parse(rule.events) : rule.events,
			})
		},
		removeRule(context, rule) {
			context.commit('removeRule', rule)
		},
		async pushUpdateRule(context, rule) {
			await confirmPassword()
			let result
			if (rule.id < 0) {
				result = await axios.post(getApiUrl(''), rule)
			} else {
				result = await axios.put(getApiUrl(`/${rule.id}`), rule)
			}
			Vue.set(rule, 'id', result.data.ocs.data.id)
			context.commit('updateRule', rule)
		},
		async deleteRule(context, rule) {
			await confirmPassword()
			await axios.delete(getApiUrl(`/${rule.id}`))
			context.commit('removeRule', rule)
		},
		setValid(context, { rule, valid }) {
			rule.valid = valid
			context.commit('updateRule', rule)
		},
	},
	getters: {
		getRules(state) {
			return state.rules.filter((rule) => typeof state.operations[rule.class] !== 'undefined').sort((rule1, rule2) => {
				return rule1.id - rule2.id || rule2.class - rule1.class
			})
		},
		/**
		 * @param state
		 * @return {OperatorPlugin}
		 */
		getOperationForRule(state) {
			return (rule) => state.operations[rule.class]
		},
		getEntityForOperation(state) {
			return (operation) => state.entities.find((entity) => operation.fixedEntity === entity.id)
		},
		getEventsForOperation(state) {
			return (operation) => state.events
		},

		/**
		 * Return all available checker plugins for a given entity class
		 *
		 * @param {object} state the store state
		 * @return {Function} the available plugins
		 */
		getChecksForEntity(state) {
			return (entity) => {
				return Object.values(state.checks)
					.filter((check) => check.supportedEntities.indexOf(entity) > -1 || check.supportedEntities.length === 0)
					.map((check) => state.plugins.checks[check.id])
					.reduce((obj, item) => {
						obj[item.class] = item
						return obj
					}, {})
			}
		},
	},
})

export default store
