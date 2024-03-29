<!DOCTYPE html>

<!--[if lt IE 7 ]> <html class="ie ie6 no-js" <?php language_attributes(); ?>> <![endif]-->
<!--[if IE 7 ]>    <html class="ie ie7 no-js" <?php language_attributes(); ?>> <![endif]-->
<!--[if IE 8 ]>    <html class="ie ie8 no-js" <?php language_attributes(); ?>> <![endif]-->
<!--[if IE 9 ]>    <html class="ie ie9 no-js" <?php language_attributes(); ?>> <![endif]-->
<!--[if gt IE 9]><!--><html class="no-js" <?php language_attributes(); ?>><!--<![endif]-->
<!-- the "no-js" class is for Modernizr. -->

<head id="www-sitename-com" data-template-set="html5-reset-wordpress-theme" profile="http://gmpg.org/xfn/11">

  <meta charset="<?php bloginfo('charset'); ?>">

  <!-- Always force latest IE rendering engine (even in intranet) & Chrome Frame -->
  <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">

  <?php if (is_search()) { ?>
  <meta name="robots" content="noindex, nofollow" />
  <?php } ?>

  <title>
       <?php
          if (function_exists('is_tag') && is_tag()) {
             single_tag_title("Tag Archive for &quot;"); echo '&quot; - '; }
          elseif (is_archive()) {
             wp_title(''); echo ' Archive - '; }
          elseif (is_search()) {
             echo 'Search for &quot;'.wp_specialchars($s).'&quot; - '; }
          elseif (!(is_404()) && (is_single()) || (is_page())) {
             wp_title(''); echo ' - '; }
          elseif (is_404()) {
             echo 'Not Found - '; }
          if (is_home()) {
             bloginfo('name'); echo ' - '; bloginfo('description'); }
          else {
              bloginfo('name'); }
          if ($paged>1) {
             echo ' - page '. $paged; }
       ?>
  </title>

  <meta name="title" content="<?php
          if (function_exists('is_tag') && is_tag()) {
             single_tag_title("Tag Archive for &quot;"); echo '&quot; - '; }
          elseif (is_archive()) {
             wp_title(''); echo ' Archive - '; }
          elseif (is_search()) {
             echo 'Search for &quot;'.wp_specialchars($s).'&quot; - '; }
          elseif (!(is_404()) && (is_single()) || (is_page())) {
             wp_title(''); echo ' - '; }
          elseif (is_404()) {
             echo 'Not Found - '; }
          if (is_home()) {
             bloginfo('name'); echo ' - '; bloginfo('description'); }
          else {
              bloginfo('name'); }
          if ($paged>1) {
             echo ' - page '. $paged; }
       ?>">
  <meta name="description" content="<?php bloginfo('description'); ?>">

  <meta name="google-site-verification" content="">
  <!-- Speaking of Google, don't forget to set your site up: http://google.com/webmasters -->

  <meta name="author" content="<?php bloginfo('name') ?>">
  <meta name="Copyright" content="Copyright <?php bloginfo('name') ?> <?php echo date('Y') ?>. All Rights Reserved.">

  <!-- Dublin Core Metadata : http://dublincore.org/ -->
  <meta name="DC.title" content="<?php bloginfo('name') ?>">
  <meta name="DC.subject" content="<?php bloginfo('description') ?>">
  <meta name="DC.creator" content="ColorJar - http://www.colorjar.com">

  <!-- OpenGraph Metadata -->
  <meta property="og:title"     content="<?php the_title() ?>" />
  <meta property="og:type"      content="website" />
  <meta property="og:site_name" content="<?php bloginfo('name') ?>" />
  <meta property="og:locale"    content="en_US" />
  <meta property="og:url"       content="<?php the_permalink() ?>" />
  <meta property="og:image"     content="<?php bloginfo('template_directory') ?>/_/images/apple-touch-icon.png" />
  <meta name="description"      content="<?php bloginfo('description') ?>" />
  <meta property="fb:admins"    content="" />

  <!--  Mobile Viewport meta tag
  j.mp/mobileviewport & davidbcalhoun.com/2010/viewport-metatag
  device-width : Occupy full width of the screen in its current orientation
  initial-scale = 1.0 retains dimensions instead of zooming out if page height > device height
  maximum-scale = 1.0 retains dimensions instead of zooming in if page width < device width -->
  <!-- Uncomment to use; use thoughtfully!
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
  -->

  <link rel="icon"             href="<?php bloginfo('template_directory'); ?>/_/images/favicon.gif">
  <link rel="shortcut icon"    href="<?php bloginfo('template_directory'); ?>/_/images/favicon.gif">
  <link rel="apple-touch-icon" href="<?php bloginfo('template_directory'); ?>/_/images/apple-touch-icon.png">

  <!-- CSS: screen, mobile & print are all in the same file -->
  <link rel="stylesheet" href="<?php bloginfo('stylesheet_url'); ?>">

  <!-- all our JS is at the bottom of the page, except for Modernizr. -->
  <script src="<?php bloginfo('template_directory'); ?>/_/js/modernizr-1.7.min.js"></script>

  <link rel="pingback" href="<?php bloginfo('pingback_url'); ?>" />

  <?php if ( is_singular() ) wp_enqueue_script( 'comment-reply' ); ?>

  <?php wp_head(); ?>

</head>

<body <?php body_class(); ?>>

  <div id="page-wrap"><!-- not needed? up to you: http://camendesign.com/code/developpeurs_sans_frontieres -->

    <header id="header">
      <h1><a href="<?php echo get_option('home'); ?>/"><?php bloginfo('name'); ?></a></h1>
      <div class="description"><?php bloginfo('description'); ?></div>
    </header>

