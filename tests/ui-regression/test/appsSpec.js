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

describe('apps', function () {

	before(async () => {
    await helper.init(this) 
    await helper.login(this) 
  });
	after(async () => await helper.exit());

	config.resolutions.forEach(function (resolution) {
		it('apps.' + resolution.title, async function () {
			return helper.takeAndCompare(this, 'index.php/settings/apps', async function (page) {
				await page.waitForSelector('#apps-list .section', {timeout: 5000});
				await page.waitFor(500);
			}, {viewport: resolution, waitUntil: 'networkidle2'});
		});

		['your-apps', 'enabled', 'disabled', 'app-bundles'].forEach(function(endpoint) {
			it('apps.' + endpoint + '.' + resolution.title, async function () {
				return helper.takeAndCompare(this, undefined, async function (page) {
					try {
						await page.waitForSelector('#app-navigation-toggle', {
							visible: true,
							timeout: 1000,
						}).then((element) => element.click())
					} catch (err) {}
					await helper.delay(500);
					await page.click('li#app-category-' + endpoint + ' a');
					await helper.delay(500);
					await page.waitForSelector('#app-content:not(.icon-loading)');
				}, {viewport: resolution});
			});
		});
	});

});
