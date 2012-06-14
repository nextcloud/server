
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
