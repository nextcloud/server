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

const helper = require('../helper.js');
const config = require('../config.js');

describe('login', function () {

	before(async () => await helper.init(this));
	after(async () => await helper.exit());

	/**
	 * Test login page rendering
	 */
	config.resolutions.forEach(function (resolution) {
		it('login-page.' + resolution.title, async function () {
			return helper.takeAndCompare(this, '/', async (page) => {
				// make sure the cursor is not blinking in the login field
				await page.$eval('body', function (e) {
					$('#user').blur();
				});
				return await helper.delay(100);
			}, {viewport: resolution});
		});

		it('login-page.forgot.' + resolution.title, async function () {
			return helper.takeAndCompare(this, undefined, async (page) => {
				const lostPassword = await page.$('#lost-password');
				await lostPassword.click();
				await helper.delay(500);
				await page.$eval('body', function (e) {
					$('#user').blur();
				});
			}, {viewport: resolution});
		});
	});

	/**
	 * Perform login
	 */
	config.resolutions.forEach(function (resolution) {
		it('login-success.' + resolution.title, async function () {
			this.timeout(30000);
			await helper.resetBrowser();
			return helper.takeAndCompare(this, '/', async function (page) {
				await page.waitForSelector('input#user');
				await page.type('#user', 'admin');
				await page.type('#password', 'admin');
				const inputElement = await page.$('input[type=submit]');
				await inputElement.click();
				await page.waitForNavigation({waitUntil: 'networkidle2'});
				await page.waitForSelector('#header');
				await page.$eval('body', function (e) {
					// force relative timestamp to fixed value, since it breaks screenshot diffing
					$('.live-relative-timestamp').removeClass('live-relative-timestamp').text('5 minutes ago');
				});
				return await helper.delay(100);
			}, {viewport: resolution});
		})
	});

});
