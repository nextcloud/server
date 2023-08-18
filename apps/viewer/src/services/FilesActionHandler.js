/**
 * @copyright Copyright (c) 2020 Azul <azul@riseup.net>
 *
 * @author Azul <azul@riseup.net>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

/**
 * @param {Node} node The file to open
 * @param {any} view any The files view
 * @param {string} dir the directory path
 */
export default function(node, view, dir) {
	// replace potential leading double slashes
	const path = `${node.dirname}/${node.basename}`.replace(/^\/\//, '/')
	const oldRoute = [
		window.OCP.Files.Router.name,
		window.OCP.Files.Router.params,
		window.OCP.Files.Router.query,
		true,
	]
	const onClose = () => window.OCP.Files.Router.goToRoute(...oldRoute)
	pushToHistory(node, view, dir)
	OCA.Viewer.open({ path, onPrev: pushToHistory, onNext: pushToHistory, onClose })
}

/**
 * @param {Node} node The file to open
 * @param {any} view any The files view
 * @param {string} dir the directory path
 */
function pushToHistory(node, view, dir) {
	window.OCP.Files.Router.goToRoute(
		null,
		{ view: view.id, fileid: node.fileid },
		{ dir, openfile: true },
		true,
	)
}
