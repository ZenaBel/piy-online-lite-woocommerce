jQuery(document).ready(function($){
	
		  document.getElementById("po_gen_price").addEventListener("input", function() {
			this.value = this.value.replace(/,/g, '.');
		  });
	
		var ajaxurl = info.admin_url;
		var nonce   = info.nonce;
		// multiple select with AJAX search
		$('.po_gen_products').select2({
			ajax: {
				url: ajaxurl, // AJAX URL is predefined in WordPress admin
				dataType: 'json',
				type: 'POST',
				delay: 250, // delay in ms while typing when to perform a AJAX search
				data: function (params) {
					return {
						q: params.term, // search query
						action: 'pogetproducts', // AJAX action for admin-ajax.php
						nonce: nonce
					};
				},
				processResults: function( data ) {

					var options = [];
					if ( data ) {
	 
						// data is the array of arrays, and each of them contains ID and the Label of the option
						$.each( data, function( index, text ) { // do not forget that "index" is just auto incremented value
							options.push( { id: text[0], text: text[1]  } );
						});

					}
					return {
						results: options
					};
				},
				cache: true
			},
			minimumInputLength: 3 // the minimum of symbols to input before perform a search
		});
	});

	jQuery(document).ready(function($){
		$('.po_gen_categories, .po_gen_tags, .po_gen_attributes').select2();
	});
