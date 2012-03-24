var actual_cover;
var paths = [];
var crumbCount = 0;
$(document).ready(returnToElement(0));

function returnToElement(num) {
	while (crumbCount != num) {
		$('#g-album-navigation .last').remove();
		$('#g-album-navigation .crumb :last').parent().addClass('last');
		crumbCount--;
		paths.pop();
	}
	var p='';
	for (var i in paths) p += paths[i]+'/';
	$('#g-album-loading').show();
	$.getJSON(OC.filePath('gallery','ajax','galleryOp.php'), {operation: 'get_gallery', path: p }, albumClickHandler);
}

function albumClick(title) {
	paths.push(title);
	crumbCount++;
	var p = '';
	for (var i in paths) p += paths[i]+'/';
	$('#g-album-loading').show();
	$.getJSON(OC.filePath('gallery','ajax','galleryOp.php'), {operation: 'get_gallery', path: p }, function(r) {
		albumClickHandler(r);
	if ($('#g-album-navigation :last-child'))
		$('#g-album-navigation :last-child').removeClass('last');
	$('#g-album-navigation').append('<div class="crumb last real" style="background-image:url(\''+OC.imagePath('core','breadcrumb')+'\')"><a href=\"javascript:returnToElement('+crumbCount+');\">'+decodeURIComponent(escape(title))+'</a></div>');
	});
}

function shareGallery() {
  var existing_token = '';
  if (Albums.token)
    existing_token = document.location.origin + OC.linkTo('gallery', 'sharing.php') + '?token=' + Albums.token;
  var form_fields = [{text: 'Share', name: 'share', type: 'checkbox', value: Albums.shared},
                     {text: 'Share recursive', name: 'recursive', type: 'checkbox', value: Albums.recursive},
                     {text: 'Shared gallery address', name: 'address', type: 'text', value: existing_token}];
    OC.dialogs.form(form_fields, t('gallery', 'Share gallery'), function(values){
    var p = '';
    for (var i in paths) p += '/'+paths[i];
    if (p == '') p = '/';
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
          actual_addr = document.location.origin + OC.linkTo('gallery', 'sharing.php') + '?token=' + Albums.token;
        $('input[name="address"]').val(actual_addr);
      } else {
        OC.dialogs.alert(t('gallery', 'Error: ') + r.cause, t('gallery', 'Internal error'));
      }
    });
  });
}

function albumClickHandler(r) {
	Albums.photos = [];
	Albums.albums = [];
	if (r.status == 'success') {
		for (var i in r.albums) {
		var a = r.albums[i];
			Albums.add(a.name, a.numOfItems, a.path, a.shared, a.recursive, a.token);
		}
		for (var i in r.photos) {
			Albums.photos.push(r.photos[i]);
		}
    Albums.shared = r.shared;
    if (Albums.shared) {
      Albums.recursive = r.recursive;
      Albums.token = r.token;
    } else {
      Albums.recursive = false;
      Albums.token = '';
    }
		var targetDiv = document.getElementById('gallery_list');
		if (targetDiv) {
			$(targetDiv).html('');
			Albums.display(targetDiv);
			//$('#gallery_list').sortable({revert:true});
			$('.album').each(function(i, el) {
				$(el).click(albumClick.bind(null,$(el).attr('title')));
				//$(el).draggable({connectToSortable: '#gallery_list', handle: '.dummy'});
			});
		} else {
			OC.dialogs.alert(t('gallery', 'Error: no such layer `gallery_list`'), t('gallery', 'Internal error'));
		}
	} else {
		OC.dialogs.alert(t('gallery', 'Error: ') + r.cause, t('gallery', 'Internal error'));
	}
	$('#g-album-loading').hide();
}

var albumCounter = 0;
var totalAlbums = 0;

function scanForAlbums(cleanup) {
	Scanner.scanAlbums();
	return;
}

function settings() {
	$( '#g-dialog-settings' ).dialog({
		height: 180,
		width: 350,
		modal: false,
		buttons: [
			{
				text: t('gallery', 'Apply'),
				click: function() {
					var scanning_root = $('#g-scanning-root').val();
					var disp_order = $('#g-display-order option:selected').val();
					if (scanning_root == '') {
						alert('Scanning root cannot be empty');
						return;
					}
					$.getJSON(OC.filePath('gallery','ajax','galleryOp.php'), {operation: 'store_settings', root: scanning_root, order: disp_order}, function(r) {
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
