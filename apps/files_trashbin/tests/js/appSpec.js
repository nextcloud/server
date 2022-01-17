/**
* @copyright 2014 Vincent Petry <pvince81@owncloud.com>
 *
 * @author Vincent Petry <vincent@nextcloud.com>
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

describe('OCA.Trashbin.App tests', function() {
	var App = OCA.Trashbin.App;

	beforeEach(function() {
		$('#testArea').append(
			'<div id="app-navigation">' +
			'<ul><li data-id="files"><a>Files</a></li>' +
			'<li data-id="trashbin"><a>Trashbin</a></li>' +
			'</div>' +
			'<div id="app-content">' +
			'<div id="app-content-files" class="hidden">' +
			'</div>' +
			'<div id="app-content-trashbin" class="hidden">' +
			'</div>' +
			'</div>' +
			'</div>'
		);
		App.initialize($('#app-content-trashbin'));
	});
	afterEach(function() {
		App._initialized = false;
		App.fileList = null;
	});

	describe('initialization', function() {
		it('creates a custom filelist instance', function() {
			App.initialize();
			expect(App.fileList).toBeDefined();
			expect(App.fileList.$el.is('#app-content-trashbin')).toEqual(true);
		});

		it('registers custom file actions', function() {
			var fileActions;
			App.initialize();

			fileActions = App.fileList.fileActions;

			expect(fileActions.actions.all).toBeDefined();
			expect(fileActions.actions.all.Restore).toBeDefined();
			expect(fileActions.actions.all.Delete).toBeDefined();

			expect(fileActions.actions.all.Rename).not.toBeDefined();
			expect(fileActions.actions.all.Download).not.toBeDefined();

			expect(fileActions.defaults.dir).toEqual('Open');
		});
	});
});
