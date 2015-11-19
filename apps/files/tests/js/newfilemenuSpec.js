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

describe('OCA.Files.NewFileMenu', function() {
	var FileList = OCA.Files.FileList;
	var menu, fileList, $uploadField, $trigger;

	beforeEach(function() {
		// dummy upload button
		var $container = $('<div id="app-content-files"></div>');
		$uploadField = $('<input id="file_upload_start"></input>');
		$trigger = $('<a href="#">Menu</a>');
		$container.append($uploadField).append($trigger);
		$('#testArea').append($container);

		fileList = new FileList($container);
		menu = new OCA.Files.NewFileMenu({
			fileList: fileList
		});
		menu.showAt($trigger);
	});
	afterEach(function() {
		OC.hideMenus();
		fileList = null;
		menu = null;
	});

	describe('rendering', function() {
		it('renders menu items', function() {
			var $items = menu.$el.find('.menuitem');
			expect($items.length).toEqual(2);
			// label points to the file_upload_start item
			var $item = $items.eq(0);
			expect($item.is('label')).toEqual(true);
			expect($item.attr('for')).toEqual('file_upload_start');
		});
	});
	describe('New file/folder', function() {
		var $input;
		var createDirectoryStub;

		beforeEach(function() {
			createDirectoryStub = sinon.stub(FileList.prototype, 'createDirectory');
			menu.$el.find('.menuitem').eq(1).click();
			$input = menu.$el.find('form.filenameform input');
		});
		afterEach(function() {
			createDirectoryStub.restore();
		});

		it('sets default text in field', function() {
			expect($input.length).toEqual(1);
			expect($input.val()).toEqual('New folder');
		});
		it('prevents entering invalid file names', function() {
			$input.val('..');
			$input.trigger(new $.Event('keyup', {keyCode: 13}));
			$input.closest('form').submit();

			expect(createDirectoryStub.notCalled).toEqual(true);
		});
		it('prevents entering file names that already exist', function() {
			var inListStub = sinon.stub(fileList, 'inList').returns(true);
			$input.val('existing.txt');
			$input.trigger(new $.Event('keyup', {keyCode: 13}));
			$input.closest('form').submit();

			expect(createDirectoryStub.notCalled).toEqual(true);
			inListStub.restore();
		});
		it('creates directory when clicking on create directory field', function() {
			$input = menu.$el.find('form.filenameform input');
			$input.val('some folder');
			$input.trigger(new $.Event('keyup', {keyCode: 13}));
			$input.closest('form').submit();

			expect(createDirectoryStub.calledOnce).toEqual(true);
			expect(createDirectoryStub.getCall(0).args[0]).toEqual('some folder');
		});
	});
	describe('custom entries', function() {
		var oldPlugins;
		var plugin;
		var actionStub;

		beforeEach(function() {
			oldPlugins = _.extend({}, OC.Plugins._plugins);
			actionStub = sinon.stub();
			plugin = {
				attach: function(menu) {
					menu.addMenuEntry({
						id: 'file',
						displayName: t('files_texteditor', 'Text file'),
						templateName: t('files_texteditor', 'New text file.txt'),
						iconClass: 'icon-filetype-text',
						fileType: 'file',
						actionHandler: actionStub
					});
				}
			};

			OC.Plugins.register('OCA.Files.NewFileMenu', plugin);
			menu = new OCA.Files.NewFileMenu({
				fileList: fileList
			});
			menu.showAt($trigger);
		});
		afterEach(function() {
			OC.Plugins._plugins = oldPlugins;
		});
		it('renders custom menu items', function() {
			expect(menu.$el.find('.menuitem').length).toEqual(3);
			expect(menu.$el.find('.menuitem[data-action=file]').length).toEqual(1);
		});
		it('calls action handler when clicking on custom item', function() {
			menu.$el.find('.menuitem').eq(2).click();
			var $input = menu.$el.find('form.filenameform input');
			$input.val('some name');
			$input.trigger(new $.Event('keyup', {keyCode: 13}));
			$input.closest('form').submit();

			expect(actionStub.calledOnce).toEqual(true);
			expect(actionStub.getCall(0).args[0]).toEqual('some name');
		});
		it('switching fields removes the previous form', function() {
			menu.$el.find('.menuitem').eq(2).click();
			expect(menu.$el.find('form').length).toEqual(1);
		});
	});
});
