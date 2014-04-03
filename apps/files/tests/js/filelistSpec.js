/**
* ownCloud
*
* @author Vincent Petry
* @copyright 2014 Vincent Petry <pvince81@owncloud.com>
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

/* global OC, FileList */
describe('FileList tests', function() {
	var testFiles, alertStub, notificationStub,
		pushStateStub;

	/**
	 * Generate test file data
	 */
	function generateFiles(startIndex, endIndex) {
		var files = [];
		var name;
		for (var i = startIndex; i <= endIndex; i++) {
			name = 'File with index ';
			if (i < 10) {
				// do not rely on localeCompare here
				// and make the sorting predictable
				// cross-browser
				name += '0';
			}
			name += i + '.txt';
			files.push({
				id: i,
				type: 'file',
				name: name,
				mimetype: 'text/plain',
				size: i * 2,
				etag: 'abc'
			});
		}
		return files;
	}

	beforeEach(function() {
		// init horrible parameters
		var $body = $('body');
		$body.append('<input type="hidden" id="dir" value="/subdir"></input>');
		$body.append('<input type="hidden" id="permissions" value="31"></input>');
		// dummy files table
		$body.append('<table id="filestable"></table>');

		// prevents URL changes during tests
		pushStateStub = sinon.stub(window.history, 'pushState');

		alertStub = sinon.stub(OC.dialogs, 'alert');
		notificationStub = sinon.stub(OC.Notification, 'show');

		// init parameters and test table elements
		$('#testArea').append(
			'<input type="hidden" id="dir" value="/subdir"></input>' +
			'<input type="hidden" id="permissions" value="31"></input>' +
			// dummy controls
			'<div id="controls">' +
			'   <div class="actions creatable"></div>' +
			'   <div class="notCreatable"></div>' +
			'</div>' +
			// dummy table
			// TODO: at some point this will be rendered by the FileList class itself!
			'<table id="filestable">' +
			'<thead><tr>' +
			'<th id="headerName" class="hidden column-name">' +
			'<input type="checkbox" id="select_all">' +
			'<a class="name columntitle" data-sort="name"><span>Name</span><span class="sort-indicator"></span></a>' +
			'<span class="selectedActions hidden">' +
			'<a href class="download">Download</a>' +
			'<a href class="delete-selected">Delete</a></span>' +
			'</th>' +
			'<th class="hidden column-size"><a class="columntitle" data-sort="size"><span class="sort-indicator"></span></a></th>' +
			'<th class="hidden column-mtime"><a class="columntitle" data-sort="mtime"><span class="sort-indicator"></span></a></th>' +
			'</tr></thead>' +
		   	'<tbody id="fileList"></tbody>' +
			'<tfoot></tfoot>' +
			'</table>' +
			'<div id="emptycontent">Empty content message</div>'
		);

		testFiles = [{
			id: 1,
			type: 'file',
			name: 'One.txt',
			mimetype: 'text/plain',
			size: 12,
			etag: 'abc'
		}, {
			id: 2,
			type: 'file',
			name: 'Two.jpg',
			mimetype: 'image/jpeg',
			size: 12049,
			etag: 'def',
		}, {
			id: 3,
			type: 'file',
			name: 'Three.pdf',
			mimetype: 'application/pdf',
			size: 58009,
			etag: '123',
		}, {
			id: 4,
			type: 'dir',
			name: 'somedir',
			mimetype: 'httpd/unix-directory',
			size: 250,
			etag: '456'
		}];

		FileList.initialize();
	});
	afterEach(function() {
		testFiles = undefined;
		FileList.initialized = false;
		FileList.isEmpty = true;
		delete FileList._reloadCall;

		$('#dir, #permissions, #filestable').remove();
		notificationStub.restore();
		alertStub.restore();
		pushStateStub.restore();
	});
	describe('Getters', function() {
		it('Returns the current directory', function() {
			$('#dir').val('/one/two/three');
			expect(FileList.getCurrentDirectory()).toEqual('/one/two/three');
		});
		it('Returns the directory permissions as int', function() {
			$('#permissions').val('23');
			expect(FileList.getDirectoryPermissions()).toEqual(23);
		});
	});
	describe('Adding files', function() {
		var clock, now;
		beforeEach(function() {
			// to prevent date comparison issues
			clock = sinon.useFakeTimers();
			now = new Date();
		});
		afterEach(function() {
			clock.restore();
		});
		it('generates file element with correct attributes when calling add() with file data', function() {
			var fileData = {
				id: 18,
				type: 'file',
				name: 'testName.txt',
				mimetype: 'plain/text',
				size: '1234',
				etag: 'a01234c',
				mtime: '123456'
			};
			var $tr = FileList.add(fileData);

			expect($tr).toBeDefined();
			expect($tr[0].tagName.toLowerCase()).toEqual('tr');
			expect($tr.attr('data-id')).toEqual('18');
			expect($tr.attr('data-type')).toEqual('file');
			expect($tr.attr('data-file')).toEqual('testName.txt');
			expect($tr.attr('data-size')).toEqual('1234');
			expect($tr.attr('data-etag')).toEqual('a01234c');
			expect($tr.attr('data-permissions')).toEqual('31');
			expect($tr.attr('data-mime')).toEqual('plain/text');
			expect($tr.attr('data-mtime')).toEqual('123456');
			expect($tr.find('a.name').attr('href')).toEqual(OC.webroot + '/index.php/apps/files/ajax/download.php?dir=%2Fsubdir&files=testName.txt');

			expect($tr.find('.filesize').text()).toEqual('1 kB');
			expect(FileList.findFileEl('testName.txt')[0]).toEqual($tr[0]);
		});
		it('generates dir element with correct attributes when calling add() with dir data', function() {
			var fileData = {
				id: 19,
				type: 'dir',
				name: 'testFolder',
				mimetype: 'httpd/unix-directory',
				size: '1234',
				etag: 'a01234c',
				mtime: '123456'
			};
			var $tr = FileList.add(fileData);

			expect($tr).toBeDefined();
			expect($tr[0].tagName.toLowerCase()).toEqual('tr');
			expect($tr.attr('data-id')).toEqual('19');
			expect($tr.attr('data-type')).toEqual('dir');
			expect($tr.attr('data-file')).toEqual('testFolder');
			expect($tr.attr('data-size')).toEqual('1234');
			expect($tr.attr('data-etag')).toEqual('a01234c');
			expect($tr.attr('data-permissions')).toEqual('31');
			expect($tr.attr('data-mime')).toEqual('httpd/unix-directory');
			expect($tr.attr('data-mtime')).toEqual('123456');

			expect($tr.find('.filesize').text()).toEqual('1 kB');

			expect(FileList.findFileEl('testFolder')[0]).toEqual($tr[0]);
		});
		it('generates file element with default attributes when calling add() with minimal data', function() {
			var fileData = {
				type: 'file',
				name: 'testFile.txt'
			};

		    clock.tick(123456);
			var $tr = FileList.add(fileData);

			expect($tr).toBeDefined();
			expect($tr[0].tagName.toLowerCase()).toEqual('tr');
			expect($tr.attr('data-id')).toEqual(null);
			expect($tr.attr('data-type')).toEqual('file');
			expect($tr.attr('data-file')).toEqual('testFile.txt');
			expect($tr.attr('data-size')).toEqual(null);
			expect($tr.attr('data-etag')).toEqual(null);
			expect($tr.attr('data-permissions')).toEqual('31');
			expect($tr.attr('data-mime')).toEqual(null);
			expect($tr.attr('data-mtime')).toEqual('123456');

			expect($tr.find('.filesize').text()).toEqual('Pending');
		});
		it('generates dir element with default attributes when calling add() with minimal data', function() {
			var fileData = {
				type: 'dir',
				name: 'testFolder'
			};
		    clock.tick(123456);
			var $tr = FileList.add(fileData);

			expect($tr).toBeDefined();
			expect($tr[0].tagName.toLowerCase()).toEqual('tr');
			expect($tr.attr('data-id')).toEqual(null);
			expect($tr.attr('data-type')).toEqual('dir');
			expect($tr.attr('data-file')).toEqual('testFolder');
			expect($tr.attr('data-size')).toEqual(null);
			expect($tr.attr('data-etag')).toEqual(null);
			expect($tr.attr('data-permissions')).toEqual('31');
			expect($tr.attr('data-mime')).toEqual('httpd/unix-directory');
			expect($tr.attr('data-mtime')).toEqual('123456');

			expect($tr.find('.filesize').text()).toEqual('Pending');
		});
		it('generates file element with zero size when size is explicitly zero', function() {
			var fileData = {
				type: 'dir',
				name: 'testFolder',
				size: '0'
			};
			var $tr = FileList.add(fileData);
			expect($tr.find('.filesize').text()).toEqual('0 B');
		});
		it('adds new file to the end of the list', function() {
			var $tr;
			var fileData = {
				type: 'file',
				name: 'ZZZ.txt'
			};
			FileList.setFiles(testFiles);
			$tr = FileList.add(fileData);
			expect($tr.index()).toEqual(4);
		});
		it('inserts files in a sorted manner when insert option is enabled', function() {
			var $tr;
			for (var i = 0; i < testFiles.length; i++) {
				FileList.add(testFiles[i]);
			}
			expect(FileList.files[0].name).toEqual('somedir');
			expect(FileList.files[1].name).toEqual('One.txt');
			expect(FileList.files[2].name).toEqual('Three.pdf');
			expect(FileList.files[3].name).toEqual('Two.jpg');
		});
		it('inserts new file at correct position', function() {
			var $tr;
			var fileData = {
				type: 'file',
				name: 'P comes after O.txt'
			};
			for (var i = 0; i < testFiles.length; i++) {
				FileList.add(testFiles[i]);
			}
			$tr = FileList.add(fileData);
			// after "One.txt"
			expect($tr.index()).toEqual(2);
			expect(FileList.files[2]).toEqual(fileData);
		});
		it('inserts new folder at correct position in insert mode', function() {
			var $tr;
			var fileData = {
				type: 'dir',
				name: 'somedir2 comes after somedir'
			};
			for (var i = 0; i < testFiles.length; i++) {
				FileList.add(testFiles[i]);
			}
			$tr = FileList.add(fileData);
			expect($tr.index()).toEqual(1);
			expect(FileList.files[1]).toEqual(fileData);
		});
		it('inserts new file at the end correctly', function() {
			var $tr;
			var fileData = {
				type: 'file',
				name: 'zzz.txt'
			};
			for (var i = 0; i < testFiles.length; i++) {
				FileList.add(testFiles[i]);
			}
			$tr = FileList.add(fileData);
			expect($tr.index()).toEqual(4);
			expect(FileList.files[4]).toEqual(fileData);
		});
		it('removes empty content message and shows summary when adding first file', function() {
			var fileData = {
				type: 'file',
				name: 'first file.txt',
				size: 12
			};
			FileList.setFiles([]);
			expect(FileList.isEmpty).toEqual(true);
			FileList.add(fileData);
			$summary = $('#filestable .summary');
			expect($summary.hasClass('hidden')).toEqual(false);
			// yes, ugly...
			expect($summary.find('.info').text()).toEqual('0 folders and 1 file');
			expect($summary.find('.dirinfo').hasClass('hidden')).toEqual(true);
			expect($summary.find('.fileinfo').hasClass('hidden')).toEqual(false);
			expect($summary.find('.filesize').text()).toEqual('12 B');
			expect($('#filestable thead th').hasClass('hidden')).toEqual(false);
			expect($('#emptycontent').hasClass('hidden')).toEqual(true);
			expect(FileList.isEmpty).toEqual(false);
		});
	});
	describe('Removing files from the list', function() {
		it('Removes file from list when calling remove() and updates summary', function() {
			var $removedEl;
			FileList.setFiles(testFiles);
			$removedEl = FileList.remove('One.txt');
			expect($removedEl).toBeDefined();
			expect($removedEl.attr('data-file')).toEqual('One.txt');
			expect($('#fileList tr').length).toEqual(3);
			expect(FileList.files.length).toEqual(3);
			expect(FileList.findFileEl('One.txt').length).toEqual(0);

			$summary = $('#filestable .summary');
			expect($summary.hasClass('hidden')).toEqual(false);
			expect($summary.find('.info').text()).toEqual('1 folder and 2 files');
			expect($summary.find('.dirinfo').hasClass('hidden')).toEqual(false);
			expect($summary.find('.fileinfo').hasClass('hidden')).toEqual(false);
			expect($summary.find('.filesize').text()).toEqual('69 kB');
			expect(FileList.isEmpty).toEqual(false);
		});
		it('Shows empty content when removing last file', function() {
			FileList.setFiles([testFiles[0]]);
			FileList.remove('One.txt');
			expect($('#fileList tr').length).toEqual(0);
			expect(FileList.files.length).toEqual(0);
			expect(FileList.findFileEl('One.txt').length).toEqual(0);

			$summary = $('#filestable .summary');
			expect($summary.hasClass('hidden')).toEqual(true);
			expect($('#filestable thead th').hasClass('hidden')).toEqual(true);
			expect($('#emptycontent').hasClass('hidden')).toEqual(false);
			expect(FileList.isEmpty).toEqual(true);
		});
	});
	describe('Deleting files', function() {
		function doDelete() {
			var request, query;
			// note: normally called from FileActions
			FileList.do_delete(['One.txt', 'Two.jpg']);

			expect(fakeServer.requests.length).toEqual(1);
			request = fakeServer.requests[0];
			expect(request.url).toEqual(OC.webroot + '/index.php/apps/files/ajax/delete.php');

			query = fakeServer.requests[0].requestBody;
			expect(OC.parseQueryString(query)).toEqual({'dir': '/subdir', files: '["One.txt","Two.jpg"]'});
		}
		it('calls delete.php, removes the deleted entries and updates summary', function() {
			FileList.setFiles(testFiles);
			doDelete();

			fakeServer.requests[0].respond(
				200,
				{ 'Content-Type': 'application/json' },
				JSON.stringify({status: 'success'})
			);

			expect(FileList.findFileEl('One.txt').length).toEqual(0);
			expect(FileList.findFileEl('Two.jpg').length).toEqual(0);
			expect(FileList.findFileEl('Three.pdf').length).toEqual(1);
			expect(FileList.$fileList.find('tr').length).toEqual(2);

			$summary = $('#filestable .summary');
			expect($summary.hasClass('hidden')).toEqual(false);
			expect($summary.find('.info').text()).toEqual('1 folder and 1 file');
			expect($summary.find('.dirinfo').hasClass('hidden')).toEqual(false);
			expect($summary.find('.fileinfo').hasClass('hidden')).toEqual(false);
			expect($summary.find('.filesize').text()).toEqual('57 kB');
			expect(FileList.isEmpty).toEqual(false);
			expect($('#filestable thead th').hasClass('hidden')).toEqual(false);
			expect($('#emptycontent').hasClass('hidden')).toEqual(true);

			expect(notificationStub.notCalled).toEqual(true);
		});
		it('updates summary when deleting last file', function() {
			FileList.setFiles([testFiles[0], testFiles[1]]);
			doDelete();

			fakeServer.requests[0].respond(
				200,
				{ 'Content-Type': 'application/json' },
				JSON.stringify({status: 'success'})
			);

			expect(FileList.$fileList.find('tr').length).toEqual(0);

			$summary = $('#filestable .summary');
			expect($summary.hasClass('hidden')).toEqual(true);
			expect(FileList.isEmpty).toEqual(true);
			expect(FileList.files.length).toEqual(0);
			expect($('#filestable thead th').hasClass('hidden')).toEqual(true);
			expect($('#emptycontent').hasClass('hidden')).toEqual(false);
		});
		it('bring back deleted item when delete call failed', function() {
			FileList.setFiles(testFiles);
			doDelete();

			fakeServer.requests[0].respond(
				200,
				{ 'Content-Type': 'application/json' },
				JSON.stringify({status: 'error', data: {message: 'WOOT'}})
			);

			// files are still in the list
			expect(FileList.findFileEl('One.txt').length).toEqual(1);
			expect(FileList.findFileEl('Two.jpg').length).toEqual(1);
			expect(FileList.$fileList.find('tr').length).toEqual(4);

			expect(notificationStub.calledOnce).toEqual(true);
		});
	});
	describe('Renaming files', function() {
		function doRename() {
			var $input, request;

			for (var i = 0; i < testFiles.length; i++) {
				FileList.add(testFiles[i]);
			}

			// trigger rename prompt
			FileList.rename('One.txt');
			$input = FileList.$fileList.find('input.filename');
			$input.val('Tu_after_three.txt').blur();

			expect(fakeServer.requests.length).toEqual(1);
			request = fakeServer.requests[0];
			expect(request.url.substr(0, request.url.indexOf('?'))).toEqual(OC.webroot + '/index.php/apps/files/ajax/rename.php');
			expect(OC.parseQueryString(request.url)).toEqual({'dir': '/subdir', newname: 'Tu_after_three.txt', file: 'One.txt'});

			// element is renamed before the request finishes
			expect(FileList.findFileEl('One.txt').length).toEqual(0);
			expect(FileList.findFileEl('Tu_after_three.txt').length).toEqual(1);
			// input is gone
			expect(FileList.$fileList.find('input.filename').length).toEqual(0);
		}
		it('Inserts renamed file entry at correct position if rename ajax call suceeded', function() {
			doRename();

			fakeServer.requests[0].respond(200, {'Content-Type': 'application/json'}, JSON.stringify({
				status: 'success',
				data: {
					name: 'Tu_after_three.txt',
					type: 'file'
				}
			}));

			// element stays renamed
			expect(FileList.findFileEl('One.txt').length).toEqual(0);
			expect(FileList.findFileEl('Tu_after_three.txt').length).toEqual(1);
			expect(FileList.findFileEl('Tu_after_three.txt').index()).toEqual(2); // after Two.txt

			expect(alertStub.notCalled).toEqual(true);
		});
		it('Reverts file entry if rename ajax call failed', function() {
			doRename();

			fakeServer.requests[0].respond(200, {'Content-Type': 'application/json'}, JSON.stringify({
				status: 'error',
				data: {
					message: 'Something went wrong'
				}
			}));

			// element was reverted
			expect(FileList.findFileEl('One.txt').length).toEqual(1);
			expect(FileList.findFileEl('One.txt').index()).toEqual(1); // after somedir
			expect(FileList.findFileEl('Tu_after_three.txt').length).toEqual(0);

			expect(alertStub.calledOnce).toEqual(true);
		});
		it('Correctly updates file link after rename', function() {
			var $tr;
			doRename();

			fakeServer.requests[0].respond(200, {'Content-Type': 'application/json'}, JSON.stringify({
				status: 'success',
				data: {
					name: 'Tu_after_three.txt'
				}
			}));

			$tr = FileList.findFileEl('Tu_after_three.txt');
			expect($tr.find('a.name').attr('href')).toEqual(OC.webroot + '/index.php/apps/files/ajax/download.php?dir=%2Fsubdir&files=Tu_after_three.txt');
		});
		// FIXME: fix this in the source code!
		xit('Correctly updates file link after rename when path has same name', function() {
			var $tr;
			// evil case: because of buggy code
			$('#dir').val('/One.txt/subdir');
			doRename();

			fakeServer.requests[0].respond(200, {'Content-Type': 'application/json'}, JSON.stringify({
				status: 'success',
				data: {
					name: 'Tu_after_three.txt'
				}
			}));

			$tr = FileList.findFileEl('Tu_after_three.txt');
			expect($tr.find('a.name').attr('href')).toEqual(OC.webroot + '/index.php/apps/files/ajax/download.php?dir=%2Fsubdir&files=One.txt');
		});
	});
	describe('Moving files', function() {
		beforeEach(function() {
			FileList.setFiles(testFiles);
		});
		it('Moves single file to target folder', function() {
			var request;
			FileList.move('One.txt', '/somedir');

			expect(fakeServer.requests.length).toEqual(1);
			request = fakeServer.requests[0];
			expect(request.url).toEqual(OC.webroot + '/index.php/apps/files/ajax/move.php');
			expect(OC.parseQueryString(request.requestBody)).toEqual({dir: '/subdir', file: 'One.txt', target: '/somedir'});

			fakeServer.requests[0].respond(200, {'Content-Type': 'application/json'}, JSON.stringify({
				status: 'success',
				data: {
					name: 'One.txt',
					type: 'file'
				}
			}));

			expect(FileList.findFileEl('One.txt').length).toEqual(0);

			// folder size has increased
			expect(FileList.findFileEl('somedir').data('size')).toEqual(262);
			expect(FileList.findFileEl('somedir').find('.filesize').text()).toEqual('262 B');

			expect(notificationStub.notCalled).toEqual(true);
		});
		it('Moves list of files to target folder', function() {
			var request;
			FileList.move(['One.txt', 'Two.jpg'], '/somedir');

			expect(fakeServer.requests.length).toEqual(2);
			request = fakeServer.requests[0];
			expect(request.url).toEqual(OC.webroot + '/index.php/apps/files/ajax/move.php');
			expect(OC.parseQueryString(request.requestBody)).toEqual({dir: '/subdir', file: 'One.txt', target: '/somedir'});

			request = fakeServer.requests[1];
			expect(request.url).toEqual(OC.webroot + '/index.php/apps/files/ajax/move.php');
			expect(OC.parseQueryString(request.requestBody)).toEqual({dir: '/subdir', file: 'Two.jpg', target: '/somedir'});

			fakeServer.requests[0].respond(200, {'Content-Type': 'application/json'}, JSON.stringify({
				status: 'success',
				data: {
					name: 'One.txt',
					type: 'file'
				}
			}));

			expect(FileList.findFileEl('One.txt').length).toEqual(0);

			// folder size has increased
			expect(FileList.findFileEl('somedir').data('size')).toEqual(262);
			expect(FileList.findFileEl('somedir').find('.filesize').text()).toEqual('262 B');

			fakeServer.requests[1].respond(200, {'Content-Type': 'application/json'}, JSON.stringify({
				status: 'success',
				data: {
					name: 'Two.jpg',
					type: 'file'
				}
			}));

			expect(FileList.findFileEl('Two.jpg').length).toEqual(0);

			// folder size has increased
			expect(FileList.findFileEl('somedir').data('size')).toEqual(12311);
			expect(FileList.findFileEl('somedir').find('.filesize').text()).toEqual('12 kB');

			expect(notificationStub.notCalled).toEqual(true);
		});
		it('Shows notification if a file could not be moved', function() {
			var request;
			FileList.move('One.txt', '/somedir');

			expect(fakeServer.requests.length).toEqual(1);
			request = fakeServer.requests[0];
			expect(request.url).toEqual(OC.webroot + '/index.php/apps/files/ajax/move.php');
			expect(OC.parseQueryString(request.requestBody)).toEqual({dir: '/subdir', file: 'One.txt', target: '/somedir'});

			fakeServer.requests[0].respond(200, {'Content-Type': 'application/json'}, JSON.stringify({
				status: 'error',
				data: {
					message: 'Error while moving file',
				}
			}));

			expect(FileList.findFileEl('One.txt').length).toEqual(1);

			expect(notificationStub.calledOnce).toEqual(true);
			expect(notificationStub.getCall(0).args[0]).toEqual('Error while moving file');
		});
	});
	describe('List rendering', function() {
		it('renders a list of files using add()', function() {
			expect(FileList.files.length).toEqual(0);
			expect(FileList.files).toEqual([]);
			FileList.setFiles(testFiles);
			expect($('#fileList tr').length).toEqual(4);
			expect(FileList.files.length).toEqual(4);
			expect(FileList.files).toEqual(testFiles);
		});
		it('updates summary using the file sizes', function() {
			var $summary;
			FileList.setFiles(testFiles);
			$summary = $('#filestable .summary');
			expect($summary.hasClass('hidden')).toEqual(false);
			expect($summary.find('.info').text()).toEqual('1 folder and 3 files');
			expect($summary.find('.filesize').text()).toEqual('69 kB');
		});
		it('shows headers, summary and hide empty content message after setting files', function(){
			FileList.setFiles(testFiles);
			expect($('#filestable thead th').hasClass('hidden')).toEqual(false);
			expect($('#emptycontent').hasClass('hidden')).toEqual(true);
			expect(FileList.$el.find('.summary').hasClass('hidden')).toEqual(false);
		});
		it('hides headers, summary and show empty content message after setting empty file list', function(){
			FileList.setFiles([]);
			expect($('#filestable thead th').hasClass('hidden')).toEqual(true);
			expect($('#emptycontent').hasClass('hidden')).toEqual(false);
			expect(FileList.$el.find('.summary').hasClass('hidden')).toEqual(true);
		});
		it('hides headers, empty content message, and summary when list is empty and user has no creation permission', function(){
			$('#permissions').val(0);
			FileList.setFiles([]);
			expect($('#filestable thead th').hasClass('hidden')).toEqual(true);
			expect($('#emptycontent').hasClass('hidden')).toEqual(true);
			expect(FileList.$el.find('.summary').hasClass('hidden')).toEqual(true);
		});
		it('calling findFileEl() can find existing file element', function() {
			FileList.setFiles(testFiles);
			expect(FileList.findFileEl('Two.jpg').length).toEqual(1);
		});
		it('calling findFileEl() returns empty when file not found in file', function() {
			FileList.setFiles(testFiles);
			expect(FileList.findFileEl('unexist.dat').length).toEqual(0);
		});
		it('only add file if in same current directory', function() {
			$('#dir').val('/current dir');
			var fileData = {
				type: 'file',
				name: 'testFile.txt',
				directory: '/current dir'
			};
			var $tr = FileList.add(fileData);
			expect(FileList.findFileEl('testFile.txt').length).toEqual(1);
		});
		it('triggers "fileActionsReady" event after update', function() {
			var handler = sinon.stub();
			FileList.$fileList.on('fileActionsReady', handler);
			FileList.setFiles(testFiles);
			expect(handler.calledOnce).toEqual(true);
		});
		it('triggers "updated" event after update', function() {
			var handler = sinon.stub();
			FileList.$fileList.on('updated', handler);
			FileList.setFiles(testFiles);
			expect(handler.calledOnce).toEqual(true);
		});
		it('does not update summary when removing non-existing files', function() {
			// single file
			FileList.setFiles([testFiles[0]]);
			$summary = $('#filestable .summary');
			expect($summary.hasClass('hidden')).toEqual(false);
			expect($summary.find('.info').text()).toEqual('0 folders and 1 file');
			FileList.remove('unexist.txt');
			expect($summary.hasClass('hidden')).toEqual(false);
			expect($summary.find('.info').text()).toEqual('0 folders and 1 file');
		});
	});
	describe('Rendering next page on scroll', function() {
		beforeEach(function() {
			FileList.setFiles(generateFiles(0, 64));
		});
		it('renders only the first page', function() {
			expect(FileList.files.length).toEqual(65);
			expect($('#fileList tr').length).toEqual(20);
		});
		it('renders the second page when scrolling down (trigger nextPage)', function() {
			// TODO: can't simulate scrolling here, so calling nextPage directly
			FileList._nextPage(true);
			expect($('#fileList tr').length).toEqual(40);
			FileList._nextPage(true);
			expect($('#fileList tr').length).toEqual(60);
			FileList._nextPage(true);
			expect($('#fileList tr').length).toEqual(65);
			FileList._nextPage(true);
			// stays at 65
			expect($('#fileList tr').length).toEqual(65);
		});
		it('inserts into the DOM if insertion point is in the visible page ', function() {
			FileList.add({
				id: 2000,
				type: 'file',
				name: 'File with index 15b.txt'
			});
			expect($('#fileList tr').length).toEqual(21);
			expect(FileList.findFileEl('File with index 15b.txt').index()).toEqual(16);
		});
		it('does not inserts into the DOM if insertion point is not the visible page ', function() {
			FileList.add({
				id: 2000,
				type: 'file',
				name: 'File with index 28b.txt'
			});
			expect($('#fileList tr').length).toEqual(20);
			expect(FileList.findFileEl('File with index 28b.txt').length).toEqual(0);
			FileList._nextPage(true);
			expect($('#fileList tr').length).toEqual(40);
			expect(FileList.findFileEl('File with index 28b.txt').index()).toEqual(29);
		});
		it('appends into the DOM when inserting a file after the last visible element', function() {
			FileList.add({
				id: 2000,
				type: 'file',
				name: 'File with index 19b.txt'
			});
			expect($('#fileList tr').length).toEqual(21);
			FileList._nextPage(true);
			expect($('#fileList tr').length).toEqual(41);
		});
		it('appends into the DOM when inserting a file on the last page when visible', function() {
			FileList._nextPage(true);
			expect($('#fileList tr').length).toEqual(40);
			FileList._nextPage(true);
			expect($('#fileList tr').length).toEqual(60);
			FileList._nextPage(true);
			expect($('#fileList tr').length).toEqual(65);
			FileList._nextPage(true);
			FileList.add({
				id: 2000,
				type: 'file',
				name: 'File with index 88.txt'
			});
			expect($('#fileList tr').length).toEqual(66);
			FileList._nextPage(true);
			expect($('#fileList tr').length).toEqual(66);
		});
		it('shows additional page when appending a page of files and scrolling down', function() {
			var newFiles = generateFiles(66, 81);
			for (var i = 0; i < newFiles.length; i++) {
				FileList.add(newFiles[i]);
			}
			expect($('#fileList tr').length).toEqual(20);
			FileList._nextPage(true);
			expect($('#fileList tr').length).toEqual(40);
			FileList._nextPage(true);
			expect($('#fileList tr').length).toEqual(60);
			FileList._nextPage(true);
			expect($('#fileList tr').length).toEqual(80);
			FileList._nextPage(true);
			expect($('#fileList tr').length).toEqual(81);
			FileList._nextPage(true);
			expect($('#fileList tr').length).toEqual(81);
		});
		it('automatically renders next page when there are not enough elements visible', function() {
			// delete the 15 first elements
			for (var i = 0; i < 15; i++) {
				FileList.remove(FileList.files[0].name);
			}
			// still makes sure that there are 20 elements visible, if any
			expect($('#fileList tr').length).toEqual(25);
		});
	});
	describe('file previews', function() {
		var previewLoadStub;

		function getImageUrl($el) {
			// might be slightly different cross-browser
			var url = $el.css('background-image');
			var r = url.match(/url\(['"]?([^'")]*)['"]?\)/);
			if (!r) {
				return url;
			}
			return r[1];
		}

		beforeEach(function() {
			previewLoadStub = sinon.stub(Files, 'lazyLoadPreview');
		});
		afterEach(function() {
			previewLoadStub.restore();
		});
		it('renders default icon for file when none provided and no preview is available', function() {
			var fileData = {
				type: 'file',
				name: 'testFile.txt'
			};
			var $tr = FileList.add(fileData);
			var $td = $tr.find('td.filename');
			expect(getImageUrl($td)).toEqual(OC.webroot + '/core/img/filetypes/file.svg');
			expect(previewLoadStub.notCalled).toEqual(true);
		});
		it('renders default icon for dir when none provided and no preview is available', function() {
			var fileData = {
				type: 'dir',
				name: 'test dir'
			};
			var $tr = FileList.add(fileData);
			var $td = $tr.find('td.filename');
			expect(getImageUrl($td)).toEqual(OC.webroot + '/core/img/filetypes/folder.svg');
			expect(previewLoadStub.notCalled).toEqual(true);
		});
		it('renders provided icon for file when provided', function() {
			var fileData = {
				type: 'file',
				name: 'test dir',
				icon: OC.webroot + '/core/img/filetypes/application-pdf.svg'
			};
			var $tr = FileList.add(fileData);
			var $td = $tr.find('td.filename');
			expect(getImageUrl($td)).toEqual(OC.webroot + '/core/img/filetypes/application-pdf.svg');
			expect(previewLoadStub.notCalled).toEqual(true);
		});
		it('renders preview when no icon was provided and preview is available', function() {
			var fileData = {
				type: 'file',
				name: 'test dir',
				isPreviewAvailable: true
			};
			var $tr = FileList.add(fileData);
			var $td = $tr.find('td.filename');
			expect(getImageUrl($td)).toEqual(OC.webroot + '/core/img/filetypes/file.svg');
			expect(previewLoadStub.calledOnce).toEqual(true);
			// third argument is callback
			previewLoadStub.getCall(0).args[2](OC.webroot + '/somepath.png');
			expect(getImageUrl($td)).toEqual(OC.webroot + '/somepath.png');
		});
		it('renders default file type icon when no icon was provided and no preview is available', function() {
			var fileData = {
				type: 'file',
				name: 'test dir',
				isPreviewAvailable: false
			};
			var $tr = FileList.add(fileData);
			var $td = $tr.find('td.filename');
			expect(getImageUrl($td)).toEqual(OC.webroot + '/core/img/filetypes/file.svg');
			expect(previewLoadStub.notCalled).toEqual(true);
		});
	});
	describe('viewer mode', function() {
		it('enabling viewer mode hides files table and action buttons', function() {
			FileList.setViewerMode(true);
			expect($('#filestable').hasClass('hidden')).toEqual(true);
			expect($('.actions').hasClass('hidden')).toEqual(true);
			expect($('.notCreatable').hasClass('hidden')).toEqual(true);
		});
		it('disabling viewer mode restores files table and action buttons', function() {
			FileList.setViewerMode(true);
			FileList.setViewerMode(false);
			expect($('#filestable').hasClass('hidden')).toEqual(false);
			expect($('.actions').hasClass('hidden')).toEqual(false);
			expect($('.notCreatable').hasClass('hidden')).toEqual(true);
		});
		it('disabling viewer mode restores files table and action buttons with correct permissions', function() {
			$('#permissions').val(0);
			FileList.setViewerMode(true);
			FileList.setViewerMode(false);
			expect($('#filestable').hasClass('hidden')).toEqual(false);
			expect($('.actions').hasClass('hidden')).toEqual(true);
			expect($('.notCreatable').hasClass('hidden')).toEqual(false);
		});
	});
	describe('loading file list', function() {
		beforeEach(function() {
			var data = {
				status: 'success',
				data: {
					files: testFiles,
					permissions: 31
				}
			};
			fakeServer.respondWith(/\/index\.php\/apps\/files\/ajax\/list.php\?dir=%2F(subdir|anothersubdir)/, [
					200, {
						"Content-Type": "application/json"
					},
					JSON.stringify(data)
			]);
		});
		it('fetches file list from server and renders it when reload() is called', function() {
			FileList.reload();
			expect(fakeServer.requests.length).toEqual(1);
			var url = fakeServer.requests[0].url;
			var query = url.substr(url.indexOf('?') + 1);
			expect(OC.parseQueryString(query)).toEqual({'dir': '/subdir', sort: 'name', sortdirection: 'asc'});
			fakeServer.respond();
			expect($('#fileList tr').length).toEqual(4);
			expect(FileList.findFileEl('One.txt').length).toEqual(1);
		});
		it('switches dir and fetches file list when calling changeDirectory()', function() {
			FileList.changeDirectory('/anothersubdir');
			expect(FileList.getCurrentDirectory()).toEqual('/anothersubdir');
			expect(fakeServer.requests.length).toEqual(1);
			var url = fakeServer.requests[0].url;
			var query = url.substr(url.indexOf('?') + 1);
			expect(OC.parseQueryString(query)).toEqual({'dir': '/anothersubdir', sort: 'name', sortdirection: 'asc'});
			fakeServer.respond();
		});
		it('switches to root dir when current directory does not exist', function() {
			fakeServer.respondWith(/\/index\.php\/apps\/files\/ajax\/list.php\?dir=%2funexist/, [
					404, {
						"Content-Type": "application/json"
					},
					''
			]);
			FileList.changeDirectory('/unexist');
			fakeServer.respond();
			expect(FileList.getCurrentDirectory()).toEqual('/');
		});
		it('shows mask before loading file list then hides it at the end', function() {
			var showMaskStub = sinon.stub(FileList, 'showMask');
			var hideMaskStub = sinon.stub(FileList, 'hideMask');
			FileList.changeDirectory('/anothersubdir');
			expect(showMaskStub.calledOnce).toEqual(true);
			expect(hideMaskStub.calledOnce).toEqual(false);
			fakeServer.respond();
			expect(showMaskStub.calledOnce).toEqual(true);
			expect(hideMaskStub.calledOnce).toEqual(true);
			showMaskStub.restore();
			hideMaskStub.restore();
		});
		it('changes URL to target dir', function() {
			FileList.changeDirectory('/somedir');
			expect(pushStateStub.calledOnce).toEqual(true);
			expect(pushStateStub.getCall(0).args[0]).toEqual({dir: '/somedir'});
			expect(pushStateStub.getCall(0).args[2]).toEqual(OC.webroot + '/index.php/apps/files?dir=/somedir');
		});
		it('refreshes breadcrumb after update', function() {
			var setDirSpy = sinon.spy(FileList.breadcrumb, 'setDirectory');
			FileList.changeDirectory('/anothersubdir');
			fakeServer.respond();
			expect(FileList.breadcrumb.setDirectory.calledOnce).toEqual(true);
			expect(FileList.breadcrumb.setDirectory.calledWith('/anothersubdir')).toEqual(true);
			setDirSpy.restore();
		});
	});
	describe('breadcrumb events', function() {
		beforeEach(function() {
			var data = {
				status: 'success',
				data: {
					files: testFiles,
					permissions: 31
				}
			};
			fakeServer.respondWith(/\/index\.php\/apps\/files\/ajax\/list.php\?dir=%2Fsubdir/, [
					200, {
						"Content-Type": "application/json"
					},
					JSON.stringify(data)
			]);
		});
		it('clicking on root breadcrumb changes directory to root', function() {
			FileList.changeDirectory('/subdir/two/three with space/four/five');
			fakeServer.respond();
			var changeDirStub = sinon.stub(FileList, 'changeDirectory');
			FileList.breadcrumb.$el.find('.crumb:eq(0)').click();

			expect(changeDirStub.calledOnce).toEqual(true);
			expect(changeDirStub.getCall(0).args[0]).toEqual('/');
			changeDirStub.restore();
		});
		it('clicking on breadcrumb changes directory', function() {
			FileList.changeDirectory('/subdir/two/three with space/four/five');
			fakeServer.respond();
			var changeDirStub = sinon.stub(FileList, 'changeDirectory');
			FileList.breadcrumb.$el.find('.crumb:eq(3)').click();

			expect(changeDirStub.calledOnce).toEqual(true);
			expect(changeDirStub.getCall(0).args[0]).toEqual('/subdir/two/three with space');
			changeDirStub.restore();
		});
		it('dropping files on breadcrumb calls move operation', function() {
			var request, query, testDir = '/subdir/two/three with space/four/five';
			FileList.changeDirectory(testDir);
			fakeServer.respond();
			var $crumb = FileList.breadcrumb.$el.find('.crumb:eq(3)');
			// no idea what this is but is required by the handler
			var ui = {
				helper: {
					find: sinon.stub()
				}
			};
			// returns a list of tr that were dragged
			ui.helper.find.returns([
				$('<tr data-file="One.txt" data-dir="' + testDir + '"></tr>'),
				$('<tr data-file="Two.jpg" data-dir="' + testDir + '"></tr>')
			]);
			// simulate drop event
			FileList._onDropOnBreadCrumb(new $.Event('drop', {target: $crumb}), ui);

			// will trigger two calls to move.php (first one was previous list.php)
			expect(fakeServer.requests.length).toEqual(3);

			request = fakeServer.requests[1];
			expect(request.method).toEqual('POST');
			expect(request.url).toEqual(OC.webroot + '/index.php/apps/files/ajax/move.php');
			query = OC.parseQueryString(request.requestBody);
			expect(query).toEqual({
				target: '/subdir/two/three with space',
				dir: testDir,
				file: 'One.txt'
			});

			request = fakeServer.requests[2];
			expect(request.method).toEqual('POST');
			expect(request.url).toEqual(OC.webroot + '/index.php/apps/files/ajax/move.php');
			query = OC.parseQueryString(request.requestBody);
			expect(query).toEqual({
				target: '/subdir/two/three with space',
				dir: testDir,
				file: 'Two.jpg'
			});
		});
		it('dropping files on same dir breadcrumb does nothing', function() {
			var request, query, testDir = '/subdir/two/three with space/four/five';
			FileList.changeDirectory(testDir);
			fakeServer.respond();
			var $crumb = FileList.breadcrumb.$el.find('.crumb:last');
			// no idea what this is but is required by the handler
			var ui = {
				helper: {
					find: sinon.stub()
				}
			};
			// returns a list of tr that were dragged
			ui.helper.find.returns([
				$('<tr data-file="One.txt" data-dir="' + testDir + '"></tr>'),
				$('<tr data-file="Two.jpg" data-dir="' + testDir + '"></tr>')
			]);
			// simulate drop event
			FileList._onDropOnBreadCrumb(new $.Event('drop', {target: $crumb}), ui);

			// no extra server request
			expect(fakeServer.requests.length).toEqual(1);
		});
	});
	describe('Download Url', function() {
		it('returns correct download URL for single files', function() {
			expect(Files.getDownloadUrl('some file.txt')).toEqual(OC.webroot + '/index.php/apps/files/ajax/download.php?dir=%2Fsubdir&files=some%20file.txt');
			expect(Files.getDownloadUrl('some file.txt', '/anotherpath/abc')).toEqual(OC.webroot + '/index.php/apps/files/ajax/download.php?dir=%2Fanotherpath%2Fabc&files=some%20file.txt');
			$('#dir').val('/');
			expect(Files.getDownloadUrl('some file.txt')).toEqual(OC.webroot + '/index.php/apps/files/ajax/download.php?dir=%2F&files=some%20file.txt');
		});
		it('returns correct download URL for multiple files', function() {
			expect(Files.getDownloadUrl(['a b c.txt', 'd e f.txt'])).toEqual(OC.webroot + '/index.php/apps/files/ajax/download.php?dir=%2Fsubdir&files=%5B%22a%20b%20c.txt%22%2C%22d%20e%20f.txt%22%5D');
		});
		it('returns the correct ajax URL', function() {
			expect(Files.getAjaxUrl('test', {a:1, b:'x y'})).toEqual(OC.webroot + '/index.php/apps/files/ajax/test.php?a=1&b=x%20y');
		});
	});
	describe('File selection', function() {
		beforeEach(function() {
			FileList.setFiles(testFiles);
		});
		it('Selects a file when clicking its checkbox', function() {
			var $tr = FileList.findFileEl('One.txt');
			expect($tr.find('input:checkbox').prop('checked')).toEqual(false);
			$tr.find('td.filename input:checkbox').click();

			expect($tr.find('input:checkbox').prop('checked')).toEqual(true);
		});
		it('Selects/deselect a file when clicking on the name while holding Ctrl', function() {
			var $tr = FileList.findFileEl('One.txt');
			var $tr2 = FileList.findFileEl('Three.pdf');
			var e;
			expect($tr.find('input:checkbox').prop('checked')).toEqual(false);
			expect($tr2.find('input:checkbox').prop('checked')).toEqual(false);
			e = new $.Event('click');
			e.ctrlKey = true;
			$tr.find('td.filename .name').trigger(e);

			expect($tr.find('input:checkbox').prop('checked')).toEqual(true);
			expect($tr2.find('input:checkbox').prop('checked')).toEqual(false);

			// click on second entry, does not clear the selection
			e = new $.Event('click');
			e.ctrlKey = true;
			$tr2.find('td.filename .name').trigger(e);
			expect($tr.find('input:checkbox').prop('checked')).toEqual(true);
			expect($tr2.find('input:checkbox').prop('checked')).toEqual(true);

			expect(_.pluck(FileList.getSelectedFiles(), 'name')).toEqual(['One.txt', 'Three.pdf']);

			// deselect now
			e = new $.Event('click');
			e.ctrlKey = true;
			$tr2.find('td.filename .name').trigger(e);
			expect($tr.find('input:checkbox').prop('checked')).toEqual(true);
			expect($tr2.find('input:checkbox').prop('checked')).toEqual(false);
			expect(_.pluck(FileList.getSelectedFiles(), 'name')).toEqual(['One.txt']);
		});
		it('Selects a range when clicking on one file then Shift clicking on another one', function() {
			var $tr = FileList.findFileEl('One.txt');
			var $tr2 = FileList.findFileEl('Three.pdf');
			var e;
			$tr.find('td.filename input:checkbox').click();
			e = new $.Event('click');
			e.shiftKey = true;
			$tr2.find('td.filename .name').trigger(e);

			expect($tr.find('input:checkbox').prop('checked')).toEqual(true);
			expect($tr2.find('input:checkbox').prop('checked')).toEqual(true);
			expect(FileList.findFileEl('Two.jpg').find('input:checkbox').prop('checked')).toEqual(true);
			var selection = _.pluck(FileList.getSelectedFiles(), 'name');
			expect(selection.length).toEqual(3);
			expect(selection).toContain('One.txt');
			expect(selection).toContain('Two.jpg');
			expect(selection).toContain('Three.pdf');
		});
		it('Selects a range when clicking on one file then Shift clicking on another one that is above the first one', function() {
			var $tr = FileList.findFileEl('One.txt');
			var $tr2 = FileList.findFileEl('Three.pdf');
			var e;
			$tr2.find('td.filename input:checkbox').click();
			e = new $.Event('click');
			e.shiftKey = true;
			$tr.find('td.filename .name').trigger(e);

			expect($tr.find('input:checkbox').prop('checked')).toEqual(true);
			expect($tr2.find('input:checkbox').prop('checked')).toEqual(true);
			expect(FileList.findFileEl('Two.jpg').find('input:checkbox').prop('checked')).toEqual(true);
			var selection = _.pluck(FileList.getSelectedFiles(), 'name');
			expect(selection.length).toEqual(3);
			expect(selection).toContain('One.txt');
			expect(selection).toContain('Two.jpg');
			expect(selection).toContain('Three.pdf');
		});
		it('Selecting all files will automatically check "select all" checkbox', function() {
			expect($('#select_all').prop('checked')).toEqual(false);
			$('#fileList tr td.filename input:checkbox').click();
			expect($('#select_all').prop('checked')).toEqual(true);
		});
		it('Selecting all files on the first visible page will not automatically check "select all" checkbox', function() {
			FileList.setFiles(generateFiles(0, 41));
			expect($('#select_all').prop('checked')).toEqual(false);
			$('#fileList tr td.filename input:checkbox').click();
			expect($('#select_all').prop('checked')).toEqual(false);
		});
		it('Clicking "select all" will select/deselect all files', function() {
			FileList.setFiles(generateFiles(0, 41));
			$('#select_all').click();
			expect($('#select_all').prop('checked')).toEqual(true);
			$('#fileList tr input:checkbox').each(function() {
				expect($(this).prop('checked')).toEqual(true);
			});
			expect(_.pluck(FileList.getSelectedFiles(), 'name').length).toEqual(42);

			$('#select_all').click();
			expect($('#select_all').prop('checked')).toEqual(false);

			$('#fileList tr input:checkbox').each(function() {
				expect($(this).prop('checked')).toEqual(false);
			});
			expect(_.pluck(FileList.getSelectedFiles(), 'name').length).toEqual(0);
		});
		it('Clicking "select all" then deselecting a file will uncheck "select all"', function() {
			$('#select_all').click();
			expect($('#select_all').prop('checked')).toEqual(true);

			var $tr = FileList.findFileEl('One.txt');
			$tr.find('input:checkbox').click();

			expect($('#select_all').prop('checked')).toEqual(false);
			expect(_.pluck(FileList.getSelectedFiles(), 'name').length).toEqual(3);
		});
		it('Updates the selection summary when doing a few manipulations with "Select all"', function() {
			$('#select_all').click();
			expect($('#select_all').prop('checked')).toEqual(true);

			var $tr = FileList.findFileEl('One.txt');
			// unselect one
			$tr.find('input:checkbox').click();

			expect($('#select_all').prop('checked')).toEqual(false);
			expect(_.pluck(FileList.getSelectedFiles(), 'name').length).toEqual(3);

			// select all
			$('#select_all').click();
			expect($('#select_all').prop('checked')).toEqual(true);
			expect(_.pluck(FileList.getSelectedFiles(), 'name').length).toEqual(4);

			// unselect one
			$tr.find('input:checkbox').click();
			expect($('#select_all').prop('checked')).toEqual(false);
			expect(_.pluck(FileList.getSelectedFiles(), 'name').length).toEqual(3);

			// re-select it
			$tr.find('input:checkbox').click();
			expect($('#select_all').prop('checked')).toEqual(true);
			expect(_.pluck(FileList.getSelectedFiles(), 'name').length).toEqual(4);
		});
		it('Auto-selects files on next page when "select all" is checked', function() {
			FileList.setFiles(generateFiles(0, 41));
			$('#select_all').click();

			expect(FileList.$fileList.find('tr input:checkbox:checked').length).toEqual(20);
			FileList._nextPage(true);
			expect(FileList.$fileList.find('tr input:checkbox:checked').length).toEqual(40);
			FileList._nextPage(true);
			expect(FileList.$fileList.find('tr input:checkbox:checked').length).toEqual(42);
			expect(_.pluck(FileList.getSelectedFiles(), 'name').length).toEqual(42);
		});
		it('Selecting files updates selection summary', function() {
			var $summary = $('#headerName a.name>span:first');
			expect($summary.text()).toEqual('Name');
			FileList.findFileEl('One.txt').find('input:checkbox').click();
			FileList.findFileEl('Three.pdf').find('input:checkbox').click();
			FileList.findFileEl('somedir').find('input:checkbox').click();
			expect($summary.text()).toEqual('1 folder & 2 files');
		});
		it('Unselecting files hides selection summary', function() {
			var $summary = $('#headerName a.name>span:first');
			FileList.findFileEl('One.txt').find('input:checkbox').click().click();
			expect($summary.text()).toEqual('Name');
		});
		it('Select/deselect files shows/hides file actions', function() {
			var $actions = $('#headerName .selectedActions');
			var $checkbox = FileList.findFileEl('One.txt').find('input:checkbox');
			expect($actions.hasClass('hidden')).toEqual(true);
			$checkbox.click();
			expect($actions.hasClass('hidden')).toEqual(false);
			$checkbox.click();
			expect($actions.hasClass('hidden')).toEqual(true);
		});
		it('Selection is cleared when switching dirs', function() {
			$('#select_all').click();
			var data = {
				status: 'success',
				data: {
					files: testFiles,
					permissions: 31
				}
			};
			fakeServer.respondWith(/\/index\.php\/apps\/files\/ajax\/list.php/, [
					200, {
						"Content-Type": "application/json"
					},
					JSON.stringify(data)
			]);
			FileList.changeDirectory('/');
			fakeServer.respond();
			expect($('#select_all').prop('checked')).toEqual(false);
			expect(_.pluck(FileList.getSelectedFiles(), 'name')).toEqual([]);
		});
		it('getSelectedFiles returns the selected files even when they are on the next page', function() {
			var selectedFiles;
			FileList.setFiles(generateFiles(0, 41));
			$('#select_all').click();
			// unselect one to not have the "allFiles" case
			FileList.$fileList.find('tr input:checkbox:first').click();

			// only 20 files visible, must still return all the selected ones
			selectedFiles = _.pluck(FileList.getSelectedFiles(), 'name');

			expect(selectedFiles.length).toEqual(41);
		});
		describe('Actions', function() {
			beforeEach(function() {
				FileList.findFileEl('One.txt').find('input:checkbox').click();
				FileList.findFileEl('Three.pdf').find('input:checkbox').click();
				FileList.findFileEl('somedir').find('input:checkbox').click();
			});
			it('getSelectedFiles returns the selected file data', function() {
				var files = FileList.getSelectedFiles();
				expect(files.length).toEqual(3);
				expect(files[0]).toEqual({
					id: 1,
					name: 'One.txt',
					mimetype: 'text/plain',
					type: 'file',
					size: 12,
					etag: 'abc'
				});
				expect(files[1]).toEqual({
					id: 3,
					type: 'file',
					name: 'Three.pdf',
					mimetype: 'application/pdf',
					size: 58009,
					etag: '123'
				});
				expect(files[2]).toEqual({
					id: 4,
					type: 'dir',
					name: 'somedir',
					mimetype: 'httpd/unix-directory',
					size: 250,
					etag: '456'
				});
			});
			it('Removing a file removes it from the selection', function() {
				FileList.remove('Three.pdf');
				var files = FileList.getSelectedFiles();
				expect(files.length).toEqual(2);
				expect(files[0]).toEqual({
					id: 1,
					name: 'One.txt',
					mimetype: 'text/plain',
					type: 'file',
					size: 12,
					etag: 'abc'
				});
				expect(files[1]).toEqual({
					id: 4,
					type: 'dir',
					name: 'somedir',
					mimetype: 'httpd/unix-directory',
					size: 250,
					etag: '456'
				});
			});
			describe('Download', function() {
				it('Opens download URL when clicking "Download"', function() {
					var redirectStub = sinon.stub(OC, 'redirect');
					$('.selectedActions .download').click();
					expect(redirectStub.calledOnce).toEqual(true);
					expect(redirectStub.getCall(0).args[0]).toEqual(OC.webroot + '/index.php/apps/files/ajax/download.php?dir=%2Fsubdir&files=%5B%22One.txt%22%2C%22Three.pdf%22%2C%22somedir%22%5D');
					redirectStub.restore();
				});
				it('Downloads root folder when all selected in root folder', function() {
					$('#dir').val('/');
					$('#select_all').click();
					var redirectStub = sinon.stub(OC, 'redirect');
					$('.selectedActions .download').click();
					expect(redirectStub.calledOnce).toEqual(true);
					expect(redirectStub.getCall(0).args[0]).toEqual(OC.webroot + '/index.php/apps/files/ajax/download.php?dir=%2F&files=');
					redirectStub.restore();
				});
				it('Downloads parent folder when all selected in subfolder', function() {
					$('#select_all').click();
					var redirectStub = sinon.stub(OC, 'redirect');
					$('.selectedActions .download').click();
					expect(redirectStub.calledOnce).toEqual(true);
					expect(redirectStub.getCall(0).args[0]).toEqual(OC.webroot + '/index.php/apps/files/ajax/download.php?dir=%2F&files=subdir');
					redirectStub.restore();
				});
			});
			describe('Delete', function() {
				it('Deletes selected files when "Delete" clicked', function() {
					var request;
					$('.selectedActions .delete-selected').click();
					expect(fakeServer.requests.length).toEqual(1);
					request = fakeServer.requests[0];
					expect(request.url).toEqual(OC.webroot + '/index.php/apps/files/ajax/delete.php');
					expect(OC.parseQueryString(request.requestBody))
						.toEqual({'dir': '/subdir', files: '["One.txt","Three.pdf","somedir"]'});
					fakeServer.requests[0].respond(
						200,
						{ 'Content-Type': 'application/json' },
						JSON.stringify({status: 'success'})
					);
					expect(FileList.findFileEl('One.txt').length).toEqual(0);
					expect(FileList.findFileEl('Three.pdf').length).toEqual(0);
					expect(FileList.findFileEl('somedir').length).toEqual(0);
					expect(FileList.findFileEl('Two.jpg').length).toEqual(1);
				});
				it('Deletes all files when all selected when "Delete" clicked', function() {
					var request;
					$('#select_all').click();
					$('.selectedActions .delete-selected').click();
					expect(fakeServer.requests.length).toEqual(1);
					request = fakeServer.requests[0];
					expect(request.url).toEqual(OC.webroot + '/index.php/apps/files/ajax/delete.php');
					expect(OC.parseQueryString(request.requestBody))
						.toEqual({'dir': '/subdir', allfiles: 'true'});
					fakeServer.requests[0].respond(
						200,
						{ 'Content-Type': 'application/json' },
						JSON.stringify({status: 'success'})
					);
					expect(FileList.isEmpty).toEqual(true);
				});
			});
		});
	});
	describe('Sorting files', function() {
		it('Sorts by name by default', function() {
			FileList.reload();
			expect(fakeServer.requests.length).toEqual(1);
			var url = fakeServer.requests[0].url;
			var query = OC.parseQueryString(url.substr(url.indexOf('?') + 1));
			expect(query.sort).toEqual('name');
			expect(query.sortdirection).toEqual('asc');
		});
		it('Reloads file list with a different sort when clicking on column header of unsorted column', function() {
			FileList.$el.find('.column-size .columntitle').click();
			expect(fakeServer.requests.length).toEqual(1);
			var url = fakeServer.requests[0].url;
			var query = OC.parseQueryString(url.substr(url.indexOf('?') + 1));
			expect(query.sort).toEqual('size');
			expect(query.sortdirection).toEqual('asc');
		});
		it('Toggles sort direction when clicking on already sorted column', function() {
			FileList.$el.find('.column-name .columntitle').click();
			expect(fakeServer.requests.length).toEqual(1);
			var url = fakeServer.requests[0].url;
			var query = OC.parseQueryString(url.substr(url.indexOf('?') + 1));
			expect(query.sort).toEqual('name');
			expect(query.sortdirection).toEqual('desc');
		});
		it('Toggles the sort indicator when clicking on a column header', function() {
			var ASC_CLASS = FileList.SORT_INDICATOR_ASC_CLASS;
			var DESC_CLASS = FileList.SORT_INDICATOR_DESC_CLASS;
			FileList.$el.find('.column-size .columntitle').click();
			// moves triangle to size column
			expect(
				FileList.$el.find('.column-name .sort-indicator').hasClass(ASC_CLASS + ' ' + DESC_CLASS)
			).toEqual(false);
			expect(
				FileList.$el.find('.column-size .sort-indicator').hasClass(ASC_CLASS)
			).toEqual(true);

			// click again on size column, reverses direction
			FileList.$el.find('.column-size .columntitle').click();
			expect(
				FileList.$el.find('.column-size .sort-indicator').hasClass(DESC_CLASS)
			).toEqual(true);

			// click again on size column, reverses direction
			FileList.$el.find('.column-size .columntitle').click();
			expect(
				FileList.$el.find('.column-size .sort-indicator').hasClass(ASC_CLASS)
			).toEqual(true);

			// click on mtime column, moves indicator there
			FileList.$el.find('.column-mtime .columntitle').click();
			expect(
				FileList.$el.find('.column-size .sort-indicator').hasClass(ASC_CLASS + ' ' + DESC_CLASS)
			).toEqual(false);
			expect(
				FileList.$el.find('.column-mtime .sort-indicator').hasClass(ASC_CLASS)
			).toEqual(true);
		});
		it('Uses correct sort comparator when inserting files', function() {
			testFiles.sort(FileList.Comparators.size);
			// this will make it reload the testFiles with the correct sorting
			FileList.$el.find('.column-size .columntitle').click();
			expect(fakeServer.requests.length).toEqual(1);
			fakeServer.requests[0].respond(
				200,
				{ 'Content-Type': 'application/json' },
				JSON.stringify({
					status: 'success',
					data: {
						files: testFiles,
						permissions: 31
					}
				})
			);
			var newFileData = {
				id: 999,
				type: 'file',
				name: 'new file.txt',
				mimetype: 'text/plain',
				size: 40001,
				etag: '999'
			};
			FileList.add(newFileData);
			expect(FileList.files.length).toEqual(5);
			expect(FileList.$fileList.find('tr').length).toEqual(5);
			expect(FileList.findFileEl('One.txt').index()).toEqual(0);
			expect(FileList.findFileEl('somedir').index()).toEqual(1);
			expect(FileList.findFileEl('Two.jpg').index()).toEqual(2);
			expect(FileList.findFileEl('new file.txt').index()).toEqual(3);
			expect(FileList.findFileEl('Three.pdf').index()).toEqual(4);
		});
		it('Uses correct reversed sort comparator when inserting files', function() {
			testFiles.sort(FileList.Comparators.size);
			testFiles.reverse();
			// this will make it reload the testFiles with the correct sorting
			FileList.$el.find('.column-size .columntitle').click();
			expect(fakeServer.requests.length).toEqual(1);
			fakeServer.requests[0].respond(
				200,
				{ 'Content-Type': 'application/json' },
				JSON.stringify({
					status: 'success',
					data: {
						files: testFiles,
						permissions: 31
					}
				})
			);
			// reverse sort
			FileList.$el.find('.column-size .columntitle').click();
			fakeServer.requests[1].respond(
				200,
				{ 'Content-Type': 'application/json' },
				JSON.stringify({
					status: 'success',
					data: {
						files: testFiles,
						permissions: 31
					}
				})
			);
			var newFileData = {
				id: 999,
				type: 'file',
				name: 'new file.txt',
				mimetype: 'text/plain',
				size: 40001,
				etag: '999'
			};
			FileList.add(newFileData);
			expect(FileList.files.length).toEqual(5);
			expect(FileList.$fileList.find('tr').length).toEqual(5);
			expect(FileList.findFileEl('One.txt').index()).toEqual(4);
			expect(FileList.findFileEl('somedir').index()).toEqual(3);
			expect(FileList.findFileEl('Two.jpg').index()).toEqual(2);
			expect(FileList.findFileEl('new file.txt').index()).toEqual(1);
			expect(FileList.findFileEl('Three.pdf').index()).toEqual(0);
		});
	});
});
