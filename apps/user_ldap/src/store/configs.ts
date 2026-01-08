/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { LDAPConfig } from '../models/index.ts'

import { loadState } from '@nextcloud/initial-state'
import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { copyConfig, createConfig, deleteConfig, getConfig, updateConfig } from '../services/ldapConfigService.ts'

export const useLDAPConfigsStore = defineStore('ldap-configs', () => {
	const ldapConfigs = ref(loadState('user_ldap', 'ldapConfigs') as Record<string, LDAPConfig>)
	const selectedConfigId = ref<string | undefined>(Object.keys(ldapConfigs.value)[0])
	const selectedConfig = computed(() => selectedConfigId.value === undefined ? undefined : ldapConfigs.value[selectedConfigId.value])
	const updatingConfig = ref(0)

	/**
	 *
	 * @param configId
	 * @param postSetHooks
	 */
	function getConfigProxy<J>(configId: string, postSetHooks: Partial<Record<keyof LDAPConfig, (value: J) => void>> = {}) {
		if (ldapConfigs.value[configId] === undefined) {
			throw new Error(`Config with id ${configId} does not exist`)
		}

		return new Proxy(ldapConfigs.value[configId], {
			get(target, property) {
				return target[property]
			},
			set(target, property: string, newValue) {
				target[property] = newValue

				;(async () => {
					updatingConfig.value++
					await updateConfig(configId, { [property]: newValue })
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
		ldapConfigs.value[configId] = await getConfig(configId)
		selectedConfigId.value = configId
		return configId
	}

	/**
	 *
	 * @param fromConfigId
	 */
	async function _copyConfig(fromConfigId: string) {
		if (ldapConfigs.value[fromConfigId] === undefined) {
			throw new Error(`Config with id ${fromConfigId} does not exist`)
		}

		const configId = await copyConfig(fromConfigId)

		ldapConfigs.value[configId] = { ...ldapConfigs.value[fromConfigId] }
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
			if (Object.keys(ldapConfigs.value).length === 1) {
				// Ensure at least one config exists before deleting the last one
				selectedConfigId.value = await create()
				// The new config id could be the same as the deleted one, so only delete if different
				if (selectedConfigId.value !== configId) {
					delete ldapConfigs.value[configId]
				}
			} else {
				// Select the first config that is not the deleted one
				selectedConfigId.value = Object.keys(ldapConfigs.value).filter((_configId) => configId !== _configId)[0]
				delete ldapConfigs.value[configId]
			}
		}
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
