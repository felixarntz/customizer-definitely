( function( exports, $ ) {

	exports.callbacks.update_style = function( value, args ) {
		//TODO: break style tag into per setting tags, make sure to include after the Customizer CSS file (give 'data-scope="customizer"' attribute to all the tags)
		if ( $( '#wpcd-customizer-styles' ).length < 1 ) {
			$( 'head' ).append( '<style type="text/css" id="wpcd-customizer-styles"></style>' );
		}

		var style_content = $( '#wpcd-customizer-styles' ).text();

		for ( var i in args ) {
			var data = args[ i ];

			if ( data.selectors.length > 0 && data.property ) {
				style_content = _update_single_style( style_content, value, data );
			}
		}

		$( '#wpcd-customizer-styles' ).text( style_content );
	};

	exports.callbacks.update_attr = function( value, args ) {
		for ( var i in args ) {
			var data = args[ i ];

			if ( data.selectors.length > 0 && data.property ) {
				_update_single_attr( value, data );
			}
		}
	};

	exports.callbacks.update_content = function( value, args ) {
		for ( var i in args ) {
			var data = args[ i ];

			if ( data.selectors.length > 0 ) {
				_update_single_content( value, data );
			}
		}
	};

	function _update_single_style( style_content, value, data ) {
		var re = new RegExp( '^' + data.selectors.join( ', ' ) + ' \{\\n' + '([^]+)\\n\}\\n', 'm' );
		var found = false;

		style_content = style_content.replace( re, function( match, properties ) {
			found = true;

			var subre = new RegExp( '^' + data.property + ': (.+);$', 'm' );
			var subfound = false;

			var new_properties = properties.replace( subre, function( match, property_content ) {
				subfound = true;
				return match.replace( property_content, data.prefix + value + data.suffix );
			});

			if ( ! subfound ) {
				new_properties += '\n' + data.property + ': ' + data.prefix + value + data.suffix + ';';
			}

			return match.replace( properties, new_properties );
		});

		if ( ! found ) {
			if ( '' !== style_content ) {
				style_content += '\n';
			}
			style_content += data.selectors.join( ', ' ) + ' {\n' + data.property + ' ' + data.prefix + value + data.suffix + ';\n}\n';
		}

		return style_content;
	}

	function _update_single_attr( value, data ) {
		$( data.selectors.join( ', ' ) ).each( function() {
			$( this ).attr( data.property, data.prefix + value + data.suffix );
		});
	}

	function _update_single_content( value, data ) {
		$( data.selectors.join( ', ' ) ).each( function() {
			if ( 'html' === data.property ) {
				$( this ).html( data.prefix + value + data.suffix );
			} else {
				$( this ).text( data.prefix + value + data.suffix );
			}
		});
	}

})( wpcd_customizer, jQuery );
