( function( exports, wp ) {

	exports.init = function() {
		var settings_keys = Object.keys( exports.settings );

		for ( var i in settings_keys ) {
			var setting_slug = settings_keys[ i ];
			var setting_args = exports.settings[ setting_slug ];

			var update_callback;
			var update_args = {};
			var timeout = 0;
			var preprocess_callback;
			var preprocess_args = {};

			if ( typeof setting_args.update_callback !== 'undefined' ) {
				if ( typeof setting_args.update_callback === 'string' && typeof exports.update_callbacks[ setting_args.update_callback ] === 'function' ) {
					update_callback = exports.update_callbacks[ setting_args.update_callback ];
				} else if ( typeof setting_args.update_callback === 'function' ) {
					update_callback = setting_args.update_callback;
				}
			}

			if ( typeof setting_args.update_args === 'object' ) {
				update_args = setting_args.update_args;
			}

			if ( typeof setting_args.timeout !== 'undefined' ) {
				timeout = parseInt( setting_args.timeout, 10 );
			}

			if ( typeof setting_args.preprocess_callback !== 'undefined' ) {
				if ( typeof setting_args.preprocess_callback === 'string' && typeof exports.preprocess_callbacks[ setting_args.preprocess_callback ] === 'function' ) {
					preprocess_callback = exports.preprocess_callbacks[ setting_args.preprocess_callback ];
				} else if ( typeof setting_args.preprocess_callback === 'function' ) {
					preprocess_callback = setting_args.preprocess_callback;
				}
			}

			if ( typeof setting_args.preprocess_args === 'object' ) {
				preprocess_args = setting_args.preprocess_args;
			}

			if ( update_callback ) {
				exports.bind_setting( setting_slug, update_callback, update_args, timeout, preprocess_callback, preprocess_args );
			}
		}
	};

	exports.bind_setting = function( setting_slug, update_callback, update_args, timeout, preprocess_callback, preprocess_args ) {
		function preprocess_and_update( setting_value ) {
			if ( preprocess_callback ) {
				preprocess_callback.call( setting_value, function( val ) {
					exports.update_setting( setting_slug, update_callback, val, update_args );
				}, preprocess_args );
			} else {
				exports.update_setting( setting_slug, update_callback, setting_value, update_args );
			}
		}

		if ( 0 < timeout ) {
			wp.customize( setting_slug, function( value ) {
				var intent;

				value.bind( function( to ) {
					if ( typeof intent !== 'undefined' ) {
						window.clearTimeout( intent );
					}

					intent = window.setTimeout( function() {
						preprocess_and_update( to );
					}, timeout );
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
		callback.call( undefined, value, args, slug );
	};

	exports.init();

})( wpcd_customizer, wp );
