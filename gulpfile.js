var autoprefixer = require('gulp-autoprefixer');
var concat = require('gulp-concat');
var gulp = require('gulp');
var request = require('request');
var fs = require('fs');
var gulputil = require('gulp-util');
var rename = require('gulp-rename');
var sass = require('gulp-sass')( require('sass') );
var sourcemaps = require('gulp-sourcemaps');
var uglify = require('gulp-uglify');

function do_scss( src ) {
	var dir = src.substring( 0, src.lastIndexOf('/') );
	return gulp.src( './src/scss/' + src + '.scss' )
		.pipe( sourcemaps.init() )
		.pipe( sass( { outputStyle: 'compressed' } ).on('error', sass.logError) )
		.pipe( autoprefixer() )
		.pipe( gulp.dest( './css/' + dir ) )
        .pipe( sass( { outputStyle: 'compressed' } ).on('error', sass.logError) )
		.pipe( rename( { suffix: '.dev' } ) )
        .pipe( sourcemaps.write() )
        .pipe( gulp.dest( './css/' + dir ) );
}

function do_js( src ) {
	var dir = src.substring( 0, src.lastIndexOf('/') );
	return gulp.src( './src/js/' + src + '.js' )
		.pipe( sourcemaps.init() )
		.pipe( gulp.dest( './js/' + dir ) )
		.pipe( uglify() )
		.pipe( rename( { suffix: '.dev' } ) )
		.pipe( sourcemaps.write() )
		.pipe( gulp.dest( './js/' + dir ) );
}

function concat_js( src, dest ) {
	return gulp.src( src )
		.pipe( sourcemaps.init() )
		.pipe( uglify() )
		.pipe( concat( dest ) )
		.pipe( gulp.dest( './js/' ) )
		.pipe( rename( { suffix: '.dev' } ) )
		.pipe( sourcemaps.write() )
		.pipe( gulp.dest( './js/' ) );

}

// scss
gulp.task('scss:admin', function(){
	return do_scss( 'admin/edit' );
});


gulp.task( 'js:admin', function(){
	return do_js( 'admin/edit' );
} );

gulp.task('js', gulp.parallel( 'js:admin', ) );

gulp.task('scss', gulp.parallel( 'scss:admin', ) );

gulp.task('scss-legacy', function(){
	return do_scss( 'admin/edit-legacy' );
});

gulp.task('build', gulp.parallel('js', 'scss','scss-legacy') );

gulp.task('watch', function() {
	// place code for your default task here
	gulp.watch('./src/scss/**/*.scss',gulp.parallel( 'scss', 'scss-legacy' ));
	gulp.watch('./src/js/**/*.js',gulp.parallel( 'js' ) );
});
gulp.task('default', gulp.parallel('build','watch'));
