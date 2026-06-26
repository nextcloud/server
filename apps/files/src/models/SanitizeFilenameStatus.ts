/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * The current status of the filename sanitization
 */
export enum SanitizeFilenameStatus {
	Unknown = 0,
	Scheduled = 1,
	Running = 2,
	Done = 3,
	Error = 4,
}
