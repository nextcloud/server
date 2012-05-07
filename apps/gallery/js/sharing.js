$(document).ready(function() {
  $.getJSON(OC.filePath('gallery', 'ajax', 'sharing.php'), {operation: 'get_gallery', token: TOKEN}, albumClickHandler);
});

var paths = [];
var counter = 0;

function returnTo(num) {
  while (num != counter) {
    paths.pop();
    $('.breadcrumbelement:last').remove();
    counter--;
  }
  path = '';
  for (var e in paths) path += '/' + paths[e];
  $.getJSON(OC.filePath('gallery', 'ajax', 'sharing.php'), {operation: 'get_gallery', token: TOKEN, path: path}, function(r) {
    albumClickHandler(r);
  });
}

function albumClickHandler(r) {
  var element = $('div#gallery_list');
  element.html('');
  var album_template = '<div class="gallery_box"><div><a rel="images"><img src="' + OC.filePath('gallery', 'ajax', 'sharing.php') + '?token='+TOKEN+'&operation=get_album_thumbnail&albumname=IMGPATH"></a></div><h1></h1></div>';

  for (var i in r.albums) {
    var a = r.albums[i];
    var local = $(album_template.replace('IMGPATH', encodeURIComponent(a)));
    local.attr('title', a);
    $('h1', local).html(a);
    element.append(local);
  }

  $('div.gallery_box').each(function(i, element) {
    $(element).click(function() {
      paths.push($(this).attr('title'));
      path = '';
      for (var e in paths) path += '/' + paths[e];
      $.getJSON(OC.filePath('gallery', 'ajax', 'sharing.php'), {operation: 'get_gallery', token: TOKEN, path: path}, function(r) {
        var name = paths[paths.length-1];
        counter++;
        var d = '<span class="breadcrumbelement" onclick="javascript:returnTo('+counter+');return false;">'+name+'</span>';
        d = $(d).addClass('inside');
        $('#breadcrumb').append(d);
        albumClickHandler(r);
      });
    });
  });

  var pat = '';
  for (var a in paths) pat += '/'+paths[a];
  var photo_template = '<div class="gallery_box"><div><a rel="images" href="*HREF*" target="_blank"><img src="' + OC.filePath('gallery', 'ajax', 'sharing.php') + '?token='+TOKEN+'&operation=get_thumbnail&img=IMGPATH"></a></div></div>';
  for (var a in r.photos) {
    var local = photo_template.replace('IMGPATH', encodeURIComponent(r.photos[a])).replace('*HREF*', OC.filePath('gallery', 'ajax', 'sharing.php') + '?token='+TOKEN+'&operation=get_photo&photo='+encodeURIComponent(r.photos[a]));
    element.append(local);
  }
}
