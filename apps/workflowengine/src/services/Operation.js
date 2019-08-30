import ConvertToPdf from './../components/Operations/ConvertToPdf'
import Tag from './../components/Operations/Tag'

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

const Operators = OCP.InitialState.loadState('workflowengine', 'operators')

/**
 * Extend operators for testing
 */
Operators['OCA\\FilesAccessControl\\Operation'] = {
	...Operators['OCA\\FilesAccessControl\\Operation'],
	color: 'var(--color-error)',
	entities: [
		'OCA\\WorkflowEngine\\Entity\\File'
	],
	operation: 'deny'
}
Operators['OCA\\WorkflowPDFConverter\\Operation'] = {
	id: 'OCA\\WorkflowPDFConverter\\Operation',
	name: 'Convert to PDF',
	description: 'Generate a PDF file',
	color: '#dc5047',
	iconClass: 'icon-convert-pdf',
	options: ConvertToPdf
}

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
Operators['OCA\\FilesAutomatedTagging\\Operation'] = {
	id: 'OCA\\FilesAutomatedTagging\\Operation',
	name: 'Tag a file',
	description: 'Assign a tag to a file',
	iconClass: 'icon-tag-white',
	color: 'var(--color-primary)',
	options: Tag
}

export {
	Operators,
	ALL_CHECKS
}
