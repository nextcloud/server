/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

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
		component: RequestURL,
	},
	{
		class: 'OCA\\WorkflowEngine\\Check\\RequestTime',
		name: t('workflowengine', 'Request time'),
		operators: [
			{ operator: 'in', name: t('workflowengine', 'between') },
			{ operator: '!in', name: t('workflowengine', 'not between') },
		],
		component: RequestTime,
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
		component: RequestUserAgent,
	},
	{
		class: 'OCA\\WorkflowEngine\\Check\\UserGroupMembership',
		name: t('workflowengine', 'Group membership'),
		operators: [
			{ operator: 'is', name: t('workflowengine', 'is member of') },
			{ operator: '!is', name: t('workflowengine', 'is not member of') },
		],
		component: RequestUserGroup,
	},
]

export default RequestChecks
