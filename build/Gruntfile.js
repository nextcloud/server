module.exports = function (grunt) {
	grunt.initConfig({
		svg_sprite: {
			options: {
				// Task-specific options go here.
			},
			actions: {
				expand: true,
				cwd: '../core/img',
				src: [
					'actions/*.svg'
				],
				dest: '../core',
				options: {
					mode: {
						css: {
							prefix: '.icon-',
							sprite: 'images/actions',
							dimensions: true,
							render: {
								scss: {
									dest: 'actions'
								}
							}
						}
					},

					shape: {
						id: {
							/**
							 * @param {string} name
							 * @returns {string}
							 */
							generator: function(name) {
								return name.substring('actions/'.length, name.indexOf('.svg'));
							}
						}
					}
				}
			}
		}
	});

	grunt.loadNpmTasks('grunt-svg-sprite');

	grunt.registerTask('default', ['svg_sprite']);
};
