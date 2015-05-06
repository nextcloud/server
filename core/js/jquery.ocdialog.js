(function($) {
	$.widget('oc.ocdialog', {
		options: {
			width: 'auto',
			height: 'auto',
			closeButton: true,
			closeOnEscape: true,
			modal: false
		},
		_create: function() {
			var self = this;

			this.originalCss = {
				display: this.element[0].style.display,
				width: this.element[0].style.width,
				height: this.element[0].style.height
			};

			this.originalTitle = this.element.attr('title');
			this.options.title = this.options.title || this.originalTitle;

			this.$dialog = $('<div class="oc-dialog" />')
				.attr({
					// Setting tabIndex makes the div focusable
					tabIndex: -1,
					role: 'dialog'
				})
				.insertBefore(this.element);
			this.$dialog.append(this.element.detach());
			this.element.removeAttr('title').addClass('oc-dialog-content').appendTo(this.$dialog);

			this.$dialog.css({
				display: 'inline-block',
				position: 'fixed'
			});

			$(document).on('keydown keyup', function(event) {
				if (
					event.target !== self.$dialog.get(0) &&
					self.$dialog.find($(event.target)).length === 0
				) {
					return;
				}
				// Escape
				if (
					event.keyCode === 27 &&
					event.type === 'keydown' &&
					self.options.closeOnEscape
				) {
					event.stopImmediatePropagation();
					self.close();
					return false;
				}
				// Enter
				if(event.keyCode === 13) {
					event.stopImmediatePropagation();
					if(event.type === 'keyup') {
						event.preventDefault();
						return false;
					}
					// If no button is selected we trigger the primary
					if (
						self.$buttonrow &&
						self.$buttonrow.find($(event.target)).length === 0
					) {
						var $button = self.$buttonrow.find('button.primary');
						if($button) {
							$button.trigger('click');
						}
					} else if(self.$buttonrow) {
						$(event.target).trigger('click');
					}
					return false;
				}
			});
			$(window).resize(function() {
				self.parent = self.$dialog.parent().length > 0 ? self.$dialog.parent() : $('body');
				var pos = self.parent.position();
				self.$dialog.css({
					left: pos.left + ($(window).innerWidth() - self.$dialog.outerWidth())/2,
					top: pos.top + ($(window).innerHeight() - self.$dialog.outerHeight())/2,
					width: Math.min(self.options.width, $(window).innerWidth() - 20 ),
					height: Math.min(self.options.height, $(window).innerHeight() - 20)
				});
				// set sizes of content
				self._setSizes();
			});

			this._setOptions(this.options);
			$(window).trigger('resize');
			this._createOverlay();
		},
		_init: function() {
			this.$dialog.focus();
			this._trigger('open');
		},
		_setOption: function(key, value) {
			var self = this;
			switch(key) {
				case 'title':
					if(this.$title) {
						this.$title.text(value);
					} else {
						var $title = $('<h3 class="oc-dialog-title">'
							+ value
							+ '</h3>');
						this.$title = $title.prependTo(this.$dialog);
					}
					this._setSizes();
					break;
				case 'buttons':
					if(this.$buttonrow) {
						this.$buttonrow.empty();
					} else {
						var $buttonrow = $('<div class="oc-dialog-buttonrow" />');
						this.$buttonrow = $buttonrow.appendTo(this.$dialog);
					}
					if (value.length === 1) {
						this.$buttonrow.addClass('onebutton');
					} else if (value.length === 2) {
						this.$buttonrow.addClass('twobuttons');
					} else if (value.length === 3) {
						this.$buttonrow.addClass('threebuttons');
					}
					$.each(value, function(idx, val) {
						var $button = $('<button>').text(val.text);
						if (val.classes) {
							$button.addClass(val.classes);
						}
						if(val.defaultButton) {
							$button.addClass('primary');
							self.$defaultButton = $button;
						}
						self.$buttonrow.append($button);
						$button.click(function() {
							val.click.apply(self.element[0], arguments);
						});
					});
					this.$buttonrow.find('button')
						.on('focus', function(event) {
							self.$buttonrow.find('button').removeClass('primary');
							$(this).addClass('primary');
						});
					this._setSizes();
					break;
				case 'closeButton':
					if(value) {
						var $closeButton = $('<a class="oc-dialog-close svg"></a>');
						this.$dialog.prepend($closeButton);
						$closeButton.on('click', function() {
							self.close();
						});
					} else {
						this.$dialog.find('.oc-dialog-close').remove();
					}
					break;
				case 'width':
					this.$dialog.css('width', value);
					break;
				case 'height':
					this.$dialog.css('height', value);
					break;
				case 'close':
					this.closeCB = value;
					break;
			}
			//this._super(key, value);
			$.Widget.prototype._setOption.apply(this, arguments );
		},
		_setOptions: function(options) {
			//this._super(options);
			$.Widget.prototype._setOptions.apply(this, arguments);
		},
		_setSizes: function() {
			var content_height = this.$dialog.height();
			if(this.$title) {
				content_height -= this.$title.outerHeight(true);
			}
			if(this.$buttonrow) {
				content_height -= this.$buttonrow.outerHeight(true);
			}
			this.parent = this.$dialog.parent().length > 0 ? this.$dialog.parent() : $('body');
			content_height = Math.min(content_height, this.parent.height()-20);
			if (content_height> 0) {
				this.element.css({
					height: content_height + 'px',
					width: this.$dialog.innerWidth()-20 + 'px'
				});
			} else {
				this.element.css({
					width : this.$dialog.innerWidth() - 20 + 'px'
				});
			}
		},
		_createOverlay: function() {
			if(!this.options.modal) {
				return;
			}

			var self = this;
			this.overlay = $('<div>')
				.addClass('oc-dialog-dim')
				.appendTo($('#content'));
			this.overlay.on('click keydown keyup', function(event) {
				if(event.target !== self.$dialog.get(0) && self.$dialog.find($(event.target)).length === 0) {
					event.preventDefault();
					event.stopPropagation();
					return;
				}
			});
		},
		_destroyOverlay: function() {
			if (!this.options.modal) {
				return;
			}

			if (this.overlay) {
				this.overlay.off('click keydown keyup');
				this.overlay.remove();
				this.overlay = null;
			}
		},
		widget: function() {
			return this.$dialog;
		},
		close: function() {
			this._destroyOverlay();
			var self = this;
			// Ugly hack to catch remaining keyup events.
			setTimeout(function() {
				self._trigger('close', self);
				self.$dialog.hide();
			}, 200);
		},
		destroy: function() {
			if(this.$title) {
				this.$title.remove();
			}
			if(this.$buttonrow) {
				this.$buttonrow.remove();
			}

			if(this.originalTitle) {
				this.element.attr('title', this.originalTitle);
			}
			this.element.removeClass('oc-dialog-content')
					.css(this.originalCss).detach().insertBefore(this.$dialog);
			this.$dialog.remove();
		}
	});
}(jQuery));
