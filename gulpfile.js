var gulp = require('gulp');
var uglify = require('gulp-uglify');
var concat = require('gulp-concat');
var gutil = require('gulp-util');
var mainBowerFiles = require('main-bower-files');
var _ = require('underscore');
var less = require('gulp-less');
var addsrc = require('gulp-add-src');
var minifyCss = require('gulp-minify-css');

gulp.task('default', function() {
  // place code for your default task here
});

gulp.task('styles', function () {
  var lessfiles = mainBowerFiles({
    filter : /^.*\.(less)$/,
    //debugging: true,
  });

  var cssfiles = mainBowerFiles({
    filter : /^.*\.(css)$/,
    //debugging: true,
  });

  gutil.log(lessfiles);
  gutil.log(cssfiles);

  return gulp.src(lessfiles)
    .pipe(less())
    .pipe(addsrc.append(cssfiles))
    .pipe(concat('lib.min.css'))
    .pipe(minifyCss({compatibility: 'ie8'}))
    .pipe(gulp.dest('assets/css'));
});

gulp.task('scripts', function() {
  var mainfiles = mainBowerFiles({
    filter : /^.*\.(js)$/,
    debugging: true,
  });

  var jquery = _.find(mainfiles,function(file){
    return _.last( file.split('/') ) === 'jquery.js';
  });

  var angular = _.find(mainfiles,function(file){
    return _.last( file.split('/') ) === 'angular.js';
  });
  
  var other = _.filter(mainfiles,function(file){
    return _.last( file.split('/') ) !== 'jquery.js';
  });
  mainfiles = _.union([jquery,angular],other);


  gutil.log(mainfiles);

  return gulp.src( mainfiles )
    .pipe(addsrc.append(
      'bower_components/angular-bootstrap/ui-bootstrap-tpls.js'
    ))
    .pipe(concat('lib.min.js'))
    .pipe(uglify())
    .pipe(gulp.dest('assets/js'));
});

gulp.task('fonts', function() {
    return gulp.src([
        'bower_components/fontawesome/fonts/fontawesome-webfont.*'
      ])
      .pipe(gulp.dest('assets/fonts/'));
});

gulp.task('watch', function() {
  gulp.watch('bower_components/**', ['scripts']);
});