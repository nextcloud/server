/**
 * Inspired by http://jqueryui.com/demos/autocomplete/#combobox
 */

(function( $ ) {
	$.widget('ui.combobox', {
		options: { 
			id: null,
			name: null,
			showButton: false,
			editable: true
		},
		_create: function() {
			var self = this,
				select = this.element.hide(),
				selected = select.children(':selected'),
				value = selected.val() ? selected.text() : '';
			var input = this.input = $('<input type="text">')
				.insertAfter( select )
				.val( value )
				.autocomplete({
					delay: 0,
					minLength: 0,
					source: function( request, response ) {
						var matcher = new RegExp( $.ui.autocomplete.escapeRegex(request.term), "i" );
						response( select.children( "option" ).map(function() {
							var text = $( this ).text();
							if ( this.value && ( !request.term || matcher.test(text) ) )
								return {
									label: text.replace(
										new RegExp(
											"(?![^&;]+;)(?!<[^<>]*)(" +
											$.ui.autocomplete.escapeRegex(request.term) +
											")(?![^<>]*>)(?![^&;]+;)", "gi"
										), "<strong>$1</strong>" ),
									value: text,
									option: this
								};
						}) );
					},
					select: function( event, ui ) {
						self.input.val($(ui.item.option).text());
						self.input.trigger('change');
						ui.item.option.selected = true;
						self._trigger( "selected", event, {
							item: ui.item.option
						});
					},
					change: function( event, ui ) {
						if ( !ui.item ) {
							var matcher = new RegExp( "^" + $.ui.autocomplete.escapeRegex( $(this).val() ) + "$", "i" ),
								valid = false;
							self.input.val($(this).val());
							//self.input.trigger('change');
							select.children( "option" ).each(function() {
								if ( $( this ).text().match( matcher ) ) {
									this.selected = valid = true;
									return false;
								}
							});
							if ( !self.options['editable'] && !valid ) {
								// remove invalid value, as it didn't match anything
								$( this ).val( "" );
								select.val( "" );
								input.data( "autocomplete" ).term = "";
								return false;
							}
						}
					}
				})
				.addClass( "ui-widget ui-widget-content ui-corner-left" );

			input.data( "autocomplete" )._renderItem = function( ul, item ) {
				return $( "<li></li>" )
					.data( "item.autocomplete", item )
					.append( "<a>" + item.label + "</a>" )
					.appendTo( ul );
			};
			$.each(this.options, function(key, value) {
				self._setOption(key, value);
			});

			if(this.options['showButton']) {
				this.button = $( "<button type='button'>&nbsp;</button>" )
					.attr( "tabIndex", -1 )
					.attr( "title", "Show All Items" )
					.insertAfter( input )
					.addClass('svg')
					.addClass('action')
					.addClass('combo-button')
					.click(function() {
						// close if already visible
						if ( input.autocomplete( "widget" ).is( ":visible" ) ) {
							input.autocomplete( "close" );
							return;
						}

						// work around a bug (likely same cause as #5265)
						$( this ).blur();

						// pass empty string as value to search for, displaying all results
						input.autocomplete( "search", "" );
						input.focus();
					});
			}
		},
		destroy: function() {
			this.input.remove();
			//this.button.remove();
			this.element.show();
			$.Widget.prototype.destroy.call( this );
		},
		value: function(val) {
			if(val != undefined) {
				this.input.val(val);
			} else {
				return this.input.val();
			}
		},
		_setOption: function( key, value ) {
			switch( key ) {
				case 'id':
					this.options['id'] = value;
					this.input.attr('id', value);
					break;
				case 'name':
					this.options['name'] = value;
					this.input.attr('name', value);
					break;
				case 'attributes':
					var input = this.input;
					$.each(this.options['attributes'], function(key, value) {
						input.attr(key, value);
					});
					break;
				case 'classes':
					var input = this.input;
					$.each(this.options['classes'], function(key, value) {
						input.addClass(value);
					});
					break;
				case 'editable':
				case 'showButton':
					this.options[key] = value;
					break;
			}
			// In jQuery UI 1.8, you have to manually invoke the _setOption method from the base widget
			$.Widget.prototype._setOption.apply( this, arguments );
			// In jQuery UI 1.9 and above, you use the _super method instead
			//this._super( "_setOption", key, value );
		}
	});
})( jQuery );
