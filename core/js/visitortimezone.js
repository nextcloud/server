$(document).ready(function () {
		var visitortimezone = (-new Date().getTimezoneOffset() / 60);
		$('#timezone-offset').val(visitortimezone);
});