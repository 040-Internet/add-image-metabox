(function($) {

	var doc = $(document);
	var formfield = null;
	var num = '';

  doc.on('click', '.get-image', function(e) {
    e.preventDefault();

    $('html').addClass('image_spe');
		num = $(this).data('num');
		formfield = $('.id_img[data-num="'+num+'"]').attr('name');

    var id = $("#post_ID").val();
    tb_show('', 'media-upload.php?post_id='+id+'&type=image&TB_iframe=true');
  });

  doc.on('click', '.del-image', function() {
		var number = $(this).data('num');
		$('.img-preview[data-num="'+number+'"]').empty();
		$('.id_img[data-num="'+number+'"]').val('');
	});

  // user inserts file into post. only run custom if user started process using the above process
	// window.send_to_editor(html) is how wp would normally handle the received data

	window.original_send_to_editor = window.send_to_editor;
	window.send_to_editor = function(html){
    var fileurl;
		if (formfield) {
			var matches = html.match(/wp-image-([0-9]*)/);
			var imgfull = $(html).find('img').css({width:"150px", height:"150px"});

			$('input[name="'+formfield+'"]').val(matches[1]);
			$('.img-preview[data-num="'+num+'"]').append($(imgfull));

			tb_remove();

			$('html').removeClass('image_spe');

			formfield = null;
			num = null;
		}else{
			window.original_send_to_editor(html);
		}
	}


	doc.on('click', '.add-more-slides, .remove-slides', function() {
		var action = $(this).data('action');
  	var hiddenInput = $('.slide-amount');
		var slideAmount = parseInt(hiddenInput.val());

		if(action == 'add') {
	  	var newAmount = ++slideAmount;
	  	$(hiddenInput).val(newAmount);

	  	var html = '<div class="image-entry"><input type="hidden" name="image'+newAmount+'" id="image'+newAmount+'" class="id_img" data-num="'+newAmount+'"><div class="img-preview" data-num="'+newAmount+'"></div><a class="get-image button-primary" data-num="'+newAmount+'">Add New</a><a class="del-image button-secondary" data-num="'+newAmount+'">Delete</a></div>';

	  	$('#droppable').append(html);
		} else {
	  	var newAmount = --slideAmount;
	  	$(hiddenInput).val(newAmount);

	  	$('#droppable').children().last().remove();
		}

  });

}(jQuery));