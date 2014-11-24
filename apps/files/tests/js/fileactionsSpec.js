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
		fileList.destroy();
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
			path: '/somepath/dir',
			mimetype: 'text/plain',
			size: '1234',
			etag: 'a01234c',
			mtime: '123456'
		};
		var $tr = fileList.add(fileData);
		FileActions.display($tr.find('td.filename'), true, fileList);

		$tr.find('.action.delete').click();

		expect(deleteStub.calledOnce).toEqual(true);
		expect(deleteStub.getCall(0).args[0]).toEqual('testName.txt');
		expect(deleteStub.getCall(0).args[1]).toEqual('/somepath/dir');
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
	describe('custom rendering', function() {
		var $tr;
		beforeEach(function() {
			var fileData = {
				id: 18,
				type: 'file',
				name: 'testName.txt',
				mimetype: 'text/plain',
				size: '1234',
				etag: 'a01234c',
				mtime: '123456'
			};
			$tr = fileList.add(fileData);
		});
		it('regular function', function() {
			var actionStub = sinon.stub();
			FileActions.registerAction({
				name: 'Test',
				displayName: '',
				mime: 'all',
				permissions: OC.PERMISSION_READ,
				render: function(actionSpec, isDefault, context) {
					expect(actionSpec.name).toEqual('Test');
					expect(actionSpec.displayName).toEqual('');
					expect(actionSpec.permissions).toEqual(OC.PERMISSION_READ);
					expect(actionSpec.mime).toEqual('all');
					expect(isDefault).toEqual(false);

					expect(context.fileList).toEqual(fileList);
					expect(context.$file[0]).toEqual($tr[0]);

					var $customEl = $('<a href="#"><span>blabli</span><span>blabla</span></a>');
					$tr.find('td:first').append($customEl);
					return $customEl;
				},
				actionHandler: actionStub
			});
			FileActions.display($tr.find('td.filename'), true, fileList);

			var $actionEl = $tr.find('td:first .action-test');
			expect($actionEl.length).toEqual(1);
			expect($actionEl.hasClass('action')).toEqual(true);

			$actionEl.click();
			expect(actionStub.calledOnce).toEqual(true);
			expect(actionStub.getCall(0).args[0]).toEqual('testName.txt');
		});
	});
	describe('merging', function() {
		var $tr;
		beforeEach(function() {
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
			$tr = fileList.add(fileData);
		});
		afterEach(function() {
			$tr = null;
		});
		it('copies all actions to target file actions', function() {
			var actions1 = new OCA.Files.FileActions();
			var actions2 = new OCA.Files.FileActions();
			var actionStub1 = sinon.stub();
			var actionStub2 = sinon.stub();
			actions1.register(
					'all',
					'Test',
					OC.PERMISSION_READ,
					OC.imagePath('core', 'actions/test'),
					actionStub1
			);
			actions2.register(
					'all',
					'Test2',
					OC.PERMISSION_READ,
					OC.imagePath('core', 'actions/test'),
					actionStub2
			);
			actions2.merge(actions1);

			actions2.display($tr.find('td.filename'), true, fileList);

			expect($tr.find('.action-test').length).toEqual(1);
			expect($tr.find('.action-test2').length).toEqual(1);

			$tr.find('.action-test').click();
			expect(actionStub1.calledOnce).toEqual(true);
			expect(actionStub2.notCalled).toEqual(true);

			actionStub1.reset();

			$tr.find('.action-test2').click();
			expect(actionStub1.notCalled).toEqual(true);
			expect(actionStub2.calledOnce).toEqual(true);
		});
		it('overrides existing actions on merge', function() {
			var actions1 = new OCA.Files.FileActions();
			var actions2 = new OCA.Files.FileActions();
			var actionStub1 = sinon.stub();
			var actionStub2 = sinon.stub();
			actions1.register(
					'all',
					'Test',
					OC.PERMISSION_READ,
					OC.imagePath('core', 'actions/test'),
					actionStub1
			);
			actions2.register(
					'all',
					'Test', // override
					OC.PERMISSION_READ,
					OC.imagePath('core', 'actions/test'),
					actionStub2
			);
			actions1.merge(actions2);

			actions1.display($tr.find('td.filename'), true, fileList);

			expect($tr.find('.action-test').length).toEqual(1);

			$tr.find('.action-test').click();
			expect(actionStub1.notCalled).toEqual(true);
			expect(actionStub2.calledOnce).toEqual(true);
		});
		it('overrides existing action when calling register after merge', function() {
			var actions1 = new OCA.Files.FileActions();
			var actions2 = new OCA.Files.FileActions();
			var actionStub1 = sinon.stub();
			var actionStub2 = sinon.stub();
			actions1.register(
					'all',
					'Test',
					OC.PERMISSION_READ,
					OC.imagePath('core', 'actions/test'),
					actionStub1
			);

			actions1.merge(actions2);

			// late override
			actions1.register(
					'all',
					'Test', // override
					OC.PERMISSION_READ,
					OC.imagePath('core', 'actions/test'),
					actionStub2
			);

			actions1.display($tr.find('td.filename'), true, fileList);

			expect($tr.find('.action-test').length).toEqual(1);

			$tr.find('.action-test').click();
			expect(actionStub1.notCalled).toEqual(true);
			expect(actionStub2.calledOnce).toEqual(true);
		});
		it('leaves original file actions untouched (clean copy)', function() {
			var actions1 = new OCA.Files.FileActions();
			var actions2 = new OCA.Files.FileActions();
			var actionStub1 = sinon.stub();
			var actionStub2 = sinon.stub();
			actions1.register(
					'all',
					'Test',
					OC.PERMISSION_READ,
					OC.imagePath('core', 'actions/test'),
					actionStub1
			);

			// copy the Test action to actions2
			actions2.merge(actions1);

			// late override
			actions2.register(
					'all',
					'Test', // override
					OC.PERMISSION_READ,
					OC.imagePath('core', 'actions/test'),
					actionStub2
			);

			// check if original actions still call the correct handler
			actions1.display($tr.find('td.filename'), true, fileList);

			expect($tr.find('.action-test').length).toEqual(1);

			$tr.find('.action-test').click();
			expect(actionStub1.calledOnce).toEqual(true);
			expect(actionStub2.notCalled).toEqual(true);
		});
	});
	describe('events', function() {
		var clock;
		beforeEach(function() {
			clock = sinon.useFakeTimers();
		});
		afterEach(function() {
			clock.restore();
		});
		it('notifies update event handlers once after multiple changes', function() {
			var actionStub = sinon.stub();
			var handler = sinon.stub();
			FileActions.on('registerAction', handler);
			FileActions.register(
					'all',
					'Test',
					OC.PERMISSION_READ,
					OC.imagePath('core', 'actions/test'),
					actionStub
			);
			FileActions.register(
					'all',
					'Test2',
					OC.PERMISSION_READ,
					OC.imagePath('core', 'actions/test'),
					actionStub
			);
			expect(handler.calledTwice).toEqual(true);
		});
		it('does not notifies update event handlers after unregistering', function() {
			var actionStub = sinon.stub();
			var handler = sinon.stub();
			FileActions.on('registerAction', handler);
			FileActions.off('registerAction', handler);
			FileActions.register(
					'all',
					'Test',
					OC.PERMISSION_READ,
					OC.imagePath('core', 'actions/test'),
					actionStub
			);
			FileActions.register(
					'all',
					'Test2',
					OC.PERMISSION_READ,
					OC.imagePath('core', 'actions/test'),
					actionStub
			);
			expect(handler.notCalled).toEqual(true);
		});
	});
});
