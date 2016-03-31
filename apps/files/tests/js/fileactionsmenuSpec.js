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

describe('OCA.Files.FileActionsMenu tests', function() {
	var fileList, fileActions, menu, actionStub, menuContext, $tr;

	beforeEach(function() {
		// init horrible parameters
		var $body = $('#testArea');
		$body.append('<input type="hidden" id="dir" value="/subdir"></input>');
		$body.append('<input type="hidden" id="permissions" value="31"></input>');
		// dummy files table
		actionStub = sinon.stub();
		fileActions = new OCA.Files.FileActions();
		fileList = new OCA.Files.FileList($body, {
			fileActions: fileActions
		});

		fileActions.registerAction({
			name: 'Testdropdown',
			displayName: 'Testdropdowndisplay',
			mime: 'all',
			permissions: OC.PERMISSION_READ,
			icon: function () {
				return OC.imagePath('core', 'actions/download');
			},
			actionHandler: actionStub
		});

		fileActions.registerAction({
			name: 'Testdropdownnoicon',
			displayName: 'Testdropdowndisplaynoicon',
			mime: 'all',
			permissions: OC.PERMISSION_READ,
			actionHandler: actionStub
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

		menuContext = {
			$file: $tr,
			fileList: fileList,
			fileActions: fileActions,
			dir: fileList.getCurrentDirectory()
		};
		menu = new OCA.Files.FileActionsMenu();
		menu.show(menuContext);
	});
	afterEach(function() {
		fileActions = null;
		fileList.destroy();
		fileList = undefined;
		menu.remove();
		$('#dir, #permissions, #filestable').remove();
	});

	describe('rendering', function() {
		it('renders dropdown actions in menu', function() {
			var $action = menu.$el.find('a[data-action=Testdropdown]');
			expect($action.length).toEqual(1);
			expect($action.find('img').attr('src'))
				.toEqual(OC.imagePath('core', 'actions/download'));
			expect($action.find('.no-icon').length).toEqual(0);

			$action = menu.$el.find('a[data-action=Testdropdownnoicon]');
			expect($action.length).toEqual(1);
			expect($action.find('img').length).toEqual(0);
			expect($action.find('.no-icon').length).toEqual(1);
		});
		it('does not render default actions', function() {
			expect(menu.$el.find('a[data-action=Testdefault]').length).toEqual(0);
		});
		it('does not render inline actions', function() {
			expect(menu.$el.find('a[data-action=Testinline]').length).toEqual(0);
		});
		it('only renders actions relevant to the mime type', function() {
			fileActions.registerAction({
				name: 'Match',
				displayName: 'MatchDisplay',
				mime: 'text/plain',
				permissions: OC.PERMISSION_READ
			});
			fileActions.registerAction({
				name: 'Nomatch',
				displayName: 'NoMatchDisplay',
				mime: 'application/octet-stream',
				permissions: OC.PERMISSION_READ
			});

			menu.render();
			expect(menu.$el.find('a[data-action=Match]').length).toEqual(1);
			expect(menu.$el.find('a[data-action=NoMatch]').length).toEqual(0);
		});
		it('only renders actions relevant to the permissions', function() {
			fileActions.registerAction({
				name: 'Match',
				displayName: 'MatchDisplay',
				mime: 'text/plain',
				permissions: OC.PERMISSION_UPDATE
			});
			fileActions.registerAction({
				name: 'Nomatch',
				displayName: 'NoMatchDisplay',
				mime: 'text/plain',
				permissions: OC.PERMISSION_DELETE
			});

			menu.render();
			expect(menu.$el.find('a[data-action=Match]').length).toEqual(1);
			expect(menu.$el.find('a[data-action=NoMatch]').length).toEqual(0);
		});
		it('sorts by order attribute, then name', function() {
			fileActions.registerAction({
				name: 'Baction',
				displayName: 'Baction',
				order: 2,
				mime: 'text/plain',
				permissions: OC.PERMISSION_ALL
			});
			fileActions.registerAction({
				name: 'Zaction',
				displayName: 'Zaction',
				order: 1,
				mime: 'text/plain',
				permissions: OC.PERMISSION_ALL
			});
			fileActions.registerAction({
				name: 'Yaction',
				displayName: 'Yaction',
				mime: 'text/plain',
				permissions: OC.PERMISSION_ALL
			});
			fileActions.registerAction({
				name: 'Waction',
				displayName: 'Waction',
				mime: 'text/plain',
				permissions: OC.PERMISSION_ALL
			});

			menu.render();
			var zactionIndex = menu.$el.find('a[data-action=Zaction]').closest('li').index();
			var bactionIndex = menu.$el.find('a[data-action=Baction]').closest('li').index();
			expect(zactionIndex).toBeLessThan(bactionIndex);

			var wactionIndex = menu.$el.find('a[data-action=Waction]').closest('li').index();
			var yactionIndex = menu.$el.find('a[data-action=Yaction]').closest('li').index();
			expect(wactionIndex).toBeLessThan(yactionIndex);
		});
		it('calls displayName function', function() {
			var displayNameStub = sinon.stub().returns('Test');

			fileActions.registerAction({
				name: 'Something',
				displayName: displayNameStub,
				mime: 'text/plain',
				permissions: OC.PERMISSION_ALL
			});

			menu.render();

			expect(displayNameStub.calledOnce).toEqual(true);
			expect(displayNameStub.calledWith(menuContext)).toEqual(true);
			expect(menu.$el.find('a[data-action=Something]').text()).toEqual('Test');
		});
	});

	describe('action handler', function() {
		it('calls action handler when clicking menu item', function() {
			var $action = menu.$el.find('a[data-action=Testdropdown]');
			$action.click();

			expect(actionStub.calledOnce).toEqual(true);
			expect(actionStub.getCall(0).args[0]).toEqual('testName.txt');
			expect(actionStub.getCall(0).args[1].$file[0]).toEqual($tr[0]);
			expect(actionStub.getCall(0).args[1].fileList).toEqual(fileList);
			expect(actionStub.getCall(0).args[1].fileActions).toEqual(fileActions);
			expect(actionStub.getCall(0).args[1].dir).toEqual('/subdir');
		});
	});
	describe('default actions from registerDefaultActions', function() {
		beforeEach(function() {
			fileActions.clear();
			fileActions.registerDefaultActions();
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
			fileActions.display($tr.find('td.filename'), true, fileList);

			var menuContext = {
				$file: $tr,
				fileList: fileList,
				fileActions: fileActions,
				dir: fileList.getCurrentDirectory()
			};
			menu = new OCA.Files.FileActionsMenu();
			menu.show(menuContext);

			menu.$el.find('.action-download').click();

			expect(redirectStub.calledOnce).toEqual(true);
			expect(redirectStub.getCall(0).args[0]).toContain(
				OC.webroot +
				'/remote.php/webdav/subdir/testName.txt'
			);
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
			fileActions.display($tr.find('td.filename'), true, fileList);

			var menuContext = {
				$file: $tr,
				fileList: fileList,
				fileActions: fileActions,
				dir: '/anotherpath/there'
			};
			menu = new OCA.Files.FileActionsMenu();
			menu.show(menuContext);

			menu.$el.find('.action-download').click();

			expect(redirectStub.calledOnce).toEqual(true);
			expect(redirectStub.getCall(0).args[0]).toContain(
				OC.webroot + '/remote.php/webdav/anotherpath/there/testName.txt'
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
			fileActions.display($tr.find('td.filename'), true, fileList);

			var menuContext = {
				$file: $tr,
				fileList: fileList,
				fileActions: fileActions,
				dir: '/somepath/dir'
			};
			menu = new OCA.Files.FileActionsMenu();
			menu.show(menuContext);

			menu.$el.find('.action-delete').click();

			expect(deleteStub.calledOnce).toEqual(true);
			expect(deleteStub.getCall(0).args[0]).toEqual('testName.txt');
			expect(deleteStub.getCall(0).args[1]).toEqual('/somepath/dir');
			deleteStub.restore();
		});
	});
});

