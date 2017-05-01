// Various jquery fixes

// Set autocomplete width the same as the related input
// See http://stackoverflow.com/a/11845718
jQuery.ui.autocomplete.prototype._resizeMenu = function () {
	var ul = this.menu.element;
	ul.outerWidth(this.element.outerWidth());
};
