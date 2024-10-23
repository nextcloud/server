import type { Node } from '@nextcloud/files'

declare module '@nextcloud/event-bus' {
	interface NextcloudEvents {
		'systemtags:node:updated': Node
	}
}

export {}
