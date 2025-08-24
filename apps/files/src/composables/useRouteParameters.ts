/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import { computed } from 'vue'
import { useRoute } from 'vue-router/composables'

/**
 * Get information about the current route
 */
export function useRouteParameters() {

	const route = useRoute()

	/**
	 * Get the path of the current active directory
	 */
	const directory = computed<string>(
		() => String(route.query.dir || '/')
			// Remove any trailing slash but leave root slash
			.replace(/^(.+)\/$/, '$1'),
	)

	/**
	 * Get the current fileId used on the route
	 */
	const fileId = computed<number | null>(() => {
		const fileId = Number.parseInt(route.params.fileid ?? '0') || null
		return Number.isNaN(fileId) ? null : fileId
	})

	/**
	 * State of `openFile` route param
	 */
	const openFile = computed<boolean>(
		// if `openfile` is set it is considered truthy, but allow to explicitly set it to 'false'
		() => 'openfile' in route.query && (typeof route.query.openfile !== 'string' || route.query.openfile.toLocaleLowerCase() !== 'false'),
	)

	const openDetails = computed<boolean>(
		// if `opendetails` is set it is considered truthy, but allow to explicitly set it to 'false'
		() => 'opendetails' in route.query && (typeof route.query.opendetails !== 'string' || route.query.opendetails.toLocaleLowerCase() !== 'false'),
	)

	return {
		/** Path of currently open directory */
		directory,

		/** Current active fileId */
		fileId,

		/** Should the active node should be opened (`openFile` route param) */
		openFile,

		/** Should the details sidebar be shown (`openDetails` route param) */
		openDetails,
	}
}
