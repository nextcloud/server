/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { defineStore } from 'pinia'
import Vue, { computed, ref } from 'vue'

import { loadState } from '@nextcloud/initial-state'

import { createConfig, deleteConfig, getConfig } from '../services/ldapConfigService'
import type { LDAPConfig } from '../models'

export const useLDAPConfigsStore = defineStore('ldap-configs', () => {
	const ldapConfigs = ref(loadState('user_ldap', 'ldapConfigs') as Record<string, LDAPConfig>)
	const selectedConfigId = ref<string>(Object.keys(ldapConfigs.value)[0])
	const selectedConfig = computed<LDAPConfig>(() => ldapConfigs.value[selectedConfigId.value])

	/**
	 *
	 */
	async function create() {
		const configId = await createConfig()
		const config = await getConfig(configId)
		ldapConfigs.value[configId] = config
		selectedConfigId.value = configId
		return configId
	}

	/**
	 *
	 * @param fromConfigId
	 */
	async function copyConfig(fromConfigId: string) {
		const configId = await createConfig()
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
			Vue.delete(ldapConfigs.value, configId)
		}

		const firstConfigId = Object.keys(ldapConfigs.value)[0] ?? await create()
		selectedConfigId.value = firstConfigId
	}

	return {
		ldapConfigs,
		selectedConfigId,
		selectedConfig,
		create,
		copyConfig,
		removeConfig,
	}
})
