/*
 * Copyright (c) 2016
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

(function(OC, OCA) {
	var TEMPLATE =
		'<div class="newCommentRow comment">' +
		'    <div class="authorRow">' +
		'        {{#if avatarEnabled}}' +
		'        <div class="avatar" data-username="{{userId}}"></div>' +
		'        {{/if}}' +
		'        <div class="author">{{userDisplayName}}</div>' +
		'    </div>' +
		'    <form class="newCommentForm">' +
		'        <textarea class="message" placeholder="{{newMessagePlaceholder}}"></textarea>' +
		'        <input class="submit" type="submit" value="{{submitText}}" />' +
		'    </form>' +
		'    <ul class="comments">' +
		'    </ul>' +
		'</div>' +
		'<div class="empty hidden">{{emptyResultLabel}}</div>' +
		'<input type="button" class="showMore hidden" value="{{moreLabel}}"' +
		' name="show-more" id="show-more" />' +
		'<div class="loading hidden" style="height: 50px"></div>';

	var COMMENT_TEMPLATE =
		'<li class="comment">' +
		'    <div class="authorRow">' +
		'        {{#if avatarEnabled}}' +
		'        <div class="avatar" data-username="{{actorId}}"> </div>' +
		'        {{/if}}' +
		'        <div class="author">{{actorDisplayName}}</div>' +
		'        <div class="date has-tooltip" title="{{altDate}}">{{date}}</div>' +
		'    </div>' +
		'    <div class="message">{{{formattedMessage}}}</div>' +
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
			'click .showMore': '_onClickShowMore'
		},

		initialize: function() {
			OCA.Files.DetailTabView.prototype.initialize.apply(this, arguments);
			this.collection = new OCA.Comments.CommentsCollection();
			this.collection.on('request', this._onRequest, this);
			this.collection.on('sync', this._onEndRequest, this);
			this.collection.on('add', this._onAddModel, this);

			this._avatarsEnabled = !!OC.config.enable_avatars;

			// TODO: error handling
			_.bindAll(this, '_onSubmitComment');
		},

		template: function(params) {
			if (!this._template) {
				this._template = Handlebars.compile(TEMPLATE);
			}
			var currentUser = OC.getCurrentUser();
			return this._template(_.extend({
				avatarEnabled: this._avatarsEnabled,
				userId: currentUser.uid,
				userDisplayName: currentUser.displayName,
				newMessagePlaceholder: t('comments', 'Type in a new comment...'),
				submitText: t('comments', 'Post')
			}, params));
		},

		commentTemplate: function(params) {
			if (!this._commentTemplate) {
				this._commentTemplate = Handlebars.compile(COMMENT_TEMPLATE);
			}
			return this._commentTemplate(_.extend({
				avatarEnabled: this._avatarsEnabled
			}, params));
		},

		getLabel: function() {
			return t('comments', 'Comments');
		},

		setFileInfo: function(fileInfo) {
			if (fileInfo) {
				this.render();
				this.collection.setObjectId(fileInfo.id);
				// reset to first page
				this.collection.reset([], {silent: true});
				this.nextPage();
			} else {
				this.render();
				this.collection.reset();
			}
		},

		render: function() {
			this.$el.html(this.template({
				emptyResultLabel: t('comments', 'No other comments available'),
				moreLabel: t('comments', 'More comments...')
			}));
			this.$el.find('.has-tooltip').tooltip();
			this.$container = this.$el.find('ul.comments');
			this.$el.find('.avatar').avatar(OC.getCurrentUser().uid, 28);
			this.delegateEvents();
		},

		_formatItem: function(commentModel) {
			var timestamp = new Date(commentModel.get('creationDateTime')).getTime();
			var data = _.extend({
				date: OC.Util.relativeModifiedDate(timestamp),
				altDate: OC.Util.formatDate(timestamp),
				formattedMessage: this._formatMessage(commentModel.get('message'))
			}, commentModel.attributes);
			// TODO: format
			return data;
		},

		_toggleLoading: function(state) {
			this._loading = state;
			this.$el.find('.loading').toggleClass('hidden', !state);
		},

		_onRequest: function() {
			this._toggleLoading(true);
			this.$el.find('.showMore').addClass('hidden');
		},

		_onEndRequest: function() {
			this._toggleLoading(false);
			this.$el.find('.empty').toggleClass('hidden', !!this.collection.length);
			this.$el.find('.showMore').toggleClass('hidden', !this.collection.hasMoreResults());
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

		_onClickShowMore: function(ev) {
			ev.preventDefault();
			this.nextPage();
		},

		_onSubmitComment: function(e) {
			var currentUser = OC.getCurrentUser();
			var $textArea = $(e.target).find('textarea');
			e.preventDefault();
			this.collection.create({
				actorId: currentUser.uid,
				actorDisplayName: currentUser.displayName,
				actorType: 'users',
				verb: 'comment',
				message: $textArea.val(),
				creationDateTime: (new Date()).getTime()
			}, {at: 0});

			// TODO: spinner/disable field?
			$textArea.val('');
			return false;
		}
	});

	OCA.Comments.CommentsTabView = CommentsTabView;
})(OC, OCA);

