import type { Navigation } from '@nextcloud/files'

declare module 'vue/types/vue' {
	interface Vue {
		$navigation: Navigation
	}
}
