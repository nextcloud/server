function constructSharingPath() {
  return document.location.protocol + '//' + document.location.host + OC.linkTo('', 'public.php') + '?service=gallery&token=' + Albums.token;
}

function shareGallery() {
  var existing_token = '';
  //if (Albums.token)
  //  existing_token = constructSharingPath();
  var form_fields = [{text: 'Share', name: 'share', type: 'checkbox', value: false},
                     {text: 'Share recursive', name: 'recursive', type: 'checkbox', value: false},
                     {text: 'Shared gallery address', name: 'address', type: 'text', value: ''}];
    OC.dialogs.form(form_fields, t('gallery', 'Share gallery'), function(values){
    var p = '';
    for (var i in paths) p += paths[i]+'/';
    if (p == '') p = '/';
    alert(p);
    $.getJSON(OC.filePath('gallery', 'ajax', 'galleryOp.php'), {operation: 'share', path: p, share: values[0].value, recursive: values[1].value}, function(r) {
      if (r.status == 'success') {
        Albums.shared = r.sharing;
        if (Albums.shared) {
          Albums.token = r.token;
          Albums.recursive = r.recursive;
        } else {
          Albums.token = '';
          Albums.recursive = false;
        }
        var actual_addr = '';
        if (Albums.token)
          actual_addr = constructSharingPath();
        $('input[name="address"]').val(actual_addr);
      } else {
        OC.dialogs.alert(t('gallery', 'Error: ') + r.cause, t('gallery', 'Internal error'));
      }
    });
  });
}

function explode(element) {
	$('div', element).each(function(index, elem) {
		if ($(elem).hasClass('title')) {
			$(elem).addClass('visible');
		} else {
			$(elem).css('margin-top', Math.floor(30-(Math.random()*60)) + 'px')
			       .css('margin-left', Math.floor(30-(Math.random()*60))+ 'px')
			       .css('z-index', '999');
		}
	});
}

function deplode(element) {
	$('div', element).each(function(index, elem) {
		if ($(elem).hasClass('title')) {
			$(elem).removeClass('visible');
		} else {
			$(elem).css('margin-top', Math.floor(5-(Math.random()*10)) + 'px')
			   .css('margin-left', Math.floor(5-(Math.random()*10))+ 'px')
			   .css('z-index', '3');
		}
	});
}

function openNewGal(album_name) {
	root = root + decodeURIComponent(album_name) + "/";
	var url = window.location.protocol+"//"+window.location.hostname+OC.linkTo('gallery', 'index.php');
	url = url + "?root="+encodeURIComponent(root);

	window.location = url;
}
