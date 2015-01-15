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

describe('OCA.Files.FileList tests', function() {
	var testFiles, alertStub, notificationStub, fileList, pageSizeStub;
	var bcResizeStub;

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
		alertStub = sinon.stub(OC.dialogs, 'alert');
		notificationStub = sinon.stub(OC.Notification, 'show');
		// prevent resize algo to mess up breadcrumb order while
		// testing
		bcResizeStub = sinon.stub(OCA.Files.BreadCrumb.prototype, '_resize');

		// init parameters and test table elements
		$('#testArea').append(
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
			'<a href class="delete-selected">Delete</a></span>' +
			'</th>' +
			'<th class="hidden column-size"><a class="columntitle" data-sort="size"><span class="sort-indicator"></span></a></th>' +
			'<th class="hidden column-mtime"><a class="columntitle" data-sort="mtime"><span class="sort-indicator"></span></a></th>' +
			'</tr></thead>' +
			'<tbody id="fileList"></tbody>' +
			'<tfoot></tfoot>' +
			'</table>' +
			'<div id="emptycontent">Empty content message</div>' +
			'<div class="nofilterresults hidden"></div>' +
			'</div>'
		);

		testFiles = [{
			id: 1,
			type: 'file',
			name: 'One.txt',
			mimetype: 'text/plain',
			size: 12,
			etag: 'abc',
			permissions: OC.PERMISSION_ALL
		}, {
			id: 2,
			type: 'file',
			name: 'Two.jpg',
			mimetype: 'image/jpeg',
			size: 12049,
			etag: 'def',
			permissions: OC.PERMISSION_ALL
		}, {
			id: 3,
			type: 'file',
			name: 'Three.pdf',
			mimetype: 'application/pdf',
			size: 58009,
			etag: '123',
			permissions: OC.PERMISSION_ALL
		}, {
			id: 4,
			type: 'dir',
			name: 'somedir',
			mimetype: 'httpd/unix-directory',
			size: 250,
			etag: '456',
			permissions: OC.PERMISSION_ALL
		}];
		pageSizeStub = sinon.stub(OCA.Files.FileList.prototype, 'pageSize').returns(20);
		fileList = new OCA.Files.FileList($('#app-content-files'));
	});
	afterEach(function() {
		testFiles = undefined;
		fileList = undefined;

		notificationStub.restore();
		alertStub.restore();
		bcResizeStub.restore();
		pageSizeStub.restore();
	});
	describe('Getters', function() {
		it('Returns the current directory', function() {
			$('#dir').val('/one/two/three');
			expect(fileList.getCurrentDirectory()).toEqual('/one/two/three');
		});
		it('Returns the directory permissions as int', function() {
			$('#permissions').val('23');
			expect(fileList.getDirectoryPermissions()).toEqual(23);
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
				mimetype: 'text/plain',
				size: '1234',
				etag: 'a01234c',
				mtime: '123456'
			};
			var $tr = fileList.add(fileData);

			expect($tr).toBeDefined();
			expect($tr[0].tagName.toLowerCase()).toEqual('tr');
			expect($tr.attr('data-id')).toEqual('18');
			expect($tr.attr('data-type')).toEqual('file');
			expect($tr.attr('data-file')).toEqual('testName.txt');
			expect($tr.attr('data-size')).toEqual('1234');
			expect($tr.attr('data-etag')).toEqual('a01234c');
			expect($tr.attr('data-permissions')).toEqual('31');
			expect($tr.attr('data-mime')).toEqual('text/plain');
			expect($tr.attr('data-mtime')).toEqual('123456');
			expect($tr.find('a.name').attr('href'))
				.toEqual(OC.webroot + '/index.php/apps/files/ajax/download.php?dir=%2Fsubdir&files=testName.txt');
			expect($tr.find('.nametext').text().trim()).toEqual('testName.txt');

			expect($tr.find('.filesize').text()).toEqual('1 kB');
			expect($tr.find('.date').text()).not.toEqual('?');
			expect(fileList.findFileEl('testName.txt')[0]).toEqual($tr[0]);
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
			var $tr = fileList.add(fileData);

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
			expect($tr.find('.date').text()).not.toEqual('?');

			expect(fileList.findFileEl('testFolder')[0]).toEqual($tr[0]);
		});
		it('generates file element with default attributes when calling add() with minimal data', function() {
			var fileData = {
				type: 'file',
				name: 'testFile.txt'
			};

			clock.tick(123456);
			var $tr = fileList.add(fileData);

			expect($tr).toBeDefined();
			expect($tr[0].tagName.toLowerCase()).toEqual('tr');
			expect($tr.attr('data-id')).toBeUndefined();
			expect($tr.attr('data-type')).toEqual('file');
			expect($tr.attr('data-file')).toEqual('testFile.txt');
			expect($tr.attr('data-size')).toBeUndefined();
			expect($tr.attr('data-etag')).toBeUndefined();
			expect($tr.attr('data-permissions')).toEqual('31');
			expect($tr.attr('data-mime')).toBeUndefined();
			expect($tr.attr('data-mtime')).toEqual('123456');

			expect($tr.find('.filesize').text()).toEqual('Pending');
			expect($tr.find('.date').text()).not.toEqual('?');
		});
		it('generates dir element with default attributes when calling add() with minimal data', function() {
			var fileData = {
				type: 'dir',
				name: 'testFolder'
			};
			clock.tick(123456);
			var $tr = fileList.add(fileData);

			expect($tr).toBeDefined();
			expect($tr[0].tagName.toLowerCase()).toEqual('tr');
			expect($tr.attr('data-id')).toBeUndefined();
			expect($tr.attr('data-type')).toEqual('dir');
			expect($tr.attr('data-file')).toEqual('testFolder');
			expect($tr.attr('data-size')).toBeUndefined();
			expect($tr.attr('data-etag')).toBeUndefined();
			expect($tr.attr('data-permissions')).toEqual('31');
			expect($tr.attr('data-mime')).toEqual('httpd/unix-directory');
			expect($tr.attr('data-mtime')).toEqual('123456');

			expect($tr.find('.filesize').text()).toEqual('Pending');
			expect($tr.find('.date').text()).not.toEqual('?');
		});
		it('generates file element with zero size when size is explicitly zero', function() {
			var fileData = {
				type: 'dir',
				name: 'testFolder',
				size: '0'
			};
			var $tr = fileList.add(fileData);
			expect($tr.find('.filesize').text()).toEqual('0 kB');
		});
		it('generates file element with unknown date when mtime invalid', function() {
			var fileData = {
				type: 'dir',
				name: 'testFolder',
				mtime: -1
			};
			var $tr = fileList.add(fileData);
			expect($tr.find('.date .modified').text()).toEqual('?');
		});
		it('adds new file to the end of the list', function() {
			var $tr;
			var fileData = {
				type: 'file',
				name: 'ZZZ.txt'
			};
			fileList.setFiles(testFiles);
			$tr = fileList.add(fileData);
			expect($tr.index()).toEqual(4);
		});
		it('inserts files in a sorted manner when insert option is enabled', function() {
			var $tr;
			for (var i = 0; i < testFiles.length; i++) {
				fileList.add(testFiles[i]);
			}
			expect(fileList.files[0].name).toEqual('somedir');
			expect(fileList.files[1].name).toEqual('One.txt');
			expect(fileList.files[2].name).toEqual('Three.pdf');
			expect(fileList.files[3].name).toEqual('Two.jpg');
		});
		it('inserts new file at correct position', function() {
			var $tr;
			var fileData = {
				type: 'file',
				name: 'P comes after O.txt'
			};
			for (var i = 0; i < testFiles.length; i++) {
				fileList.add(testFiles[i]);
			}
			$tr = fileList.add(fileData);
			// after "One.txt"
			expect($tr.index()).toEqual(2);
			expect(fileList.files[2]).toEqual(fileData);
		});
		it('inserts new folder at correct position in insert mode', function() {
			var $tr;
			var fileData = {
				type: 'dir',
				name: 'somedir2 comes after somedir'
			};
			for (var i = 0; i < testFiles.length; i++) {
				fileList.add(testFiles[i]);
			}
			$tr = fileList.add(fileData);
			expect($tr.index()).toEqual(1);
			expect(fileList.files[1]).toEqual(fileData);
		});
		it('inserts new file at the end correctly', function() {
			var $tr;
			var fileData = {
				type: 'file',
				name: 'zzz.txt'
			};
			for (var i = 0; i < testFiles.length; i++) {
				fileList.add(testFiles[i]);
			}
			$tr = fileList.add(fileData);
			expect($tr.index()).toEqual(4);
			expect(fileList.files[4]).toEqual(fileData);
		});
		it('removes empty content message and shows summary when adding first file', function() {
			var $summary;
			var fileData = {
				type: 'file',
				name: 'first file.txt',
				size: 12
			};
			fileList.setFiles([]);
			expect(fileList.isEmpty).toEqual(true);
			fileList.add(fileData);
			$summary = $('#filestable .summary');
			expect($summary.hasClass('hidden')).toEqual(false);
			// yes, ugly...
			expect($summary.find('.info').text()).toEqual('0 folders and 1 file');
			expect($summary.find('.dirinfo').hasClass('hidden')).toEqual(true);
			expect($summary.find('.fileinfo').hasClass('hidden')).toEqual(false);
			expect($summary.find('.filesize').text()).toEqual('12 B');
			expect($('#filestable thead th').hasClass('hidden')).toEqual(false);
			expect($('#emptycontent').hasClass('hidden')).toEqual(true);
			expect(fileList.isEmpty).toEqual(false);
		});
	});
	describe('Removing files from the list', function() {
		it('Removes file from list when calling remove() and updates summary', function() {
			var $summary;
			var $removedEl;
			fileList.setFiles(testFiles);
			$removedEl = fileList.remove('One.txt');
			expect($removedEl).toBeDefined();
			expect($removedEl.attr('data-file')).toEqual('One.txt');
			expect($('#fileList tr').length).toEqual(3);
			expect(fileList.files.length).toEqual(3);
			expect(fileList.findFileEl('One.txt').length).toEqual(0);

			$summary = $('#filestable .summary');
			expect($summary.hasClass('hidden')).toEqual(false);
			expect($summary.find('.info').text()).toEqual('1 folder and 2 files');
			expect($summary.find('.dirinfo').hasClass('hidden')).toEqual(false);
			expect($summary.find('.fileinfo').hasClass('hidden')).toEqual(false);
			expect($summary.find('.filesize').text()).toEqual('69 kB');
			expect(fileList.isEmpty).toEqual(false);
		});
		it('Shows empty content when removing last file', function() {
			var $summary;
			fileList.setFiles([testFiles[0]]);
			fileList.remove('One.txt');
			expect($('#fileList tr').length).toEqual(0);
			expect(fileList.files.length).toEqual(0);
			expect(fileList.findFileEl('One.txt').length).toEqual(0);

			$summary = $('#filestable .summary');
			expect($summary.hasClass('hidden')).toEqual(true);
			expect($('#filestable thead th').hasClass('hidden')).toEqual(true);
			expect($('#emptycontent').hasClass('hidden')).toEqual(false);
			expect(fileList.isEmpty).toEqual(true);
		});
	});
	describe('Deleting files', function() {
		function doDelete() {
			var request, query;
			// note: normally called from FileActions
			fileList.do_delete(['One.txt', 'Two.jpg']);

			expect(fakeServer.requests.length).toEqual(1);
			request = fakeServer.requests[0];
			expect(request.url).toEqual(OC.webroot + '/index.php/apps/files/ajax/delete.php');

			query = fakeServer.requests[0].requestBody;
			expect(OC.parseQueryString(query)).toEqual({'dir': '/subdir', files: '["One.txt","Two.jpg"]'});
		}
		it('calls delete.php, removes the deleted entries and updates summary', function() {
			var $summary;
			fileList.setFiles(testFiles);
			doDelete();

			fakeServer.requests[0].respond(
				200,
				{ 'Content-Type': 'application/json' },
				JSON.stringify({status: 'success'})
			);

			expect(fileList.findFileEl('One.txt').length).toEqual(0);
			expect(fileList.findFileEl('Two.jpg').length).toEqual(0);
			expect(fileList.findFileEl('Three.pdf').length).toEqual(1);
			expect(fileList.$fileList.find('tr').length).toEqual(2);

			$summary = $('#filestable .summary');
			expect($summary.hasClass('hidden')).toEqual(false);
			expect($summary.find('.info').text()).toEqual('1 folder and 1 file');
			expect($summary.find('.dirinfo').hasClass('hidden')).toEqual(false);
			expect($summary.find('.fileinfo').hasClass('hidden')).toEqual(false);
			expect($summary.find('.filesize').text()).toEqual('57 kB');
			expect(fileList.isEmpty).toEqual(false);
			expect($('#filestable thead th').hasClass('hidden')).toEqual(false);
			expect($('#emptycontent').hasClass('hidden')).toEqual(true);

			expect(notificationStub.notCalled).toEqual(true);
		});
		it('shows spinner on files to be deleted', function() {
			fileList.setFiles(testFiles);
			doDelete();

			expect(fileList.findFileEl('One.txt').find('.icon-loading-small:not(.icon-delete)').length).toEqual(1);
			expect(fileList.findFileEl('Three.pdf').find('.icon-delete:not(.icon-loading-small)').length).toEqual(1);
		});
		it('shows spinner on all files when deleting all', function() {
			fileList.setFiles(testFiles);

			fileList.do_delete();

			expect(fileList.$fileList.find('tr .icon-loading-small:not(.icon-delete)').length).toEqual(4);
		});
		it('updates summary when deleting last file', function() {
			var $summary;
			fileList.setFiles([testFiles[0], testFiles[1]]);
			doDelete();

			fakeServer.requests[0].respond(
				200,
				{ 'Content-Type': 'application/json' },
				JSON.stringify({status: 'success'})
			);

			expect(fileList.$fileList.find('tr').length).toEqual(0);

			$summary = $('#filestable .summary');
			expect($summary.hasClass('hidden')).toEqual(true);
			expect(fileList.isEmpty).toEqual(true);
			expect(fileList.files.length).toEqual(0);
			expect($('#filestable thead th').hasClass('hidden')).toEqual(true);
			expect($('#emptycontent').hasClass('hidden')).toEqual(false);
		});
		it('bring back deleted item when delete call failed', function() {
			fileList.setFiles(testFiles);
			doDelete();

			fakeServer.requests[0].respond(
				200,
				{ 'Content-Type': 'application/json' },
				JSON.stringify({status: 'error', data: {message: 'WOOT'}})
			);

			// files are still in the list
			expect(fileList.findFileEl('One.txt').length).toEqual(1);
			expect(fileList.findFileEl('Two.jpg').length).toEqual(1);
			expect(fileList.$fileList.find('tr').length).toEqual(4);

			expect(notificationStub.calledOnce).toEqual(true);
		});
	});
	describe('Renaming files', function() {
		function doCancelRename() {
			var $input;
			for (var i = 0; i < testFiles.length; i++) {
				fileList.add(testFiles[i]);
			}

			// trigger rename prompt
			fileList.rename('One.txt');
			$input = fileList.$fileList.find('input.filename');
			// keep same name
			$input.val('One.txt');
			// trigger submit because triggering blur doesn't work in all browsers
			$input.closest('form').trigger('submit');

			expect(fakeServer.requests.length).toEqual(0);
		}
		function doRename() {
			var $input, request;

			for (var i = 0; i < testFiles.length; i++) {
				var file = testFiles[i];
				file.path = '/some/subdir';
				fileList.add(file, {silent: true});
			}

			// trigger rename prompt
			fileList.rename('One.txt');
			$input = fileList.$fileList.find('input.filename');
			$input.val('Tu_after_three.txt');
			// trigger submit because triggering blur doesn't work in all browsers
			$input.closest('form').trigger('submit');

			expect(fakeServer.requests.length).toEqual(1);
			request = fakeServer.requests[0];
			expect(request.url.substr(0, request.url.indexOf('?'))).toEqual(OC.webroot + '/index.php/apps/files/ajax/rename.php');
			expect(OC.parseQueryString(request.url)).toEqual({'dir': '/some/subdir', newname: 'Tu_after_three.txt', file: 'One.txt'});
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
			expect(fileList.findFileEl('One.txt').length).toEqual(0);
			expect(fileList.findFileEl('Tu_after_three.txt').length).toEqual(1);
			expect(fileList.findFileEl('Tu_after_three.txt').index()).toEqual(2); // after Two.txt

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
			expect(fileList.findFileEl('One.txt').length).toEqual(1);
			expect(fileList.findFileEl('One.txt').index()).toEqual(1); // after somedir
			expect(fileList.findFileEl('Tu_after_three.txt').length).toEqual(0);

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

			$tr = fileList.findFileEl('Tu_after_three.txt');
			expect($tr.find('a.name').attr('href')).toEqual(OC.webroot + '/index.php/apps/files/ajax/download.php?dir=%2Fsubdir&files=Tu_after_three.txt');
		});
		it('Triggers "fileActionsReady" event after rename', function() {
			var handler = sinon.stub();
			fileList.$fileList.on('fileActionsReady', handler);
			doRename();
			expect(handler.notCalled).toEqual(true);
			fakeServer.requests[0].respond(200, {'Content-Type': 'application/json'}, JSON.stringify({
				status: 'success',
				data: {
					name: 'Tu_after_three.txt'
				}
			}));
			expect(handler.calledOnce).toEqual(true);
			expect(fileList.$fileList.find('.test').length).toEqual(0);
		});
		it('Leaves the summary alone when reinserting renamed element', function() {
			var $summary = $('#filestable .summary');
			doRename();
			fakeServer.requests[0].respond(200, {'Content-Type': 'application/json'}, JSON.stringify({
				status: 'success',
				data: {
					name: 'Tu_after_three.txt'
				}
			}));
			expect($summary.find('.info').text()).toEqual('1 folder and 3 files');
		});
		it('Leaves the summary alone when cancel renaming', function() {
			var $summary = $('#filestable .summary');
			doCancelRename();
			expect($summary.find('.info').text()).toEqual('1 folder and 3 files');
		});
		it('Hides actions while rename in progress', function() {
			var $tr;
			doRename();

			// element is renamed before the request finishes
			$tr = fileList.findFileEl('Tu_after_three.txt');
			expect($tr.length).toEqual(1);
			expect(fileList.findFileEl('One.txt').length).toEqual(0);
			// file actions are hidden
			expect($tr.find('.action').hasClass('hidden')).toEqual(true);
			expect($tr.find('.fileactions').hasClass('hidden')).toEqual(true);

			// input and form are gone
			expect(fileList.$fileList.find('input.filename').length).toEqual(0);
			expect(fileList.$fileList.find('form').length).toEqual(0);
		});
		it('Validates the file name', function() {
			var $input, $tr;

			for (var i = 0; i < testFiles.length; i++) {
				fileList.add(testFiles[i], {silent: true});
			}

			// trigger rename prompt
			fileList.rename('One.txt');
			$input = fileList.$fileList.find('input.filename');
			$input.val('Two.jpg');

			// simulate key to trigger validation
			$input.trigger(new $.Event('keyup', {keyCode: 97}));

			// input is still there with error
			expect(fileList.$fileList.find('input.filename').length).toEqual(1);
			expect(fileList.$fileList.find('input.filename').hasClass('error')).toEqual(true);

			// trigger submit does not send server request
			$input.closest('form').trigger('submit');
			expect(fakeServer.requests.length).toEqual(0);

			// simulate escape key
			$input.trigger(new $.Event('keyup', {keyCode: 27}));

			// element is added back with the correct name
			$tr = fileList.findFileEl('One.txt');
			expect($tr.length).toEqual(1);
			expect($tr.find('a .nametext').text().trim()).toEqual('One.txt');
			expect($tr.find('a.name').is(':visible')).toEqual(true);

			$tr = fileList.findFileEl('Two.jpg');
			expect($tr.length).toEqual(1);
			expect($tr.find('a .nametext').text().trim()).toEqual('Two.jpg');
			expect($tr.find('a.name').is(':visible')).toEqual(true);

			// input and form are gone
			expect(fileList.$fileList.find('input.filename').length).toEqual(0);
			expect(fileList.$fileList.find('form').length).toEqual(0);
		});
		it('Restores thumbnail when rename was cancelled', function() {
			doRename();

			expect(OC.TestUtil.getImageUrl(fileList.findFileEl('Tu_after_three.txt').find('.thumbnail')))
				.toEqual(OC.imagePath('core', 'loading.gif'));

			fakeServer.requests[0].respond(200, {'Content-Type': 'application/json'}, JSON.stringify({
				status: 'error',
				data: {
					message: 'Something went wrong'
				}
			}));

			expect(fileList.findFileEl('One.txt').length).toEqual(1);
			expect(OC.TestUtil.getImageUrl(fileList.findFileEl('One.txt').find('.thumbnail')))
				.toEqual(OC.imagePath('core', 'filetypes/file.svg'));
		});
	});
	describe('Moving files', function() {
		beforeEach(function() {
			fileList.setFiles(testFiles);
		});
		it('Moves single file to target folder', function() {
			var request;
			fileList.move('One.txt', '/somedir');

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

			expect(fileList.findFileEl('One.txt').length).toEqual(0);

			// folder size has increased
			expect(fileList.findFileEl('somedir').data('size')).toEqual(262);
			expect(fileList.findFileEl('somedir').find('.filesize').text()).toEqual('262 B');

			expect(notificationStub.notCalled).toEqual(true);
		});
		it('Moves list of files to target folder', function() {
			var request;
			fileList.move(['One.txt', 'Two.jpg'], '/somedir');

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

			expect(fileList.findFileEl('One.txt').length).toEqual(0);

			// folder size has increased
			expect(fileList.findFileEl('somedir').data('size')).toEqual(262);
			expect(fileList.findFileEl('somedir').find('.filesize').text()).toEqual('262 B');

			fakeServer.requests[1].respond(200, {'Content-Type': 'application/json'}, JSON.stringify({
				status: 'success',
				data: {
					name: 'Two.jpg',
					type: 'file'
				}
			}));

			expect(fileList.findFileEl('Two.jpg').length).toEqual(0);

			// folder size has increased
			expect(fileList.findFileEl('somedir').data('size')).toEqual(12311);
			expect(fileList.findFileEl('somedir').find('.filesize').text()).toEqual('12 kB');

			expect(notificationStub.notCalled).toEqual(true);
		});
		it('Shows notification if a file could not be moved', function() {
			var request;
			fileList.move('One.txt', '/somedir');

			expect(fakeServer.requests.length).toEqual(1);
			request = fakeServer.requests[0];
			expect(request.url).toEqual(OC.webroot + '/index.php/apps/files/ajax/move.php');
			expect(OC.parseQueryString(request.requestBody)).toEqual({dir: '/subdir', file: 'One.txt', target: '/somedir'});

			fakeServer.requests[0].respond(200, {'Content-Type': 'application/json'}, JSON.stringify({
				status: 'error',
				data: {
					message: 'Error while moving file'
				}
			}));

			expect(fileList.findFileEl('One.txt').length).toEqual(1);

			expect(notificationStub.calledOnce).toEqual(true);
			expect(notificationStub.getCall(0).args[0]).toEqual('Error while moving file');
		});
		it('Restores thumbnail if a file could not be moved', function() {
			var request;
			fileList.move('One.txt', '/somedir');

			expect(OC.TestUtil.getImageUrl(fileList.findFileEl('One.txt').find('.thumbnail')))
				.toEqual(OC.imagePath('core', 'loading.gif'));

			expect(fakeServer.requests.length).toEqual(1);
			request = fakeServer.requests[0];

			fakeServer.requests[0].respond(200, {'Content-Type': 'application/json'}, JSON.stringify({
				status: 'error',
				data: {
					message: 'Error while moving file'
				}
			}));

			expect(fileList.findFileEl('One.txt').length).toEqual(1);

			expect(notificationStub.calledOnce).toEqual(true);
			expect(notificationStub.getCall(0).args[0]).toEqual('Error while moving file');

			expect(OC.TestUtil.getImageUrl(fileList.findFileEl('One.txt').find('.thumbnail')))
				.toEqual(OC.imagePath('core', 'filetypes/file.svg'));
		});
	});
	describe('List rendering', function() {
		it('renders a list of files using add()', function() {
			expect(fileList.files.length).toEqual(0);
			expect(fileList.files).toEqual([]);
			fileList.setFiles(testFiles);
			expect($('#fileList tr').length).toEqual(4);
			expect(fileList.files.length).toEqual(4);
			expect(fileList.files).toEqual(testFiles);
		});
		it('updates summary using the file sizes', function() {
			var $summary;
			fileList.setFiles(testFiles);
			$summary = $('#filestable .summary');
			expect($summary.hasClass('hidden')).toEqual(false);
			expect($summary.find('.info').text()).toEqual('1 folder and 3 files');
			expect($summary.find('.filesize').text()).toEqual('69 kB');
		});
		it('shows headers, summary and hide empty content message after setting files', function(){
			fileList.setFiles(testFiles);
			expect($('#filestable thead th').hasClass('hidden')).toEqual(false);
			expect($('#emptycontent').hasClass('hidden')).toEqual(true);
			expect(fileList.$el.find('.summary').hasClass('hidden')).toEqual(false);
		});
		it('hides headers, summary and show empty content message after setting empty file list', function(){
			fileList.setFiles([]);
			expect($('#filestable thead th').hasClass('hidden')).toEqual(true);
			expect($('#emptycontent').hasClass('hidden')).toEqual(false);
			expect(fileList.$el.find('.summary').hasClass('hidden')).toEqual(true);
		});
		it('hides headers, empty content message, and summary when list is empty and user has no creation permission', function(){
			$('#permissions').val(0);
			fileList.setFiles([]);
			expect($('#filestable thead th').hasClass('hidden')).toEqual(true);
			expect($('#emptycontent').hasClass('hidden')).toEqual(true);
			expect(fileList.$el.find('.summary').hasClass('hidden')).toEqual(true);
		});
		it('calling findFileEl() can find existing file element', function() {
			fileList.setFiles(testFiles);
			expect(fileList.findFileEl('Two.jpg').length).toEqual(1);
		});
		it('calling findFileEl() returns empty when file not found in file', function() {
			fileList.setFiles(testFiles);
			expect(fileList.findFileEl('unexist.dat').length).toEqual(0);
		});
		it('only add file if in same current directory', function() {
			$('#dir').val('/current dir');
			var fileData = {
				type: 'file',
				name: 'testFile.txt',
				directory: '/current dir'
			};
			var $tr = fileList.add(fileData);
			expect(fileList.findFileEl('testFile.txt').length).toEqual(1);
		});
		it('triggers "fileActionsReady" event after update', function() {
			var handler = sinon.stub();
			fileList.$fileList.on('fileActionsReady', handler);
			fileList.setFiles(testFiles);
			expect(handler.calledOnce).toEqual(true);
			expect(handler.getCall(0).args[0].$files.length).toEqual(testFiles.length);
		});
		it('triggers "fileActionsReady" event after single add', function() {
			var handler = sinon.stub();
			var $tr;
			fileList.setFiles(testFiles);
			fileList.$fileList.on('fileActionsReady', handler);
			$tr = fileList.add({name: 'test.txt'});
			expect(handler.calledOnce).toEqual(true);
			expect(handler.getCall(0).args[0].$files.is($tr)).toEqual(true);
		});
		it('triggers "fileActionsReady" event after next page load with the newly appended files', function() {
			var handler = sinon.stub();
			fileList.setFiles(generateFiles(0, 64));
			fileList.$fileList.on('fileActionsReady', handler);
			fileList._nextPage();
			expect(handler.calledOnce).toEqual(true);
			expect(handler.getCall(0).args[0].$files.length).toEqual(fileList.pageSize());
		});
		it('does not trigger "fileActionsReady" event after single add with silent argument', function() {
			var handler = sinon.stub();
			fileList.setFiles(testFiles);
			fileList.$fileList.on('fileActionsReady', handler);
			fileList.add({name: 'test.txt'}, {silent: true});
			expect(handler.notCalled).toEqual(true);
		});
		it('triggers "updated" event after update', function() {
			var handler = sinon.stub();
			fileList.$fileList.on('updated', handler);
			fileList.setFiles(testFiles);
			expect(handler.calledOnce).toEqual(true);
		});
		it('does not update summary when removing non-existing files', function() {
			var $summary;
			// single file
			fileList.setFiles([testFiles[0]]);
			$summary = $('#filestable .summary');
			expect($summary.hasClass('hidden')).toEqual(false);
			expect($summary.find('.info').text()).toEqual('0 folders and 1 file');
			fileList.remove('unexist.txt');
			expect($summary.hasClass('hidden')).toEqual(false);
			expect($summary.find('.info').text()).toEqual('0 folders and 1 file');
		});
	});
	describe('Filtered list rendering', function() {
		it('filters the list of files using filter()', function() {
			expect(fileList.files.length).toEqual(0);
			expect(fileList.files).toEqual([]);
			fileList.setFiles(testFiles);
			var $summary = $('#filestable .summary');
			var $nofilterresults = fileList.$el.find(".nofilterresults");
			expect($nofilterresults.length).toEqual(1);
			expect($summary.hasClass('hidden')).toEqual(false);

			expect($('#fileList tr:not(.hidden)').length).toEqual(4);
			expect(fileList.files.length).toEqual(4);
			expect($summary.hasClass('hidden')).toEqual(false);
			expect($nofilterresults.hasClass('hidden')).toEqual(true);

			fileList.setFilter('e');
			expect($('#fileList tr:not(.hidden)').length).toEqual(3);
			expect(fileList.files.length).toEqual(4);
			expect($summary.hasClass('hidden')).toEqual(false);
			expect($summary.find('.info').text()).toEqual("1 folder and 2 files match 'e'");
			expect($nofilterresults.hasClass('hidden')).toEqual(true);

			fileList.setFilter('ee');
			expect($('#fileList tr:not(.hidden)').length).toEqual(1);
			expect(fileList.files.length).toEqual(4);
			expect($summary.hasClass('hidden')).toEqual(false);
			expect($summary.find('.info').text()).toEqual("0 folders and 1 file matches 'ee'");
			expect($nofilterresults.hasClass('hidden')).toEqual(true);

			fileList.setFilter('eee');
			expect($('#fileList tr:not(.hidden)').length).toEqual(0);
			expect(fileList.files.length).toEqual(4);
			expect($summary.hasClass('hidden')).toEqual(true);
			expect($nofilterresults.hasClass('hidden')).toEqual(false);

			fileList.setFilter('ee');
			expect($('#fileList tr:not(.hidden)').length).toEqual(1);
			expect(fileList.files.length).toEqual(4);
			expect($summary.hasClass('hidden')).toEqual(false);
			expect($summary.find('.info').text()).toEqual("0 folders and 1 file matches 'ee'");
			expect($nofilterresults.hasClass('hidden')).toEqual(true);

			fileList.setFilter('e');
			expect($('#fileList tr:not(.hidden)').length).toEqual(3);
			expect(fileList.files.length).toEqual(4);
			expect($summary.hasClass('hidden')).toEqual(false);
			expect($summary.find('.info').text()).toEqual("1 folder and 2 files match 'e'");
			expect($nofilterresults.hasClass('hidden')).toEqual(true);

			fileList.setFilter('');
			expect($('#fileList tr:not(.hidden)').length).toEqual(4);
			expect(fileList.files.length).toEqual(4);
			expect($summary.hasClass('hidden')).toEqual(false);
			expect($summary.find('.info').text()).toEqual("1 folder and 3 files");
			expect($nofilterresults.hasClass('hidden')).toEqual(true);
		});
		it('hides the emptyfiles notice when using filter()', function() {
			expect(fileList.files.length).toEqual(0);
			expect(fileList.files).toEqual([]);
			fileList.setFiles([]);
			var $summary = $('#filestable .summary');
			var $emptycontent = fileList.$el.find("#emptycontent");
			var $nofilterresults = fileList.$el.find(".nofilterresults");
			expect($emptycontent.length).toEqual(1);
			expect($nofilterresults.length).toEqual(1);

			expect($('#fileList tr:not(.hidden)').length).toEqual(0);
			expect(fileList.files.length).toEqual(0);
			expect($summary.hasClass('hidden')).toEqual(true);
			expect($emptycontent.hasClass('hidden')).toEqual(false);
			expect($nofilterresults.hasClass('hidden')).toEqual(true);

			fileList.setFilter('e');
			expect($('#fileList tr:not(.hidden)').length).toEqual(0);
			expect(fileList.files.length).toEqual(0);
			expect($summary.hasClass('hidden')).toEqual(true);
			expect($emptycontent.hasClass('hidden')).toEqual(true);
			expect($nofilterresults.hasClass('hidden')).toEqual(false);

			fileList.setFilter('');
			expect($('#fileList tr:not(.hidden)').length).toEqual(0);
			expect(fileList.files.length).toEqual(0);
			expect($summary.hasClass('hidden')).toEqual(true);
			expect($emptycontent.hasClass('hidden')).toEqual(false);
			expect($nofilterresults.hasClass('hidden')).toEqual(true);
		});
		it('does not show the emptyfiles or nofilterresults notice when the mask is active', function() {
			expect(fileList.files.length).toEqual(0);
			expect(fileList.files).toEqual([]);
			fileList.showMask();
			fileList.setFiles(testFiles);
			var $emptycontent = fileList.$el.find("#emptycontent");
			var $nofilterresults = fileList.$el.find(".nofilterresults");
			expect($emptycontent.length).toEqual(1);
			expect($nofilterresults.length).toEqual(1);

			expect($emptycontent.hasClass('hidden')).toEqual(true);
			expect($nofilterresults.hasClass('hidden')).toEqual(true);

			/*
			fileList.setFilter('e');
			expect($emptycontent.hasClass('hidden')).toEqual(true);
			expect($nofilterresults.hasClass('hidden')).toEqual(false);
			*/

			fileList.setFilter('');
			expect($emptycontent.hasClass('hidden')).toEqual(true);
			expect($nofilterresults.hasClass('hidden')).toEqual(true);
		});
	});
	describe('Rendering next page on scroll', function() {
		beforeEach(function() {
			fileList.setFiles(generateFiles(0, 64));
		});
		it('renders only the first page', function() {
			expect(fileList.files.length).toEqual(65);
			expect($('#fileList tr').length).toEqual(20);
		});
		it('renders the second page when scrolling down (trigger nextPage)', function() {
			// TODO: can't simulate scrolling here, so calling nextPage directly
			fileList._nextPage(true);
			expect($('#fileList tr').length).toEqual(40);
			fileList._nextPage(true);
			expect($('#fileList tr').length).toEqual(60);
			fileList._nextPage(true);
			expect($('#fileList tr').length).toEqual(65);
			fileList._nextPage(true);
			// stays at 65
			expect($('#fileList tr').length).toEqual(65);
		});
		it('inserts into the DOM if insertion point is in the visible page ', function() {
			fileList.add({
				id: 2000,
				type: 'file',
				name: 'File with index 15b.txt'
			});
			expect($('#fileList tr').length).toEqual(21);
			expect(fileList.findFileEl('File with index 15b.txt').index()).toEqual(16);
		});
		it('does not inserts into the DOM if insertion point is not the visible page ', function() {
			fileList.add({
				id: 2000,
				type: 'file',
				name: 'File with index 28b.txt'
			});
			expect($('#fileList tr').length).toEqual(20);
			expect(fileList.findFileEl('File with index 28b.txt').length).toEqual(0);
			fileList._nextPage(true);
			expect($('#fileList tr').length).toEqual(40);
			expect(fileList.findFileEl('File with index 28b.txt').index()).toEqual(29);
		});
		it('appends into the DOM when inserting a file after the last visible element', function() {
			fileList.add({
				id: 2000,
				type: 'file',
				name: 'File with index 19b.txt'
			});
			expect($('#fileList tr').length).toEqual(21);
			fileList._nextPage(true);
			expect($('#fileList tr').length).toEqual(41);
		});
		it('appends into the DOM when inserting a file on the last page when visible', function() {
			fileList._nextPage(true);
			expect($('#fileList tr').length).toEqual(40);
			fileList._nextPage(true);
			expect($('#fileList tr').length).toEqual(60);
			fileList._nextPage(true);
			expect($('#fileList tr').length).toEqual(65);
			fileList._nextPage(true);
			fileList.add({
				id: 2000,
				type: 'file',
				name: 'File with index 88.txt'
			});
			expect($('#fileList tr').length).toEqual(66);
			fileList._nextPage(true);
			expect($('#fileList tr').length).toEqual(66);
		});
		it('shows additional page when appending a page of files and scrolling down', function() {
			var newFiles = generateFiles(66, 81);
			for (var i = 0; i < newFiles.length; i++) {
				fileList.add(newFiles[i]);
			}
			expect($('#fileList tr').length).toEqual(20);
			fileList._nextPage(true);
			expect($('#fileList tr').length).toEqual(40);
			fileList._nextPage(true);
			expect($('#fileList tr').length).toEqual(60);
			fileList._nextPage(true);
			expect($('#fileList tr').length).toEqual(80);
			fileList._nextPage(true);
			expect($('#fileList tr').length).toEqual(81);
			fileList._nextPage(true);
			expect($('#fileList tr').length).toEqual(81);
		});
		it('automatically renders next page when there are not enough elements visible', function() {
			// delete the 15 first elements
			for (var i = 0; i < 15; i++) {
				fileList.remove(fileList.files[0].name);
			}
			// still makes sure that there are 20 elements visible, if any
			expect($('#fileList tr').length).toEqual(25);
		});
	});
	describe('file previews', function() {
		var previewLoadStub;

		beforeEach(function() {
			previewLoadStub = sinon.stub(OCA.Files.FileList.prototype, 'lazyLoadPreview');
		});
		afterEach(function() {
			previewLoadStub.restore();
		});
		it('renders default icon for file when none provided and no preview is available', function() {
			var fileData = {
				type: 'file',
				name: 'testFile.txt'
			};
			var $tr = fileList.add(fileData);
			var $imgDiv = $tr.find('td.filename .thumbnail');
			expect(OC.TestUtil.getImageUrl($imgDiv)).toEqual(OC.webroot + '/core/img/filetypes/file.svg');
			expect(previewLoadStub.notCalled).toEqual(true);
		});
		it('renders default icon for dir when none provided and no preview is available', function() {
			var fileData = {
				type: 'dir',
				name: 'test dir'
			};
			var $tr = fileList.add(fileData);
			var $imgDiv = $tr.find('td.filename .thumbnail');
			expect(OC.TestUtil.getImageUrl($imgDiv)).toEqual(OC.webroot + '/core/img/filetypes/folder.svg');
			expect(previewLoadStub.notCalled).toEqual(true);
		});
		it('renders provided icon for file when provided', function() {
			var fileData = {
				type: 'file',
				name: 'test dir',
				icon: OC.webroot + '/core/img/filetypes/application-pdf.svg'
			};
			var $tr = fileList.add(fileData);
			var $imgDiv = $tr.find('td.filename .thumbnail');
			expect(OC.TestUtil.getImageUrl($imgDiv)).toEqual(OC.webroot + '/core/img/filetypes/application-pdf.svg');
			expect(previewLoadStub.notCalled).toEqual(true);
		});
		it('renders preview when no icon was provided and preview is available', function() {
			var fileData = {
				type: 'file',
				name: 'test dir',
				isPreviewAvailable: true
			};
			var $tr = fileList.add(fileData);
			var $td = $tr.find('td.filename');
			expect(OC.TestUtil.getImageUrl($td.find('.thumbnail'))).toEqual(OC.webroot + '/core/img/filetypes/file.svg');
			expect(previewLoadStub.calledOnce).toEqual(true);
			// third argument is callback
			previewLoadStub.getCall(0).args[0].callback(OC.webroot + '/somepath.png');
			expect(OC.TestUtil.getImageUrl($td.find('.thumbnail'))).toEqual(OC.webroot + '/somepath.png');
		});
		it('renders default file type icon when no icon was provided and no preview is available', function() {
			var fileData = {
				type: 'file',
				name: 'test dir',
				isPreviewAvailable: false
			};
			var $tr = fileList.add(fileData);
			var $imgDiv = $tr.find('td.filename .thumbnail');
			expect(OC.TestUtil.getImageUrl($imgDiv)).toEqual(OC.webroot + '/core/img/filetypes/file.svg');
			expect(previewLoadStub.notCalled).toEqual(true);
		});
	});
	describe('viewer mode', function() {
		it('enabling viewer mode hides files table and action buttons', function() {
			fileList.setViewerMode(true);
			expect($('#filestable').hasClass('hidden')).toEqual(true);
			expect($('.actions').hasClass('hidden')).toEqual(true);
			expect($('.notCreatable').hasClass('hidden')).toEqual(true);
		});
		it('disabling viewer mode restores files table and action buttons', function() {
			fileList.setViewerMode(true);
			fileList.setViewerMode(false);
			expect($('#filestable').hasClass('hidden')).toEqual(false);
			expect($('.actions').hasClass('hidden')).toEqual(false);
			expect($('.notCreatable').hasClass('hidden')).toEqual(true);
		});
		it('disabling viewer mode restores files table and action buttons with correct permissions', function() {
			$('#permissions').val(0);
			fileList.setViewerMode(true);
			fileList.setViewerMode(false);
			expect($('#filestable').hasClass('hidden')).toEqual(false);
			expect($('.actions').hasClass('hidden')).toEqual(true);
			expect($('.notCreatable').hasClass('hidden')).toEqual(false);
		});
		it('toggling viewer mode triggers event', function() {
			var handler = sinon.stub();
			fileList.$el.on('changeViewerMode', handler);
			fileList.setViewerMode(true);
			expect(handler.calledOnce).toEqual(true);
			expect(handler.getCall(0).args[0].viewerModeEnabled).toEqual(true);

			handler.reset();
			fileList.setViewerMode(false);
			expect(handler.calledOnce).toEqual(true);
			expect(handler.getCall(0).args[0].viewerModeEnabled).toEqual(false);
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
			fileList.reload();
			expect(fakeServer.requests.length).toEqual(1);
			var url = fakeServer.requests[0].url;
			var query = url.substr(url.indexOf('?') + 1);
			expect(OC.parseQueryString(query)).toEqual({'dir': '/subdir', sort: 'name', sortdirection: 'asc'});
			fakeServer.respond();
			expect($('#fileList tr').length).toEqual(4);
			expect(fileList.findFileEl('One.txt').length).toEqual(1);
		});
		it('switches dir and fetches file list when calling changeDirectory()', function() {
			fileList.changeDirectory('/anothersubdir');
			expect(fileList.getCurrentDirectory()).toEqual('/anothersubdir');
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
			fileList.changeDirectory('/unexist');
			fakeServer.respond();
			expect(fileList.getCurrentDirectory()).toEqual('/');
		});
		it('shows mask before loading file list then hides it at the end', function() {
			var showMaskStub = sinon.stub(fileList, 'showMask');
			var hideMaskStub = sinon.stub(fileList, 'hideMask');
			fileList.changeDirectory('/anothersubdir');
			expect(showMaskStub.calledOnce).toEqual(true);
			expect(hideMaskStub.calledOnce).toEqual(false);
			fakeServer.respond();
			expect(showMaskStub.calledOnce).toEqual(true);
			expect(hideMaskStub.calledOnce).toEqual(true);
			showMaskStub.restore();
			hideMaskStub.restore();
		});
		it('triggers "changeDirectory" event when changing directory', function() {
			var handler = sinon.stub();
			$('#app-content-files').on('changeDirectory', handler);
			fileList.changeDirectory('/somedir');
			expect(handler.calledOnce).toEqual(true);
			expect(handler.getCall(0).args[0].dir).toEqual('/somedir');
		});
		it('changes the directory when receiving "urlChanged" event', function() {
			$('#app-content-files').trigger(new $.Event('urlChanged', {view: 'files', dir: '/somedir'}));
			expect(fileList.getCurrentDirectory()).toEqual('/somedir');
		});
		it('refreshes breadcrumb after update', function() {
			var setDirSpy = sinon.spy(fileList.breadcrumb, 'setDirectory');
			fileList.changeDirectory('/anothersubdir');
			fakeServer.respond();
			expect(fileList.breadcrumb.setDirectory.calledOnce).toEqual(true);
			expect(fileList.breadcrumb.setDirectory.calledWith('/anothersubdir')).toEqual(true);
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
			fileList.changeDirectory('/subdir/two/three with space/four/five');
			fakeServer.respond();
			var changeDirStub = sinon.stub(fileList, 'changeDirectory');
			fileList.breadcrumb.$el.find('.crumb:eq(0)').click();

			expect(changeDirStub.calledOnce).toEqual(true);
			expect(changeDirStub.getCall(0).args[0]).toEqual('/');
			changeDirStub.restore();
		});
		it('clicking on breadcrumb changes directory', function() {
			fileList.changeDirectory('/subdir/two/three with space/four/five');
			fakeServer.respond();
			var changeDirStub = sinon.stub(fileList, 'changeDirectory');
			fileList.breadcrumb.$el.find('.crumb:eq(3)').click();

			expect(changeDirStub.calledOnce).toEqual(true);
			expect(changeDirStub.getCall(0).args[0]).toEqual('/subdir/two/three with space');
			changeDirStub.restore();
		});
		it('dropping files on breadcrumb calls move operation', function() {
			var request, query, testDir = '/subdir/two/three with space/four/five';
			fileList.changeDirectory(testDir);
			fakeServer.respond();
			var $crumb = fileList.breadcrumb.$el.find('.crumb:eq(3)');
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
			fileList._onDropOnBreadCrumb(new $.Event('drop', {target: $crumb}), ui);

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
			var testDir = '/subdir/two/three with space/four/five';
			fileList.changeDirectory(testDir);
			fakeServer.respond();
			var $crumb = fileList.breadcrumb.$el.find('.crumb:last');
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
			fileList._onDropOnBreadCrumb(new $.Event('drop', {target: $crumb}), ui);

			// no extra server request
			expect(fakeServer.requests.length).toEqual(1);
		});
	});
	describe('Download Url', function() {
		it('returns correct download URL for single files', function() {
			expect(fileList.getDownloadUrl('some file.txt')).toEqual(OC.webroot + '/index.php/apps/files/ajax/download.php?dir=%2Fsubdir&files=some%20file.txt');
			expect(fileList.getDownloadUrl('some file.txt', '/anotherpath/abc')).toEqual(OC.webroot + '/index.php/apps/files/ajax/download.php?dir=%2Fanotherpath%2Fabc&files=some%20file.txt');
			$('#dir').val('/');
			expect(fileList.getDownloadUrl('some file.txt')).toEqual(OC.webroot + '/index.php/apps/files/ajax/download.php?dir=%2F&files=some%20file.txt');
		});
		it('returns correct download URL for multiple files', function() {
			expect(fileList.getDownloadUrl(['a b c.txt', 'd e f.txt'])).toEqual(OC.webroot + '/index.php/apps/files/ajax/download.php?dir=%2Fsubdir&files=%5B%22a%20b%20c.txt%22%2C%22d%20e%20f.txt%22%5D');
		});
		it('returns the correct ajax URL', function() {
			expect(fileList.getAjaxUrl('test', {a:1, b:'x y'})).toEqual(OC.webroot + '/index.php/apps/files/ajax/test.php?a=1&b=x%20y');
		});
	});
	describe('File selection', function() {
		beforeEach(function() {
			fileList.setFiles(testFiles);
		});
		it('Selects a file when clicking its checkbox', function() {
			var $tr = fileList.findFileEl('One.txt');
			expect($tr.find('input:checkbox').prop('checked')).toEqual(false);
			$tr.find('td.filename input:checkbox').click();

			expect($tr.find('input:checkbox').prop('checked')).toEqual(true);
		});
		it('Selects/deselect a file when clicking on the name while holding Ctrl', function() {
			var $tr = fileList.findFileEl('One.txt');
			var $tr2 = fileList.findFileEl('Three.pdf');
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

			expect(_.pluck(fileList.getSelectedFiles(), 'name')).toEqual(['One.txt', 'Three.pdf']);

			// deselect now
			e = new $.Event('click');
			e.ctrlKey = true;
			$tr2.find('td.filename .name').trigger(e);
			expect($tr.find('input:checkbox').prop('checked')).toEqual(true);
			expect($tr2.find('input:checkbox').prop('checked')).toEqual(false);
			expect(_.pluck(fileList.getSelectedFiles(), 'name')).toEqual(['One.txt']);
		});
		it('Selects a range when clicking on one file then Shift clicking on another one', function() {
			var $tr = fileList.findFileEl('One.txt');
			var $tr2 = fileList.findFileEl('Three.pdf');
			var e;
			$tr.find('td.filename input:checkbox').click();
			e = new $.Event('click');
			e.shiftKey = true;
			$tr2.find('td.filename .name').trigger(e);

			expect($tr.find('input:checkbox').prop('checked')).toEqual(true);
			expect($tr2.find('input:checkbox').prop('checked')).toEqual(true);
			expect(fileList.findFileEl('Two.jpg').find('input:checkbox').prop('checked')).toEqual(true);
			var selection = _.pluck(fileList.getSelectedFiles(), 'name');
			expect(selection.length).toEqual(3);
			expect(selection).toContain('One.txt');
			expect(selection).toContain('Two.jpg');
			expect(selection).toContain('Three.pdf');
		});
		it('Selects a range when clicking on one file then Shift clicking on another one that is above the first one', function() {
			var $tr = fileList.findFileEl('One.txt');
			var $tr2 = fileList.findFileEl('Three.pdf');
			var e;
			$tr2.find('td.filename input:checkbox').click();
			e = new $.Event('click');
			e.shiftKey = true;
			$tr.find('td.filename .name').trigger(e);

			expect($tr.find('input:checkbox').prop('checked')).toEqual(true);
			expect($tr2.find('input:checkbox').prop('checked')).toEqual(true);
			expect(fileList.findFileEl('Two.jpg').find('input:checkbox').prop('checked')).toEqual(true);
			var selection = _.pluck(fileList.getSelectedFiles(), 'name');
			expect(selection.length).toEqual(3);
			expect(selection).toContain('One.txt');
			expect(selection).toContain('Two.jpg');
			expect(selection).toContain('Three.pdf');
		});
		it('Selecting all files will automatically check "select all" checkbox', function() {
			expect($('.select-all').prop('checked')).toEqual(false);
			$('#fileList tr td.filename input:checkbox').click();
			expect($('.select-all').prop('checked')).toEqual(true);
		});
		it('Selecting all files on the first visible page will not automatically check "select all" checkbox', function() {
			fileList.setFiles(generateFiles(0, 41));
			expect($('.select-all').prop('checked')).toEqual(false);
			$('#fileList tr td.filename input:checkbox').click();
			expect($('.select-all').prop('checked')).toEqual(false);
		});
		it('Clicking "select all" will select/deselect all files', function() {
			fileList.setFiles(generateFiles(0, 41));
			$('.select-all').click();
			expect($('.select-all').prop('checked')).toEqual(true);
			$('#fileList tr input:checkbox').each(function() {
				expect($(this).prop('checked')).toEqual(true);
			});
			expect(_.pluck(fileList.getSelectedFiles(), 'name').length).toEqual(42);

			$('.select-all').click();
			expect($('.select-all').prop('checked')).toEqual(false);

			$('#fileList tr input:checkbox').each(function() {
				expect($(this).prop('checked')).toEqual(false);
			});
			expect(_.pluck(fileList.getSelectedFiles(), 'name').length).toEqual(0);
		});
		it('Clicking "select all" then deselecting a file will uncheck "select all"', function() {
			$('.select-all').click();
			expect($('.select-all').prop('checked')).toEqual(true);

			var $tr = fileList.findFileEl('One.txt');
			$tr.find('input:checkbox').click();

			expect($('.select-all').prop('checked')).toEqual(false);
			expect(_.pluck(fileList.getSelectedFiles(), 'name').length).toEqual(3);
		});
		it('Updates the selection summary when doing a few manipulations with "Select all"', function() {
			$('.select-all').click();
			expect($('.select-all').prop('checked')).toEqual(true);

			var $tr = fileList.findFileEl('One.txt');
			// unselect one
			$tr.find('input:checkbox').click();

			expect($('.select-all').prop('checked')).toEqual(false);
			expect(_.pluck(fileList.getSelectedFiles(), 'name').length).toEqual(3);

			// select all
			$('.select-all').click();
			expect($('.select-all').prop('checked')).toEqual(true);
			expect(_.pluck(fileList.getSelectedFiles(), 'name').length).toEqual(4);

			// unselect one
			$tr.find('input:checkbox').click();
			expect($('.select-all').prop('checked')).toEqual(false);
			expect(_.pluck(fileList.getSelectedFiles(), 'name').length).toEqual(3);

			// re-select it
			$tr.find('input:checkbox').click();
			expect($('.select-all').prop('checked')).toEqual(true);
			expect(_.pluck(fileList.getSelectedFiles(), 'name').length).toEqual(4);
		});
		it('Auto-selects files on next page when "select all" is checked', function() {
			fileList.setFiles(generateFiles(0, 41));
			$('.select-all').click();

			expect(fileList.$fileList.find('tr input:checkbox:checked').length).toEqual(20);
			fileList._nextPage(true);
			expect(fileList.$fileList.find('tr input:checkbox:checked').length).toEqual(40);
			fileList._nextPage(true);
			expect(fileList.$fileList.find('tr input:checkbox:checked').length).toEqual(42);
			expect(_.pluck(fileList.getSelectedFiles(), 'name').length).toEqual(42);
		});
		it('Selecting files updates selection summary', function() {
			var $summary = $('#headerName a.name>span:first');
			expect($summary.text()).toEqual('Name');
			fileList.findFileEl('One.txt').find('input:checkbox').click();
			fileList.findFileEl('Three.pdf').find('input:checkbox').click();
			fileList.findFileEl('somedir').find('input:checkbox').click();
			expect($summary.text()).toEqual('1 folder & 2 files');
		});
		it('Unselecting files hides selection summary', function() {
			var $summary = $('#headerName a.name>span:first');
			fileList.findFileEl('One.txt').find('input:checkbox').click().click();
			expect($summary.text()).toEqual('Name');
		});
		it('Select/deselect files shows/hides file actions', function() {
			var $actions = $('#headerName .selectedActions');
			var $checkbox = fileList.findFileEl('One.txt').find('input:checkbox');
			expect($actions.hasClass('hidden')).toEqual(true);
			$checkbox.click();
			expect($actions.hasClass('hidden')).toEqual(false);
			$checkbox.click();
			expect($actions.hasClass('hidden')).toEqual(true);
		});
		it('Selection is cleared when switching dirs', function() {
			$('.select-all').click();
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
				]
			);
			fileList.changeDirectory('/');
			fakeServer.respond();
			expect($('.select-all').prop('checked')).toEqual(false);
			expect(_.pluck(fileList.getSelectedFiles(), 'name')).toEqual([]);
		});
		it('getSelectedFiles returns the selected files even when they are on the next page', function() {
			var selectedFiles;
			fileList.setFiles(generateFiles(0, 41));
			$('.select-all').click();
			// unselect one to not have the "allFiles" case
			fileList.$fileList.find('tr input:checkbox:first').click();

			// only 20 files visible, must still return all the selected ones
			selectedFiles = _.pluck(fileList.getSelectedFiles(), 'name');

			expect(selectedFiles.length).toEqual(41);
		});
		describe('clearing the selection', function() {
			it('clears selected files selected individually calling setFiles()', function() {
				var selectedFiles;

				fileList.setFiles(generateFiles(0, 41));
				fileList.$fileList.find('tr:eq(5) input:checkbox:first').click();
				fileList.$fileList.find('tr:eq(7) input:checkbox:first').click();

				selectedFiles = _.pluck(fileList.getSelectedFiles(), 'name');
				expect(selectedFiles.length).toEqual(2);

				fileList.setFiles(generateFiles(0, 2));

				selectedFiles = _.pluck(fileList.getSelectedFiles(), 'name');
				expect(selectedFiles.length).toEqual(0);
			});
			it('clears selected files selected with select all when calling setFiles()', function() {
				var selectedFiles;

				fileList.setFiles(generateFiles(0, 41));
				$('.select-all').click();

				selectedFiles = _.pluck(fileList.getSelectedFiles(), 'name');
				expect(selectedFiles.length).toEqual(42);

				fileList.setFiles(generateFiles(0, 2));

				selectedFiles = _.pluck(fileList.getSelectedFiles(), 'name');
				expect(selectedFiles.length).toEqual(0);
			});
		});
		describe('Selection overlay', function() {
			it('show delete action according to directory permissions', function() {
				fileList.setFiles(testFiles);
				$('#permissions').val(OC.PERMISSION_READ | OC.PERMISSION_DELETE);
				$('.select-all').click();
				expect(fileList.$el.find('.delete-selected').hasClass('hidden')).toEqual(false);
				$('.select-all').click();
				$('#permissions').val(OC.PERMISSION_READ);
				$('.select-all').click();
				expect(fileList.$el.find('.delete-selected').hasClass('hidden')).toEqual(true);
			});
			it('show doesnt show the delete action if one or more files are not deletable', function () {
				fileList.setFiles(testFiles);
				$('#permissions').val(OC.PERMISSION_READ | OC.PERMISSION_DELETE);
				$('.select-all').click();
				expect(fileList.$el.find('.delete-selected').hasClass('hidden')).toEqual(false);
				testFiles[0].permissions = OC.PERMISSION_READ;
				$('.select-all').click();
				fileList.setFiles(testFiles);
				$('.select-all').click();
				expect(fileList.$el.find('.delete-selected').hasClass('hidden')).toEqual(true);
			});
		});
		describe('Actions', function() {
			beforeEach(function() {
				fileList.findFileEl('One.txt').find('input:checkbox').click();
				fileList.findFileEl('Three.pdf').find('input:checkbox').click();
				fileList.findFileEl('somedir').find('input:checkbox').click();
			});
			it('getSelectedFiles returns the selected file data', function() {
				var files = fileList.getSelectedFiles();
				expect(files.length).toEqual(3);
				expect(files[0]).toEqual({
					id: 1,
					name: 'One.txt',
					mimetype: 'text/plain',
					type: 'file',
					size: 12,
					etag: 'abc',
					permissions: OC.PERMISSION_ALL
				});
				expect(files[1]).toEqual({
					id: 3,
					type: 'file',
					name: 'Three.pdf',
					mimetype: 'application/pdf',
					size: 58009,
					etag: '123',
					permissions: OC.PERMISSION_ALL
				});
				expect(files[2]).toEqual({
					id: 4,
					type: 'dir',
					name: 'somedir',
					mimetype: 'httpd/unix-directory',
					size: 250,
					etag: '456',
					permissions: OC.PERMISSION_ALL
				});
			});
			it('Removing a file removes it from the selection', function() {
				fileList.remove('Three.pdf');
				var files = fileList.getSelectedFiles();
				expect(files.length).toEqual(2);
				expect(files[0]).toEqual({
					id: 1,
					name: 'One.txt',
					mimetype: 'text/plain',
					type: 'file',
					size: 12,
					etag: 'abc',
					permissions: OC.PERMISSION_ALL
				});
				expect(files[1]).toEqual({
					id: 4,
					type: 'dir',
					name: 'somedir',
					mimetype: 'httpd/unix-directory',
					size: 250,
					etag: '456',
					permissions: OC.PERMISSION_ALL
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
					$('.select-all').click();
					var redirectStub = sinon.stub(OC, 'redirect');
					$('.selectedActions .download').click();
					expect(redirectStub.calledOnce).toEqual(true);
					expect(redirectStub.getCall(0).args[0]).toEqual(OC.webroot + '/index.php/apps/files/ajax/download.php?dir=%2F&files=');
					redirectStub.restore();
				});
				it('Downloads parent folder when all selected in subfolder', function() {
					$('.select-all').click();
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
					expect(fileList.findFileEl('One.txt').length).toEqual(0);
					expect(fileList.findFileEl('Three.pdf').length).toEqual(0);
					expect(fileList.findFileEl('somedir').length).toEqual(0);
					expect(fileList.findFileEl('Two.jpg').length).toEqual(1);
				});
				it('Deletes all files when all selected when "Delete" clicked', function() {
					var request;
					$('.select-all').click();
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
					expect(fileList.isEmpty).toEqual(true);
				});
			});
		});
		it('resets the file selection on reload', function() {
			fileList.$el.find('.select-all').click();
			fileList.reload();
			expect(fileList.$el.find('.select-all').prop('checked')).toEqual(false);
			expect(fileList.getSelectedFiles()).toEqual([]);
		});
		describe('Disabled selection', function() {
			beforeEach(function() {
				fileList._allowSelection = false;
				fileList.setFiles(testFiles);
			});
			it('Does not render checkboxes', function() {
				expect(fileList.$fileList.find('.selectCheckBox').length).toEqual(0);
			});
			it('Does not select a file with Ctrl or Shift if selection is not allowed', function() {
				var $tr = fileList.findFileEl('One.txt');
				var $tr2 = fileList.findFileEl('Three.pdf');
				var e;
				e = new $.Event('click');
				e.ctrlKey = true;
				$tr.find('td.filename .name').trigger(e);

				// click on second entry, does not clear the selection
				e = new $.Event('click');
				e.ctrlKey = true;
				$tr2.find('td.filename .name').trigger(e);

				expect(fileList.getSelectedFiles().length).toEqual(0);

				// deselect now
				e = new $.Event('click');
				e.shiftKey = true;
				$tr2.find('td.filename .name').trigger(e);
				expect(fileList.getSelectedFiles().length).toEqual(0);
			});
		})
	});
	describe('File actions', function() {
		it('Clicking on a file name will trigger default action', function() {
			var actionStub = sinon.stub();
			fileList.setFiles(testFiles);
			fileList.fileActions.register(
				'text/plain',
				'Test',
				OC.PERMISSION_ALL,
				function() {
					// Specify icon for hitory button
					return OC.imagePath('core','actions/history');
				},
				actionStub
			);
			fileList.fileActions.setDefault('text/plain', 'Test');
			var $tr = fileList.findFileEl('One.txt');
			$tr.find('td.filename>a.name').click();
			expect(actionStub.calledOnce).toEqual(true);
			expect(actionStub.getCall(0).args[0]).toEqual('One.txt');
			var context = actionStub.getCall(0).args[1];
			expect(context.$file.is($tr)).toEqual(true);
			expect(context.fileList).toBeDefined();
			expect(context.fileActions).toBeDefined();
			expect(context.dir).toEqual('/subdir');
		});
		it('redisplays actions when new actions have been registered', function() {
			var actionStub = sinon.stub();
			var readyHandler = sinon.stub();
			var clock = sinon.useFakeTimers();
			var debounceStub = sinon.stub(_, 'debounce', function(callback) {
				return function() {
					// defer instead of debounce, to make it work with clock
					_.defer(callback);
				};
			});

			// need to reinit the list to make the debounce call
			fileList.destroy();
			fileList = new OCA.Files.FileList($('#app-content-files'));

			fileList.setFiles(testFiles);

			fileList.$fileList.on('fileActionsReady', readyHandler);

			fileList.fileActions.register(
				'text/plain',
				'Test',
				OC.PERMISSION_ALL,
				function() {
					// Specify icon for hitory button
					return OC.imagePath('core','actions/history');
				},
				actionStub
			);
			var $tr = fileList.findFileEl('One.txt');
			expect($tr.find('.action-test').length).toEqual(0);
			expect(readyHandler.notCalled).toEqual(true);

			// update is delayed
			clock.tick(100);
			expect($tr.find('.action-test').length).toEqual(1);
			expect(readyHandler.calledOnce).toEqual(true);

			clock.restore();
			debounceStub.restore();
		});
	});
	describe('Sorting files', function() {
		it('Sorts by name by default', function() {
			fileList.reload();
			expect(fakeServer.requests.length).toEqual(1);
			var url = fakeServer.requests[0].url;
			var query = OC.parseQueryString(url.substr(url.indexOf('?') + 1));
			expect(query.sort).toEqual('name');
			expect(query.sortdirection).toEqual('asc');
		});
		it('Reloads file list with a different sort when clicking on column header of unsorted column', function() {
			fileList.$el.find('.column-size .columntitle').click();
			expect(fakeServer.requests.length).toEqual(1);
			var url = fakeServer.requests[0].url;
			var query = OC.parseQueryString(url.substr(url.indexOf('?') + 1));
			expect(query.sort).toEqual('size');
			expect(query.sortdirection).toEqual('desc');
		});
		it('Toggles sort direction when clicking on already sorted column', function() {
			fileList.$el.find('.column-name .columntitle').click();
			expect(fakeServer.requests.length).toEqual(1);
			var url = fakeServer.requests[0].url;
			var query = OC.parseQueryString(url.substr(url.indexOf('?') + 1));
			expect(query.sort).toEqual('name');
			expect(query.sortdirection).toEqual('desc');
		});
		it('Toggles the sort indicator when clicking on a column header', function() {
			var ASC_CLASS = fileList.SORT_INDICATOR_ASC_CLASS;
			var DESC_CLASS = fileList.SORT_INDICATOR_DESC_CLASS;
			fileList.$el.find('.column-size .columntitle').click();
			// moves triangle to size column, check indicator on name is hidden
			expect(
				fileList.$el.find('.column-name .sort-indicator').hasClass('hidden')
			).toEqual(true);
			// check indicator on size is visible and defaults to descending
			expect(
				fileList.$el.find('.column-size .sort-indicator').hasClass('hidden')
			).toEqual(false);
			expect(
				fileList.$el.find('.column-size .sort-indicator').hasClass(DESC_CLASS)
			).toEqual(true);

			// click again on size column, reverses direction
			fileList.$el.find('.column-size .columntitle').click();
			expect(
				fileList.$el.find('.column-size .sort-indicator').hasClass('hidden')
			).toEqual(false);
			expect(
				fileList.$el.find('.column-size .sort-indicator').hasClass(ASC_CLASS)
			).toEqual(true);

			// click again on size column, reverses direction
			fileList.$el.find('.column-size .columntitle').click();
			expect(
				fileList.$el.find('.column-size .sort-indicator').hasClass('hidden')
			).toEqual(false);
			expect(
				fileList.$el.find('.column-size .sort-indicator').hasClass(DESC_CLASS)
			).toEqual(true);

			// click on mtime column, moves indicator there
			fileList.$el.find('.column-mtime .columntitle').click();
			expect(
				fileList.$el.find('.column-size .sort-indicator').hasClass('hidden')
			).toEqual(true);
			expect(
				fileList.$el.find('.column-mtime .sort-indicator').hasClass('hidden')
			).toEqual(false);
			expect(
				fileList.$el.find('.column-mtime .sort-indicator').hasClass(DESC_CLASS)
			).toEqual(true);
		});
		it('Uses correct sort comparator when inserting files', function() {
			testFiles.sort(OCA.Files.FileList.Comparators.size);
			testFiles.reverse();	//default is descending
			// this will make it reload the testFiles with the correct sorting
			fileList.$el.find('.column-size .columntitle').click();
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
			fileList.add(newFileData);
			expect(fileList.findFileEl('Three.pdf').index()).toEqual(0);
			expect(fileList.findFileEl('new file.txt').index()).toEqual(1);
			expect(fileList.findFileEl('Two.jpg').index()).toEqual(2);
			expect(fileList.findFileEl('somedir').index()).toEqual(3);
			expect(fileList.findFileEl('One.txt').index()).toEqual(4);
			expect(fileList.files.length).toEqual(5);
			expect(fileList.$fileList.find('tr').length).toEqual(5);
		});
		it('Uses correct reversed sort comparator when inserting files', function() {
			testFiles.sort(OCA.Files.FileList.Comparators.size);
			// this will make it reload the testFiles with the correct sorting
			fileList.$el.find('.column-size .columntitle').click();
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
			fileList.$el.find('.column-size .columntitle').click();
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
			fileList.add(newFileData);
			expect(fileList.findFileEl('One.txt').index()).toEqual(0);
			expect(fileList.findFileEl('somedir').index()).toEqual(1);
			expect(fileList.findFileEl('Two.jpg').index()).toEqual(2);
			expect(fileList.findFileEl('new file.txt').index()).toEqual(3);
			expect(fileList.findFileEl('Three.pdf').index()).toEqual(4);
			expect(fileList.files.length).toEqual(5);
			expect(fileList.$fileList.find('tr').length).toEqual(5);
		});
	});
	/**
	 * Test upload mostly by testing the code inside the event handlers
	 * that were registered on the magic upload object
	 */
	describe('file upload', function() {
		var $uploader;

		beforeEach(function() {
			// note: this isn't the real blueimp file uploader from jquery.fileupload
			// but it makes it possible to simulate the event triggering to
			// test the response of the handlers
			$uploader = $('#file_upload_start');
			fileList.setFiles(testFiles);
		});

		afterEach(function() {
			$uploader = null;
		});

		describe('dropping external files', function() {
			var uploadData;

			/**
			 * Simulate drop event on the given target
			 *
			 * @param $target target element to drop on
			 * @return event object including the result
			 */
			function dropOn($target, data) {
				var eventData = {
					originalEvent: {
						target: $target
					}
				};
				var ev = new $.Event('fileuploaddrop', eventData);
				// using triggerHandler instead of trigger so we can pass
				// extra data
				$uploader.triggerHandler(ev, data || {});
				return ev;
			}

			beforeEach(function() {
				// simulate data structure from jquery.upload
				uploadData = {
					files: [{
						relativePath: 'fileToUpload.txt'
					}]
				};
			});
			afterEach(function() {
				uploadData = null;
			});
			it('drop on a tr or crumb outside file list does not trigger upload', function() {
				var $anotherTable = $('<table><tbody><tr><td>outside<div class="crumb">crumb</div></td></tr></table>');
				var ev;
				$('#testArea').append($anotherTable);
				ev = dropOn($anotherTable.find('tr'), uploadData);
				expect(ev.result).toEqual(false);

				ev = dropOn($anotherTable.find('.crumb'));
				expect(ev.result).toEqual(false);
			});
			it('drop on an element outside file list container does not trigger upload', function() {
				var $anotherEl = $('<div>outside</div>');
				var ev;
				$('#testArea').append($anotherEl);
				ev = dropOn($anotherEl);

				expect(ev.result).toEqual(false);
			});
			it('drop on an element inside the table triggers upload', function() {
				var ev;
				ev = dropOn(fileList.$fileList.find('th:first'), uploadData);

				expect(ev.result).not.toEqual(false);
			});
			it('drop on an element on the table container triggers upload', function() {
				var ev;
				ev = dropOn($('#app-content-files'), uploadData);

				expect(ev.result).not.toEqual(false);
			});
			it('drop on an element inside the table does not trigger upload if no upload permission', function() {
				$('#permissions').val(0);
				var ev;
				ev = dropOn(fileList.$fileList.find('th:first'));

				expect(ev.result).toEqual(false);
				expect(notificationStub.calledOnce).toEqual(true);
			});
			it('drop on an folder does not trigger upload if no upload permission on that folder', function() {
				var $tr = fileList.findFileEl('somedir');
				var ev;
				$tr.data('permissions', OC.PERMISSION_READ);
				ev = dropOn($tr);

				expect(ev.result).toEqual(false);
				expect(notificationStub.calledOnce).toEqual(true);
			});
			it('drop on a file row inside the table triggers upload to current folder', function() {
				var ev;
				ev = dropOn(fileList.findFileEl('One.txt').find('td:first'), uploadData);

				expect(ev.result).not.toEqual(false);
			});
			it('drop on a folder row inside the table triggers upload to target folder', function() {
				var ev, formData;
				ev = dropOn(fileList.findFileEl('somedir').find('td:eq(2)'), uploadData);

				expect(ev.result).not.toEqual(false);
				expect(uploadData.targetDir).toEqual('/subdir/somedir');
			});
			it('drop on a breadcrumb inside the table triggers upload to target folder', function() {
				var ev, formData;
				fileList.changeDirectory('a/b/c/d');
				ev = dropOn(fileList.$el.find('.crumb:eq(2)'), uploadData);

				expect(ev.result).not.toEqual(false);
				expect(uploadData.targetDir).toEqual('/a/b');
			});
		});
	});
	describe('Handeling errors', function () {
		beforeEach(function () {
			redirectStub = sinon.stub(OC, 'redirect');

			fileList = new OCA.Files.FileList($('#app-content-files'));
		});
		afterEach(function () {
			fileList = undefined;

			redirectStub.restore();
		});
		it('reloads the page on authentication errors', function () {
			fileList.reload();
			fakeServer.requests[0].respond(
				200,
				{ 'Content-Type': 'application/json' },
				JSON.stringify({
					status: 'error',
					data: {
						'error': 'authentication_error'
					}
				})
			);
			expect(redirectStub.calledWith(OC.generateUrl('apps/files'))).toEqual(true);
		});
	});
});
