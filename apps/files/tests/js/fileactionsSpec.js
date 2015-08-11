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
	var fileList, fileActions;

	beforeEach(function() {
		// init horrible parameters
		var $body = $('#testArea');
		$body.append('<input type="hidden" id="dir" value="/subdir"></input>');
		$body.append('<input type="hidden" id="permissions" value="31"></input>');
		// dummy files table
		fileActions = new OCA.Files.FileActions();
		fileActions.registerAction({
			name: 'Testdropdown',
			displayName: 'Testdropdowndisplay',
			mime: 'all',
			permissions: OC.PERMISSION_READ,
			icon: function () {
				return OC.imagePath('core', 'actions/download');
			}
		});

		fileActions.registerAction({
			name: 'Testinline',
			displayName: 'Testinlinedisplay',
			type: OCA.Files.FileActions.TYPE_INLINE,
			mime: 'all',
			permissions: OC.PERMISSION_READ
		});

		fileActions.registerAction({
			name: 'Testdefault',
			displayName: 'Testdefaultdisplay',
			mime: 'all',
			permissions: OC.PERMISSION_READ
		});
		fileActions.setDefault('all', 'Testdefault');
		fileList = new OCA.Files.FileList($body, {
			fileActions: fileActions
		});
	});
	afterEach(function() {
		fileActions = null;
		fileList.destroy();
		fileList = undefined;
		$('#dir, #permissions, #filestable').remove();
	});
	it('calling clear() clears file actions', function() {
		fileActions.clear();
		expect(fileActions.actions).toEqual({});
		expect(fileActions.defaults).toEqual({});
		expect(fileActions.icons).toEqual({});
		expect(fileActions.currentFile).toBe(null);
	});
	describe('displaying actions', function() {
		var $tr;

		beforeEach(function() {
			var fileData = {
				id: 18,
				type: 'file',
				name: 'testName.txt',
				mimetype: 'text/plain',
				size: '1234',
				etag: 'a01234c',
				mtime: '123456',
				permissions: OC.PERMISSION_READ | OC.PERMISSION_UPDATE
			};

			// note: FileActions.display() is called implicitly
			$tr = fileList.add(fileData);
		});
		it('renders inline file actions', function() {
			// actions defined after call
			expect($tr.find('.action.action-testinline').length).toEqual(1);
			expect($tr.find('.action.action-testinline').attr('data-action')).toEqual('Testinline');
		});
		it('does not render dropdown actions', function() {
			expect($tr.find('.action.action-testdropdown').length).toEqual(0);
		});
		it('does not render default action', function() {
			expect($tr.find('.action.action-testdefault').length).toEqual(0);
		});
		it('replaces file actions when displayed twice', function() {
			fileActions.display($tr.find('td.filename'), true, fileList);
			fileActions.display($tr.find('td.filename'), true, fileList);

			expect($tr.find('.action.action-testinline').length).toEqual(1);
		});
		it('renders actions menu trigger', function() {
			expect($tr.find('.action.action-menu').length).toEqual(1);
			expect($tr.find('.action.action-menu').attr('data-action')).toEqual('menu');
		});
		it('only renders actions relevant to the mime type', function() {
			fileActions.registerAction({
				name: 'Match',
				displayName: 'MatchDisplay',
				type: OCA.Files.FileActions.TYPE_INLINE,
				mime: 'text/plain',
				permissions: OC.PERMISSION_READ
			});
			fileActions.registerAction({
				name: 'Nomatch',
				displayName: 'NoMatchDisplay',
				type: OCA.Files.FileActions.TYPE_INLINE,
				mime: 'application/octet-stream',
				permissions: OC.PERMISSION_READ
			});

			fileActions.display($tr.find('td.filename'), true, fileList);
			expect($tr.find('.action.action-match').length).toEqual(1);
			expect($tr.find('.action.action-nomatch').length).toEqual(0);
		});
		it('only renders actions relevant to the permissions', function() {
			fileActions.registerAction({
				name: 'Match',
				displayName: 'MatchDisplay',
				type: OCA.Files.FileActions.TYPE_INLINE,
				mime: 'text/plain',
				permissions: OC.PERMISSION_UPDATE
			});
			fileActions.registerAction({
				name: 'Nomatch',
				displayName: 'NoMatchDisplay',
				type: OCA.Files.FileActions.TYPE_INLINE,
				mime: 'text/plain',
				permissions: OC.PERMISSION_DELETE
			});

			fileActions.display($tr.find('td.filename'), true, fileList);
			expect($tr.find('.action.action-match').length).toEqual(1);
			expect($tr.find('.action.action-nomatch').length).toEqual(0);
		});
	});
	describe('action handler', function() {
		var actionStub, $tr;

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
			actionStub = sinon.stub();
			fileActions.registerAction({
				name: 'Test',
				type: OCA.Files.FileActions.TYPE_INLINE,
				mime: 'all',
				icon: OC.imagePath('core', 'actions/test'), 
				permissions: OC.PERMISSION_READ,
				actionHandler: actionStub
			});
			$tr = fileList.add(fileData);
		});
		it('passes context to action handler', function() {
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
		describe('actions menu', function() {
			it('shows actions menu inside row when clicking the menu trigger', function() {
				expect($tr.find('td.filename .fileActionsMenu').length).toEqual(0);
				$tr.find('.action-menu').click();
				expect($tr.find('td.filename .fileActionsMenu').length).toEqual(1);
			});
			it('shows highlight on current row', function() {
				$tr.find('.action-menu').click();
				expect($tr.hasClass('mouseOver')).toEqual(true);
			});
			it('cleans up after hiding', function() {
				var clock = sinon.useFakeTimers();
				$tr.find('.action-menu').click();
				expect($tr.find('.fileActionsMenu').length).toEqual(1);
				OC.hideMenus();
				// sliding animation
				clock.tick(500);
				expect($tr.hasClass('mouseOver')).toEqual(false);
				expect($tr.find('.fileActionsMenu').length).toEqual(0);
			});
		});
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
			fileActions.registerAction({
				name: 'Test',
				displayName: '',
				mime: 'all',
				type: OCA.Files.FileActions.TYPE_INLINE,
				permissions: OC.PERMISSION_READ,
				render: function(actionSpec, isDefault, context) {
					expect(actionSpec.name).toEqual('Test');
					expect(actionSpec.displayName).toEqual('');
					expect(actionSpec.permissions).toEqual(OC.PERMISSION_READ);
					expect(actionSpec.mime).toEqual('all');
					expect(isDefault).toEqual(false);

					expect(context.fileList).toEqual(fileList);
					expect(context.$file[0]).toEqual($tr[0]);

					var $customEl = $('<a class="action action-test" href="#"><span>blabli</span><span>blabla</span></a>');
					$tr.find('td:first').append($customEl);
					return $customEl;
				},
				actionHandler: actionStub
			});
			fileActions.display($tr.find('td.filename'), true, fileList);

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
			actions1.registerAction({
				name: 'Test',
				type: OCA.Files.FileActions.TYPE_INLINE,
				mime: 'all',
				permissions: OC.PERMISSION_READ,
				icon: OC.imagePath('core', 'actions/test'),
				actionHandler: actionStub1
			});
			actions2.registerAction({
				name: 'Test2',
				type: OCA.Files.FileActions.TYPE_INLINE,
				mime: 'all',
				permissions: OC.PERMISSION_READ,
				icon: OC.imagePath('core', 'actions/test'),
				actionHandler: actionStub2
			});
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
			actions1.registerAction({
				name: 'Test',
				type: OCA.Files.FileActions.TYPE_INLINE,
				mime: 'all',
				permissions: OC.PERMISSION_READ,
				icon: OC.imagePath('core', 'actions/test'),
				actionHandler: actionStub1
			});
			actions2.registerAction({
				name: 'Test', // override
				mime: 'all',
				type: OCA.Files.FileActions.TYPE_INLINE,
				permissions: OC.PERMISSION_READ,
				icon: OC.imagePath('core', 'actions/test'),
				actionHandler: actionStub2
			});
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
			actions1.registerAction({
				mime: 'all',
				name: 'Test',
				type: OCA.Files.FileActions.TYPE_INLINE,
				permissions: OC.PERMISSION_READ,
				icon: OC.imagePath('core', 'actions/test'),
				actionHandler: actionStub1
			});

			actions1.merge(actions2);

			// late override
			actions1.registerAction({
				mime: 'all',
				name: 'Test', // override
				type: OCA.Files.FileActions.TYPE_INLINE,
				permissions: OC.PERMISSION_READ,
				icon: OC.imagePath('core', 'actions/test'),
				actionHandler: actionStub2
			});

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
			actions1.registerAction({
				mime: 'all',
				name: 'Test',
				type: OCA.Files.FileActions.TYPE_INLINE,
				permissions: OC.PERMISSION_READ,
				icon: OC.imagePath('core', 'actions/test'),
				actionHandler: actionStub1
			});

			// copy the Test action to actions2
			actions2.merge(actions1);

			// late override
			actions2.registerAction({
				mime: 'all',
				name: 'Test', // override
				type: OCA.Files.FileActions.TYPE_INLINE,
				permissions: OC.PERMISSION_READ,
				icon: OC.imagePath('core', 'actions/test'),
				actionHandler: actionStub2
			});

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
			fileActions.on('registerAction', handler);
			fileActions.registerAction({
				mime: 'all',
				name: 'Test',
				type: OCA.Files.FileActions.TYPE_INLINE,
				permissions: OC.PERMISSION_READ,
				icon: OC.imagePath('core', 'actions/test'),
				actionHandler: actionStub
			});
			fileActions.registerAction({
				mime: 'all',
				name: 'Test2',
				permissions: OC.PERMISSION_READ,
				icon: OC.imagePath('core', 'actions/test'),
				actionHandler: actionStub
			});
			expect(handler.calledTwice).toEqual(true);
		});
		it('does not notifies update event handlers after unregistering', function() {
			var actionStub = sinon.stub();
			var handler = sinon.stub();
			fileActions.on('registerAction', handler);
			fileActions.off('registerAction', handler);
			fileActions.registerAction({
				mime: 'all',
				name: 'Test',
				type: OCA.Files.FileActions.TYPE_INLINE,
				permissions: OC.PERMISSION_READ,
				icon: OC.imagePath('core', 'actions/test'),
				actionHandler: actionStub
			});
			fileActions.registerAction({
				mime: 'all',
				name: 'Test2',
				type: OCA.Files.FileActions.TYPE_INLINE,
				permissions: OC.PERMISSION_READ,
				icon: OC.imagePath('core', 'actions/test'),
				actionHandler: actionStub
			});
			expect(handler.notCalled).toEqual(true);
		});
	});
});
