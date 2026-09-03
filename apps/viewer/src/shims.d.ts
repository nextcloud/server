/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare module '*.svg?raw' {
	const content: string
	export default content
}

// The plyr export is broken, let's fix it here
declare module 'plyr' {
	// Import the *type* from the real declaration file
	import type PlyrType from 'plyr/src/js/plyr.d.ts'
	// Import the *value* (class implementation) from the JS file
	import type PlyrImpl from 'plyr/src/js/plyr.js'

	const Plyr: typeof PlyrImpl & typeof PlyrType
	export default Plyr
}
