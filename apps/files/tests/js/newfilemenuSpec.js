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
			expect($items.length).toEqual(3);
			// label points to the file_upload_start item
			var $item = $items.eq(0);
			expect($item.is('label')).toEqual(true);
			expect($item.attr('for')).toEqual('file_upload_start');
		});
	});
	describe('New file/folder', function() {
		var $input;
		var createFileStub;
		var createDirectoryStub;

		beforeEach(function() {
			createFileStub = sinon.stub(FileList.prototype, 'createFile');
			createDirectoryStub = sinon.stub(FileList.prototype, 'createDirectory');
			menu.$el.find('.menuitem').eq(1).click();
			$input = menu.$el.find('form.filenameform input');
		});
		afterEach(function() {
			createFileStub.restore();
			createDirectoryStub.restore();
		});

		it('sets default text in field', function() {
			expect($input.length).toEqual(1);
			expect($input.val()).toEqual('New text file.txt');
		});
		it('creates file when enter is pressed', function() {
			$input.val('somefile.txt');
			$input.trigger(new $.Event('keyup', {keyCode: 13}));
			$input.parent('form').submit();

			expect(createFileStub.calledOnce).toEqual(true);
			expect(createFileStub.getCall(0).args[0]).toEqual('somefile.txt');
			expect(createDirectoryStub.notCalled).toEqual(true);
		});
		it('prevents entering invalid file names', function() {
			$input.val('..');
			$input.trigger(new $.Event('keyup', {keyCode: 13}));
			$input.closest('form').submit();

			expect(createFileStub.notCalled).toEqual(true);
			expect(createDirectoryStub.notCalled).toEqual(true);
		});
		it('prevents entering file names that already exist', function() {
			var inListStub = sinon.stub(fileList, 'inList').returns(true);
			$input.val('existing.txt');
			$input.trigger(new $.Event('keyup', {keyCode: 13}));
			$input.closest('form').submit();

			expect(createFileStub.notCalled).toEqual(true);
			expect(createDirectoryStub.notCalled).toEqual(true);
			inListStub.restore();
		});
		it('switching fields removes the previous form', function() {
			menu.$el.find('.menuitem').eq(2).click();
			expect(menu.$el.find('form').length).toEqual(1);
		});
		it('creates directory when clicking on create directory field', function() {
			menu.$el.find('.menuitem').eq(2).click();
			$input = menu.$el.find('form.filenameform input');
			$input.val('some folder');
			$input.trigger(new $.Event('keyup', {keyCode: 13}));
			$input.closest('form').submit();

			expect(createDirectoryStub.calledOnce).toEqual(true);
			expect(createDirectoryStub.getCall(0).args[0]).toEqual('some folder');
			expect(createFileStub.notCalled).toEqual(true);
		});
	});
});
