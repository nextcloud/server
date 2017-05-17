$(document).ready(function () {
	var collection = new OC.Settings.AuthTokenCollection();
	var view = new OC.Settings.AuthTokenView({
		collection: collection
	});
	view.reload();
});
