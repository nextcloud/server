/**
 * @copyright 2021 François Freitag <mail@franek.fr>
 *
 * @author François Freitag <mail@franek.fr>
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

import '@testing-library/jest-dom'

// Mock `window.location` with Jest spies and extend expect
import 'jest-location-mock'

// Mock `window.fetch` with Jest
import 'jest-fetch-mock'
