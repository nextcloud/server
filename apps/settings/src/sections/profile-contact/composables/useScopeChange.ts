/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { loadState } from '@nextcloud/initial-state'
import { computed } from 'vue'
import {
	PROPERTY_READABLE_KEYS_ENUM,
	PROPERTY_READABLE_SUPPORTED_SCOPES_ENUM,
	SCOPE_ENUM,
	UNPUBLISHED_READABLE_PROPERTIES,
} from '../../../constants/AccountPropertyConstants.ts'
import { savePrimaryAccountPropertyScope } from '../../../service/PersonalInfo/PersonalInfoService.js'
import { handleError } from '../../../utils/handlers.ts'

export interface ScopeProps {
	/** Human readable name of the property */
	readable: string
	/** Federation scope of the property */
	scope: string
	/** Whether this controls an additional value, e.g. a secondary email */
	additional?: boolean
	/** The additional value this scope belongs to */
	additionalValue?: string
	/** Saves the scope of an additional value */
	handleAdditionalScopeChange?: (value: string, scope: string) => Promise<unknown>
}

/**
 * Scopes a property supports, and saving a picked one
 *
 * @param props Props of the scope control
 * @param setScope Shows a scope, called with the saved one again if saving fails
 * @param errorMessage Shown when saving fails
 */
export function useScopeChange(props: ScopeProps, setScope: (scope: string) => void, errorMessage: () => string) {
	const { federationEnabled, lookupServerUploadEnabled } = loadState<{ federationEnabled?: boolean, lookupServerUploadEnabled?: boolean }>('settings', 'accountParameters', {})

	const supportedScopes = computed(() => {
		// copy to avoid mutating the shared constant
		const scopes = [...PROPERTY_READABLE_SUPPORTED_SCOPES_ENUM[props.readable]]
		if (UNPUBLISHED_READABLE_PROPERTIES.includes(props.readable)) {
			return scopes
		}
		if (federationEnabled) {
			scopes.push(SCOPE_ENUM.FEDERATED)
		}
		if (lookupServerUploadEnabled) {
			scopes.push(SCOPE_ENUM.PUBLISHED)
		}
		return scopes
	})

	let savedScope = props.scope

	/**
	 * @param scope The picked scope
	 */
	async function changeScope(scope: string) {
		setScope(scope)
		try {
			if (props.additional && props.handleAdditionalScopeChange) {
				await props.handleAdditionalScopeChange(props.additionalValue ?? '', scope)
			} else {
				await savePrimaryAccountPropertyScope(PROPERTY_READABLE_KEYS_ENUM[props.readable], scope)
			}
			savedScope = scope
		} catch (error) {
			setScope(savedScope)
			handleError(error, errorMessage())
		}
	}

	return { supportedScopes, changeScope }
}
