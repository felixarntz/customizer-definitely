( function( exports, wp ) {

	exports.init = function() {
		var settings_keys = Object.keys( exports.settings );

		for ( var i in settings_keys ) {
			var setting_slug = settings_keys[ i ];
			var setting_args = exports.settings[ setting_slug ];
			var setting_function;
			var setting_timeout = 0;

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

			if ( setting_function ) {
				exports.bind_setting( setting_slug, setting_function, setting_args.data, setting_timeout );
			}
		}
	};

	exports.bind_setting = function( setting_slug, setting_function, setting_data, setting_timeout ) {
		if ( 0 < setting_timeout ) {
			wp.customize( setting_slug, function( style ) {
				var intent;

				style.bind( function( value ) {
					if ( typeof intent !== 'undefined' ) {
						window.clearTimeout( intent );
					}

					intent = window.setTimeout( function() {
						exports.update_setting( setting_function, value, setting_data );
					}, setting_timeout );
				});
			});
		} else {
			wp.customize( setting_slug, function( style ) {
				style.bind( function( value ) {
					exports.update_setting( setting_function, value, setting_data );
				});
			});
		}
	};

	exports.update_setting = function( callback, value, args ) {
		callback.call( value, args );
	};

})( wpcd_customizer, wp );
