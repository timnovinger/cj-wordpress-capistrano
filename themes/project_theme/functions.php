<?php

  // =================
  // LOAD DEPENDENCIES
  // =================
  require_once('_/inc/custom_post_types.php');

  // -----------------
  // YAML SUPPORT
  // ----------------
  // require_once('_/inc/spyc.php');
  // $text_array = spyc_load_file(dirname(__FILE__).'/_/yaml/sample.yaml');

  // Include the most up to date jQuery
  wp_deregister_script('jquery');
  wp_register_script('jquery', ("http://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"), false);
  wp_enqueue_script('jquery');

  // Include the most up to date jQuery UI
  // wp_register_script('jquery-ui', ("http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.16/jquery-ui.min.js"), false);
  // wp_enqueue_script('jquery-ui');




  // =================
  // CUSTOM FUNCTIONS
  // =================

  // Easily grab custom field data
  function get_custom_field($key, $echo = FALSE)
  {
    global $post;
    $custom_field = get_post_meta($post->ID, $key, true);
    if ($echo == FALSE) return $custom_field;
    echo $custom_field;
  }

  // Basic trim by word
  function neat_trim($str, $n, $delim='...')
  {
    $len = strlen($str);

    if ($len > $n) {
      preg_match('/(.{' . $n . '}.*?)\b/', $str, $matches);
      return rtrim($matches[1]) . $delim;
    } else {
      return $str;
    }
  }




  // ========================
  // OPTIONAL WORDPRESS FIXES
  // ========================

  // -------------------------------------------------------------------------------------------------
  // As of WP 3.1.1 addition of classes for css styling to parents of custom post types doesn't exist.
  // We want the correct classes added to the correct custom post type parent in the wp-nav-menu for
  // css styling and highlighting, so we're modifying each individually…
  //
  // The id of each link is required for each one you want to modify.
  //
  // http://wordpress.org/support/topic/why-does-blog-become-current_page_parent-with-custom-post-type
  // -------------------------------------------------------------------------------------------------
  /*
  function remove_parent_classes($class)
  {
    // check for current page classes, return false if they exist.
    return ($class == 'current_page_item' || $class == 'current_page_parent' || $class == 'current_page_ancestor'  || $class == 'current-menu-item') ? FALSE : TRUE;
  }

  function add_class_to_wp_nav_menu($classes)
  {
    switch (get_post_type())
    {
      case 'fund':
        // we're viewing a custom post_typet type, so remove the 'current_page_xxx and current-menu-item' from all menu items.
        $classes = array_filter($classes, "remove_parent_classes");

        // add the current page class to a specific menu item (replace #item##).
        if (in_array('menu-item-283', $classes))
        {
          $classes[] = 'current_page_parent';
        }
    break;
  }
    return $classes;
  }
  add_filter('nav_menu_css_class', 'add_class_to_wp_nav_menu');
  */

?>
