/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { AxiosError } from '@nextcloud/axios'
import type { AxiosResponse } from '@nextcloud/axios'
import type { OCSResponse } from '@nextcloud/typings/ocs'
import type { LDAPConfig } from '../models/index.ts'

import axios from '@nextcloud/axios'
import { getDialogBuilder, showError, showSuccess } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import { generateOcsUrl, generateUrl } from '@nextcloud/router'
import path from 'path'
import logger from './logger.ts'

const AJAX_ENDPOINT = generateUrl('apps/user_ldap/ajax')

export type WizardAction
	= 'guessPortAndTLS'
		| 'guessBaseDN'
		| 'detectEmailAttribute'
		| 'detectUserDisplayNameAttribute'
		| 'determineGroupMemberAssoc'
		| 'determineUserObjectClasses'
		| 'determineGroupObjectClasses'
		| 'determineGroupsForUsers'
		| 'determineGroupsForGroups'
		| 'determineAttributes'
		| 'getUserListFilter'
		| 'getUserLoginFilter'
		| 'getGroupFilter'
		| 'countUsers'
		| 'countGroups'
		| 'countInBaseDN'
		| 'testLoginName'
		| 'save'

/**
 *
 */
export async function createConfig() {
	const response = await axios.post(generateOcsUrl('apps/user_ldap/api/v1/config')) as AxiosResponse<OCSResponse<{ configID: string }>>
	logger.debug('Created configuration', { configId: response.data.ocs.data.configID })
	return response.data.ocs.data.configID
}

/**
 *
 * @param configId
 */
export async function copyConfig(configId: string) {
	const params = new FormData()
	params.set('copyConfig', configId)

	const response = await axios.post(
		path.join(AJAX_ENDPOINT, 'getNewServerConfigPrefix.php'),
		params,
	) as AxiosResponse<{ status: 'error' | 'success', configPrefix: string }>

	logger.debug('Created configuration', { configId: response.data.configPrefix })
	return response.data.configPrefix
}

/**
 *
 * @param configId
 */
export async function getConfig(configId: string): Promise<LDAPConfig> {
	const response = await axios.get(generateOcsUrl('apps/user_ldap/api/v1/config/{configId}', { configId })) as AxiosResponse<OCSResponse<LDAPConfig>>
	logger.debug('Fetched configuration', { configId, config: response.data.ocs.data })
	return response.data.ocs.data
}

/**
 *
 * @param configId
 * @param config
 */
export async function updateConfig(configId: string, config: LDAPConfig): Promise<LDAPConfig> {
	const response = await axios.put(
		generateOcsUrl('apps/user_ldap/api/v1/config/{configId}', { configId }),
		{ configData: config },
	) as AxiosResponse<OCSResponse<LDAPConfig>>

	logger.debug('Updated configuration', { configId, config })

	return response.data.ocs.data
}

/**
 *
 * @param configId
 */
export async function deleteConfig(configId: string): Promise<boolean> {
	try {
		const isConfirmed = await confirmOperation(
			t('user_ldap', 'Confirm action'),
			t('user_ldap', 'Are you sure you want to permanently delete this LDAP configuration? This cannot be undone.'),
		)
		if (!isConfirmed) {
			return false
		}

		await axios.delete(generateOcsUrl('apps/user_ldap/api/v1/config/{configId}', { configId }))
		logger.debug('Deleted configuration', { configId })
	} catch (error) {
		const errorResponse = (error as AxiosError<OCSResponse>).response
		showError(errorResponse?.data.ocs.meta.message || t('user_ldap', 'Fail to delete config'))
	}

	return true
}

/**
 *
 * @param configId
 */
export async function testConfiguration(configId: string) {
	const params = new FormData()
	params.set('ldap_serverconfig_chooser', configId)

	const response = await axios.post(
		path.join(AJAX_ENDPOINT, 'testConfiguration.php'),
		params,
	) as AxiosResponse<{ message: string, status: 'error' | 'success' }>

	logger.debug(`Configuration is ${response.data.status === 'success' ? 'valide' : 'invalide'}`, { configId, params, response })

	return response.data
}

/**
 *
 * @param subject
 */
export async function clearMapping(subject: 'user' | 'group') {
	const isConfirmed = await confirmOperation(
		t('user_ldap', 'Confirm action'),
		t('user_ldap', 'Are you sure you want to permanently clear the LDAP mapping? This cannot be undone.'),
	)
	if (!isConfirmed) {
		return false
	}

	const params = new FormData()
	params.set('ldap_clear_mapping', subject)

	const response = await axios.post(
		path.join(AJAX_ENDPOINT, 'clearMappings.php'),
		params,
	)

	if (response.data.status === 'success') {
		logger.debug('Cleared mapping', { subject, params, response })
		showSuccess(t('user_ldap', 'Mapping cleared'))
	} else {
		showError(t('user_ldap', 'Failed to clear mapping'))
	}
}

/**
 *
 * @param action
 * @param configId
 * @param extraParams
 */
export async function callWizard(action: WizardAction, configId: string, extraParams: Record<string, string> = {}) {
	const params = new FormData()
	params.set('action', action)
	params.set('ldap_serverconfig_chooser', configId)

	Object.entries(extraParams).forEach(([key, value]) => {
		params.set(key, value)
	})

	const response = await axios.post(
		path.join(AJAX_ENDPOINT, 'wizard.php'),
		params,
	) as AxiosResponse<{ status: 'error', message?: string } | { status: 'success', changes?: Record<string, unknown>, options?: Record<string, []> }>

	logger.debug(`Called wizard action: ${action}`, { configId, params, response })

	if (response.data.status === 'error') {
		const message = response.data.message ?? t('user_ldap', 'An error occurred')
		showError(message)
		throw new Error(message)
	}

	return response.data
}

/**
 *
 */
export async function showEnableAutomaticFilterInfo() {
	return await confirmOperation(
		t('user_ldap', 'Mode switch'),
		t('user_ldap', 'Switching the mode will enable automatic LDAP queries. Depending on your LDAP size they may take a while. Do you still want to switch the mode?'),
	)
}

/**
 *
 * @param name
 * @param text
 */
export async function confirmOperation(name: string, text: string): Promise<boolean> {
	let result = false
	const dialog = getDialogBuilder(name)
		.setText(text)
		.setSeverity('warning')
		.addButton({
			label: t('user_ldap', 'Cancel'),
			callback() {},
		})
		.addButton({
			label: t('user_ldap', 'Confirm'),
			variant: 'error',
			callback() {
				result = true
			},
		})
		.build()

	await dialog.show()
	return result
}
