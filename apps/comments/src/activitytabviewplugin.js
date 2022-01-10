/**
 * Copyright (c) 2016
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @license GNU AGPL version 3 or any later version
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

/** @typedef {import('jquery')} jQuery */

(function() {
	OCA.Comments.ActivityTabViewPlugin = {

		/**
		 * Prepare activity for display
		 *
		 * @param {OCA.Activity.ActivityModel} model for this activity
		 * @param {jQuery} $el jQuery handle for this activity
		 * @param {string} view The view that displayes this activity
		 */
		prepareModelForDisplay(model, $el, view) {
			if (model.get('app') !== 'comments' || model.get('type') !== 'comments') {
				return
			}

			if (view === 'ActivityTabView') {
				$el.addClass('comment')
				if (model.get('message') && this._isLong(model.get('message'))) {
					$el.addClass('collapsed')
					const $overlay = $('<div>').addClass('message-overlay')
					$el.find('.activitymessage').after($overlay)
					$el.on('click', this._onClickCollapsedComment)
				}
			}
		},

		/*
		 * Copy of CommentsTabView._onClickComment()
		 */
		_onClickCollapsedComment(ev) {
			let $row = $(ev.target)
			if (!$row.is('.comment')) {
				$row = $row.closest('.comment')
			}
			$row.removeClass('collapsed')
		},

		/*
		 * Copy of CommentsTabView._isLong()
		 */
		_isLong(message) {
			return message.length > 250 || (message.match(/\n/g) || []).length > 1
		},
	}

})()

OC.Plugins.register('OCA.Activity.RenderingPlugins', OCA.Comments.ActivityTabViewPlugin)
