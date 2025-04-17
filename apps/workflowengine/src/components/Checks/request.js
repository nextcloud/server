/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { registerCustomElement } from '../../helpers/window.js'
import RequestUserAgent from './RequestUserAgent.vue'
import RequestTime from './RequestTime.vue'
import RequestURL from './RequestURL.vue'
import RequestUserGroup from './RequestUserGroup.vue'

const RequestChecks = [
	{
		class: 'OCA\\WorkflowEngine\\Check\\RequestURL',
		name: t('workflowengine', 'Request URL'),
		operators: [
			{ operator: 'is', name: t('workflowengine', 'is') },
			{ operator: '!is', name: t('workflowengine', 'is not') },
			{ operator: 'matches', name: t('workflowengine', 'matches') },
			{ operator: '!matches', name: t('workflowengine', 'does not match') },
		],
		element: registerCustomElement(RequestURL, 'oca-workflowengine-checks-request_url'),
	},
	{
		class: 'OCA\\WorkflowEngine\\Check\\RequestTime',
		name: t('workflowengine', 'Request time'),
		operators: [
			{ operator: 'in', name: t('workflowengine', 'between') },
			{ operator: '!in', name: t('workflowengine', 'not between') },
		],
		element: registerCustomElement(RequestTime, 'oca-workflowengine-checks-request_time'),
	},
	{
		class: 'OCA\\WorkflowEngine\\Check\\RequestUserAgent',
		name: t('workflowengine', 'Request user agent'),
		operators: [
			{ operator: 'is', name: t('workflowengine', 'is') },
			{ operator: '!is', name: t('workflowengine', 'is not') },
			{ operator: 'matches', name: t('workflowengine', 'matches') },
			{ operator: '!matches', name: t('workflowengine', 'does not match') },
		],
		element: registerCustomElement(RequestUserAgent, 'oca-workflowengine-checks-request_user_agent'),
	},
	{
		class: 'OCA\\WorkflowEngine\\Check\\UserGroupMembership',
		name: t('workflowengine', 'Group membership'),
		operators: [
			{ operator: 'is', name: t('workflowengine', 'is member of') },
			{ operator: '!is', name: t('workflowengine', 'is not member of') },
		],
		element: registerCustomElement(RequestUserGroup, 'oca-workflowengine-checks-request_user_group'),
	},
]

export default RequestChecks
