/*
 * Copyright (c) 2014 Vincent Petry <pvince81@owncloud.com>
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

describe('OCA.Files.TagsPlugin tests', function() {
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
		OCA.Files.TagsPlugin.attach(fileList);

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
			tags: ['tag1', 'tag2']
		}];
	});
	afterEach(function() {
		fileList.destroy();
		fileList = null;
	});

	describe('Favorites icon', function() {
		it('renders favorite icon and extra data', function() {
			var $action, $tr;
			fileList.setFiles(testFiles);
			$tr = fileList.$el.find('tbody tr:first');
			$action = $tr.find('.action-favorite');
			expect($action.length).toEqual(1);
			expect($action.hasClass('permanent')).toEqual(false);

			expect($tr.attr('data-tags').split('|')).toEqual(['tag1', 'tag2']);
			expect($tr.attr('data-favorite')).not.toBeDefined();
		});
		it('renders permanent favorite icon and extra data', function() {
			var $action, $tr;
			testFiles[0].tags.push(OC.TAG_FAVORITE);
			fileList.setFiles(testFiles);
			$tr = fileList.$el.find('tbody tr:first');
			$action = $tr.find('.action-favorite');
			expect($action.length).toEqual(1);
			expect($action.hasClass('permanent')).toEqual(true);

			expect($tr.attr('data-tags').split('|')).toEqual(['tag1', 'tag2', OC.TAG_FAVORITE]);
			expect($tr.attr('data-favorite')).toEqual('true');
		});
		it('adds has-favorites class on table', function() {
			expect(fileList.$el.hasClass('has-favorites')).toEqual(true);
		});
	});
	describe('Applying tags', function() {
		it('sends request to server and updates icon', function() {
			var request;
			fileList.setFiles(testFiles);
			$tr = fileList.$el.find('tbody tr:first');
			$action = $tr.find('.action-favorite');
			$action.click();

			expect(fakeServer.requests.length).toEqual(1);
			var request = fakeServer.requests[0];
			expect(JSON.parse(request.requestBody)).toEqual({
				tags: ['tag1', 'tag2', OC.TAG_FAVORITE]
			});
			request.respond(200, {'Content-Type': 'application/json'}, JSON.stringify({
				tags: ['tag1', 'tag2', 'tag3', OC.TAG_FAVORITE]
			}));

			expect($tr.attr('data-favorite')).toEqual('true');
			expect($tr.attr('data-tags').split('|')).toEqual(['tag1', 'tag2', 'tag3', OC.TAG_FAVORITE]);
			expect(fileList.files[0].tags).toEqual(['tag1', 'tag2', 'tag3', OC.TAG_FAVORITE]);
			expect($action.find('img').attr('src')).toEqual(OC.imagePath('core', 'actions/starred'));

			$action.click();
			request = fakeServer.requests[1];
			expect(JSON.parse(request.requestBody)).toEqual({
				tags: ['tag1', 'tag2', 'tag3']
			});
			request.respond(200, {'Content-Type': 'application/json'}, JSON.stringify({
				tags: ['tag1', 'tag2', 'tag3']
			}));

			expect($tr.attr('data-favorite')).toEqual('false');
			expect($tr.attr('data-tags').split('|')).toEqual(['tag1', 'tag2', 'tag3']);
			expect(fileList.files[0].tags).toEqual(['tag1', 'tag2', 'tag3']);
			expect($action.find('img').attr('src')).toEqual(OC.imagePath('core', 'actions/star'));
		});
	});
});
