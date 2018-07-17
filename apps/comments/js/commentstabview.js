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
		'<div class="emptycontent hidden"><div class="icon-comment"></div>' +
		'<p>{{emptyResultLabel}}</p></div>' +
		'<input type="button" class="showMore hidden" value="{{moreLabel}}"' +
		' name="show-more" id="show-more" />' +
		'<div class="loading hidden" style="height: 50px"></div>';

	var EDIT_COMMENT_TEMPLATE =
		'<div class="newCommentRow comment" data-id="{{id}}">' +
		'    <div class="authorRow">' +
		'        <div class="avatar currentUser" data-username="{{actorId}}"></div>' +
		'        <div class="author currentUser">{{actorDisplayName}}</div>' +
		'{{#if isEditMode}}' +
		'        <a href="#" class="action delete icon icon-delete has-tooltip" title="{{deleteTooltip}}"></a>' +
		'        <div class="deleteLoading icon-loading-small hidden"></div>'+
		'{{/if}}' +
		'    </div>' +
		'    <form class="newCommentForm">' +
		'        <div contentEditable="true" class="message" data-placeholder="{{newMessagePlaceholder}}">{{message}}</div>' +
		'        <input class="submit icon-confirm" type="submit" value="" />' +
		'{{#if isEditMode}}' +
		'        <input class="cancel pull-right" type="button" value="{{cancelText}}" />' +
		'{{/if}}' +
		'        <div class="submitLoading icon-loading-small hidden"></div>'+
		'    </form>' +
		'</div>';

	var COMMENT_TEMPLATE =
		'<li class="comment{{#if isUnread}} unread{{/if}}{{#if isLong}} collapsed{{/if}}" data-id="{{id}}">' +
		'    <div class="authorRow">' +
		'        <div class="avatar{{#if isUserAuthor}} currentUser{{/if}}" {{#if actorId}}data-username="{{actorId}}"{{/if}}> </div>' +
		'        <div class="author{{#if isUserAuthor}} currentUser{{/if}}">{{actorDisplayName}}</div>' +
		'{{#if isUserAuthor}}' +
		'        <a href="#" class="action edit icon icon-rename has-tooltip" title="{{editTooltip}}"></a>' +
		'{{/if}}' +
		'        <div class="date has-tooltip live-relative-timestamp" data-timestamp="{{timestamp}}" title="{{altDate}}">{{date}}</div>' +
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
		_autoCompleteData: undefined,

		events: {
			'submit .newCommentForm': '_onSubmitComment',
			'click .showMore': '_onClickShowMore',
			'click .action.edit': '_onClickEditComment',
			'click .action.delete': '_onClickDeleteComment',
			'click .cancel': '_onClickCloseComment',
			'click .comment': '_onClickComment',
			'keyup div.message': '_onTextChange',
			'change div.message': '_onTextChange',
			'input div.message': '_onTextChange',
			'paste div.message': '_onPaste'
		},

		_commentMaxLength: 1000,

		initialize: function() {
			OCA.Files.DetailTabView.prototype.initialize.apply(this, arguments);
			this.collection = new OCA.Comments.CommentCollection();
			this.collection.on('request', this._onRequest, this);
			this.collection.on('sync', this._onEndRequest, this);
			this.collection.on('add', this._onAddModel, this);
			this.collection.on('change:message', this._onChangeModel, this);

			this._commentMaxThreshold = this._commentMaxLength * 0.9;

			// TODO: error handling
			_.bindAll(this, '_onTypeComment', '_initAutoComplete', '_onAutoComplete');
		},

		template: function(params) {
			if (!this._template) {
				this._template = Handlebars.compile(TEMPLATE);
			}
			var currentUser = OC.getCurrentUser();
			return this._template(_.extend({
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
				actorId: currentUser.uid,
				actorDisplayName: currentUser.displayName,
				newMessagePlaceholder: t('comments', 'New comment …'),
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
				this._initAutoComplete($('#commentsTabView').find('.newCommentForm .message'));
				this.collection.setObjectId(this.model.id);
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
				emptyResultLabel: t('comments', 'No comments yet, start the conversation!'),
				moreLabel: t('comments', 'More comments …')
			}));
			this.$el.find('.comments').before(this.editCommentTemplate({}));
			this.$el.find('.has-tooltip').tooltip();
			this.$container = this.$el.find('ul.comments');
			this.$el.find('.avatar').avatar(OC.getCurrentUser().uid, 32);
			this.delegateEvents();
			this.$el.find('.message').on('keydown input change', this._onTypeComment);

			autosize(this.$el.find('.newCommentRow .message'))
		},

		_initAutoComplete: function($target) {
			var s = this;
			var limit = 10;
			if(!_.isUndefined(OC.appConfig.comments)) {
				limit = OC.appConfig.comments.maxAutoCompleteResults;
			}
			$target.atwho({
				at: '@',
				limit: limit,
				callbacks: {
					remoteFilter: s._onAutoComplete,
					highlighter: function (li) {
						// misuse the highlighter callback to instead of
						// highlighting loads the avatars.
						var $li = $(li);
						$li.find('.avatar').avatar(undefined, 32);
						return $li;
					},
					sorter: function (q, items) { return items; }
				},
				displayTpl: function (item) {
					return '<li>'
						+ '<span class="avatar-name-wrapper">'
						+ '<div class="avatar" '
						+ ' data-username="' + escapeHTML(item.id) + '"'	// for avatars
						+ ' data-user="' + escapeHTML(item.id) + '"'		// for contactsmenu
						+ ' data-user-display-name="' + escapeHTML(item.label) + '"></div>'
						+ ' <strong>' + escapeHTML(item.label) + '</strong>'
						+ '</span></li>';
				},
				insertTpl: function (item) {
					return ''
						+ '<span class="avatar-name-wrapper">'
						+ '<div class="avatar" '
						+ ' data-username="' + escapeHTML(item.id) + '"'	// for avatars
						+ ' data-user="' + escapeHTML(item.id) + '"'		// for contactsmenu
						+ ' data-user-display-name="' + escapeHTML(item.label) + '"></div>'
						+ ' <strong>' + escapeHTML(item.label) + '</strong>'
						+ '</span>';
				},
				searchKey: "label"
			});
			$target.on('inserted.atwho', function (je, $el) {
				var editionMode = true;
				s._postRenderItem(
					// we need to pass the parent of the inserted element
					// passing the whole comments form would re-apply and request
					// avatars from the server
					$(je.target).find(
						'div[data-username="' + $el.find('[data-username]').data('username') + '"]'
					).parent(),
					editionMode
				);
			});
		},

		_onAutoComplete: function(query, callback) {
			if(_.isEmpty(query)) {
				return;
			}
			var s = this;
			if(!_.isUndefined(this._autoCompleteRequestTimer)) {
				clearTimeout(this._autoCompleteRequestTimer);
			}
			this._autoCompleteRequestTimer = _.delay(function() {
				if(!_.isUndefined(this._autoCompleteRequestCall)) {
					this._autoCompleteRequestCall.abort();
				}
				this._autoCompleteRequestCall = $.get(
					OC.generateUrl('/autocomplete/get'),
					{
						search: query,
						itemType: 'files',
						itemId: s.model.get('id'),
						sorter: 'commenters|share-recipients',
						limit: OC.appConfig.comments.maxAutoCompleteResults
					},
					function (data) {
						callback(data);
					}
				);
			}, 400);
		},

		_formatItem: function(commentModel) {
			var timestamp = new Date(commentModel.get('creationDateTime')).getTime();
			var data = _.extend({
				timestamp: timestamp,
				date: OC.Util.relativeModifiedDate(timestamp),
				altDate: OC.Util.formatDate(timestamp),
				formattedMessage: this._formatMessage(commentModel.get('message'), commentModel.get('mentions'))
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
			this.$el.find('.emptycontent').toggleClass('hidden', !!this.collection.length);
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

		/**
		 * takes care of post-rendering after a new comment was added to the
		 * collection
		 *
		 * @param model
		 * @param collection
		 * @param options
		 * @private
		 */
		_onAddModel: function(model, collection, options) {
			// we need to render it immediately, to ensure that the right
			// order of comments is kept on opening comments tab
			var $comment = $(this.commentTemplate(this._formatItem(model)));
			if (!_.isUndefined(options.at) && collection.length > 1) {
				this.$container.find('li').eq(options.at).before($comment);
			} else {
				this.$container.append($comment);
			}
			this._postRenderItem($comment);
			$('#commentsTabView').find('.newCommentForm div.message').text('').prop('contenteditable', true);

			// we need to update the model, because it consists of client data
			// only, but the server might add meta data, e.g. about mentions
			var oldMentions = model.get('mentions');
			var self = this;
			model.fetch({
				success: function (model) {
					if(_.isEqual(oldMentions, model.get('mentions'))) {
						// don't attempt to render if unnecessary, avoids flickering
						return;
					}
					var $updated = $(self.commentTemplate(self._formatItem(model)));
					$comment.html($updated.html());
					self._postRenderItem($comment);
				}
			})

		},

		/**
		 * takes care of post-rendering after a new comment was edited
		 *
		 * @param model
		 * @private
		 */
		_onChangeModel: function (model) {
			if(model.get('message').trim() === model.previous('message').trim()) {
				return;
			}

			var $form = this.$container.find('.comment[data-id="' + model.id + '"] form');
			var $row = $form.closest('.comment');
			var $target = $row.data('commentEl');
			if(_.isUndefined($target)) {
				// ignore noise – this is only set after editing a comment and hitting post
				return;
			}
			var self = this;

			// we need to update the model, because it consists of client data
			// only, but the server might add meta data, e.g. about mentions
			model.fetch({
				success: function (model) {
					$target.removeClass('hidden');
					$row.remove();

					var $message = $target.find('.message');
					$message
						.html(self._formatMessage(model.get('message'), model.get('mentions')))
						.find('.avatar')
						.each(function () { $(this).avatar(); });
					self._postRenderItem($message);
				}
			});
		},

		_postRenderItem: function($el, editionMode) {
			$el.find('.has-tooltip').tooltip();
			$el.find('.avatar').each(function() {
				var $this = $(this);
				$this.avatar($this.attr('data-username'), 32);
			});

			var username = $el.find('.avatar').data('username');
			if (username !== oc_current_user) {
				$el.find('.authorRow .avatar, .authorRow .author').contactsMenu(
					username, 0, $el.find('.authorRow'));
			}

			var $message = $el.find('.message');
			if($message.length === 0) {
				// it is the case when writing a comment and mentioning a person
				$message = $el;
			}
			this._postRenderMessage($message, editionMode);
		},

		_postRenderMessage: function($el, editionMode) {
			if (editionMode) {
				return;
			}

			$el.find('.avatar').each(function() {
				var avatar = $(this);
				var strong = $(this).next();
				var appendTo = $(this).parent();

				var username = $(this).data('username');
				if (username !== oc_current_user) {
					$.merge(avatar, strong).contactsMenu(avatar.data('user'), 0, appendTo);
				}
			});
		},

		/**
		 * Convert a message to be displayed in HTML,
		 * converts newlines to <br> tags.
		 */
		_formatMessage: function(message, mentions, editMode) {
			message = escapeHTML(message).replace(/\n/g, '<br/>');

			for(var i in mentions) {
				if(!mentions.hasOwnProperty(i)) {
					return;
				}
				var mention = '@' + mentions[i].mentionId;

				// escape possible regex characters in the name
				mention = mention.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

				var displayName = this._composeHTMLMention(mentions[i].mentionId, mentions[i].mentionDisplayName);

				// replace every mention either at the start of the input or after a whitespace
				// followed by a non-word character.
				message = message.replace(new RegExp("(^|\\s)(" + mention + ")\\b", 'g'),
					function(match, p1) {
						// to  get number of whitespaces (0 vs 1) right
						return p1+displayName;
					}
				);
			}
			if(editMode !== true) {
				message = OCP.Comments.plainToRich(message);
			}
			return message;
		},

		_composeHTMLMention: function(uid, displayName) {
			var avatar = '<div class="avatar" '
				+ 'data-username="' + _.escape(uid) + '"'
				+ ' data-user="' + _.escape(uid) + '"'
				+ ' data-user-display-name="'
				+ _.escape(displayName) + '"></div>';

			var isCurrentUser = (uid === OC.getCurrentUser().uid);

			return ''
				+ '<span class="atwho-inserted" contenteditable="false">'
				+ '<span class="avatar-name-wrapper' + (isCurrentUser ? ' currentUser' : '') + '">'
				+ avatar + ' <strong>'+ _.escape(displayName)+'</strong>'
				+ '</span>'
				+ '</span>';
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
			$formRow.find('.message').on('keydown input change', this._onTypeComment);

			// copy avatar element from original to avoid flickering
			$formRow.find('.avatar:first').replaceWith($comment.find('.avatar:first').clone());
			$formRow.find('.has-tooltip').tooltip();

			var $message = $formRow.find('.message');
			$message
				.html(this._formatMessage(commentToEdit.get('message'), commentToEdit.get('mentions'), true))
				.find('.avatar')
				.each(function () { $(this).avatar(); });
			var editionMode = true;
			this._postRenderItem($message, editionMode);

			// Enable autosize
			autosize($formRow.find('.message'));

			// enable autocomplete
			this._initAutoComplete($formRow.find('.message'));

			return false;
		},

		_onTypeComment: function(ev) {
			var $field = $(ev.target);
			var len = $field.text().length;
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

			// Submits form with Enter, but Shift+Enter is a new line. If the
			// autocomplete popover is being shown Enter does not submit the
			// form either; it will be handled by At.js which will add the
			// currently selected item to the message.
			if (ev.keyCode === 13 && !ev.shiftKey && !$field.atwho('isSelecting')) {
				$submitButton.click();
				ev.preventDefault();
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
			var $loading = $comment.find('.deleteLoading');
			var $commentField = $comment.find('.message');
			var $submit = $comment.find('.submit');
			var $cancel = $comment.find('.cancel');

			$commentField.prop('contenteditable', false);
			$submit.prop('disabled', true);
			$cancel.prop('disabled', true);
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
					$commentField.prop('contenteditable', true);
					$submit.prop('disabled', false);
					$cancel.prop('disabled', false);

					OC.Notification.showTemporary(t('comments', 'Error occurred while retrieving comment with ID {id}', {id: commentId}));
				}
			});

			return false;
		},

		_onClickShowMore: function(ev) {
			ev.preventDefault();
			this.nextPage();
		},

		/**
		 * takes care of updating comment element states after submit (either new
		 * comment or edit).
		 *
		 * @param {OC.Backbone.Model} model
		 * @param {jQuery} $form
		 * @private
		 */
		_onSubmitSuccess: function(model, $form) {
			var $submit = $form.find('.submit');
			var $loading = $form.find('.submitLoading');

			$submit.removeClass('hidden');
			$loading.addClass('hidden');
		},

		_commentBodyHTML2Plain: function($el) {
			var $comment = $el.clone();

			$comment.find('.avatar-name-wrapper').each(function () {
				var $this = $(this);
				var $inserted = $this.parent();
				$inserted.html('@' + $this.find('.avatar').data('username'));
			});

			$comment.html(OCP.Comments.richToPlain($comment.html()));

			var oldHtml;
			var html = $comment.html();
			do {
				// replace works one by one
				oldHtml = html;
				html = oldHtml.replace("<br>", "\n");	// preserve line breaks
			} while(oldHtml !== html);
			$comment.html(html);

			return $comment.text();
		},

		_onSubmitComment: function(e) {
			var self = this;
			var $form = $(e.target);
			var commentId = $form.closest('.comment').data('id');
			var currentUser = OC.getCurrentUser();
			var $submit = $form.find('.submit');
			var $loading = $form.find('.submitLoading');
			var $commentField = $form.find('.message');
			var message = $commentField.text().trim();
			e.preventDefault();

			if (!message.length || message.length > this._commentMaxLength) {
				return;
			}

			$commentField.prop('contenteditable', false);
			$submit.addClass('hidden');
			$loading.removeClass('hidden');

			message = this._commentBodyHTML2Plain($commentField);
			if (commentId) {
				// edit mode
				var comment = this.collection.get(commentId);
				comment.save({
					message: message
				}, {
					success: function(model) {
						self._onSubmitSuccess(model, $form);
					},
					error: function() {
						self._onSubmitError($form, commentId);
					}
				});
			} else {
				this.collection.create({
					actorId: currentUser.uid,
					actorDisplayName: currentUser.displayName,
					actorType: 'users',
					verb: 'comment',
					message: message,
					creationDateTime: (new Date()).toUTCString()
				}, {
					at: 0,
					// wait for real creation before adding
					wait: true,
					success: function(model) {
						self._onSubmitSuccess(model, $form);
					},
					error: function() {
						self._onSubmitError($form, undefined);
					}
				});
			}

			return false;
		},

		/**
		 * takes care of updating the UI after an error on submit (either new
		 * comment or edit).
		 *
		 * @param {jQuery} $form
		 * @param {string|undefined} commentId
		 * @private
		 */
		_onSubmitError: function($form, commentId) {
			$form.find('.submit').removeClass('hidden');
			$form.find('.submitLoading').addClass('hidden');
			$form.find('.message').prop('contenteditable', true);

			if(!_.isUndefined(commentId)) {
				OC.Notification.show(t('comments', 'Error occurred while updating comment with id {id}', {id: commentId}), {type: 'error'});
			} else {
				OC.Notification.show(t('comments', 'Error occurred while posting comment'), {type: 'error'});
			}
		},

		/**
		 * ensures the contenteditable div is really empty, when user removed
		 * all input, so that the placeholder will be shown again
		 *
		 * @private
		 */
		_onTextChange: function() {
			var $message = $('#commentsTabView').find('.newCommentForm div.message');
			if(!$message.text().trim().length) {
				$message.empty();
			}
		},

		/**
		 * Limit pasting to plain text
		 *
		 * @param e
		 * @private
		 */
		_onPaste: function (e) {
			e.preventDefault();
			var text = e.originalEvent.clipboardData.getData("text/plain");
			document.execCommand('insertText', false, text);
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
