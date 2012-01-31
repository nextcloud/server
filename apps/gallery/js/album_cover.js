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

function scanForAlbums() {
  var albumCounter = 0;
  var totalAlbums = 0;
  $.getJSON('ajax/galleryOp.php?operation=filescan', function(r) {

    if (r.status == 'success') {
      totalAlbums = r.paths.length;
      if (totalAlbums == 0) {
        $('#notification').text(t('gallery', "No photos found")).fadeIn().slideDown().delay(3000).fadeOut().slideUp();
        return;
      }
      $('#scanprogressbar').progressbar({ value: (albumCounter/totalAlbums)*100 }).fadeIn();
      for(var a in r.paths) {
        $.getJSON('ajax/galleryOp.php?operation=partial_create&path='+r.paths[a], function(r) {

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
          }
        });
      }
    } else {
      alert('Error occured: ' + r.message);
    }
  });
}

function galleryRemove(albumName) {
  // a workaround for a flaw in the demo system (http://dev.jqueryui.com/ticket/4375), ignore!
  $( "#dialog:ui-dialog" ).dialog( "destroy" );
  $('#albumName', $("#dialog-confirm")).text(albumName);

  $( '#dialog-confirm' ).dialog({
    resizable: false,
    height:150,
    buttons: [{
        text: t('gallery', 'OK'),
        click: function() {
          $.getJSON("ajax/galleryOp.php", {operation: "remove", name: albumName}, function(r) {
            if (r.status == "success") {
            $(".gallery_album_box").filterAttr('data-album',albumName).remove();
              Albums.remove(albumName);
            } else {
              alert("Error: " + r.cause);
            }
            $('#dialog-confirm').dialog('close');
          });
        }},
        {
          text: t('gallery', 'Cancel'),
          click: function() {
            $( this ).dialog( 'close' );
        }}]
  });
}

function galleryRename(name) {
  $('#name', $('#dialog-form')).val(name);
  $( "#dialog-form" ).dialog({
        height: 140,
        width: 350,
        modal: false,
        buttons: [{
            text: t('gallery', 'Change name'),
            click: function() {
              var newname = $('#name', $('#dialog-form')).val();
              if (newname == name || newname == '') {
                $(this).dialog("close");
                return;
              }
              if (Albums.find(newname)) {
                alert("Album ", newname, " exists");
                $(this).dialog("close");
                return;
              }
              $.getJSON("ajax/galleryOp.php", {operation: "rename", oldname: name, newname: newname}, function(r) {
                if (r.status == "success") {
                  Albums.rename($(".gallery_album_box").filterAttr('data-album',name), newname);
                } else {
                  alert("Error: " + r.cause);
                }
                $('#dialog-form').dialog("close");
              });

            }
          },
          {
            text: t('gallery', 'Cancel'),
            click: function() {
              $( this ).dialog( "close" );
            }
          }
        ],
  });
}

