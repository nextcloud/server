/**
 * @copyright 2018 Julius Härtl <jus@bitgrid.net>
 *
 * @author 2018 Julius Härtl <jus@bitgrid.net>
 *
 * @license GNU AGPL version 3 or any later version
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

module.exports = {

	/**
	 * Define resolutions to be tested when diffing screenshots
	 */
	resolutions: [
		{title: 'mobile', w: 360, h: 480},
		{title: 'narrow', w: 800, h: 600},
		{title: 'normal', w: 1024, h: 768},
		{title: 'wide', w: 1920, h: 1080},
		{title: 'qhd', w: 2560, h: 1440},
	],

	/**
	 * URL that holds the base branch
	 */
	urlBase: 'http://ui-regression-php-master/',

	/**
	 * URL that holds the branch to be diffed
	 */
	urlChange: 'http://ui-regression-php/',

	/**
	 * Path to output directory for screenshot files
	 */
	outputDirectory: 'out',

	/**
	 * Run in headless mode (useful for debugging)
	 */
	headless: true,

	slowMo: 0,

};
