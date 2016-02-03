/**
* ownCloud
*
* @author Vincent Petry
* @copyright 2016 Vincent Petry <pvince81@owncloud.com>
*
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
* License as published by the Free Software Foundation; either
* comment 3 of the License, or any later comment.
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

describe('OCA.Comments.CommentsTabView tests', function() {
	var view, fileInfoModel;
	var fetchStub;
	var testComments;
	var clock;

	beforeEach(function() {
		clock = sinon.useFakeTimers(Date.UTC(2016, 1, 3, 10, 5, 9));
		fetchStub = sinon.stub(OCA.Comments.CommentCollection.prototype, 'fetchNext');
		view = new OCA.Comments.CommentsTabView();
		fileInfoModel = new OCA.Files.FileInfoModel({
			id: 5,
			name: 'One.txt',
			mimetype: 'text/plain',
			permissions: 31,
			path: '/subdir',
			size: 123456789,
			etag: 'abcdefg',
			mtime: Date.UTC(2016, 1, 0, 0, 0, 0)
		});
		view.render();
		var comment1 = new OCA.Comments.CommentModel({
			id: 1,
			actorType: 'users',
			actorId: 'user1',
			actorDisplayName: 'User One',
			objectType: 'files',
			objectId: 5,
			message: 'First',
			creationDateTime: new Date(Date.UTC(2016, 1, 3, 10, 5, 0)).toUTCString()
		});
		var comment2 = new OCA.Comments.CommentModel({
			id: 2,
			actorType: 'users',
			actorId: 'user2',
			actorDisplayName: 'User Two',
			objectType: 'files',
			objectId: 5,
			message: 'Second\nNewline',
			creationDateTime: new Date(Date.UTC(2016, 1, 3, 10, 0, 0)).toUTCString()
		});

		testComments = [comment1, comment2];
	});
	afterEach(function() {
		view.remove();
		view = undefined;
		fetchStub.restore();
		clock.restore();
	});
	describe('rendering', function() {
		it('reloads matching comments when setting file info model', function() {
			view.setFileInfo(fileInfoModel);
			expect(fetchStub.calledOnce).toEqual(true);
		});

		it('renders loading icon while fetching comments', function() {
			view.setFileInfo(fileInfoModel);
			view.collection.trigger('request');

			expect(view.$el.find('.loading').length).toEqual(1);
			expect(view.$el.find('.comments li').length).toEqual(0);
		});

		it('renders comments', function() {

			view.setFileInfo(fileInfoModel);
			view.collection.set(testComments);

			var $comments = view.$el.find('.comments>li');
			expect($comments.length).toEqual(2);
			var $item = $comments.eq(0);
			expect($item.find('.author').text()).toEqual('User One');
			expect($item.find('.date').text()).toEqual('seconds ago');
			expect($item.find('.message').text()).toEqual('First');

			$item = $comments.eq(1);
			expect($item.find('.author').text()).toEqual('User Two');
			expect($item.find('.date').text()).toEqual('5 minutes ago');
			expect($item.find('.message').html()).toEqual('Second<br>Newline');
		});
	});
	describe('more comments', function() {
		var hasMoreResultsStub;

		beforeEach(function() {
			view.collection.set(testComments);
			hasMoreResultsStub = sinon.stub(OCA.Comments.CommentCollection.prototype, 'hasMoreResults');
		});
		afterEach(function() {
			hasMoreResultsStub.restore();
		});

		it('shows "More comments" button when more comments are available', function() {
			hasMoreResultsStub.returns(true);
			view.collection.trigger('sync');

			expect(view.$el.find('.showMore').hasClass('hidden')).toEqual(false);
		});
		it('does not show "More comments" button when more comments are available', function() {
			hasMoreResultsStub.returns(false);
			view.collection.trigger('sync');

			expect(view.$el.find('.showMore').hasClass('hidden')).toEqual(true);
		});
		it('fetches and appends the next page when clicking the "More" button', function() {
			hasMoreResultsStub.returns(true);

			expect(fetchStub.notCalled).toEqual(true);

			view.$el.find('.showMore').click();

			expect(fetchStub.calledOnce).toEqual(true);
		});
		it('appends comment to the list when added to collection', function() {
			var comment3 = new OCA.Comments.CommentModel({
				id: 3,
				actorType: 'users',
				actorId: 'user3',
				actorDisplayName: 'User Three',
				objectType: 'files',
				objectId: 5,
				message: 'Third',
				creationDateTime: new Date(Date.UTC(2016, 1, 3, 5, 0, 0)).toUTCString()
			});

			view.collection.add(comment3);

			expect(view.$el.find('.comments>li').length).toEqual(3);

			var $item = view.$el.find('.comments>li').eq(2);
			expect($item.find('.author').text()).toEqual('User Three');
			expect($item.find('.date').text()).toEqual('5 hours ago');
			expect($item.find('.message').html()).toEqual('Third');
		});
	});
	describe('posting comments', function() {
		var createStub;
		var currentUserStub;

		beforeEach(function() {
			view.collection.set(testComments);
			createStub = sinon.stub(OCA.Comments.CommentCollection.prototype, 'create');
			currentUserStub = sinon.stub(OC, 'getCurrentUser');
			currentUserStub.returns({
				uid: 'testuser',
				displayName: 'Test User'
			});
		});
		afterEach(function() {
			createStub.restore();
			currentUserStub.restore();
		});

		it('creates a new comment when clicking post button', function() {
			view.$el.find('.message').val('New message');
			view.$el.find('form').submit();

			expect(createStub.calledOnce).toEqual(true);
			expect(createStub.lastCall.args[0]).toEqual({
				actorId: 'testuser',
				actorDisplayName: 'Test User',
				actorType: 'users',
				verb: 'comment',
				message: 'New message',
				creationDateTime: new Date(Date.UTC(2016, 1, 3, 10, 5, 9)).toUTCString()
			});
		});
		it('does not create a comment if the field is empty', function() {
			view.$el.find('.message').val('   ');
			view.$el.find('form').submit();

			expect(createStub.notCalled).toEqual(true);
		});

	});
	describe('read marker', function() {
		var updateMarkerStub;

		beforeEach(function() {
			updateMarkerStub = sinon.stub(OCA.Comments.CommentCollection.prototype, 'updateReadMarker');
		});
		afterEach(function() { 
			updateMarkerStub.restore();
		});

		it('resets the read marker after REPORT', function() {
			testComments[0].set('isUnread', true, {silent: true});
			testComments[1].set('isUnread', true, {silent: true});
			view.collection.set(testComments);
			view.collection.trigger('sync', 'REPORT');

			expect(updateMarkerStub.calledOnce).toEqual(true);
			expect(updateMarkerStub.lastCall.args[0]).toBeFalsy();
		});
		it('does not reset the read marker if there was no unread comments', function() {
			view.collection.set(testComments);
			view.collection.trigger('sync', 'REPORT');

			expect(updateMarkerStub.notCalled).toEqual(true);
		});
		it('does not reset the read marker when posting comments', function() {
			testComments[0].set('isUnread', true, {silent: true});
			testComments[1].set('isUnread', true, {silent: true});
			view.collection.set(testComments);
			view.collection.trigger('sync', 'POST');

			expect(updateMarkerStub.notCalled).toEqual(true);
		});
	});
});
