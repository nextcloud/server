/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

window.addEventListener('DOMContentLoaded', () => {
	globalThis.OCA.WorkflowEngine.registerOperator({
		id: 'OCA\\Files_Versions\\BlockVersioningOperation',
		color: '#ff5900',
		operation: 'deny',
	})
})
