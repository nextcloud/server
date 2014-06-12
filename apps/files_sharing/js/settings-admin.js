$(document).ready(function() {

	var loadTemplate = function (theme, template) {
		$.get(
			OC.generateUrl('apps/files_sharing/settings/mailtemplate'),
			{ theme: theme, template: template }
		).done(function( result ) {
			$( '#mailTemplateSettings textarea' ).val(result);
		}).fail(function( result ) {
			OC.dialogs.alert(result.responseJSON.message, t('files_sharing', 'Could not load template'));
		});
	};

	// load default template
	var theme = $( '#mts-theme' ).val();
	var template = $( '#mts-template' ).val();
	loadTemplate(theme, template);

	$( '#mts-template' ).change(
		function() {
			var theme = $( '#mts-theme' ).val();
			var template = $( this ).val();
			loadTemplate(theme, template);
		}
	);

	$( '#mts-theme' ).change(
		function() {
			var theme = $( this ).val();
			var template = $( '#mts-template' ).val();
			loadTemplate(theme, template);
		}
	);

	$( '#mailTemplateSettings .actions' ).on('click', '.save',
		function() {
			var theme = $( '#mts-theme' ).val();
			var template = $( '#mts-template' ).val();
			var content = $( '#mailTemplateSettings textarea' ).val();
			OC.msg.startSaving('#mts-msg');
			$.post(
				OC.generateUrl('apps/files_sharing/settings/mailtemplate'),
				{ theme: theme, template: template, content: content }
			).done(function() {
				var data = { status:'success', data:{message:t('files_sharing', 'Saved')} };
				OC.msg.finishedSaving('#mts-msg', data);
			}).fail(function(result) {
				var data = { status: 'error', data:{message:result.responseJSON.message} };
				OC.msg.finishedSaving('#mts-msg', data);
			});
		}
	);

	$( '#mailTemplateSettings .actions' ).on('click', '.reset',
		function() {
			var theme = $( '#mts-theme' ).val();
			var template = $( '#mts-template' ).val();
			OC.msg.startSaving('#mts-msg');
			$.ajax({
				type: "DELETE",
				url: OC.generateUrl('apps/files_sharing/settings/mailtemplate'),
				data: { theme: theme, template: template }
			}).done(function() {
				var data = { status:'success', data:{message:t('files_sharing', 'Reset')} };
				OC.msg.finishedSaving('#mts-msg', data);

				// load default template
				var theme = $( '#mts-theme' ).val();
				var template = $( '#mts-template' ).val();
				loadTemplate(theme, template);
			}).fail(function(result) {
				var data = { status: 'error', data:{message:result.responseJSON.message} };
				OC.msg.finishedSaving('#mts-msg', data);
			});
		}
	);

});
