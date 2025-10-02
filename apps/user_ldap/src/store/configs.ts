/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { LDAPConfig } from '../models/index.ts'

import { loadState } from '@nextcloud/initial-state'
import { defineStore } from 'pinia'
import Vue, { computed, ref } from 'vue'
import { callWizard, copyConfig, createConfig, deleteConfig, getConfig } from '../services/ldapConfigService.ts'

export const useLDAPConfigsStore = defineStore('ldap-configs', () => {
	const ldapConfigs = ref(loadState('user_ldap', 'ldapConfigs') as Record<string, LDAPConfig>)
	const selectedConfigId = ref<string>(Object.keys(ldapConfigs.value)[0])
	const selectedConfig = computed(() => ldapConfigs.value[selectedConfigId.value])
	const updatingConfig = ref(0)

	/**
	 *
	 * @param configId
	 * @param postSetHooks
	 */
	function getConfigProxy<J>(configId: string, postSetHooks: Partial<Record<keyof LDAPConfig, (value: J) => void>> = {}) {
		return new Proxy(ldapConfigs.value[configId], {
			get(target, property) {
				return target[property]
			},
			set(target, property: string, newValue) {
				target[property] = newValue

				;(async () => {
					updatingConfig.value++
					await callWizard('save', configId, { key: property, val: newValue })
					updatingConfig.value--

					if (postSetHooks[property] !== undefined) {
						postSetHooks[property](target[property])
					}
				})()

				return true
			},
		})
	}

	/**
	 *
	 */
	async function create() {
		const configId = await createConfig()
		Vue.set(ldapConfigs.value, configId, await getConfig(configId))
		selectedConfigId.value = configId
		return configId
	}

	/**
	 *
	 * @param fromConfigId
	 */
	async function _copyConfig(fromConfigId: string) {
		const configId = await copyConfig(fromConfigId)
		Vue.set(ldapConfigs.value, configId, { ...ldapConfigs.value[fromConfigId] })
		selectedConfigId.value = configId
		return configId
	}

	/**
	 *
	 * @param configId
	 */
	async function removeConfig(configId: string) {
		const result = await deleteConfig(configId)
		if (result === true) {
			Vue.delete(ldapConfigs.value, configId)
		}

		selectedConfigId.value = Object.keys(ldapConfigs.value)[0] ?? await create()
	}

	return {
		ldapConfigs,
		selectedConfigId,
		selectedConfig,
		updatingConfig,
		getConfigProxy,
		create,
		copyConfig: _copyConfig,
		removeConfig,
	}
})
