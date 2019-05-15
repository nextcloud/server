module.exports = function(currentValue, itemValue) {
	if (currentValue === itemValue) {
		return 'selected="selected"';
	}

	return "";
}
