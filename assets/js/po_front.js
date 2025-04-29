jQuery(document).ready(function($){

	var ajaxurl = info.admin_url;
	var nonce   = info.nonce;

	let emoji_picker = '';


	$("#po_show_fields").on("click", function() {

		const product_id = $(this).attr('data-product_id');
		const request_sent = $(this).attr('data-sent');
		const enable_by = $(this).attr('data-enable_by');
		const term_id = $(this).attr('data-term');
		const checkbox = $(this);


		if('false' === request_sent && $(checkbox).is(':checked') ){
			$(document.body).css({'cursor' : 'wait'});
			$('.po-display-fields').empty();
			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action   : 'get_fields',
					nonce    : nonce,
					product_id : product_id,
					term_id : term_id,
					enable_by : enable_by
				},
				success: function (response) {
					$(document.body).css({'cursor' : 'default'});
					$(checkbox).attr('data-sent', 'true');
					$('.po-display-fields').append(response['data']);

					// Get the input field
					let inputField = $('#po_text');
					let emoji_picker = $('#emoji-picker');


					let emoji_enable = $(inputField).attr('data-emoji-picker');

					if ('yes' === emoji_enable) {

						// const pickerOptions = { onEmojiSelect: function(emoji) {
                    	// 	let val  = $(input_field).val();
                    	// 	$(input_field).val(val+= emoji.native);
		                // } }
						// const picker = new EmojiMart.Picker(pickerOptions);
						// $(emoji_picker).append(picker);
						const inputField = $('#po_text');
						const pickerOptions = {
							onEmojiSelect: function(emoji) {
								// Get the cursor position
								const cursorPos = inputField[0].selectionStart;
								// Insert the selected emoji at the cursor position
								const currentValue = inputField.val();
								const newValue =
									currentValue.substring(0, cursorPos) +
									emoji.native +
									currentValue.substring(cursorPos);
								inputField.val(newValue);
								// Move the cursor position after the inserted emoji
								const newCursorPos = cursorPos + emoji.native.length;
								inputField[0].setSelectionRange(newCursorPos, newCursorPos);

							}
						};

						const picker = new EmojiMart.Picker(pickerOptions);
						$(emoji_picker).append(picker);
						$(emoji_picker).hide();

						$(inputField).focus(function(){
							$(emoji_picker).show();
						});

						$(document).on('click', function(event) {
							if (event.target !== inputField[0] && event.target !== emoji_picker) {
								$(emoji_picker).hide();
							}
						});
					}
				},
				error: function (response) {
					$(document.body).css({'cursor' : 'default'});
					$('.po-display-fields').append(response['error']);
				}
			});
		}else if('true' === request_sent) {
			if ($(checkbox).is(':checked')) {
				$('.po-display-fields').show();
			}else{
				$('.po-display-fields').hide();
			}
		}

	});
});
