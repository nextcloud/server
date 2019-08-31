const ALL_CHECKS = [
	'OCA\\WorkflowEngine\\Check\\FileMimeType',
	'OCA\\WorkflowEngine\\Check\\FileName',
	'OCA\\WorkflowEngine\\Check\\FileSize',
	'OCA\\WorkflowEngine\\Check\\FileSystemTags',
	'OCA\\WorkflowEngine\\Check\\RequestRemoteAddress',
	'OCA\\WorkflowEngine\\Check\\RequestTime',
	'OCA\\WorkflowEngine\\Check\\RequestURL',
	'OCA\\WorkflowEngine\\Check\\RequestUserAgent',
	'OCA\\WorkflowEngine\\Check\\UserGroupMembership'
]

const Operators = {}
/**
 * Extend operators for testing
 */

Operators['OCA\\TestExample\\Operation1'] = {
	id: 'OCA\\TestExample\\Operation1',
	name: 'Rename file',
	description: '🚧 For UI mocking only',
	iconClass: 'icon-address-white',
	color: 'var(--color-success)',
	operation: 'deny'
}
Operators['OCA\\TestExample\\Operation2'] = {
	id: 'OCA\\TestExample\\Operation2',
	name: 'Notify me',
	description: '🚧 For UI mocking only',
	iconClass: 'icon-comment-white',
	color: 'var(--color-warning)',
	operation: 'deny'
}

export {
	Operators,
	ALL_CHECKS
}
