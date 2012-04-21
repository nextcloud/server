/**
 * Inspired by http://jqueryui.com/demos/autocomplete/#multiple
 */

(function( $ ) {
	$.widget('ui.multiple_autocomplete', {
		_create: function() {
			var self = this;
			function split( val ) {
				return val.split( /,\s*/ );
			}
			function extractLast( term ) {
				return split( term ).pop();
			}
			function showOptions() {
				if(!self.element.autocomplete('widget').is(':visible') && self.element.val().trim() == '') {
					self.element.autocomplete('search', '');
				}
			}
			//console.log('_create: ' + this.options['id']);
			this.element.bind('click', function( event ) {
				showOptions();
			});
			this.element.bind('input', function( event ) {
				showOptions();
			});
			this.element.bind('blur', function( event ) {
				var tmp = self.element.val().trim();
				if(tmp[tmp.length-1] == ',') {
					self.element.val(tmp.substring(0, tmp.length-1));
				} else {
					self.element.val(tmp);
				}
				if(self.element.val().trim() != '') {
					self.element.trigger('change'); // Changes wasn't saved when only using the dropdown.
				}
			});
			this.element.bind( "keydown", function( event ) {
				if ( event.keyCode === $.ui.keyCode.TAB &&
						$( this ).data( "autocomplete" ).menu.active ) {
					event.preventDefault();
				}
			})
			.autocomplete({
				minLength: 0,
				source: function( request, response ) {
					// delegate back to autocomplete, but extract the last term
					response( $.ui.autocomplete.filter(
						self.options.source, extractLast( request.term ) ) );
				},
				focus: function() {
					// prevent value inserted on focus
					return false;
				},
				select: function( event, ui ) {
					var terms = split( this.value );
					// remove the current input
					terms.pop();
					// add the selected item
					terms.push( ui.item.value );
					// add placeholder to get the comma-and-space at the end
					terms.push( "" );
					this.value = terms.join( ", " );
					return false;
				}
			});
			/*this.button = $( "<button type='button'>&nbsp;</button>" )
				.attr( "tabIndex", -1 )
				.attr( "title", "Show All Items" )
				.insertAfter( this.element )
				.addClass('svg')
				.addClass('action')
				.addClass('combo-button')
				.click(function() {
					// close if already visible
					if ( self.element.autocomplete( "widget" ).is( ":visible" ) ) {
						self.element.autocomplete( "close" );
						return;
					}

					// work around a bug (likely same cause as #5265)
					$( this ).blur();

					var tmp = self.element.val().trim();
					if(tmp[tmp.length-1] != ',') {
						self.element.val(tmp+', ');
					}
					// pass empty string as value to search for, displaying all results
					self.element.autocomplete( "search", "" );
					self.element.focus();
				});*/
		},
	});
})( jQuery );
