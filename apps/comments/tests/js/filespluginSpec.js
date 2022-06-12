/**
 * Copyright (c) 2016 Vincent Petry <pvince81@owncloud.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Vincent Petry <vincent@nextcloud.com>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

describe('OCA.Comments.FilesPlugin tests', function() {
	var fileList;
	var testFiles;

	beforeEach(function() {
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

		fileList = new OCA.Files.FileList($div);
		OCA.Comments.FilesPlugin.attach(fileList);

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
			isShareMountPoint: false,
			commentsUnread: 3
		}];
	});
	afterEach(function() {
		fileList.destroy();
		fileList = null;
	});

	describe('Comment icon', function() {
		it('does not render icon when no unread comments available', function() {
			testFiles[0].commentsUnread = 0;
			fileList.setFiles(testFiles);
			var $tr = fileList.findFileEl('One.txt');
			expect($tr.find('.action-comment').length).toEqual(0);
		});
		it('renders comment icon and extra data', function() {
			var $action, $tr;
			fileList.setFiles(testFiles);
			$tr = fileList.findFileEl('One.txt');
			$action = $tr.find('.action-comment');
			expect($action.length).toEqual(1);
			expect($action.hasClass('permanent')).toEqual(true);

			expect($tr.attr('data-comments-unread')).toEqual('3');
		});
		it('clicking icon opens sidebar', function() {
			var sidebarTabStub = sinon.stub(OCA.Files.Sidebar, 'setActiveTab');
			var sidebarStub = sinon.stub(OCA.Files.Sidebar, 'open');
			var $action, $tr;
			fileList.setFiles(testFiles);
			$tr = fileList.findFileEl('One.txt');
			$action = $tr.find('.action-comment');
			$action.click();

			expect(sidebarTabStub.calledOnce).toEqual(true);
			expect(sidebarTabStub.lastCall.args[0]).toEqual('comments');
			expect(sidebarStub.calledOnce).toEqual(true);
			expect(sidebarStub.lastCall.args[0]).toEqual('/subdir/One.txt');
		});
	});
	describe('elementToFile', function() {
		it('returns comment count', function() {
			fileList.setFiles(testFiles);
			var $tr = fileList.findFileEl('One.txt');
			var data = fileList.elementToFile($tr);
			expect(data.commentsUnread).toEqual(3);
		});
		it('does not set comment count when not set', function() {
			delete testFiles[0].commentsUnread;
			fileList.setFiles(testFiles);
			var $tr = fileList.findFileEl('One.txt');
			var data = fileList.elementToFile($tr);
			expect(data.commentsUnread).not.toBeDefined();
		});
		it('does not set comment count when zero', function() {
			testFiles[0].commentsUnread = 0;
			fileList.setFiles(testFiles);
			var $tr = fileList.findFileEl('One.txt');
			var data = fileList.elementToFile($tr);
			expect(data.commentsUnread).not.toBeDefined();
		});
	});
});
