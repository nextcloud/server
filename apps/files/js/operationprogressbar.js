/*
 * Copyright (c) 2018
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

(function() {
	var OperationProgressBar = OC.Backbone.View.extend({
		tagName: 'div',
		id: 'uploadprogresswrapper',
		events: {
			'click button.stop': '_onClickCancel'
		},

		render: function() {
			this.$el.html(OCA.Files.Templates['operationprogressbar']({
				textCancelButton: t('Cancel operation')
			}));
			this.setProgressBarText(t('Uploading …'), t('…'));
		},

		hideProgressBar: function() {
			var self = this;
			$('#uploadprogresswrapper .stop').fadeOut();
			$('#uploadprogressbar').fadeOut(function() {
				self.$el.trigger(new $.Event('resized'));
			});
		},

		hideCancelButton: function() {
			$('#uploadprogresswrapper .stop').fadeOut(function() {
				this.$el.trigger(new $.Event('resized'));
			});
		},

		showProgressBar: function() {
			$('#uploadprogresswrapper .stop').show();
			$('#uploadprogresswrapper .label').show();
			$('#uploadprogressbar').fadeIn();
			this.$el.trigger(new $.Event('resized'));
		},

		setProgressBarValue: function(value) {
			$('#uploadprogressbar').progressbar({value: value});
		},

		setProgressBarText: function(textDesktop, textMobile, title) {
			var labelHtml = OCA.Files.Templates['operationprogressbarlabel']({textDesktop: textDesktop, textMobile: textMobile});
			$('#uploadprogressbar .ui-progressbar-value').html(labelHtml);
			$('#uploadprogressbar .ui-progressbar-value>em').addClass('inner');
			$('#uploadprogressbar>em').replaceWith(labelHtml);
			$('#uploadprogressbar>em').addClass('outer');
			$('#uploadprogressbar').tooltip({placement: 'bottom'});
			if(title) {
				$('#uploadprogressbar').attr('original-title', title);
			}
			$('#uploadprogresswrapper .stop').show();
		},

		_onClickCancel: function (event) {
			this.trigger('cancel');
			return false;
		}
	});

	OCA.Files.OperationProgressBar = OperationProgressBar;
})(OC, OCA);
