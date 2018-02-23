jQuery( function ( $ ) {

	$( '.key-label input' ).bind( 'input keydown change', function() {
		var keyLabel = $( this ).val();
		keyLabel = keyLabel.toLowerCase().replace( /\W+/g,'_' );

		if ( !$( '.machine-name' ).hasClass('disabled') ){
			$( '.machine-name input' ).val( keyLabel );
			$( '.machine-name-label a' ).text( keyLabel );
		}
	});
	$( '.machine-name input' ).change( function() {
		var keyLabel = $( this ).val();
		keyLabel = keyLabel.toLowerCase().replace( /\W+/g,'_' );
		$( this ).val( keyLabel );
	});
	$( '.show-key-name' ).click( function() {
		$( '.machine-name' ).removeClass( 'hidden' );
		return false;
	});
	$( '#create_key' ).click( function() {
		if ( !$('#key_value').attr('disabled') ) {
			$('#key_value').attr('disabled', 'disabled');
		}
		else {
			$('#key_value').removeAttr('disabled', 'disabled');
		}
	});
	$(document).on('change', '.option-override-select',  function(){

		var optionNumber = parseInt( $(this).attr('name').slice(-1) );
		var optionsTotal = $('#option-total-number').val();
		var optionValue = $(this).find(':selected').data('option-value');

		for (i = optionNumber + 1; i < optionsTotal + 1; i++) {
			$('#option-override-select-' + i).remove();
		}
		if ( !$.isPlainObject(optionValue) ){
			try {
				var value = $.parseJSON(optionValue);
			} catch (e) {
				var value = optionValue;
			}
			$('#key_value').val(value);
			$("#option-total-number").val(optionNumber);
		}
		else {
			$('#key_value').val('');
			optionNumber++;
			var html = '<select name="option_value_' + optionNumber + '" id="option-override-select-' + optionNumber + '" class="option-override-select"><option value="">Choose a sub-option</option>';
			$.each(optionValue, function(subOption, subValue){
				if ( !$.isPlainObject(subValue) ){
					html += '<option value="' + subOption + '" data-option-value="' + subValue + '">' + subOption + '</option>';
				}
				else {
					subValue = JSON.stringify(subValue);
					html += '<option value="' + subOption + '" data-option-value=\'' + subValue + '\'>' + subOption + '</option>';
				}
			});
			html += '</select>';
			$(this).after(html);
			$("#option-total-number").val(optionNumber);
		}
	});
});
