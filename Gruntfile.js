'use strict';
module.exports = function(grunt) {
	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),
		banner: '/*!\n' +
				' * <%= pkg.name %> version <%= pkg.version %>\n' +
				' * \n' +
				' * <%= pkg.author.name %> <<%= pkg.author.email %>>\n' +
				' */',
		pluginheader:	'/*\n' +
						'Plugin Name: Customizer Definitely\n' +
						'Plugin URI: <%= pkg.homepage %>\n' +
						'Description: <%= pkg.description %>\n' +
						'Version: <%= pkg.version %>\n' +
						'Author: <%= pkg.author.name %>\n' +
						'Author URI: <%= pkg.author.url %>\n' +
						'License: <%= pkg.license.name %>\n' +
						'License URI: <%= pkg.license.url %>\n' +
						'Text Domain: customizer-definitely\n' +
						'Tags: <%= pkg.keywords.join(", ") %>\n' +
						'*/',
		fileheader:		'/**\n' +
						' * @package WPCD\n' +
						' * @version <%= pkg.version %>\n' +
						' * @author <%= pkg.author.name %> <<%= pkg.author.email %>>\n' +
						' */',

		clean: {
			framework: [
				'assets/framework.min.js'
			],
			functions: [
				'assets/functions.min.js'
			]
		},

		jshint: {
			options: {
				jshintrc: 'assets/.jshintrc'
			},
			framework: {
				src: [
					'assets/framework.js'
				]
			},
			functions: {
				src: [
					'assets/functions.js'
				]
			}
		},

		uglify: {
			options: {
				preserveComments: 'some',
				report: 'min'
			},
			framework: {
				src: 'assets/framework.js',
				dest: 'assets/framework.min.js'
			},
			functions: {
				src: 'assets/functions.js',
				dest: 'assets/functions.min.js'
			}
		},

		usebanner: {
			options: {
				position: 'top',
				banner: '<%= banner %>'
			},
			framework: {
				src: [
					'assets/framework.min.js'
				]
			},
			functions: {
				src: [
					'assets/functions.min.js'
				]
			}
		},

		replace: {
			header: {
				src: [
					'customizer-definitely.php'
				],
				overwrite: true,
				replacements: [{
					from: /((?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:\/\/.*))/,
					to: '<%= pluginheader %>'
				}]
			},
			version: {
				src: [
					'customizer-definitely.php',
					'inc/**/*.php'
				],
				overwrite: true,
				replacements: [{
					from: /\/\*\*\s+\*\s@package\s[^*]+\s+\*\s@version\s[^*]+\s+\*\s@author\s[^*]+\s\*\//,
					to: '<%= fileheader %>'
				}]
			}
		}

 	});

	grunt.loadNpmTasks('grunt-contrib-clean');
	grunt.loadNpmTasks('grunt-contrib-jshint');
	grunt.loadNpmTasks('grunt-contrib-uglify');
	grunt.loadNpmTasks('grunt-banner');
	grunt.loadNpmTasks('grunt-text-replace');

	grunt.registerTask('framework', [
		'clean:framework',
		'jshint:framework',
		'uglify:framework'
	]);

	grunt.registerTask('functions', [
		'clean:functions',
		'jshint:functions',
		'uglify:functions'
	]);

	grunt.registerTask('plugin', [
		'usebanner',
		'replace:version',
		'replace:header'
	]);

	grunt.registerTask('default', [
		'framework',
		'functions'
	]);

	grunt.registerTask('build', [
		'framework',
		'functions',
		'plugin'
	]);
};
