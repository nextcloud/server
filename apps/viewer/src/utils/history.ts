/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { IFile, IFolder, IView } from '@nextcloud/files'

import { getViewer } from '../api_package/viewer.ts'
import { logger } from '../services/logger.ts'

/**
 * Links the viewer to the Files router history so that:
 * - opening a file and navigating between files push history entries, letting
 *   the browser back/forward buttons move between the shown files;
 * - a refresh on an `openfile=true` URL re-opens the viewer (handled by the
 *   Files app calling the viewer file action again);
 * - closing the viewer unwinds those entries so pressing back returns to the
 *   page the viewer was opened from, not to a previously shown file.
 */

/**
 * The Files router, or undefined in standalone mode (no Files app).
 */
function getRouter() {
	return window.OCP?.Files?.Router
}

/**
 * The route name to navigate to. The current route name is often unset, so we
 * fall back to the Files list route (also used by public shares) which carries
 * the fileid in its path. Without a named route the fileid would only live in
 * memory and never reach the URL.
 *
 * @param router - The Files router
 */
function routeName(router: NonNullable<ReturnType<typeof getRouter>>): string {
	return router.name || 'filelist'
}

/**
 * How many entries the viewer has pushed on top of the opening page, tracked in
 * the browser history state so it survives back/forward. Zero on the opening
 * page. We keep our own counter rather than the router position because the
 * router history state is not always populated.
 */
function currentOffset(): number {
	const offset = window.history.state?.viewerPos
	return typeof offset === 'number' ? offset : 0
}

/**
 * Push a history entry for the given file so back/forward can reach it, tagging
 * it with the current viewer offset.
 *
 * @param node - The file to push
 * @param view - The files view the viewer was opened from
 * @param dir - The directory the file lives in
 */
function pushToHistory(node: IFile, view: IView, dir: string): void {
	const router = getRouter()
	if (!router || node.fileid === undefined) {
		return
	}
	const offset = currentOffset() + 1
	// Do not carry a stale `editing` flag onto a freshly opened/navigated file;
	// the editing state is (re)applied by updateEditingParam when actually editing.
	const query: Record<string, string | (string | null)[] | null | undefined> = {
		...router.query,
		dir,
		openfile: 'true',
	}
	delete query.editing
	router.goToRoute(
		routeName(router),
		{ ...router.params, view: view.id, fileid: String(node.fileid) },
		query,
		false,
	)
	window.history.replaceState({ ...window.history.state, viewerPos: offset }, '')
}

/**
 * React to browser back/forward while the viewer is open: move the viewer to
 * the file in the URL, or close it once the user leaves the viewer range.
 */
function onPopState(): void {
	const router = getRouter()
	if (router?.query?.openfile === 'true') {
		const fileid = Number(router.params?.fileid)
		if (!Number.isNaN(fileid)) {
			getViewer().goTo(fileid)
		}
		// Sync the editing state from the URL (back/forward may cross an edit).
		getViewer().setEditing(router.query?.editing === 'true')
		return
	}

	teardown()
	getViewer().close()
}

/**
 * Reflect the editing state in the current history entry (replace, so toggling
 * the editor does not add entries). A refresh then reopens in the same state.
 *
 * @param editing - Whether the viewer is in editing mode
 */
function updateEditingParam(editing: boolean): void {
	const router = getRouter()
	if (!router || (router.query?.editing === 'true') === editing) {
		return
	}
	const query = { ...router.query }
	if (editing) {
		query.editing = 'true'
	} else {
		delete query.editing
	}
	router.goToRoute(routeName(router), router.params, query, true)
}

/**
 * Stop reacting to history navigation.
 */
function teardown(): void {
	window.removeEventListener('popstate', onPopState)
}

/**
 * Clean up history when the viewer is closed. Unwinds every entry the viewer
 * pushed so the back button returns to the opening page.
 */
function closeHistory(): void {
	teardown()

	const router = getRouter()
	if (!router || router.query?.openfile !== 'true') {
		// Already left the viewer range (closed via back navigation): nothing to do.
		return
	}

	const offset = currentOffset()
	if (offset > 0) {
		// Jump back past every entry the viewer added, in one step, so the back
		// button returns to the opening page instead of a previously shown file.
		window.history.go(-offset)
		return
	}

	// Opened from an openfile URL with no pre-viewer entry to return to (refresh):
	// just drop the openfile flag on the current entry.
	const query = { ...router.query }
	delete query.openfile
	delete query.editing
	router.goToRoute(routeName(router), router.params, query, true)
}

/**
 * Open the viewer while keeping the Files router history in sync.
 *
 * @param contents - The files available to the viewer
 * @param file - The file to open
 * @param view - The files view the viewer was opened from
 * @param folder - The folder the files live in
 * @param handlerId - Optional handler to force
 */
export function openWithHistory(
	contents: IFile[],
	file: IFile,
	view: IView | undefined,
	folder: IFolder | undefined,
	handlerId?: string,
): void {
	const router = getRouter()
	if (!router || !view || !folder) {
		// Standalone mode: no history integration, just open.
		getViewer().open(contents, file, { view, folder }, handlerId)
		return
	}

	// When opened from an openfile URL (refresh, or the Files app re-invoking the
	// action after we set openfile) the current entry already points to the file,
	// so we must not push another entry for it.
	const deeplink = router.query?.openfile === 'true'
	window.addEventListener('popstate', onPopState)

	if (!deeplink) {
		pushToHistory(file, view, folder.path)
	} else {
		logger.debug('Viewer opened from an openfile URL, reusing the current history entry')
	}

	getViewer().open(contents, file, {
		view,
		folder,
		// Open straight into editing only on a refresh/deeplink (openfile already
		// set); a fresh open must never inherit a stale editing flag.
		editing: deeplink && router.query?.editing === 'true',
		onNext: (navFile) => pushToHistory(navFile, view, folder.path),
		onPrev: (navFile) => pushToHistory(navFile, view, folder.path),
		onClose: closeHistory,
		onEditingChange: updateEditingParam,
	}, handlerId)
}
