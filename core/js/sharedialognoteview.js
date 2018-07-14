/*
 * @copyright Copyright (c) 2018 John Molakvoæ <skjnldsv@protonmail.com>
 *
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

/* global moment, Handlebars */

(function() {
	if (!OC.Share) {
		OC.Share = {};
	}

	var TEMPLATE =
		'	<form id="newNoteForm" data-shareId="{{shareId}}">' +
		'		<textarea class="message" placeholder="{{placeholder}}">{{note}}</textarea>' +
		'		<input class="submit icon-confirm has-tooltip" type="submit" value="" title="{{submitText}}"/>' +
		'	</form>' +
		'	<div class="error hidden">{{error}}</div>'
	;

	/**
	 * @class OCA.Share.ShareDialogNoteView
	 * @member {OC.Share.ShareItemModel} model
	 * @member {jQuery} $el
	 * @memberof OCA.Sharing
	 * @classdesc
	 *
	 * Represents the expiration part in the GUI of the share dialogue
	 *
	 */
	var ShareDialogNoteView = OC.Backbone.View.extend({

		id: 'shareNote',

		className: 'hidden',

		shareId: undefined,

		events: {
			'submit #newNoteForm': '_onSubmitComment'
		},

		_onSubmitComment: function(e) {
			var self = this;
			var $form = $(e.target);
			var $submit = $form.find('.submit');
			var $commentField = $form.find('.message');
			var $error = $form.siblings('.error');
			var message = $commentField.val().trim();
			e.preventDefault();

			if (message.length < 1) {
				return;
			}

			$submit.prop('disabled', true);
			$form.addClass('icon-loading').prop('disabled', true);

			// send data
			$.ajax({
				method: 'PUT',
				url: OC.generateUrl('/ocs/v2.php/apps/files_sharing/api/v1/shares/' + self.shareId),
				data: { note: message },
				complete : function() {
					$submit.prop('disabled', false);
					$form.removeClass('icon-loading').prop('disabled', false);
				},
				error: function() {
					$error.show();
					setTimeout(function() {
						$error.hide();
					}, 3000);
				}
			});

			// update local js object
			var shares = this.model.get('shares');
			var share = shares.filter(function (share) {
				return share.id === self.shareId;
			});
			share[0].note = message;

			return message;
		},

		render: function(shareId) {
			this.shareId = shareId;
			var shares = this.model.get('shares');
			if (!shares) {
				return;
			}
			var share = shares.filter(function (share) {
				return share.id === shareId;
			});
			if (share.length !== 1) {
				// should not happend
				return;
			}
			this.$el.show();
			this.$el.html(this.template({
				note: share[0].note,
				submitText: t('core', 'Submit the note'),
				placeholder: t('core', 'Add a note…'),
				error: t('core', 'An error has occured. Unable to save the note.'),
				shareId: shareId
			}));

			this.delegateEvents();

			return this;
		},

		hide() {
			this.$el.hide();
		},

		/**
		 * @returns {Function} from Handlebars
		 * @private
		 */
		template: function (data) {
			if (!this._template) {
				this._template = Handlebars.compile(TEMPLATE);
			}
			return this._template(data);
		}

	});

	OC.Share.ShareDialogNoteView = ShareDialogNoteView;

})();
