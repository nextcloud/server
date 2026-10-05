/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Check, CheckPlugin, Comparison } from '../../types.ts'

import { t } from '@nextcloud/l10n'
import FileMimeType from './FileMimeType.vue'
import FileSystemTag from './FileSystemTag.vue'
import { registerCustomElement } from '../../helpers/customElements.ts'
import { stringValidator, validateIPv4, validateIPv6 } from '../../helpers/validators.ts'

/** The comparisons a check offers that matches either literally or by regex. */
function stringOrRegexOperators(): Comparison[] {
	return [
		{ operator: 'matches', name: t('workflowengine', 'matches') },
		{ operator: '!matches', name: t('workflowengine', 'does not match') },
		{ operator: 'is', name: t('workflowengine', 'is') },
		{ operator: '!is', name: t('workflowengine', 'is not') },
	]
}

const FileChecks: CheckPlugin[] = [
	{
		class: 'OCA\\WorkflowEngine\\Check\\FileName',
		name: t('workflowengine', 'File name'),
		operators: stringOrRegexOperators,
		placeholder: (check: Check) => {
			if (check.operator === 'matches' || check.operator === '!matches') {
				return '/^dummy-.+$/i'
			}
			return 'filename.txt'
		},
		validate: stringValidator,
	},

	{
		class: 'OCA\\WorkflowEngine\\Check\\FileMimeType',
		name: t('workflowengine', 'File MIME type'),
		operators: stringOrRegexOperators,
		element: registerCustomElement(FileMimeType, 'oca-workflowengine-checks-file_mime_type'),
	},

	{
		class: 'OCA\\WorkflowEngine\\Check\\FileSize',
		name: t('workflowengine', 'File size (upload)'),
		operators: [
			{ operator: 'less', name: t('workflowengine', 'less') },
			{ operator: '!greater', name: t('workflowengine', 'less or equals') },
			{ operator: '!less', name: t('workflowengine', 'greater or equals') },
			{ operator: 'greater', name: t('workflowengine', 'greater') },
		],
		placeholder: () => '5 MB',
		validate: (check: Check) => (check.value ? check.value.match(/^[0-9]+[ ]?[kmgt]?b$/i) !== null : false),
	},

	{
		class: 'OCA\\WorkflowEngine\\Check\\RequestRemoteAddress',
		name: t('workflowengine', 'Request remote address'),
		operators: [
			{ operator: 'matchesIPv4', name: t('workflowengine', 'matches IPv4') },
			{ operator: '!matchesIPv4', name: t('workflowengine', 'does not match IPv4') },
			{ operator: 'matchesIPv6', name: t('workflowengine', 'matches IPv6') },
			{ operator: '!matchesIPv6', name: t('workflowengine', 'does not match IPv6') },
		],
		placeholder: (check: Check) => {
			if (check.operator === 'matchesIPv6' || check.operator === '!matchesIPv6') {
				return '::1/128'
			}
			return '127.0.0.1/32'
		},
		validate: (check: Check) => {
			if (check.operator === 'matchesIPv6' || check.operator === '!matchesIPv6') {
				return validateIPv6(check.value)
			}
			return validateIPv4(check.value)
		},
	},

	{
		class: 'OCA\\WorkflowEngine\\Check\\FileSystemTags',
		name: t('workflowengine', 'File system tag'),
		operators: [
			{ operator: 'is', name: t('workflowengine', 'is tagged with') },
			{ operator: '!is', name: t('workflowengine', 'is not tagged with') },
		],
		element: registerCustomElement(FileSystemTag, 'oca-workflowengine-file_system_tag'),
	},
]

export default FileChecks
