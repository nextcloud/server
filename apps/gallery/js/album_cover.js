var actual_cover;
$(document).ready(function() {
  $.getJSON('ajax/galleryOp.php', {operation: 'get_galleries'}, function(r) {
    if (r.status == 'success') {
      for (var i in r.albums) {
        var a = r.albums[i];
        Albums.add(a.name, a.numOfItems);
      }
      var targetDiv = document.getElementById('gallery_list');
      if (targetDiv) {
        $(targetDiv).html('');
        Albums.display(targetDiv);
        $('#gallery_list').sortable({revert:true});
        $('.gallery_album_box').each(function(i, e) {
          $(e).draggable({connectToSortable: '#gallery_list', handle: '.dummy'})
        });
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

var albumCounter = 0;
var totalAlbums = 0;

function scanForAlbums(cleanup) {
  cleanup = cleanup?true:false;
  var albumCounter = 0;
  var totalAlbums = 0;
  $('#g-scan-button').attr('disabled', 'true');
  $.getJSON('ajax/galleryOp.php?operation=filescan', {cleanup: cleanup}, function(r) {

    if (r.status == 'success') {
      totalAlbums = r.paths.length;
      if (totalAlbums == 0) {
        $('#notification').text(t('gallery', "No photos found")).fadeIn().slideDown().delay(3000).fadeOut().slideUp();
        return;
      }
      $('#scanprogressbar').progressbar({ value: (albumCounter/totalAlbums)*100 }).fadeIn();
      for(var a in r.paths) {
		  $.getJSON('ajax/galleryOp.php',{operation:'partial_create','path':r.paths[a]}, function(r) {

          if (r.status == 'success') {
            Albums.add(r.album_details.albumName, r.album_details.imagesCount);
          }

          albumCounter++;
          $('#scanprogressbar').progressbar({ value: (albumCounter/totalAlbums)*100 });
          if (albumCounter == totalAlbums) {
            $('#scanprogressbar').fadeOut();
            var targetDiv = document.getElementById('gallery_list');
            if (targetDiv) {
              targetDiv.innerHTML = '';
              Albums.display(targetDiv);
            } else {
              alert('Error occured: no such layer `gallery_list`');
            }
            $('#g-scan-button').attr('disabled', null);
          }
        });
      }
    } else {
      alert('Error occured: ' + r.message);
    }
  });
}

function galleryRemove(albumName) {
  OC.dialogs.confirm(t('gallery', 'Do you want to remove album ') + decodeURIComponent(escape(albumName)),
                     t('gallery', 'Remove confirmation'),
                     function(decision) {
    if (decision) {
      $.getJSON("ajax/galleryOp.php", {operation: "remove", name: decodeURIComponent(escape(albumName))}, function(r) {
        if (r.status == "success") {
          $(".gallery_album_box").filterAttr('data-album',albumName).remove();
          Albums.remove(albumName);
        } else {
          OC.dialogs.alert(r.cause, "Error");
        }
      });
    }
  });
}

function galleryRename(name) {
  OC.dialogs.prompt(t('gallery', 'New album name'),
                    t('gallery', 'Change name'),
                    name,
                    function(newname) {
    if (newname == name || newname == '') return;
    if (Albums.find(newname)) {
      OC.dialogs.alert('Album ' + newname + ' exists', 'Alert');
      return;
    }
    $.getJSON('ajax/galleryOp.php', {operation: 'rename', oldname: name, newname: newname}, function(r) {
      if (r.status == 'success') {
        Albums.rename($(".gallery_album_box").filterAttr('data-album',name), newname);
      } else {
        OC.dialogs.alert('Error: ' + r.cause, 'Error');
      }
    });
  });
}

function settings() {
  $( '#g-dialog-settings' ).dialog({
        height: 180,
        width: 350,
        modal: false,
        buttons: [{
            text: t('gallery', 'Apply'),
            click: function() {
              var scanning_root = $('#g-scanning-root').val();
              var disp_order = $('#g-display-order option:selected').val();
              if (scanning_root == '') {
                alert('Scanning root cannot be empty');
                return;
              }
              $.getJSON('ajax/galleryOp.php', {operation: 'store_settings', root: scanning_root, order: disp_order}, function(r) {
                if (r.status == 'success') {
                  if (r.rescan == 'yes') { 
                    $('#g-dialog-settings').dialog('close');
                    Albums.clear(document.getElementById('gallery_list'));
                    scanForAlbums(true);
                    return;
                  }
                } else {
                  alert('Error: ' + r.cause);
                  return;
                }
                $('#g-dialog-settings').dialog('close');
              });
            }
          },
          {
            text: t('gallery', 'Cancel'),
            click: function() {
              $(this).dialog('close');
            }
          }
        ],
  });
}
