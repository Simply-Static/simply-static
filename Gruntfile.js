module.exports = function(grunt) {
    'use strict';

	require('load-grunt-tasks')(grunt);

	// Project configuration.
	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),

		makepot: {
			target: {
				options: {
					domainPath: '/languages/',          // Where to save the POT file.
					mainFile: 'simply-static.php',      // Main project file.
					potFilename: 'simply_static.pot',   // Name of the POT file.
					type: 'wp-plugin',                  // Type of project (wp-plugin or wp-theme).
					potHeaders: {
	                    poedit: true,                   // Includes common Poedit headers.
	                    'x-poedit-keywordslist': true   // Include a list of all possible gettext functions.
	                },
					processPot: function( pot, options ) {
						pot.headers['report-msgid-bugs-to'] = 'https://wordpress.org/support/plugin/simply-static\n';
						pot.headers['last-translator'] = 'FULL NAME <EMAIL@ADDRESS>\n';
						pot.headers['language-team'] = 'LANGUAGE <LL@li.org>\n';
						return pot;
					}
				}
			}
		},

		po2mo: {
			files: {
				src: 'languages/*.po',
				expand: true,
			},
		}

	});

	// Default task(s).
	grunt.registerTask( 'default', [ 'makepot', 'po2mo' ] );

};
