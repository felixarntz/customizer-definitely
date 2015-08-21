'use strict';
module.exports = function(grunt) {
	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),
		banner: '/*!\n' +
				' * <%= pkg.name %> version <%= pkg.version %>\n' +
				' * \n' +
				' * <%= pkg.author.name %> <<%= pkg.author.email %>>\n' +
				' */',
		pluginheader: 	'/*\n' +
						'Plugin Name: Customizer Definitely\n' +
						'Plugin URI: <%= pkg.homepage %>\n' +
						'Description: <%= pkg.description %>\n' +
						'Version: <%= pkg.version %>\n' +
						'Author: <%= pkg.author.name %>\n' +
						'Author URI: <%= pkg.author.url %>\n' +
						'License: <%= pkg.license.name %>\n' +
						'License URI: <%= pkg.license.url %>\n' +
						'Text Domain: wpcd\n' +
						'Domain Path: /languages/\n' +
						'Tags: wordpress, plugin, framework, library, developer, customizer, admin, backend, ui\n' +
						'*/',
		fileheader: '/**\n' +
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
			],
			translation: [
				'languages/wpcd.pot'
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
		},

		makepot: {
			translation: {
				options: {
					mainFile: 'customizer-definitely.php',
					domainPath: '/languages',
					exclude: [ 'vendor/.*' ],
					potComments: 'Copyright (c) 2015-<%= grunt.template.today("yyyy") %> <%= pkg.author.name %>',
					potFilename: 'wpcd.pot',
					potHeaders: {
						'language-team': '<%= pkg.author.name %> <<%= pkg.author.email %>>',
						'last-translator': '<%= pkg.author.name %> <<%= pkg.author.email %>>',
						'project-id-version': '<%= pkg.name %> <%= pkg.version %>',
						'report-msgid-bugs-to': '<%= pkg.homepage %>',
						'x-generator': 'grunt-wp-i18n 0.5.3',
						'x-poedit-basepath': '.',
						'x-poedit-language': 'English',
						'x-poedit-country': 'UNITED STATES',
						'x-poedit-sourcecharset': 'uft-8',
						'x-poedit-keywordslist': '__;_e;_x:1,2c;_ex:1,2c;_n:1,2; _nx:1,2,4c;_n_noop:1,2;_nx_noop:1,2,3c;esc_attr__; esc_html__;esc_attr_e; esc_html_e;esc_attr_x:1,2c; esc_html_x:1,2c;',
						'x-poedit-bookmars': '',
						'x-poedit-searchpath-0': '.',
						'x-textdomain-support': 'yes'
					},
					type: 'wp-plugin'
				}
			}
		}

 	});

	grunt.loadNpmTasks('grunt-contrib-clean');
	grunt.loadNpmTasks('grunt-contrib-jshint');
	grunt.loadNpmTasks('grunt-contrib-uglify');
	grunt.loadNpmTasks('grunt-banner');
	grunt.loadNpmTasks('grunt-text-replace');
	grunt.loadNpmTasks('grunt-wp-i18n');

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

	grunt.registerTask('translation', [
		'clean:translation',
		'makepot:translation'
	]);

	grunt.registerTask('plugin', [
		'usebanner',
		'replace:version',
		'replace:header'
	]);

	grunt.registerTask('default', [
		'framework',
		'functions',
		'translation'
	]);

	grunt.registerTask('build', [
		'framework',
		'functions',
		'translation',
		'plugin'
	]);
};
