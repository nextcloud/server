import type { Node } from '@nextcloud/files'

declare module '@nextcloud/event-bus' {
	export interface NextcloudEvents {
		// mapping of 'event name' => 'event type'
		'files:favorites:added': Node
		'files:favorites:removed': Node
		'files:node:created': Node
		'files:node:deleted': Node
		'files:node:renamed': Node
		'files:node:updated': Node
		'nextcloud:unified-search.search': { query: string }
	}
}

export {}
