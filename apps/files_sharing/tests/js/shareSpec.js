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
	var oldFileListPrototype;
	var fileList;
	var testFiles;

	beforeEach(function() {
		// back up prototype, as it will be extended by
		// the sharing code
		oldFileListPrototype = _.extend({}, OCA.Files.FileList.prototype);

		var $content = $('<div id="content"></div>');
		$('#testArea').append($content);
		// dummy file list
		var $div = $(
			'<div>' +
			'<table id="filestable">' +
			'<thead></thead>' +
			'<tbody id="fileList"></tbody>' +
			'</table>' +
			'</div>');
		$('#content').append($div);

		var fileActions = new OCA.Files.FileActions();
		OCA.Sharing.Util.initialize(fileActions);
		fileList = new OCA.Files.FileList(
			$div, {
				fileActions : fileActions
			}
		);

		testFiles = [{
			id: 1,
			type: 'file',
			name: 'One.txt',
			path: '/subdir',
			mimetype: 'text/plain',
			size: 12,
			permissions: 31,
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
		OCA.Files.FileList.prototype = oldFileListPrototype;
		delete OCA.Sharing.sharesLoaded;
		OC.Share.statuses = {};
	});

	describe('Sharing data in table row', function() {
		// TODO: test data-permissions, data-share-owner, etc
	});
	describe('Share action icon', function() {
		it('do not shows share text when not shared', function() {
			var $action;
			OC.Share.statuses = {};
			fileList.setFiles([{
				id: 1,
				type: 'file',
				name: 'One.txt',
				path: '/subdir',
				mimetype: 'text/plain',
				size: 12,
				permissions: 31,
				etag: 'abc'
			}]);
			$action = fileList.$el.find('tbody tr:first .action-share');
			expect($action.hasClass('permanent')).toEqual(false);
			expect(OC.basename($action.find('img').attr('src'))).toEqual('share.svg');
		});
		it('shows simple share text with share icon', function() {
			var $action;
			fileList.setFiles([{
				id: 1,
				type: 'file',
				name: 'One.txt',
				path: '/subdir',
				mimetype: 'text/plain',
				size: 12,
				permissions: 31,
				etag: 'abc'
			}]);
			$action = fileList.$el.find('tbody tr:first .action-share');
			expect($action.hasClass('permanent')).toEqual(true);
			expect($action.find('>span').text()).toEqual('Shared');
			expect(OC.basename($action.find('img').attr('src'))).toEqual('share.svg');
		});
		it('shows simple share text with public icon when shared with link', function() {
			var $action;
			OC.Share.statuses = {1: {link: true, path: '/subdir'}};
			fileList.setFiles([{
				id: 1,
				type: 'file',
				name: 'One.txt',
				path: '/subdir',
				mimetype: 'text/plain',
				size: 12,
				permissions: 31,
				etag: 'abc'
			}]);
			$action = fileList.$el.find('tbody tr:first .action-share');
			expect($action.hasClass('permanent')).toEqual(true);
			expect($action.find('>span').text()).toEqual('Shared');
			expect(OC.basename($action.find('img').attr('src'))).toEqual('public.svg');
		});
		it('shows owner name when owner is available', function() {
			var $action;
			fileList.setFiles([{
				id: 1,
				type: 'file',
				name: 'One.txt',
				path: '/subdir',
				mimetype: 'text/plain',
				size: 12,
				permissions: 31,
				shareOwner: 'User One',
				etag: 'abc'
			}]);
			$action = fileList.$el.find('tbody tr:first .action-share');
			expect($action.hasClass('permanent')).toEqual(true);
			expect($action.find('>span').text()).toEqual('Shared by User One');
			expect(OC.basename($action.find('img').attr('src'))).toEqual('share.svg');
		});
		it('shows recipients when recipients are available', function() {
			var $action;
			fileList.setFiles([{
				id: 1,
				type: 'file',
				name: 'One.txt',
				path: '/subdir',
				mimetype: 'text/plain',
				size: 12,
				permissions: 31,
				recipientsDisplayName: 'User One, User Two',
				etag: 'abc'
			}]);
			$action = fileList.$el.find('tbody tr:first .action-share');
			expect($action.hasClass('permanent')).toEqual(true);
			expect($action.find('>span').text()).toEqual('Shared with User One, User Two');
			expect(OC.basename($action.find('img').attr('src'))).toEqual('share.svg');
		});
	});
	describe('Share action', function() {
		// TODO: test file action / dropdown trigger
		it('updates share icon when shares were changed in dropdown', function() {
			fileList.setFiles(testFiles);
			fileList.$el.find('tr:first .action-share').click();
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
	
});
