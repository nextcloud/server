/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { loadState } from '@nextcloud/initial-state'
import { generateOcsUrl } from '@nextcloud/router'

const scopeValue = loadState('workflowengine', 'scope') === 0 ? 'global' : 'user'

const getApiUrl = (url) => {
	return generateOcsUrl('apps/workflowengine/api/v1/workflows/{scopeValue}', { scopeValue }) + url + '?format=json'
}

export {
	getApiUrl,
}
