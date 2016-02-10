/**
* ownCloud
*
* @author Vincent Petry
* @copyright 2015 Vincent Petry <pvince81@owncloud.com>
*
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
* License as published by the Free Software Foundation; either
* version 3 of the License, or any later version.
*
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU AFFERO GENERAL PUBLIC LICENSE for more details.
*
* You should have received a copy of the GNU Affero General Public
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
*
*/

describe('OCA.Sharing.PublicApp tests', function() {
	var App = OCA.Sharing.PublicApp;
	var hostStub, protocolStub, webrootStub;
	var $preview;

	beforeEach(function() {
		protocolStub = sinon.stub(OC, 'getProtocol').returns('https');
		hostStub = sinon.stub(OC, 'getHost').returns('example.com:9876');
		webrootStub = sinon.stub(OC, 'getRootPath').returns('/owncloud');
		$preview = $('<div id="preview"></div>');
		$('#testArea').append($preview);
		$preview.append(
			'<div id="mimetype"></div>' +
			'<div id="mimetypeIcon"></div>' +
			'<input type="hidden" id="sharingToken" value="sh4tok"></input>'
		);
	});

	afterEach(function() {
		protocolStub.restore();
		hostStub.restore();
		webrootStub.restore();
	});

	describe('File list', function() {
		// TODO: this should be moved to a separate file once the PublicFileList is extracted from public.js
		beforeEach(function() {
			$preview.append(
				'<div id="app-content-files">' +
				// init horrible parameters
				'<input type="hidden" id="dir" value="/subdir"/>' +
				'<input type="hidden" id="permissions" value="31"/>' +
				// dummy controls
				'<div id="controls">' +
				'   <div class="actions creatable"></div>' +
				'   <div class="notCreatable"></div>' +
				'</div>' +
				// uploader
				'<input type="file" id="file_upload_start" name="files[]" multiple="multiple">' +
				// dummy table
				// TODO: at some point this will be rendered by the fileList class itself!
				'<table id="filestable">' +
				'<thead><tr>' +
				'<th id="headerName" class="hidden column-name">' +
				'<input type="checkbox" id="select_all_files" class="select-all">' +
				'<a class="name columntitle" data-sort="name"><span>Name</span><span class="sort-indicator"></span></a>' +
				'<span class="selectedActions hidden">' +
				'<a href class="download">Download</a>' +
				'</th>' +
				'<th class="hidden column-size"><a class="columntitle" data-sort="size"><span class="sort-indicator"></span></a></th>' +
				'<th class="hidden column-mtime"><a class="columntitle" data-sort="mtime"><span class="sort-indicator"></span></a></th>' +
				'</tr></thead>' +
				'<tbody id="fileList"></tbody>' +
				'<tfoot></tfoot>' +
				'</table>' +
				// TODO: move to handlebars template
				'<div id="emptycontent"><h2>Empty content message</h2><p class="uploadmessage">Upload message</p></div>' +
				'<div class="nofilterresults hidden"></div>' +
				'</div>'
			);

			App.initialize($('#preview'));
		});
		afterEach(function() {
			App._initialized = false;
		});

		it('Uses public webdav endpoint', function() {
			expect(fakeServer.requests.length).toEqual(1);
			expect(fakeServer.requests[0].method).toEqual('PROPFIND');
			expect(fakeServer.requests[0].url).toEqual('https://example.com:9876/owncloud/public.php/webdav/subdir');
			expect(fakeServer.requests[0].requestHeaders.Authorization).toEqual('Basic c2g0dG9rOm51bGw=');
		});

		describe('Download Url', function() {
			var fileList;

			beforeEach(function() {
				fileList = App.fileList;
			});

			it('returns correct download URL for single files', function() {
				expect(fileList.getDownloadUrl('some file.txt'))
					.toEqual(OC.webroot + '/index.php/s/sh4tok/download?path=%2Fsubdir&files=some%20file.txt');
				expect(fileList.getDownloadUrl('some file.txt', '/anotherpath/abc'))
					.toEqual(OC.webroot + '/index.php/s/sh4tok/download?path=%2Fanotherpath%2Fabc&files=some%20file.txt');
				fileList.changeDirectory('/');
				expect(fileList.getDownloadUrl('some file.txt'))
					.toEqual(OC.webroot + '/index.php/s/sh4tok/download?path=%2F&files=some%20file.txt');
			});
			it('returns correct download URL for multiple files', function() {
				expect(fileList.getDownloadUrl(['a b c.txt', 'd e f.txt']))
					.toEqual(OC.webroot + '/index.php/s/sh4tok/download?path=%2Fsubdir&files=%5B%22a%20b%20c.txt%22%2C%22d%20e%20f.txt%22%5D');
			});
			it('returns the correct ajax URL', function() {
				expect(fileList.getAjaxUrl('test', {a:1, b:'x y'}))
					.toEqual(OC.webroot + '/index.php/apps/files_sharing/ajax/test.php?a=1&b=x%20y&t=sh4tok');
			});
		});
	});
});
