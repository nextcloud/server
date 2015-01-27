/*
 * Copyright (c) 2014 Vincent Petry <pvince81@owncloud.com>
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

describe('OCA.Sharing.Util tests', function() {
	var fileList;
	var testFiles;

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
		var $content = $('<div id="content"></div>');
		$('#testArea').append($content);
		// dummy file list
		var $div = $(
			'<div id="listContainer">' +
			'<table id="filestable">' +
			'<thead></thead>' +
			'<tbody id="fileList"></tbody>' +
			'</table>' +
			'</div>');
		$('#content').append($div);

		var fileActions = new OCA.Files.FileActions();
		fileList = new OCA.Files.FileList(
			$div, {
				fileActions : fileActions
			}
		);
		OCA.Sharing.Util.attach(fileList);

		testFiles = [{
			id: 1,
			type: 'file',
			name: 'One.txt',
			path: '/subdir',
			mimetype: 'text/plain',
			size: 12,
			permissions: OC.PERMISSION_ALL,
			etag: 'abc',
			shareOwner: 'User One',
			isShareMountPoint: false
		}];

		OCA.Sharing.sharesLoaded = true;
		OC.Share.statuses = {
			1: {link: false, path: '/subdir'}
		};
	});
	afterEach(function() {
		delete OCA.Sharing.sharesLoaded;
		delete OC.Share.droppedDown;
		fileList.destroy();
		fileList = null;
		OC.Share.statuses = {};
		OC.Share.currentShares = {};
	});

	describe('Sharing data in table row', function() {
		// TODO: test data-permissions, data-share-owner, etc
	});
	describe('Share action icon', function() {
		beforeEach(function() {
			OC.Share.statuses = {1: {link: false, path: '/subdir'}};
			OCA.Sharing.sharesLoaded = true;
		});
		afterEach(function() {
			OC.Share.statuses = {};
			OCA.Sharing.sharesLoaded = false;
		});
		it('do not shows share text when not shared', function() {
			var $action, $tr;
			OC.Share.statuses = {};
			fileList.setFiles([{
				id: 1,
				type: 'dir',
				name: 'One',
				path: '/subdir',
				mimetype: 'text/plain',
				size: 12,
				permissions: OC.PERMISSION_ALL,
				etag: 'abc'
			}]);
			$tr = fileList.$el.find('tbody tr:first');
			$action = $tr.find('.action-share');
			expect($action.hasClass('permanent')).toEqual(false);
			expect(OC.basename($action.find('img').attr('src'))).toEqual('share.svg');
			expect(OC.basename(getImageUrl($tr.find('.filename .thumbnail')))).toEqual('folder.svg');
			expect($action.find('img').length).toEqual(1);
		});
		it('shows simple share text with share icon', function() {
			var $action, $tr;
			fileList.setFiles([{
				id: 1,
				type: 'dir',
				name: 'One',
				path: '/subdir',
				mimetype: 'text/plain',
				size: 12,
				permissions: OC.PERMISSION_ALL,
				etag: 'abc'
			}]);
			$tr = fileList.$el.find('tbody tr:first');
			$action = $tr.find('.action-share');
			expect($action.hasClass('permanent')).toEqual(true);
			expect($action.find('>span').text()).toEqual('Shared');
			expect(OC.basename($action.find('img').attr('src'))).toEqual('share.svg');
			expect(OC.basename(getImageUrl($tr.find('.filename .thumbnail')))).toEqual('folder-shared.svg');
			expect($action.find('img').length).toEqual(1);
		});
		it('shows simple share text with public icon when shared with link', function() {
			var $action, $tr;
			OC.Share.statuses = {1: {link: true, path: '/subdir'}};
			fileList.setFiles([{
				id: 1,
				type: 'dir',
				name: 'One',
				path: '/subdir',
				mimetype: 'text/plain',
				size: 12,
				permissions: OC.PERMISSION_ALL,
				etag: 'abc'
			}]);
			$tr = fileList.$el.find('tbody tr:first');
			$action = $tr.find('.action-share');
			expect($action.hasClass('permanent')).toEqual(true);
			expect($action.find('>span').text()).toEqual('Shared');
			expect(OC.basename($action.find('img').attr('src'))).toEqual('public.svg');
			expect(OC.basename(getImageUrl($tr.find('.filename .thumbnail')))).toEqual('folder-public.svg');
			expect($action.find('img').length).toEqual(1);
		});
		it('shows owner name when owner is available', function() {
			var $action, $tr;
			fileList.setFiles([{
				id: 1,
				type: 'dir',
				name: 'One.txt',
				path: '/subdir',
				mimetype: 'text/plain',
				size: 12,
				permissions: OC.PERMISSION_ALL,
				shareOwner: 'User One',
				etag: 'abc'
			}]);
			$tr = fileList.$el.find('tbody tr:first');
			$action = $tr.find('.action-share');
			expect($action.hasClass('permanent')).toEqual(true);
			expect($action.find('>span').text()).toEqual('User One');
			expect(OC.basename($action.find('img').attr('src'))).toEqual('share.svg');
			expect(OC.basename(getImageUrl($tr.find('.filename .thumbnail')))).toEqual('folder-shared.svg');
		});
		it('shows recipients when recipients are available', function() {
			var $action, $tr;
			fileList.setFiles([{
				id: 1,
				type: 'dir',
				name: 'One.txt',
				path: '/subdir',
				mimetype: 'text/plain',
				size: 12,
				permissions: OC.PERMISSION_ALL,
				recipientsDisplayName: 'User One, User Two',
				etag: 'abc'
			}]);
			$tr = fileList.$el.find('tbody tr:first');
			$action = $tr.find('.action-share');
			expect($action.hasClass('permanent')).toEqual(true);
			expect($action.find('>span').text()).toEqual('Shared with User One, User Two');
			expect(OC.basename($action.find('img').attr('src'))).toEqual('share.svg');
			expect(OC.basename(getImageUrl($tr.find('.filename .thumbnail')))).toEqual('folder-shared.svg');
			expect($action.find('img').length).toEqual(1);
		});
		it('shows static share text when file shared with user that has no share permission', function() {
			var $action, $tr;
			fileList.setFiles([{
				id: 1,
				type: 'dir',
				name: 'One',
				path: '/subdir',
				mimetype: 'text/plain',
				size: 12,
				permissions: OC.PERMISSION_CREATE,
				etag: 'abc',
				shareOwner: 'User One'
			}]);
			$tr = fileList.$el.find('tbody tr:first');
			expect($tr.find('.action-share').length).toEqual(0);
			$action = $tr.find('.action-share-notification');
			expect($action.hasClass('permanent')).toEqual(true);
			expect($action.find('>span').text().trim()).toEqual('User One');
			expect(OC.basename($action.find('img').attr('src'))).toEqual('share.svg');
			expect(OC.basename(getImageUrl($tr.find('.filename .thumbnail')))).toEqual('folder-shared.svg');
			expect($action.find('img').length).toEqual(1);
		});
	});
	describe('Share action', function() {
		var showDropDownStub;

		function makeDummyShareItem(displayName) {
			return {
				share_with_displayname: displayName
			};
		}

		beforeEach(function() {
			showDropDownStub = sinon.stub(OC.Share, 'showDropDown', function() {
				$('#testArea').append($('<div id="dropdown"></div>'));
			});
		});
		afterEach(function() {
			showDropDownStub.restore();
		});
		it('adds share icon after sharing a non-shared file', function() {
			var $action, $tr;
			OC.Share.statuses = {};
			fileList.setFiles([{
				id: 1,
				type: 'file',
				name: 'One.txt',
				path: '/subdir',
				mimetype: 'text/plain',
				size: 12,
				permissions: OC.PERMISSION_ALL,
				etag: 'abc'
			}]);
			$action = fileList.$el.find('tbody tr:first .action-share');
			$tr = fileList.$el.find('tr:first');

			expect($action.hasClass('permanent')).toEqual(false);

			$tr.find('.action-share').click();

			expect(showDropDownStub.calledOnce).toEqual(true);

			// simulate what the dropdown does
			var shares = {};
			OC.Share.itemShares[OC.Share.SHARE_TYPE_USER] = ['user1', 'user2'];
			OC.Share.itemShares[OC.Share.SHARE_TYPE_GROUP] = ['group1', 'group2'];
			shares[OC.Share.SHARE_TYPE_USER] = _.map(['User One', 'User Two'], makeDummyShareItem);
			shares[OC.Share.SHARE_TYPE_GROUP] = _.map(['Group One', 'Group Two'], makeDummyShareItem);
			$('#dropdown').trigger(new $.Event('sharesChanged', {shares: shares}));

			expect($tr.attr('data-share-recipients')).toEqual('Group One, Group Two, User One, User Two');

			OC.Share.updateIcon('file', 1);
			expect($action.hasClass('permanent')).toEqual(true);
			expect($action.find('>span').text()).toEqual('Shared with Group One, Group Two, User One, User Two');
			expect(OC.basename($action.find('img').attr('src'))).toEqual('share.svg');
		});
		it('updates share icon after updating shares of a file', function() {
			var $action, $tr;
			OC.Share.statuses = {1: {link: false, path: '/subdir'}};
			fileList.setFiles([{
				id: 1,
				type: 'file',
				name: 'One.txt',
				path: '/subdir',
				mimetype: 'text/plain',
				size: 12,
				permissions: OC.PERMISSION_ALL,
				etag: 'abc'
			}]);
			$action = fileList.$el.find('tbody tr:first .action-share');
			$tr = fileList.$el.find('tr:first');

			expect($action.hasClass('permanent')).toEqual(true);

			$tr.find('.action-share').click();

			expect(showDropDownStub.calledOnce).toEqual(true);

			// simulate what the dropdown does
			var shares = {};
			OC.Share.itemShares[OC.Share.SHARE_TYPE_USER] = ['user1', 'user2', 'user3'];
			shares[OC.Share.SHARE_TYPE_USER] = _.map(['User One', 'User Two', 'User Three'], makeDummyShareItem);
			$('#dropdown').trigger(new $.Event('sharesChanged', {shares: shares}));

			expect($tr.attr('data-share-recipients')).toEqual('User One, User Three, User Two');

			OC.Share.updateIcon('file', 1);

			expect($action.hasClass('permanent')).toEqual(true);
			expect($action.find('>span').text()).toEqual('Shared with User One, User Three, User Two');
			expect(OC.basename($action.find('img').attr('src'))).toEqual('share.svg');
		});
		it('removes share icon after removing all shares from a file', function() {
			var $action, $tr;
			OC.Share.statuses = {1: {link: false, path: '/subdir'}};
			fileList.setFiles([{
				id: 1,
				type: 'file',
				name: 'One.txt',
				path: '/subdir',
				mimetype: 'text/plain',
				size: 12,
				permissions: OC.PERMISSION_ALL,
				etag: 'abc',
				recipients: 'User One, User Two'
			}]);
			$action = fileList.$el.find('tbody tr:first .action-share');
			$tr = fileList.$el.find('tr:first');

			expect($action.hasClass('permanent')).toEqual(true);

			$tr.find('.action-share').click();

			expect(showDropDownStub.calledOnce).toEqual(true);

			// simulate what the dropdown does
			OC.Share.itemShares = {};
			$('#dropdown').trigger(new $.Event('sharesChanged', {shares: {}}));

			expect($tr.attr('data-share-recipients')).not.toBeDefined();

			OC.Share.updateIcon('file', 1);
			expect($action.hasClass('permanent')).toEqual(false);
		});
		it('keep share text after updating reshare', function() {
			var $action, $tr;
			OC.Share.statuses = {1: {link: false, path: '/subdir'}};
			fileList.setFiles([{
				id: 1,
				type: 'file',
				name: 'One.txt',
				path: '/subdir',
				mimetype: 'text/plain',
				size: 12,
				permissions: OC.PERMISSION_ALL,
				etag: 'abc',
				shareOwner: 'User One'
			}]);
			$action = fileList.$el.find('tbody tr:first .action-share');
			$tr = fileList.$el.find('tr:first');

			expect($action.hasClass('permanent')).toEqual(true);

			$tr.find('.action-share').click();

			expect(showDropDownStub.calledOnce).toEqual(true);

			// simulate what the dropdown does
			var shares = {};
			OC.Share.itemShares[OC.Share.SHARE_TYPE_USER] = ['user2'];
			shares[OC.Share.SHARE_TYPE_USER] = _.map(['User Two'], makeDummyShareItem);
			$('#dropdown').trigger(new $.Event('sharesChanged', {shares: shares}));

			expect($tr.attr('data-share-recipients')).toEqual('User Two');

			OC.Share.updateIcon('file', 1);

			expect($action.hasClass('permanent')).toEqual(true);
			expect($action.find('>span').text()).toEqual('User One');
			expect(OC.basename($action.find('img').attr('src'))).toEqual('share.svg');
		});
		it('keep share text after unsharing reshare', function() {
			var $action, $tr;
			OC.Share.statuses = {1: {link: false, path: '/subdir'}};
			fileList.setFiles([{
				id: 1,
				type: 'file',
				name: 'One.txt',
				path: '/subdir',
				mimetype: 'text/plain',
				size: 12,
				permissions: OC.PERMISSION_ALL,
				etag: 'abc',
				shareOwner: 'User One',
				recipients: 'User Two'
			}]);
			$action = fileList.$el.find('tbody tr:first .action-share');
			$tr = fileList.$el.find('tr:first');

			expect($action.hasClass('permanent')).toEqual(true);

			$tr.find('.action-share').click();

			expect(showDropDownStub.calledOnce).toEqual(true);

			// simulate what the dropdown does
			OC.Share.itemShares = {};
			$('#dropdown').trigger(new $.Event('sharesChanged', {shares: {}}));

			expect($tr.attr('data-share-recipients')).not.toBeDefined();

			OC.Share.updateIcon('file', 1);

			expect($action.hasClass('permanent')).toEqual(true);
			expect($action.find('>span').text()).toEqual('User One');
			expect(OC.basename($action.find('img').attr('src'))).toEqual('share.svg');
		});
	});
	describe('formatRecipients', function() {
		it('returns a single recipient when one passed', function() {
			expect(OCA.Sharing.Util.formatRecipients(['User one']))
				.toEqual('User one');
		});
		it('returns two recipients when two passed', function() {
			expect(OCA.Sharing.Util.formatRecipients(['User one', 'User two']))
				.toEqual('User one, User two');
		});
		it('returns four recipients with plus when five passed', function() {
			var recipients = [
				'User one',
				'User two',
				'User three',
				'User four',
				'User five'
			];
			expect(OCA.Sharing.Util.formatRecipients(recipients))
				.toEqual('User four, User one, User three, User two, +1');
		});
		it('returns four recipients with plus when ten passed', function() {
			var recipients = [
				'User one',
				'User two',
				'User three',
				'User four',
				'User five',
				'User six',
				'User seven',
				'User eight',
				'User nine',
				'User ten'
			];
			expect(OCA.Sharing.Util.formatRecipients(recipients))
				.toEqual('User four, User one, User three, User two, +6');
		});
		it('returns four recipients with plus when four passed with counter', function() {
			var recipients = [
				'User one',
				'User two',
				'User three',
				'User four'
			];
			expect(OCA.Sharing.Util.formatRecipients(recipients, 10))
				.toEqual('User four, User one, User three, User two, +6');
		});
	});
	describe('Excluded lists', function() {
		function createListThenAttach(listId) {
			var fileActions = new OCA.Files.FileActions();
			fileList.destroy();
			fileList = new OCA.Files.FileList(
				$('#listContainer'), {
					id: listId,
					fileActions: fileActions
				}
			);
			OCA.Sharing.Util.attach(fileList);
			fileList.setFiles(testFiles);
			return fileList;
		}

		it('does not attach to trashbin or public file lists', function() {
			createListThenAttach('trashbin');
			expect($('.action-share').length).toEqual(0);
			expect($('[data-share-recipient]').length).toEqual(0);
			createListThenAttach('files.public');
			expect($('.action-share').length).toEqual(0);
			expect($('[data-share-recipient]').length).toEqual(0);
		});
	});
	
});
