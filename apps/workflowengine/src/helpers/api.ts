/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { ScopeValue } from '../types.ts'

import { generateOcsUrl } from '@nextcloud/router'
import { Scope } from '../types.ts'

/**
 * The OCS endpoint of the flows of a scope.
 *
 * @param scope - Whether to address the instance-wide or the personal flows
 * @param path - Path appended to the endpoint, e.g. `/3` to address one flow
 */
export function getApiUrl(scope: ScopeValue, path = ''): string {
	const scopeValue = scope === Scope.ADMIN ? 'global' : 'user'
	return generateOcsUrl('apps/workflowengine/api/v1/workflows/{scopeValue}', { scopeValue }) + path + '?format=json'
}
