/**
 * @copyright Copyright (c) 2016 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

/**
 * @param {any} data -
 */
export function print(data) {
	const name = OC.theme.name || 'Nextcloud'
	const newTab = window.open('', t('twofactor_backupcodes', '{name} backup codes', { name }))
	newTab.document.write('<h1>' + t('twofactor_backupcodes', '{name} backup codes', { name }) + '</h1>')
	newTab.document.write('<pre>' + data + '</pre>')
	newTab.print()
	newTab.close()
}
