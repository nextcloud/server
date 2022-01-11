/**
 * Copyright (c) 2014 Vincent Petry <pvince81@owncloud.com>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Jan-Christoph Borchardt <hey@jancborchardt.net>
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

describe('OCA.Files.FavoritesFileList tests', function() {
	var fileList;

	beforeEach(function() {
		// init parameters and test table elements
		$('#testArea').append(
			'<div id="app-content-container">' +
			// init horrible parameters
			'<input type="hidden" id="dir" value="/"></input>' +
			'<input type="hidden" id="permissions" value="31"></input>' +
			// dummy controls
			'<div id="controls">' +
			'   <div class="actions creatable"></div>' +
			'   <div class="notCreatable"></div>' +
			'</div>' +
			// dummy table
			// TODO: at some point this will be rendered by the fileList class itself!
			'<table id="filestable" class="list-container view-grid">' +
			'<thead><tr>' +
			'<th id="headerName" class="hidden column-name">' +
			'<a class="name columntitle" data-sort="name"><span>Name</span><span class="sort-indicator"></span></a>' +
			'</th>' +
			'<th class="hidden column-mtime">' +
			'<a class="columntitle" data-sort="mtime"><span class="sort-indicator"></span></a>' +
			'</th>' +
			'</tr></thead>' +
			'<tbody id="fileList"></tbody>' +
			'<tfoot></tfoot>' +
			'</table>' +
			'<div id="emptycontent">Empty content message</div>' +
			'</div>'
		);
	});

	describe('loading file list', function() {
		var fetchStub;

		beforeEach(function() {
			fileList = new OCA.Files.FavoritesFileList(
				$('#app-content-container')
			);
			OCA.Files.FavoritesPlugin.attach(fileList);

			fetchStub = sinon.stub(fileList.filesClient, 'getFilteredFiles');
		});
		afterEach(function() {
			fetchStub.restore();
			fileList.destroy();
			fileList = undefined;
		});
		it('render files', function(done) {
			var deferred = $.Deferred();
			fetchStub.returns(deferred.promise());

			fileList.reload();

			expect(fetchStub.calledOnce).toEqual(true);

			deferred.resolve(207, [{
				id: 7,
				name: 'test.txt',
				path: '/somedir',
				size: 123,
				mtime: 11111000,
				tags: [OC.TAG_FAVORITE],
				permissions: OC.PERMISSION_ALL,
				mimetype: 'text/plain'
			}]);

			setTimeout(function() {
				var $rows = fileList.$el.find('tbody tr');
				var $tr = $rows.eq(0);
				expect($rows.length).toEqual(1);
				expect($tr.attr('data-id')).toEqual('7');
				expect($tr.attr('data-type')).toEqual('file');
				expect($tr.attr('data-file')).toEqual('test.txt');
				expect($tr.attr('data-path')).toEqual('/somedir');
				expect($tr.attr('data-size')).toEqual('123');
				expect(parseInt($tr.attr('data-permissions'), 10))
					.toEqual(OC.PERMISSION_ALL);
				expect($tr.attr('data-mime')).toEqual('text/plain');
				expect($tr.attr('data-mtime')).toEqual('11111000');
				expect($tr.find('a.name').attr('href')).toEqual(
					OC.getRootPath() +
					'/remote.php/webdav/somedir/test.txt'
				);
				expect($tr.find('.nametext').text().trim()).toEqual('test.txt');

				done();
			}, 0);
		});
	});
});
