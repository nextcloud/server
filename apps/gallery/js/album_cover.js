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

function constructSharingPath() {
  return document.location.protocol + '//' + document.location.host + OC.linkTo('', 'public.php') + '?service=gallery&token=' + Albums.token;
}

function shareGallery() {
  var existing_token = '';
  if (Albums.token)
    existing_token = constructSharingPath();
  var form_fields = [{text: 'Share', name: 'share', type: 'checkbox', value: Albums.shared},
                     {text: 'Share recursive', name: 'recursive', type: 'checkbox', value: Albums.recursive},
                     {text: 'Shared gallery address', name: 'address', type: 'text', value: existing_token}];
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
		$(document).ready(function(){
			var targetDiv = $('#gallery_list');
			targetDiv.html('');
			Albums.display(targetDiv);
			//$('#gallery_list').sortable({revert:true});
			$('.album').each(function(i, el) {
				$(el).click(albumClick.bind(null,$(el).attr('title')));
				//$(el).draggable({connectToSortable: '#gallery_list', handle: '.dummy'});
			});
		});
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
  OC.dialogs.form([{text: t('gallery', 'Scanning root'), name: 'root', type:'text', value:gallery_scanning_root},
                  {text: t('gallery', 'Default order'), name: 'order', type:'select', value:gallery_default_order, options:[
                      {text:t('gallery', 'Ascending'), value:'ASC'}, {text: t('gallery', 'Descending'), value:'DESC'} ]}],
                  t('gallery', 'Settings'),
                  function(values) {
                    var scanning_root = values[0].value;
                    var disp_order = values[1].value;
					if (scanning_root == '') {
            OC.dialogs.alert(t('gallery', 'Scanning root cannot be empty'), t('gallery', 'Error'));
						return;
					}
					$.getJSON(OC.filePath('gallery','ajax','galleryOp.php'), {operation: 'store_settings', root: scanning_root, order: disp_order}, function(r) {
						if (r.status == 'success') {
              if (r.rescan == 'yes') {
                Albums.clear(document.getElementById('gallery_list'));
                scanForAlbums(true);
              }
              gallery_scanning_root = scanning_root;
						} else {
              OC.dialogs.alert(t('gallery', 'Error: ') + r.cause, t('gallery', 'Error'));
              return;
						}
					});
				});
}
