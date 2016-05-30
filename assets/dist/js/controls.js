/*!
 * Customizer Definitely (https://github.com/felixarntz/customizer-definitely)
 * By Felix Arntz (https://leaves-and-love.net)
 * Licensed under GNU General Public License v3 (http://www.gnu.org/licenses/gpl-3.0.html)
 */
( function( wp, $ ) {

	var api = wp.customize;

	api.WPDLibColorControl = api.Control.extend({
		ready: function() {
			var control = this;
			var input = this.container.find( '.wpdlib-input-color' );

			input.wpColorPicker({
				change: function() {
					control.setting.set( input.wpColorPicker( 'color' ) );
				},
				clear: function() {
					control.setting.set( '' );
				}
			});

			control.setting.bind( function( value ) {
				input.val( value );
				input.wpColorPicker( 'color', value );
			});
		}
	});

	api.WPDLibMediaControl = api.Control.extend({
		ready: function() {
			var control = this;
			var input = this.container.find( '.wpdlib-input-media' );

			input.wpMediaPicker({
				change: function() {
					control.setting.set( input.wpMediaPicker( 'value' ) );
				},
				clear: function() {
					control.setting.set( '' );
				}
			});

			/*control.setting.bind( function( value ) {
				input.val( value );
				input.wpMediaPicker( 'value', value );
			});*/
		}
	});

	api.WPDLibMapControl = api.Control.extend({
		ready: function() {
			var control = this;
			var input = this.container.find( '.wpdlib-input-map' );

			input.wpMapPicker({
				change: function() {
					control.setting.set( input.wpMapPicker( 'value' ) );
				}
			});

			api.section( control.section() ).container.on( 'expanded', function() {
				input.wpMapPicker( 'refresh' );
			});

			/*control.setting.bind( function( value ) {
				input.val( value );
				input.wpMapPicker( 'value', value );
			});*/
		}
	});

	api.controlConstructor.wpdlib_color = api.WPDLibColorControl;
	api.controlConstructor.wpdlib_media = api.WPDLibMediaControl;
	api.controlConstructor.wpdlib_map = api.WPDLibMapControl;

})( wp, jQuery );
