( function( exports, $ ) {

	function _update_single_style( value, data, slug ) {
		var style = '';

		if ( '' !== data.media_query ) {
			style += data.media_query + '{\n';
			style += '\t' + data.selectors.join( ', ' ) + '{\n';
			style += '\t\t' + data.property + ': ' + data.prefix + value + data.suffix + ';\n';
			style += '\t}\n';
			style += '}\n\n';
		} else {
			style += data.selectors.join( ', ' ) + '{\n';
			style += '\t' + data.property + ': ' + data.prefix + value + data.suffix + ';\n';
			style += '}\n\n';
		}

		return style;
	}

	function _update_single_attr( value, data, slug ) {
		$( data.selectors.join( ', ' ) ).each( function() {
			$( this ).attr( data.property, data.prefix + value + data.suffix );
		});
	}

	function _update_single_content( value, data, slug ) {
		$( data.selectors.join( ', ' ) ).each( function() {
			if ( 'html' === data.property ) {
				$( this ).html( data.prefix + value + data.suffix );
			} else {
				$( this ).text( data.prefix + value + data.suffix );
			}
		});
	}

	exports.util.sanitize_slug = function( slug ) {
		var parts = slug.replace( ']', '' ).split( '[' );

		return parts.join( '-' );
	};

	exports.callbacks.update_style = function( value, args, slug ) {
		var sanitized_slug = exports.util.sanitize_slug( slug );

		if ( $( '#wpcd-style-' + sanitized_slug ).length < 1 ) {
			$( 'head' ).append( '<style type="text/css" id="wpcd-style-' + sanitized_slug + '"></style>' );
		}

		var style_content = '';

		if ( value ) {
			for ( var i in args ) {
				var data = args[ i ];

				if ( data.selectors.length > 0 && data.property ) {
					style_content += _update_single_style( value, data, slug );
				}
			}
		}

		$( '#wpcd-style-' + sanitized_slug ).text( style_content );
	};

	exports.callbacks.update_attr = function( value, args, slug ) {
		for ( var i in args ) {
			var data = args[ i ];

			if ( data.selectors.length > 0 && data.property ) {
				_update_single_attr( value, data, slug );
			}
		}
	};

	exports.callbacks.update_content = function( value, args, slug ) {
		for ( var i in args ) {
			var data = args[ i ];

			if ( data.selectors.length > 0 ) {
				_update_single_content( value, data, slug );
			}
		}
	};

})( wpcd_customizer, jQuery );
