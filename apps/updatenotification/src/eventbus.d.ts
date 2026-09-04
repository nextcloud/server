/*!
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 */

interface INotificationActionEvent {
	cancelAction: boolean
	notification: {
		notificationId: number
		objectId: string
		objectType: string
	}
	action: {
		url: string
		type: 'WEB' | 'GET' | 'POST' | 'DELETE'
	}
}

declare module '@nextcloud/event-bus' {
	interface NextcloudEvents {
		'notifications:action:execute': Readonly<INotificationActionEvent>
	}
}
