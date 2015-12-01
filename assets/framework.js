( function( exports, wp ) {

	exports.init = function() {
		var settings_keys = Object.keys( exports.settings );

		for ( var i in settings_keys ) {
			var setting_slug = settings_keys[ i ];
			var setting_args = exports.settings[ setting_slug ];
			var setting_function;
			var setting_timeout = 0;
			var setting_preprocess_function;
			var setting_preprocess_args = {};

			if ( typeof setting_args.callback !== 'undefined' ) {
				if ( typeof setting_args.callback === 'string' && typeof exports.callbacks[ setting_args.callback ] === 'function' ) {
					setting_function = exports.callbacks[ setting_args.callback ];
				} else if ( typeof setting_args.callback === 'function' ) {
					setting_function = setting_args.callback;
				}
			}

			if ( typeof setting_args.timeout !== 'undefined' ) {
				setting_timeout = parseInt( setting_args.timeout, 10 );
			}

			if ( typeof setting_args.preprocess_callback !== 'undefined' ) {
				if ( typeof setting_args.preprocess_callback === 'string' && typeof exports.callbacks[ setting_args.preprocess_callback ] === 'function' ) {
					setting_preprocess_function = exports.preprocessors[ setting_args.preprocess_callback ];
				} else if ( typeof setting_args.preprocess_callback === 'function' ) {
					setting_preprocess_function = setting_args.preprocess_callback;
				}
			}

			if ( typeof setting_args.preprocess_args === 'object' && typeof setting_args.preprocess_args.length === 'undefined' ) {
				setting_preprocess_args = setting_args.preprocess_args;
			}

			if ( setting_function ) {
				exports.bind_setting( setting_slug, setting_function, setting_args.data, setting_timeout, setting_preprocess_function, setting_preprocess_args );
			}
		}
	};

	exports.bind_setting = function( setting_slug, setting_function, setting_data, setting_timeout, setting_preprocess_function, setting_preprocess_args ) {
		function preprocess_and_update( setting_value ) {
			if ( setting_preprocess_function ) {
				setting_preprocess_function.call( setting_value, function( val ) {
					exports.update_setting( setting_slug, setting_function, val, setting_data );
				}, setting_preprocess_args );
			} else {
				exports.update_setting( setting_slug, setting_function, setting_value, setting_data );
			}
		}

		if ( 0 < setting_timeout ) {
			wp.customize( setting_slug, function( value ) {
				var intent;

				value.bind( function( to ) {
					if ( typeof intent !== 'undefined' ) {
						window.clearTimeout( intent );
					}

					intent = window.setTimeout( function() {
						preprocess_and_update( to );
					}, setting_timeout );
				});
			});
		} else {
			wp.customize( setting_slug, function( value ) {
				value.bind( function( to ) {
					preprocess_and_update( to );
				});
			});
		}
	};

	exports.update_setting = function( slug, callback, value, args ) {
		callback.call( value, args, slug );
	};

})( wpcd_customizer, wp );
