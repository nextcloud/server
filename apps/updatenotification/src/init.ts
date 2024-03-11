import { subscribe } from '@nextcloud/event-bus'
import { loadState } from '@nextcloud/initial-state'
import { generateOcsUrl } from '@nextcloud/router'
import Vue, { defineAsyncComponent } from 'vue'
import axios from '@nextcloud/axios'

const navigationEntries = loadState('core', 'apps', {})

const DialogVue = defineAsyncComponent(() => import('./components/AppChangelogDialog.vue'))

/**
 * Show the app changelog dialog
 *
 * @param appId The app to show the changelog for
 * @param version Optional version to show
 */
function showDialog(appId: string, version?: string) {
	const element = document.createElement('div')
	document.body.appendChild(element)

	return new Promise((resolve) => {
		let dismissed = false

		const dialog = new Vue({
			el: element,
			render: (h) => h(DialogVue, {
				props: {
					appId,
					version,
				},
				on: {
					dismiss: () => { dismissed = true },
					'update:open': (open: boolean) => {
						if (!open) {
							dialog.$destroy?.()
							resolve(dismissed)

							if (dismissed && appId in navigationEntries) {
								window.location = navigationEntries[appId].href
							}
						}
					},
				},
			}),
		})
	})
}

interface INotificationActionEvent {
	cancelAction: boolean
	notification: Readonly<{
		notificationId: number
		objectId: string
		objectType: string
	}>
	action: Readonly<{
		url: string
		type: 'WEB'|'GET'|'POST'|'DELETE'
	}>,
}

subscribe('notifications:action:execute', (event: INotificationActionEvent) => {
	if (event.notification.objectType === 'app_updated') {
		event.cancelAction = true

		// eslint-disable-next-line @typescript-eslint/no-unused-vars
		const [_, app, version, __] = event.action.url.match(/(?<=\/)([^?]+)?version=((\d+.?)+)/) ?? []
		showDialog((app as string|undefined) || (event.notification.objectId as string), version)
			.then((dismissed) => {
				if (dismissed) {
					axios.delete(generateOcsUrl('apps/notifications/api/v2/notifications/{id}', { id: event.notification.notificationId }))
				}
			})
	}
})
