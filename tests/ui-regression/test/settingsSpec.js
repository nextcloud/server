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

describe('settings', function () {

	before(async () => {
    await helper.init(this) 
    await helper.login(this) 
  });
	after(async () => await helper.exit());

	config.resolutions.forEach(function (resolution) {
		it('personal.' + resolution.title, async function () {
			return helper.takeAndCompare(this, 'index.php/settings/user', async function (page) {
			}, {viewport: resolution});
		});

		it('admin.' + resolution.title, async function () {
			return helper.takeAndCompare(this, 'index.php/settings/admin', async function (page) {
			}, {viewport: resolution});
		});

		['sharing', 'security', 'theming', 'encryption', 'additional', 'tips-tricks'].forEach(function(endpoint) {
			it('admin.' + endpoint + '.' + resolution.title, async function () {
				return helper.takeAndCompare(this, 'index.php/settings/admin/' + endpoint, async function (page) {
				}, {viewport: resolution, waitUntil: 'networkidle2'});
			});
		});

		it('usermanagement.' + resolution.title, async function () {
			return helper.takeAndCompare(this, 'index.php/settings/users', async function (page) {
			}, {viewport: resolution});
		});

		it('usermanagement.add.' + resolution.title, async function () {
			return helper.takeAndCompare(this, undefined, async function (page) {
				try {
					await page.waitForSelector('#app-navigation-toggle', {
						visible: true,
						timeout: 1000,
					}).then((element) => element.click())
				} catch (err) {}
				let newUserButton = await page.waitForSelector('#new-user-button');
				await newUserButton.click();
				await helper.delay(200);
				await page.$eval('body', function (e) {
					$('#newusername').blur();
				})
				await helper.delay(100);
			}, {viewport: resolution});
		});

	});
});
