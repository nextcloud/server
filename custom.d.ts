/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
declare module '*.svg?raw' {
	const content: string
	export default content
}

declare module '*.svg' {
	const content: string
	export default content
}

declare module 'vue-material-design-icons/*.vue' {
	import type { Component } from 'vue'
	const icon: Component
	export default icon
}
