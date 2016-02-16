( function( exports, wp, console, $ ) {

	function _update_single_style( value, data, slug ) {
		var style = '';

		if ( '' !== data.media_query ) {
			style += data.media_query + ' {\n';
			style += '\t' + data.selectors.join( ', ' ) + ' {\n';
			style += '\t\t' + data.property + ': ' + data.prefix + value + data.suffix + ';\n';
			style += '\t}\n';
			style += '}\n\n';
		} else {
			style += data.selectors.join( ', ' ) + ' {\n';
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

	exports.util.ajax_preprocess = function( action, data, callback ) {
		if ( 'function' !== typeof callback ) {
			console.error( 'Missing update callback.' );
			return;
		}

		wp.ajax.post( action, $.extend({
			nonce: exports.util.preprocess_nonce
		}, data ) )
		.done( function( processed ) {
			callback( processed );
		})
		.fail( function( err ) {
			console.error( err );
			callback( '' );
		});
	};

	//TODO: implement AJAX PHP functions

	exports.preprocess_callbacks.post_id_to_field = function( value, callback, args ) {
		if ( ! value ) {
			callback( '' );
			return;
		}

		exports.util.ajax_preprocess( 'wpcd-post-id-to-field', {
			id: parseInt( value, 10 ),
			mode: args.mode || 'post', // or 'meta'
			field: args.field || 'title'
		}, callback );
	};

	exports.preprocess_callbacks.get_attachment_url = function( value, callback, args ) {
		if ( ! value ) {
			callback( '' );
			return;
		}

		var data = {
			id: value,
			size: args.size || 'full'
		};

		// if field contains URL and the 'full' size is needed, no processing required
		if ( 'number' !== typeof value && 'full' === data.size ) {
			callback( value );
			return;
		}

		exports.util.ajax_preprocess( 'wpcd-get-attachment-url', data, callback );
	};

	exports.preprocess_callbacks.number_format_i18n = function( value, callback, args ) {
		exports.util.ajax_preprocess( 'wpcd-number-format-i18n', {
			number: value,
			decimals: args.decimals || 0
		}, callback );
	};

	exports.preprocess_callbacks.date_i18n = function( value, callback, args ) {
		if ( ! value ) {
			callback( '' );
			return;
		}

		exports.util.ajax_preprocess( 'wpcd-date-i18n', {
			date: value,
			format: args.format || 'Y-m-d'
		}, callback );
	};

	exports.preprocess_callbacks.content_format = function( value, callback, args ) {
		if ( ! value ) {
			callback( '' );
			return;
		}

		exports.util.ajax_preprocess( 'wpcd-content-format', {
			content: value,
			filters: args.filters || []
		}, callback );
	};

	exports.preprocess_callbacks.value_to_label = function( value, callback, args ) {
		function vtol( v ) {
			if ( 'label' === args.mode && 'undefined' !== typeof args.labels && 'undefined' !== typeof args.labels[ v ] ) {
				return args.labels[ v ];
			}
			return v;
		}

		if ( 'object' === typeof value ) {
			callback( value.map( vtol ).join( ', ' ) );
		} else {
			callback( vtol( value ) );
		}
	};

	exports.preprocess_callbacks.do_not_process = function( value, callback, args ) {
		if ( ! value ) {
			callback( '' );
			return;
		}

		callback( value );
	};

	exports.update_callbacks.update_style = function( value, args, slug ) {
		var sanitized_slug = exports.util.sanitize_slug( slug );

		if ( $( '#wpcd-customizer-style-' + sanitized_slug ).length < 1 ) {
			$( 'head' ).append( '<style type="text/css" id="wpcd-customizer-style-' + sanitized_slug + '"></style>' );
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

		$( '#wpcd-customizer-style-' + sanitized_slug ).text( style_content );
	};

	exports.update_callbacks.update_attr = function( value, args, slug ) {
		for ( var i in args ) {
			var data = args[ i ];

			if ( data.selectors.length > 0 && data.property ) {
				_update_single_attr( value, data, slug );
			}
		}
	};

	exports.update_callbacks.update_content = function( value, args, slug ) {
		for ( var i in args ) {
			var data = args[ i ];

			if ( data.selectors.length > 0 ) {
				_update_single_content( value, data, slug );
			}
		}
	};

})( wpcd_customizer, wp, console, jQuery );
