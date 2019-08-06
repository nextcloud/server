import ConvertToPdf from './../components/Operations/ConvertToPdf'
import Tag from './../components/Operations/Tag'
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

class EventService {

	constructor() {
		this.events = {}
	}
	registerEvent(event) {
		this.events[event.id] = event
	}

	getAll() {
		return this.events
	}

	get(id) {
		return this.events[id]
	}

}

const operationService = new OperationService()
const eventService = new EventService()


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

/**
 * TODO: move to separate apps
 * TODO: fetch from initial state api
 **/
const EVENT_FILE_ACCESS = 'EVENT_FILE_ACCESS'
const EVENT_FILE_CHANGED = 'EVENT_FILE_CHANGED'
const EVENT_FILE_TAGGED = 'EVENT_FILE_TAGGED'

eventService.registerEvent({
	id: EVENT_FILE_ACCESS,
	name: 'File is accessed',
	icon: 'icon-desktop',
	checks: ALL_CHECKS,
})

eventService.registerEvent({
	id: EVENT_FILE_CHANGED,
	name: 'File was updated',
	icon: 'icon-folder',
	checks: ALL_CHECKS,
})


eventService.registerEvent({
	id: EVENT_FILE_TAGGED,
	name: 'File was tagged',
	icon: 'icon-tag',
	checks: ALL_CHECKS,
})

operationService.registerOperation({
	class: 'OCA\\FilesAccessControl\\Operation',
	title: 'Block access',
	description: 'todo',
	icon: 'icon-block',
	color: 'var(--color-error)',
	events: [
		EVENT_FILE_ACCESS
	],
	operation: 'deny'
})

operationService.registerOperation({
	class: 'OCA\\FilesAutomatedTagging\\Operation',
	title: 'Tag a file',
	description: 'todo',
	icon: 'icon-tag',
	events: [
		EVENT_FILE_CHANGED,
		EVENT_FILE_TAGGED
	],
	options: Tag

})

operationService.registerOperation({
	class: 'OCA\\WorkflowPDFConverter\\Operation',
	title: 'Convert to PDF',
	description: 'todo',
	color: '#dc5047',
	icon: 'icon-convert-pdf',
	events: [
		EVENT_FILE_CHANGED,
		//EVENT_FILE_TAGGED
	],
	options: ConvertToPdf
})


const legacyChecks = Object.values(OCA.WorkflowEngine.Plugins).map((plugin) => {
	if (plugin.component) {
		return {...plugin.getCheck(), component: plugin.component}
	}
	return plugin.getCheck()
}).reduce((obj, item) => {
	obj[item.class] = item
	return obj
}, {})

export {
	eventService,
	operationService
}