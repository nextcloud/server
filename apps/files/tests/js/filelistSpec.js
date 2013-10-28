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
			'<table id="filestable">' +
			'<thead><tr><th class="hidden">Name</th></tr></thead>' +
		   	'<tbody id="fileList"></tbody>' +
			'</table>' +
			'<div id="emptycontent">Empty content message</div>'
		);

		testFiles = [{
			id: 1,
			type: 'file',
			name: 'One.txt',
			mimetype: 'text/plain',
			size: 12
		}, {
			id: 2,
			type: 'file',
			name: 'Two.jpg',
			mimetype: 'image/jpeg',
			size: 12049
		}, {
			id: 3,
			type: 'file',
			name: 'Three.pdf',
			mimetype: 'application/pdf',
			size: 58009
		}, {
			id: 4,
			type: 'dir',
			name: 'somedir',
			mimetype: 'httpd/unix-directory',
			size: 250
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
		it('adds new file to the end of the list before the summary', function() {
			var fileData = {
				type: 'file',
				name: 'P comes after O.txt'
			};
			FileList.setFiles(testFiles);
			$tr = FileList.add(fileData);
			expect($tr.index()).toEqual(4);
			expect($tr.next().hasClass('summary')).toEqual(true);
		});
		it('adds new file at correct position in insert mode', function() {
			var fileData = {
				type: 'file',
				name: 'P comes after O.txt'
			};
			FileList.setFiles(testFiles);
			$tr = FileList.add(fileData, {insert: true});
			// after "One.txt"
			expect($tr.index()).toEqual(1);
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
			$summary = $('#fileList .summary');
			expect($summary.length).toEqual(1);
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
			expect($('#fileList tr:not(.summary)').length).toEqual(3);
			expect(FileList.findFileEl('One.txt').length).toEqual(0);

			$summary = $('#fileList .summary');
			expect($summary.length).toEqual(1);
			expect($summary.find('.info').text()).toEqual('1 folder and 2 files');
			expect($summary.find('.dirinfo').hasClass('hidden')).toEqual(false);
			expect($summary.find('.fileinfo').hasClass('hidden')).toEqual(false);
			expect($summary.find('.filesize').text()).toEqual('69 kB');
			expect(FileList.isEmpty).toEqual(false);
		});
		it('Shows empty content when removing last file', function() {
			FileList.setFiles([testFiles[0]]);
			FileList.remove('One.txt');
			expect($('#fileList tr:not(.summary)').length).toEqual(0);
			expect(FileList.findFileEl('One.txt').length).toEqual(0);

			$summary = $('#fileList .summary');
			expect($summary.length).toEqual(0);
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
			expect(FileList.$fileList.find('tr:not(.summary)').length).toEqual(2);

			$summary = $('#fileList .summary');
			expect($summary.length).toEqual(1);
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

			expect(FileList.$fileList.find('tr:not(.summary)').length).toEqual(0);

			$summary = $('#fileList .summary');
			expect($summary.length).toEqual(0);
			expect(FileList.isEmpty).toEqual(true);
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
			expect(FileList.$fileList.find('tr:not(.summary)').length).toEqual(4);

			expect(notificationStub.calledOnce).toEqual(true);
		});
	});
	describe('Renaming files', function() {
		function doRename() {
			var $input, request;

			FileList.setFiles(testFiles);

			// trigger rename prompt
			FileList.rename('One.txt');
			$input = FileList.$fileList.find('input.filename');
			$input.val('One_renamed.txt').blur();

			expect(fakeServer.requests.length).toEqual(1);
			var request = fakeServer.requests[0];
			expect(request.url.substr(0, request.url.indexOf('?'))).toEqual(OC.webroot + '/index.php/apps/files/ajax/rename.php');
			expect(OC.parseQueryString(request.url)).toEqual({'dir': '/subdir', newname: 'One_renamed.txt', file: 'One.txt'});

			// element is renamed before the request finishes
			expect(FileList.findFileEl('One.txt').length).toEqual(0);
			expect(FileList.findFileEl('One_renamed.txt').length).toEqual(1);
			// input is gone
			expect(FileList.$fileList.find('input.filename').length).toEqual(0);
		}
		it('Keeps renamed file entry if rename ajax call suceeded', function() {
			doRename();

			fakeServer.requests[0].respond(200, {'Content-Type': 'application/json'}, JSON.stringify({
				status: 'success',
				data: {
					name: 'One_renamed.txt'
				}
			}));

			// element stays renamed
			expect(FileList.findFileEl('One.txt').length).toEqual(0);
			expect(FileList.findFileEl('One_renamed.txt').length).toEqual(1);

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
			expect(FileList.findFileEl('One_renamed.txt').length).toEqual(0);

			expect(alertStub.calledOnce).toEqual(true);
		});
		it('Correctly updates file link after rename', function() {
			var $tr;
			doRename();

			fakeServer.requests[0].respond(200, {'Content-Type': 'application/json'}, JSON.stringify({
				status: 'success',
				data: {
					name: 'One_renamed.txt'
				}
			}));

			$tr = FileList.findFileEl('One_renamed.txt');
			expect($tr.find('a.name').attr('href')).toEqual(OC.webroot + '/index.php/apps/files/ajax/download.php?dir=%2Fsubdir&files=One_renamed.txt');
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
					name: 'One_renamed.txt'
				}
			}));

			$tr = FileList.findFileEl('One_renamed.txt');
			expect($tr.find('a.name').attr('href')).toEqual(OC.webroot + '/index.php/apps/files/ajax/download.php?dir=%2Fsubdir&files=One.txt');
		});
	});
	describe('List rendering', function() {
		it('renders a list of files using add()', function() {
			var addSpy = sinon.spy(FileList, 'add');
			FileList.setFiles(testFiles);
			expect(addSpy.callCount).toEqual(4);
			expect($('#fileList tr:not(.summary)').length).toEqual(4);
			addSpy.restore();
		});
		it('updates summary using the file sizes', function() {
			var $summary;
			FileList.setFiles(testFiles);
			$summary = $('#fileList .summary');
			expect($summary.length).toEqual(1);
			expect($summary.find('.info').text()).toEqual('1 folder and 3 files');
			expect($summary.find('.filesize').text()).toEqual('69 kB');
		});
		it('shows headers, summary and hide empty content message after setting files', function(){
			FileList.setFiles(testFiles);
			expect($('#filestable thead th').hasClass('hidden')).toEqual(false);
			expect($('#emptycontent').hasClass('hidden')).toEqual(true);
			expect(FileList.$fileList.find('.summary').length).toEqual(1);
		});
		it('hides headers, summary and show empty content message after setting empty file list', function(){
			FileList.setFiles([]);
			expect($('#filestable thead th').hasClass('hidden')).toEqual(true);
			expect($('#emptycontent').hasClass('hidden')).toEqual(false);
			expect(FileList.$fileList.find('.summary').length).toEqual(0);
		});
		it('hides headers, empty content message, and summary when list is empty and user has no creation permission', function(){
			$('#permissions').val(0);
			FileList.setFiles([]);
			expect($('#filestable thead th').hasClass('hidden')).toEqual(true);
			expect($('#emptycontent').hasClass('hidden')).toEqual(true);
			expect(FileList.$fileList.find('.summary').length).toEqual(0);
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
			expect(OC.parseQueryString(query)).toEqual({'dir': '/subdir'});
			fakeServer.respond();
			expect($('#fileList tr:not(.summary)').length).toEqual(4);
			expect(FileList.findFileEl('One.txt').length).toEqual(1);
		});
		it('switches dir and fetches file list when calling changeDirectory()', function() {
			FileList.changeDirectory('/anothersubdir');
			expect(FileList.getCurrentDirectory()).toEqual('/anothersubdir');
			expect(fakeServer.requests.length).toEqual(1);
			var url = fakeServer.requests[0].url;
			var query = url.substr(url.indexOf('?') + 1);
			expect(OC.parseQueryString(query)).toEqual({'dir': '/anothersubdir'});
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
			// FIXME: why are their attributes different than the
			// regular file trs ?
			ui.helper.find.returns([
				$('<tr data-filename="One.txt" data-dir="' + testDir + '"></tr>'),
				$('<tr data-filename="Two.jpg" data-dir="' + testDir + '"></tr>')
			]);
			// simulate drop event
			FileList._onDropOnBreadCrumb.call($crumb, new $.Event('drop'), ui);

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
			// FIXME: why are their attributes different than the
			// regular file trs ?
			ui.helper.find.returns([
				$('<tr data-filename="One.txt" data-dir="' + testDir + '"></tr>'),
				$('<tr data-filename="Two.jpg" data-dir="' + testDir + '"></tr>')
			]);
			// simulate drop event
			FileList._onDropOnBreadCrumb.call($crumb, new $.Event('drop'), ui);

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
});
