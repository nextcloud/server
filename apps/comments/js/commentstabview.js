/*
 * Copyright (c) 2016
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

/* global Handlebars, escapeHTML */

(function(OC, OCA) {
	var TEMPLATE =
		'<ul class="comments">' +
		'</ul>' +
		'<div class="empty hidden">{{emptyResultLabel}}</div>' +
		'<input type="button" class="showMore hidden" value="{{moreLabel}}"' +
		' name="show-more" id="show-more" />' +
		'<div class="loading hidden" style="height: 50px"></div>';

	var EDIT_COMMENT_TEMPLATE =
		'<div class="newCommentRow comment" data-id="{{id}}">' +
		'    <div class="authorRow">' +
		'        {{#if avatarEnabled}}' +
		'        <div class="avatar" data-username="{{actorId}}"></div>' +
		'        {{/if}}' +
		'        <div class="author">{{actorDisplayName}}</div>' +
		'{{#if isEditMode}}' +
		'        <a href="#" class="action delete icon icon-delete has-tooltip" title="{{deleteTooltip}}"></a>' +
		'{{/if}}' +
		'    </div>' +
		'    <form class="newCommentForm">' +
		'        <textarea class="message" placeholder="{{newMessagePlaceholder}}">{{message}}</textarea>' +
		'        <input class="submit" type="submit" value="{{submitText}}" />' +
		'{{#if isEditMode}}' +
		'        <input class="cancel" type="button" value="{{cancelText}}" />' +
		'{{/if}}' +
		'        <div class="submitLoading icon-loading-small hidden"></div>'+
		'    </form>' +
		'</div>';

	var COMMENT_TEMPLATE =
		'<li class="comment{{#if isUnread}} unread{{/if}}{{#if isLong}} collapsed{{/if}}" data-id="{{id}}">' +
		'    <div class="authorRow">' +
		'        {{#if avatarEnabled}}' +
		'        <div class="avatar" {{#if actorId}}data-username="{{actorId}}"{{/if}}> </div>' +
		'        {{/if}}' +
		'        <div class="author">{{actorDisplayName}}</div>' +
		'{{#if isUserAuthor}}' +
		'        <a href="#" class="action edit icon icon-rename has-tooltip" title="{{editTooltip}}"></a>' +
		'{{/if}}' +
		'        <div class="date has-tooltip" title="{{altDate}}">{{date}}</div>' +
		'    </div>' +
		'    <div class="message">{{{formattedMessage}}}</div>' +
		'{{#if isLong}}' +
		'    <div class="message-overlay"></div>' +
		'{{/if}}' +
		'</li>';

	/**
	 * @memberof OCA.Comments
	 */
	var CommentsTabView = OCA.Files.DetailTabView.extend(
		/** @lends OCA.Comments.CommentsTabView.prototype */ {
		id: 'commentsTabView',
		className: 'tab commentsTabView',

		events: {
			'submit .newCommentForm': '_onSubmitComment',
			'click .showMore': '_onClickShowMore',
			'click .action.edit': '_onClickEditComment',
			'click .action.delete': '_onClickDeleteComment',
			'click .cancel': '_onClickCloseComment',
			'click .comment': '_onClickComment'
		},

		_commentMaxLength: 1000,

		initialize: function() {
			OCA.Files.DetailTabView.prototype.initialize.apply(this, arguments);
			this.collection = new OCA.Comments.CommentCollection();
			this.collection.on('request', this._onRequest, this);
			this.collection.on('sync', this._onEndRequest, this);
			this.collection.on('add', this._onAddModel, this);

			this._avatarsEnabled = !!OC.config.enable_avatars;

			this._commentMaxThreshold = this._commentMaxLength * 0.9;

			// TODO: error handling
			_.bindAll(this, '_onTypeComment');
		},

		template: function(params) {
			if (!this._template) {
				this._template = Handlebars.compile(TEMPLATE);
			}
			var currentUser = OC.getCurrentUser();
			return this._template(_.extend({
				avatarEnabled: this._avatarsEnabled,
				actorId: currentUser.uid,
				actorDisplayName: currentUser.displayName
			}, params));
		},

		editCommentTemplate: function(params) {
			if (!this._editCommentTemplate) {
				this._editCommentTemplate = Handlebars.compile(EDIT_COMMENT_TEMPLATE);
			}
			var currentUser = OC.getCurrentUser();
			return this._editCommentTemplate(_.extend({
				avatarEnabled: this._avatarsEnabled,
				actorId: currentUser.uid,
				actorDisplayName: currentUser.displayName,
				newMessagePlaceholder: t('comments', 'Type in a new comment...'),
				deleteTooltip: t('comments', 'Delete comment'),
				submitText: t('comments', 'Post'),
				cancelText: t('comments', 'Cancel')
			}, params));
		},

		commentTemplate: function(params) {
			if (!this._commentTemplate) {
				this._commentTemplate = Handlebars.compile(COMMENT_TEMPLATE);
			}

			params = _.extend({
				avatarEnabled: this._avatarsEnabled,
				editTooltip: t('comments', 'Edit comment'),
				isUserAuthor: OC.getCurrentUser().uid === params.actorId,
				isLong: this._isLong(params.message)
			}, params);

			if (params.actorType === 'deleted_users') {
				// makes the avatar a X
				params.actorId = null;
				params.actorDisplayName = t('comments', '[Deleted user]');
			}

			return this._commentTemplate(params);
		},

		getLabel: function() {
			return t('comments', 'Comments');
		},

		setFileInfo: function(fileInfo) {
			if (fileInfo) {
				this.model = fileInfo;
				this.render();
				this.collection.setObjectId(fileInfo.id);
				// reset to first page
				this.collection.reset([], {silent: true});
				this.nextPage();
			} else {
				this.model = null;
				this.render();
				this.collection.reset();
			}
		},

		render: function() {
			this.$el.html(this.template({
				emptyResultLabel: t('comments', 'No other comments available'),
				moreLabel: t('comments', 'More comments...')
			}));
			this.$el.find('.comments').before(this.editCommentTemplate({}));
			this.$el.find('.has-tooltip').tooltip();
			this.$container = this.$el.find('ul.comments');
			if (this._avatarsEnabled) {
				this.$el.find('.avatar').avatar(OC.getCurrentUser().uid, 28);
			}
			this.delegateEvents();
			this.$el.find('textarea').on('keydown input change', this._onTypeComment);
		},

		_formatItem: function(commentModel) {
			var timestamp = new Date(commentModel.get('creationDateTime')).getTime();
			var data = _.extend({
				date: OC.Util.relativeModifiedDate(timestamp),
				altDate: OC.Util.formatDate(timestamp),
				formattedMessage: this._formatMessage(commentModel.get('message'))
			}, commentModel.attributes);
			return data;
		},

		_toggleLoading: function(state) {
			this._loading = state;
			this.$el.find('.loading').toggleClass('hidden', !state);
		},

		_onRequest: function(type) {
			if (type === 'REPORT') {
				this._toggleLoading(true);
				this.$el.find('.showMore').addClass('hidden');
			}
		},

		_onEndRequest: function(type) {
			var fileInfoModel = this.model;
			this._toggleLoading(false);
			this.$el.find('.empty').toggleClass('hidden', !!this.collection.length);
			this.$el.find('.showMore').toggleClass('hidden', !this.collection.hasMoreResults());

			if (type !== 'REPORT') {
				return;
			}

			// find first unread comment
			var firstUnreadComment = this.collection.findWhere({isUnread: true});
			if (firstUnreadComment) {
				// update read marker
				this.collection.updateReadMarker(
					null,
					{
						success: function() {
							fileInfoModel.set('commentsUnread', 0);
						}
					}
				);
			}
		},

		_onAddModel: function(model, collection, options) {
			var $el = $(this.commentTemplate(this._formatItem(model)));
			if (!_.isUndefined(options.at) && collection.length > 1) {
				this.$container.find('li').eq(options.at).before($el);
			} else {
				this.$container.append($el);
			}

			this._postRenderItem($el);
		},

		_postRenderItem: function($el) {
			$el.find('.has-tooltip').tooltip();
			if(this._avatarsEnabled) {
				$el.find('.avatar').each(function() {
					var $this = $(this);
					$this.avatar($this.attr('data-username'), 28);
				});
			}
		},

		/**
		 * Convert a message to be displayed in HTML,
		 * converts newlines to <br> tags.
		 */
		_formatMessage: function(message) {
			return escapeHTML(message).replace(/\n/g, '<br/>');
		},

		nextPage: function() {
			if (this._loading || !this.collection.hasMoreResults()) {
				return;
			}

			this.collection.fetchNext();
		},

		_onClickEditComment: function(ev) {
			ev.preventDefault();
			var $comment = $(ev.target).closest('.comment');
			var commentId = $comment.data('id');
			var commentToEdit = this.collection.get(commentId);
			var $formRow = $(this.editCommentTemplate(_.extend({
				isEditMode: true,
				submitText: t('comments', 'Save')
			}, commentToEdit.attributes)));

			$comment.addClass('hidden').removeClass('collapsed');
			// spawn form
			$comment.after($formRow);
			$formRow.data('commentEl', $comment);
			$formRow.find('textarea').on('keydown input change', this._onTypeComment);

			// copy avatar element from original to avoid flickering
			$formRow.find('.avatar').replaceWith($comment.find('.avatar').clone());
			$formRow.find('.has-tooltip').tooltip();

			return false;
		},

		_onTypeComment: function(ev) {
			var $field = $(ev.target);
			var len = $field.val().length;
			var $submitButton = $field.data('submitButtonEl');
			if (!$submitButton) {
				$submitButton = $field.closest('form').find('.submit');
				$field.data('submitButtonEl', $submitButton);
			}
			$field.tooltip('hide');
			if (len > this._commentMaxThreshold) {
				$field.attr('data-original-title', t('comments', 'Allowed characters {count} of {max}', {count: len, max: this._commentMaxLength}));
				$field.tooltip({trigger: 'manual'});
				$field.tooltip('show');
				$field.addClass('error');
			}

			var limitExceeded = (len > this._commentMaxLength);
			$field.toggleClass('error', limitExceeded);
			$submitButton.prop('disabled', limitExceeded);

			//submits form on ctrl+Enter or cmd+Enter
			if (ev.keyCode === 13 && (ev.ctrlKey || ev.metaKey)) {
				$submitButton.click();
			}
		},

		_onClickComment: function(ev) {
			var $row = $(ev.target);
			if (!$row.is('.comment')) {
				$row = $row.closest('.comment');
			}
			$row.removeClass('collapsed');
		},

		_onClickCloseComment: function(ev) {
			ev.preventDefault();
			var $row = $(ev.target).closest('.comment');
			$row.data('commentEl').removeClass('hidden');
			$row.remove();
			return false;
		},

		_onClickDeleteComment: function(ev) {
			ev.preventDefault();
			var $comment = $(ev.target).closest('.comment');
			var commentId = $comment.data('id');
			var $loading = $comment.find('.submitLoading');

			$comment.addClass('disabled');
			$loading.removeClass('hidden');
			this.collection.get(commentId).destroy({
				success: function() {
					$comment.data('commentEl').remove();
					$comment.remove();
				},
				error: function() {
					$loading.addClass('hidden');
					$comment.removeClass('disabled');
					OC.Notification.showTemporary(t('comments', 'Error occurred while retrieving comment with id {id}', {id: commentId}));
				}
			});


			return false;
		},

		_onClickShowMore: function(ev) {
			ev.preventDefault();
			this.nextPage();
		},

		_onSubmitComment: function(e) {
			var self = this;
			var $form = $(e.target);
			var commentId = $form.closest('.comment').data('id');
			var currentUser = OC.getCurrentUser();
			var $submit = $form.find('.submit');
			var $loading = $form.find('.submitLoading');
			var $textArea = $form.find('textarea');
			var message = $textArea.val().trim();
			e.preventDefault();

			if (!message.length || message.length > this._commentMaxLength) {
				return;
			}

			$textArea.prop('disabled', true);
			$submit.addClass('hidden');
			$loading.removeClass('hidden');

			if (commentId) {
				// edit mode
				var comment = this.collection.get(commentId);
				comment.save({
					message: $textArea.val()
				}, {
					success: function(model) {
						var $row = $form.closest('.comment');
						$submit.removeClass('hidden');
						$loading.addClass('hidden');
						$row.data('commentEl')
							.removeClass('hidden')
							.find('.message')
							.html(self._formatMessage(model.get('message')));
						$row.remove();
					},
					error: function() {
						$submit.removeClass('hidden');
						$loading.addClass('hidden');
						$textArea.prop('disabled', false);

						OC.Notification.showTemporary(t('comments', 'Error occurred while updating comment with id {id}', {id: commentId}));
					}
				});
			} else {
				this.collection.create({
					actorId: currentUser.uid,
					actorDisplayName: currentUser.displayName,
					actorType: 'users',
					verb: 'comment',
					message: $textArea.val(),
					creationDateTime: (new Date()).toUTCString()
				}, {
					at: 0,
					// wait for real creation before adding
					wait: true,
					success: function() {
						$submit.removeClass('hidden');
						$loading.addClass('hidden');
						$textArea.val('').prop('disabled', false);
					},
					error: function() {
						$submit.removeClass('hidden');
						$loading.addClass('hidden');
						$textArea.prop('disabled', false);

						OC.Notification.showTemporary(t('comments', 'Error occurred while posting comment'));
					}
				});
			}

			return false;
		},

		/**
		 * Returns whether the given message is long and needs
		 * collapsing
		 */
		_isLong: function(message) {
			return message.length > 250 || (message.match(/\n/g) || []).length > 1;
		}
	});

	OCA.Comments.CommentsTabView = CommentsTabView;
})(OC, OCA);

