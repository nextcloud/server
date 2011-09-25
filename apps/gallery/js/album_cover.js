var actual_cover;
$('body').ready(function() {
  $('div[class=gallery_album_box]').each(function(i, e) {
      $.getJSON('ajax/getCovers.php', { album: $(e).children('h1:last').text() }, function(a) {
        if (a.status == "success") {
          e.ic = a.imageCount;
          e.images = a.images;
          if (e.ic > 0) {
            $(e).find('img[class=gallery_album_cover]').attr('src', 'ajax/thumbnail.php?img=' + e.images[0]);
            actual_cover = 0;
          }
        }
      });
  });
  $('img[class=gallery_album_cover]').each(function(i, e) {
    $(e).mousemove(function(a) {
      if (e.parentNode.parentNode.ic!=0) {
        var x = Math.min(Math.floor((a.clientX - this.offsetLeft)/(200/e.parentNode.parentNode.ic)), e.parentNode.parentNode.ic-1);
        if (actual_cover != x) {
          $(e).attr('src', 'ajax/thumbnail.php?img=' + e.parentNode.parentNode.images[x]);
          actual_cover = x;
        }
      }
    });
  });
});

function createNewAlbum() {
  var name = prompt("album name", "");
  if (name != null && name != "") {
    $.getJSON("ajax/createAlbum.php", {album_name: name}, function(r) {
      if (r.status == "success") {
        var v = '<div class="gallery_album_box"><a href="?view='+r.name+'"><img class="gallery_album_cover"/></a><h1>'+r.name+'</h1></div>';
        $('div#gallery_list').append(v);
      }
    });
  }
}

function scanForAlbums() {
  $.getJSON('ajax/scanForAlbums.php', function(r) {
    if (r.status == 'success') {
      window.location.reload(true);
    } else {
      alert('Error occured: ' + r.message);
    }
  });
}
