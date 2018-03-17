// Require all the things (that we need).
const gulp = require('gulp');
const sort = require('gulp-sort');
const wp_pot = require('gulp-wp-pot');

// Define the source paths for each file type.
const src = {
	php: ['**/*.php','!vendor/**','!node_modules/**']
};

// Create the .pot translation file.
gulp.task('translate', function () {
    gulp.src('**/*.php')
        .pipe(sort())
        .pipe(wp_pot( {
            domain: 'wpcampus',
            destFile:'wpcampus-data-plugin.pot',
            package: 'wpcampus-data-plugin',
            bugReport: 'https://github.com/wpcampus/wpcampus-plugin/issues',
            lastTranslator: 'WPCampus <code@wpcampus.org>',
            team: 'WPCampus <code@wpcampus.org>',
            headers: false
        } ))
        .pipe(gulp.dest('languages'));
});

// I've got my eyes on you(r file changes).
gulp.task('watch', function() {
	gulp.watch(src.php, ['translate','php']);
});

// Let's get this party started.
gulp.task('default', ['translate']);
