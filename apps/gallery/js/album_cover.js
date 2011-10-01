var actual_cover;
$(document).ready(function() {
  $.getJSON('ajax/getAlbums.php', function(r) {
    if (r.status == 'success') {
      for (var i in r.albums) {
        var a = r.albums[i];
        Albums.add(a.name, a.numOfItems);
      }
      var targetDiv = document.getElementById('gallery_list');
      if (targetDiv) {
        Albums.display(targetDiv);
      } else {
        alert('Error occured: no such layer `gallery_list`');
      }
    } else {
      alert('Error occured: ' + r.message);
    }
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
