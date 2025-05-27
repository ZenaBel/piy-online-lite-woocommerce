jQuery(document).ready(function($){

	var ajaxurl = info.admin_url;

	initEmojiPicker();

	$(document).on('click', '#poe_submit', function (e) {
		e.preventDefault();

		const ribbonText = jQuery('#po_text').val();
		const addRibbon = true; // або визначати, чи обрано стрічку

		jQuery.post(ajaxurl, {
			action: 'poe_submit',
			add_ribbon: addRibbon,
			ribbon_text: ribbonText
		}, function (response) {
			jQuery('body').trigger('update_checkout');
			jQuery('button.button[name="update_cart"]').attr('disabled', false);
		});
	});
	// Видалення стрічки
	$(document).on('click', '#poe_remove_ribbon', function() {
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'poe_remove_ribbon'
			},
			success: function() {
				// Очистити поле введення
				$('#po_text').val('');
				jQuery('button.button[name="update_cart"]').attr('disabled', false);
			}
		});
	});


	// Акордеон
	$('.show-ribbon-field').on('click', function(e) {
		e.preventDefault();
		$('.ribbon-field').slideToggle();
	});

	// Додати стрічку
	$('#add_ribbon_btn').on('click', function() {
		var ribbonText = $('#ribbon_text_input').val();

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'poe_submit',
				add_ribbon: true,
				ribbon_text: ribbonText
			},
			success: function() {
				$('body').trigger('update_checkout');
				$('#ribbon_text_input').val('');
			}
		});
	});

	// Видалити стрічку
	$('#remove_ribbon_btn').on('click', function() {
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'poe_remove_ribbon'
			},
			success: function() {
				$('body').trigger('update_checkout');
				$('#ribbon_text_input').val('');
			}
		});
	});
});

jQuery(document).on('updated_wc_div', function() {
	initEmojiPicker();
});

jQuery(document).on('wc_fragments_loaded', function() {
	initEmojiPicker();
});

function initEmojiPicker() {
	const inputField = jQuery('#po_text, #ribbon_text_input');
	// Якщо поле не знайдено, виходимо
	if (!inputField.length) return;

	const pickerOptions = {
		onEmojiSelect: function(emoji) {
			const cursorPos = inputField[0].selectionStart;
			const currentValue = inputField.val();
			const newValue =
				currentValue.substring(0, cursorPos) +
				emoji.native +
				currentValue.substring(cursorPos);
			inputField.val(newValue);
			const newCursorPos = cursorPos + emoji.native.length;
			inputField[0].setSelectionRange(newCursorPos, newCursorPos);
		}
	};

	let emoji_picker = jQuery('#emoji-picker');
	const picker = new EmojiMart.Picker(pickerOptions);

	emoji_picker.empty().append(picker); // Очищаємо перед додаванням нового
	emoji_picker.hide();

	inputField.off('focus').on('focus', function() {
		emoji_picker.show();
	});

	jQuery(document).on('click', function(event) {
		if (event.target !== inputField[0] && event.target !== emoji_picker) {
			emoji_picker.hide();
		}
	});
}
