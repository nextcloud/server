import axios from '@nextcloud/axios'
import { generateOcsUrl } from '@nextcloud/router'

export default () => {
	return axios.get(generateOcsUrl('core/navigation', 2) + '/apps?format=json')
		.then(({ data }) => {
			if (data.ocs.meta.statuscode !== 200) {
				return
			}

			const addedApps = {}
			const navEntries = data.ocs.data
			const container = document.querySelector('#navigation #apps ul')

			// remove disabled apps
			navEntries.forEach((entry) => {
				if (!container.querySelector('li[data-id="' + entry.id + '"]')) {
					addedApps[entry.id] = true
				}
			})

			container.querySelectorAll('li[data-id]').forEach((el, index) => {
				const id = el.dataset.id
				// remove all apps that are not in the correct order
				if (!navEntries[index] || (navEntries[index] && navEntries[index].id !== id)) {
					el.remove()
					document.querySelector(`#appmenu li[data-id=${id}]`).remove()
				}
			})

			let previousEntry = {}
			// add enabled apps to #navigation and #appmenu
			navEntries.forEach((entry) => {
				if (container.querySelector(`li[data-id="${entry.id}"]`) === null) {
					const li = document.createElement('li')
					li.dataset.id = entry.id
					const img = `<svg width="20" height="20" viewBox="0 0 20 20" alt="">
					  <defs>
					    <filter id="invertMenuMore-${entry.id}"><feColorMatrix in="SourceGraphic" type="matrix" values="-1 0 0 0 1 0 -1 0 0 1 0 0 -1 0 1 0 0 0 1 0"></feColorMatrix></filter>
					      <mask id="hole">
					        <rect width="100%" height="100%" fill="white"></rect>
						<circle r="4.5" cx="17" cy="3" fill="black"></circle>
					      </mask>
					  </defs>
					  <image x="0" y="0" width="16" height="16" filter="url(#invertMenuMore-${entry.id})" preserveAspectRatio="xMinYMin meet" xlink:href="${entry.icon}"  class="app-icon" />
					</svg>`

					const imgElement = document.createElement('template')
					imgElement.innerHTML = img

					const a = document.createElement('a')
					a.setAttribute('href', entry.href)

					const filename = document.createElement('span')
					filename.appendChild(document.createTextNode(entry.name))

					const loading = document.createElement('div')
					loading.setAttribute('class', 'unread-counter')
					loading.style.display = 'none'

					// draw attention to the newly added app entry
					// by flashing twice the more apps menu
					if (addedApps[entry.id]) {
						a.classList.add('animated')
					}

					a.prepend(imgElement.content.firstChild, loading, filename)
					li.append(a)

					// add app icon to the navigation
					const previousElement = document.querySelector(`#navigation li[data-id=${previousEntry.id}]`)
					if (previousElement) {
						previousElement.insertAdjacentElement('afterend', li)
					} else {
						document.querySelector('#navigation #apps ul').prepend(li)
					}
				}

				if (document.getElementById('appmenu').querySelector(`li[data-id="${entry.id}"]`) === null) {
					const li = document.createElement('li')
					li.dataset.id = entry.id
					// Generating svg embedded image (see layout.user.php)
					let img
					if (OCA.Theming && OCA.Theming.inverted) {
						img = `<svg width="20" height="20" viewBox="0 0 20 20" alt="">
						  <defs>
						    <filter id="invert"><feColorMatrix in="SourceGraphic" type="matrix" values="-1 0 0 0 1 0 -1 0 0 1 0 0 -1 0 1 0 0 0 1 0" /></filter>
						  </defs>
						  <image x="0" y="0" width="20" height="20" preserveAspectRatio="xMinYMin meet" filter="url(#invert)" xlink:href="${entry.icon}"  class="app-icon" />
						</svg>`
					} else {
						img = `<svg width="20" height="20" viewBox="0 0 20 20" alt="">
						  <image x="0" y="0" width="20" height="20" preserveAspectRatio="xMinYMin meet" xlink:href="${entry.icon}"  class="app-icon" />
						</svg>`
					}
					const imgElement = document.createElement('template')
					imgElement.innerHTML = img

					const a = document.createElement('a')
					a.setAttribute('href', entry.href)

					const filename = document.createElement('span')
					filename.appendChild(document.createTextNode(entry.name))

					const loading = document.createElement('div')
					loading.setAttribute('class', 'icon-loading-dark')
					loading.style.display = 'none'

					// draw attention to the newly added app entry
					// by flashing twice the more apps menu
					if (addedApps[entry.id]) {
						a.classList.add('animated')
					}

					a.prepend(loading, filename, imgElement.content.firstChild)
					li.append(a)

					// add app icon to the navigation
					const previousElement = document.querySelector('#appmenu li[data-id=' + previousEntry.id + ']')
					if (previousElement) {
						previousElement.insertAdjacentElement('afterend', li)
					} else {
						document.queryElementById('appmenu').prepend(li)
					}
				}
				previousEntry = entry
			})
			window.dispatchEvent(new Event('resize'))
		})
}
