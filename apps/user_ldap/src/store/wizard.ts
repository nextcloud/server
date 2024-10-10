/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { defineStore } from 'pinia'
import { ref } from 'vue'

import { callWizard, type WizardAction } from '../services/ldapConfigService'
import { useLDAPConfigsStore } from './configs'

export const useWizardStore = defineStore('ldap-wizard', () => {
	const currentWizardActions = ref<string[]>([])

	const { selectedConfigId } = useLDAPConfigsStore()

	/**
	 *
	 * @param action
	 * @param params
	 */
	async function callWizardAction(action: WizardAction, params?: Record<string, string>) {
		try {
			currentWizardActions.value.push(action)
			return await callWizard(action, selectedConfigId, params)
		} finally {
			currentWizardActions.value.splice(currentWizardActions.value.indexOf(action), 1)
		}
	}

	return {
		currentWizardActions,
		callWizardAction,
	}
})
