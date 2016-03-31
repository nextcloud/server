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

	/**
	 * Creates a dummy message with the given length
	 *
	 * @param {int} len length
	 * @return {string} message
	 */
	function createMessageWithLength(len) {
		var bigMessage = '';
		for (var i = 0; i < len; i++) {
			bigMessage += 'a';
		}
		return bigMessage;
	}

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

		it('renders comments from deleted user differently', function() {
			testComments[0].set('actorType', 'deleted_users', {silent: true});
			view.collection.set(testComments);

			var $item = view.$el.find('.comment[data-id=1]');
			expect($item.find('.author').text()).toEqual('[Deleted user]');
			expect($item.find('.avatar').attr('data-username')).not.toBeDefined();
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
		it('does not create a comment if the field length is too large', function() {
			var bigMessage = '';
			for (var i = 0; i < view._commentMaxLength * 2; i++) {
				bigMessage += 'a';
			}
			view.$el.find('.message').val(bigMessage);
			view.$el.find('form').submit();

			expect(createStub.notCalled).toEqual(true);
		});
		describe('limit indicator', function() {
			var tooltipStub;
			var $message;
			var $submitButton;

			beforeEach(function() {
				tooltipStub = sinon.stub($.fn, 'tooltip');
				$message = view.$el.find('.message');
				$submitButton = view.$el.find('.submit');
			});
			afterEach(function() { 
				tooltipStub.restore(); 
			});
			
			it('does not displays tooltip when limit is far away', function() {
				$message.val(createMessageWithLength(3));
				$message.trigger('change');

				expect(tooltipStub.calledWith('show')).toEqual(false);
				expect($submitButton.prop('disabled')).toEqual(false);
				expect($message.hasClass('error')).toEqual(false);
			});
			it('displays tooltip when limit is almost reached', function() {
				$message.val(createMessageWithLength(view._commentMaxLength - 2));
				$message.trigger('change');

				expect(tooltipStub.calledWith('show')).toEqual(true);
				expect($submitButton.prop('disabled')).toEqual(false);
				expect($message.hasClass('error')).toEqual(false);
			});
			it('displays tooltip and disabled button when limit is exceeded', function() {
				$message.val(createMessageWithLength(view._commentMaxLength + 2));
				$message.trigger('change');

				expect(tooltipStub.calledWith('show')).toEqual(true);
				expect($submitButton.prop('disabled')).toEqual(true);
				expect($message.hasClass('error')).toEqual(true);
			});
		});
	});
	describe('editing comments', function() {
		var saveStub;
		var currentUserStub;

		beforeEach(function() {
			saveStub = sinon.stub(OCA.Comments.CommentModel.prototype, 'save');
			currentUserStub = sinon.stub(OC, 'getCurrentUser');
			currentUserStub.returns({
				uid: 'testuser',
				displayName: 'Test User'
			});
			view.collection.add({
				id: 1,
				actorId: 'testuser',
				actorDisplayName: 'Test User',
				actorType: 'users',
				verb: 'comment',
				message: 'New message',
				creationDateTime: new Date(Date.UTC(2016, 1, 3, 10, 5, 9)).toUTCString()
			});
			view.collection.add({
				id: 2,
				actorId: 'anotheruser',
				actorDisplayName: 'Another User',
				actorType: 'users',
				verb: 'comment',
				message: 'New message from another user',
				creationDateTime: new Date(Date.UTC(2016, 1, 3, 10, 5, 9)).toUTCString()
			});
		});
		afterEach(function() {
			saveStub.restore();
			currentUserStub.restore();
		});

		it('shows edit link for owner comments', function() {
			var $comment = view.$el.find('.comment[data-id=1]');
			expect($comment.length).toEqual(1);
			expect($comment.find('.action.edit').length).toEqual(1);
		});

		it('does not show edit link for other user\'s comments', function() {
			var $comment = view.$el.find('.comment[data-id=2]');
			expect($comment.length).toEqual(1);
			expect($comment.find('.action.edit').length).toEqual(0);
		});

		it('shows edit form when clicking edit', function() {
			var $comment = view.$el.find('.comment[data-id=1]');
			$comment.find('.action.edit').click();

			expect($comment.hasClass('hidden')).toEqual(true);
			var $formRow = view.$el.find('.newCommentRow.comment[data-id=1]');
			expect($formRow.length).toEqual(1);
		});

		it('saves message and updates comment item when clicking save', function() {
			var $comment = view.$el.find('.comment[data-id=1]');
			$comment.find('.action.edit').click();

			var $formRow = view.$el.find('.newCommentRow.comment[data-id=1]');
			expect($formRow.length).toEqual(1);

			$formRow.find('textarea').val('modified\nmessage');
			$formRow.find('form').submit();

			expect(saveStub.calledOnce).toEqual(true);
			expect(saveStub.lastCall.args[0]).toEqual({
				message: 'modified\nmessage'
			});

			var model = view.collection.get(1);
			// simulate the fact that save sets the attribute
			model.set('message', 'modified\nmessage');
			saveStub.yieldTo('success', model);

			// original comment element is visible again
			expect($comment.hasClass('hidden')).toEqual(false);
			// and its message was updated
			expect($comment.find('.message').html()).toEqual('modified<br>message');

			// form row is gone
			$formRow = view.$el.find('.newCommentRow.comment[data-id=1]');
			expect($formRow.length).toEqual(0);
		});

		it('restores original comment when cancelling', function() {
			var $comment = view.$el.find('.comment[data-id=1]');
			$comment.find('.action.edit').click();

			var $formRow = view.$el.find('.newCommentRow.comment[data-id=1]');
			expect($formRow.length).toEqual(1);

			$formRow.find('textarea').val('modified\nmessage');
			$formRow.find('.cancel').click();

			expect(saveStub.notCalled).toEqual(true);

			// original comment element is visible again
			expect($comment.hasClass('hidden')).toEqual(false);
			// and its message was not updated
			expect($comment.find('.message').html()).toEqual('New message');

			// form row is gone
			$formRow = view.$el.find('.newCommentRow.comment[data-id=1]');
			expect($formRow.length).toEqual(0);
		});

		it('destroys model when clicking delete', function() {
			var destroyStub = sinon.stub(OCA.Comments.CommentModel.prototype, 'destroy');
			var $comment = view.$el.find('.comment[data-id=1]');
			$comment.find('.action.edit').click();

			var $formRow = view.$el.find('.newCommentRow.comment[data-id=1]');
			expect($formRow.length).toEqual(1);

			$formRow.find('.delete').click();

			expect(destroyStub.calledOnce).toEqual(true);
			expect(destroyStub.thisValues[0].id).toEqual(1);

			destroyStub.yieldTo('success');

			// original comment element is gone
			$comment = view.$el.find('.comment[data-id=1]');
			expect($comment.length).toEqual(0);

			// form row is gone
			$formRow = view.$el.find('.newCommentRow.comment[data-id=1]');
			expect($formRow.length).toEqual(0);

			destroyStub.restore();
		});
		it('does not submit comment if the field is empty', function() {
			var $comment = view.$el.find('.comment[data-id=1]');
			$comment.find('.action.edit').click();
			$comment.find('.message').val('   ');
			$comment.find('form').submit();

			expect(saveStub.notCalled).toEqual(true);
		});
		it('does not submit comment if the field length is too large', function() {
			var $comment = view.$el.find('.comment[data-id=1]');
			$comment.find('.action.edit').click();
			$comment.find('.message').val(createMessageWithLength(view._commentMaxLength * 2));
			$comment.find('form').submit();

			expect(saveStub.notCalled).toEqual(true);
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
