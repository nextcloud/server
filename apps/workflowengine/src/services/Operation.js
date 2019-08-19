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

const Entities = OCP.InitialState.loadState('workflowengine', 'entities').map(entity => {
	return {
		...entity,
		// TODO: see if we should have this defined in the backend as well
		checks: [...ALL_CHECKS]
	}
})

const Checks = Object.values(OCA.WorkflowEngine.Plugins).map((plugin) => {
	if (plugin.component) {
		return {...plugin.getCheck(), component: plugin.component}
	}
	return plugin.getCheck()
}).reduce((obj, item) => {
	obj[item.class] = item
	return obj
}, {})

/**
 * Register operations
 * TODO: should be provided by the backend
 */

class OperationService {

	constructor() {
		this.operations = {}
	}
	registerOperation (operation) {
		this.operations[operation.class] = Object.assign({
			color: 'var(--color-primary)'
		}, operation)
	}

	getAll() {
		return this.operations
	}

	get(className) {
		return this.operations[className]
	}

}
const operationService = new OperationService()

operationService.registerOperation({
	class: 'OCA\\FilesAccessControl\\Operation',
	title: 'Block access',
	description: 'Deny access to files when they are accessed',
	icon: 'icon-block',
	color: 'var(--color-error)',
	entites: [
		'WorkflowEngine_Entity_File'
	],
	events: [
		// TODO: this is probably handled differently since there is no regular event for files access control
		'WorkflowEngine_Entity_File::postTouch'
	],
	operation: 'deny'
})

operationService.registerOperation({
	class: 'OCA\\TestExample\\Operation1',
	title: 'Rename file',
	description: '🚧 For UI mocking only',
	icon: 'icon-address-white',
	color: 'var(--color-success)',
	entites: [],
	events: [],
	operation: 'deny'
})
operationService.registerOperation({
	class: 'OCA\\TestExample\\Operation2',
	title: 'Notify me',
	description: '🚧 For UI mocking only',
	icon: 'icon-comment-white',
	color: 'var(--color-warning)',
	entites: [],
	events: [],
	operation: 'deny'
})
operationService.registerOperation({
	class: 'OCA\\TestExample\\Operation3',
	title: 'Call a web hook',
	description: '🚧 For UI mocking only',
	icon: 'icon-category-integration icon-invert',
	color: 'var(--color-primary)',
	entites: [],
	events: [],
	operation: 'deny'
})

operationService.registerOperation({
	class: 'OCA\\FilesAutomatedTagging\\Operation',
	title: 'Tag a file',
	description: 'Assign a tag to a file',
	icon: 'icon-tag-white',
	events: [
		'WorkflowEngine_Entity_File::postWrite',
		//'WorkflowEngine_Entity_File::postTagged',
	],
	options: Tag

})

operationService.registerOperation({
	class: 'OCA\\WorkflowPDFConverter\\Operation',
	title: 'Convert to PDF',
	description: 'Generate a PDF file',
	color: '#dc5047',
	icon: 'icon-convert-pdf',
	events: [
		'WorkflowEngine_Entity_File::postWrite',
		//EVENT_FILE_TAGGED
	],
	options: ConvertToPdf
})

console.debug('[InitialState] Entities', Entities)
console.debug('[WorkflowEngine] Checks', Checks)
console.debug('[WorkflowEngine] Operations', operationService.operations)


export {
	Entities,
	Checks,
	operationService
}
