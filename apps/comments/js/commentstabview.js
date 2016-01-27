/*
 * Copyright (c) 2016
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

(function() {
	var TEMPLATE =
		'<div>' +
		'   <form class="newCommentForm">' +
		'      <textarea></textarea>' +
		'      <input type="submit" value="{{submitText}}" />' +
		'   </form>' +
		'   <ul class="comments">' +
		'   </ul>' +
		'</div>' +
		'<div class="empty hidden">{{emptyResultLabel}}</div>' +
		/*
		'<input type="button" class="showMore hidden" value="{{moreLabel}}"' +
		' name="show-more" id="show-more" />' +
		*/
		'<div class="loading hidden" style="height: 50px"></div>';

	var COMMENT_TEMPLATE =
		'<li>' +
		'   <hr />' +
		'   <div class="authorRow">' +
		'      <span class="author"><em>{{actorDisplayName}}</em></span>' +
		'      <span class="date">{{creationDateTime}}</span>' +
		'   </div>' +
		'   <div class="message">{{message}}</div>' +
		'</li>';

	/**
	 * @memberof OCA.Comments
	 */
	var CommentsTabView = OCA.Files.DetailTabView.extend(
		/** @lends OCA.Comments.CommentsTabView.prototype */ {
		id: 'commentsTabView',
		className: 'tab commentsTabView',

		events: {
			'submit .newCommentForm': '_onSubmitComment'
		},

		initialize: function() {
			OCA.Files.DetailTabView.prototype.initialize.apply(this, arguments);
			this.collection = new OCA.Comments.CommentsCollection();
			this.collection.on('request', this._onRequest, this);
			this.collection.on('sync', this._onEndRequest, this);
			this.collection.on('add', this._onAddModel, this);
			// TODO: error handling
			_.bindAll(this, '_onSubmitComment');
		},

		template: function(params) {
			if (!this._template) {
				this._template = Handlebars.compile(TEMPLATE);
			}
			return this._template(_.extend({
				submitText: t('comments', 'Submit comment')
			}, params));
		},

		commentTemplate: function(params) {
			if (!this._commentTemplate) {
				this._commentTemplate = Handlebars.compile(COMMENT_TEMPLATE);
			}
			return this._commentTemplate(params);
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
			this.delegateEvents();
		},

		_formatItem: function(commentModel) {
			// TODO: format
			return commentModel.attributes;
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
		},

		nextPage: function() {
			if (this._loading || !this.collection.hasMoreResults()) {
				return;
			}

			this.collection.fetchNext();
		},

		_onClickShowMoreVersions: function(ev) {
			ev.preventDefault();
			this.nextPage();
		},

		_onSubmitComment: function(e) {
			var $textArea = $(e.target).find('textarea');
			e.preventDefault();
			this.collection.create({
				actorId: OC.currentUser,
				// FIXME: how to get current user's display name ?
				actorDisplayName: OC.currentUser,
				actorType: 'users',
				verb: 'comment',
				message: $textArea.val()
			}, {at: 0});

			// TODO: spinner/disable field?
			$textArea.val('');
			return false;
		}
	});

	OCA.Comments.CommentsTabView = CommentsTabView;
})();

