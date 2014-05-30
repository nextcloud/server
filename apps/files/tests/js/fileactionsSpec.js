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

describe('OCA.Files.FileActions tests', function() {
	var $filesTable, fileList;
	var FileActions; 

	beforeEach(function() {
		// init horrible parameters
		var $body = $('#testArea');
		$body.append('<input type="hidden" id="dir" value="/subdir"></input>');
		$body.append('<input type="hidden" id="permissions" value="31"></input>');
		// dummy files table
		$filesTable = $body.append('<table id="filestable"></table>');
		fileList = new OCA.Files.FileList($('#testArea'));
		FileActions = new OCA.Files.FileActions();
		FileActions.registerDefaultActions();
	});
	afterEach(function() {
		FileActions = null;
		fileList = undefined;
		$('#dir, #permissions, #filestable').remove();
	});
	it('calling clear() clears file actions', function() {
		FileActions.clear();
		expect(FileActions.actions).toEqual({});
		expect(FileActions.defaults).toEqual({});
		expect(FileActions.icons).toEqual({});
		expect(FileActions.currentFile).toBe(null);
	});
	it('calling display() sets file actions', function() {
		var fileData = {
			id: 18,
			type: 'file',
			name: 'testName.txt',
			mimetype: 'text/plain',
			size: '1234',
			etag: 'a01234c',
			mtime: '123456'
		};

		// note: FileActions.display() is called implicitly
		var $tr = fileList.add(fileData);

		// actions defined after call
		expect($tr.find('.action.action-download').length).toEqual(1);
		expect($tr.find('.action.action-download').attr('data-action')).toEqual('Download');
		expect($tr.find('.nametext .action.action-rename').length).toEqual(1);
		expect($tr.find('.nametext .action.action-rename').attr('data-action')).toEqual('Rename');
		expect($tr.find('.action.delete').length).toEqual(1);
	});
	it('calling display() twice correctly replaces file actions', function() {
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

		FileActions.display($tr.find('td.filename'), true, fileList);
		FileActions.display($tr.find('td.filename'), true, fileList);

		// actions defined after cal
		expect($tr.find('.action.action-download').length).toEqual(1);
		expect($tr.find('.nametext .action.action-rename').length).toEqual(1);
		expect($tr.find('.action.delete').length).toEqual(1);
	});
	it('redirects to download URL when clicking download', function() {
		var redirectStub = sinon.stub(OC, 'redirect');
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
		FileActions.display($tr.find('td.filename'), true, fileList);

		$tr.find('.action-download').click();

		expect(redirectStub.calledOnce).toEqual(true);
		expect(redirectStub.getCall(0).args[0]).toEqual(
			OC.webroot +
			'/index.php/apps/files/ajax/download.php' +
			'?dir=%2Fsubdir&files=testName.txt');
		redirectStub.restore();
	});
	it('takes the file\'s path into account when clicking download', function() {
		var redirectStub = sinon.stub(OC, 'redirect');
		var fileData = {
			id: 18,
			type: 'file',
			name: 'testName.txt',
			path: '/anotherpath/there',
			mimetype: 'text/plain',
			size: '1234',
			etag: 'a01234c',
			mtime: '123456'
		};
		var $tr = fileList.add(fileData);
		FileActions.display($tr.find('td.filename'), true, fileList);

		$tr.find('.action-download').click();

		expect(redirectStub.calledOnce).toEqual(true);
		expect(redirectStub.getCall(0).args[0]).toEqual(
			OC.webroot + '/index.php/apps/files/ajax/download.php' +
			'?dir=%2Fanotherpath%2Fthere&files=testName.txt'
		);
		redirectStub.restore();
	});
	it('deletes file when clicking delete', function() {
		var deleteStub = sinon.stub(fileList, 'do_delete');
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
		FileActions.display($tr.find('td.filename'), true, fileList);

		$tr.find('.action.delete').click();

		expect(deleteStub.calledOnce).toEqual(true);
		deleteStub.restore();
	});
	it('passes context to action handler', function() {
		var actionStub = sinon.stub();
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
		FileActions.register(
				'all',
				'Test',
				OC.PERMISSION_READ,
				OC.imagePath('core', 'actions/test'),
				actionStub
		);
		FileActions.display($tr.find('td.filename'), true, fileList);
		$tr.find('.action-test').click();
		expect(actionStub.calledOnce).toEqual(true);
		expect(actionStub.getCall(0).args[0]).toEqual('testName.txt');
		var context = actionStub.getCall(0).args[1];
		expect(context.$file.is($tr)).toEqual(true);
		expect(context.fileList).toBeDefined();
		expect(context.fileActions).toBeDefined();
		expect(context.dir).toEqual('/subdir');

		// when data-path is defined
		actionStub.reset();
		$tr.attr('data-path', '/somepath');
		$tr.find('.action-test').click();
		context = actionStub.getCall(0).args[1];
		expect(context.dir).toEqual('/somepath');
	});
});
