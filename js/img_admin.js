(function($){
	$(document).ready(function() {
		var custom_thumb_file_frame;
		//image uploads
		$(document).on('click', '.upld_img', function(e) {
			e.preventDefault();
			var $this=$(this);
			var objId=$this.attr('id');
			var fieldId=objId.replace('_button','');
			var mdTtl=$this.attr('media_ttl');
			if (typeof custom_thumb_file_frame === 'undefined') {
				custom_thumb_file_frame = wp.media.frames.customHeader = wp.media({
					title: mdTtl,
					library: {
						type: 'image'
					},
					button: {
						text: 'Load '+mdTtl
					},
					multiple: false
				});
				
				custom_thumb_file_frame.on('select', function() {
					var attachment = custom_thumb_file_frame.state().get('selection').first().toJSON();
					setTimeout(function() {
						console.log(attachment);
						$('#'+fieldId).val(attachment.id);
						$.post(ajaxurl,
							{obj_id:attachment.id,elm_id:fieldId,action:'yg_get_thumb_url'},
							function(response) {
								$('#'+fieldId+'_holder').html(response);
						});
						custom_thumb_file_frame = undefined;
					}, 20);
				});
			}
			if (typeof(custom_thumb_file_frame)!=="undefined") {
				custom_thumb_file_frame.open();
			}
			//custom_thumb_file_frame.open();
		});
		//image delete
		$(document).on('click', '.dlt_img', function() {
			var $this = $(this);
			var objId=$this.attr('attr_id');
			var imgId=objId+'_img';
			setTimeout(function() {
				if($('#'+objId).length)
					$('#'+objId).val('');
				if($('#'+imgId).length)
					$('#'+imgId).remove();
			}, 20);
		});
	});
})(jQuery);