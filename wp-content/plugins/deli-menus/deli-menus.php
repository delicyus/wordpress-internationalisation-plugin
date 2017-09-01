<?php
/**
Plugin Name: Deli Menus
Description: Creation de Menus
Version: 201708
Author: delicyus
Author URI: http://delicyus.com
License: GPL2
*/
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) { die; }
// Creation de menus par defaut
if(!function_exists('deli_register_menus')){
    function deli_register_menus() {

        // Ajoute le support des menus au theme 
        add_theme_support( 'menus' );

        // Menus par defaut
        $menu_boilerplate = array(
            'primary-menu'          => 'Menu primaire' ,
            'secondary-menu'        => 'Menu secondaire' ,
            'footer-mentions-menu'  => 'Menu mentions footer',
        );
        foreach ($menu_boilerplate as $slug => $menu_name ) {
             
            // Check if the menu exists
            $menu_exists = wp_get_nav_menu_object( $menu_name );
            
            // If it doesn't exist, let's create it.
            if( !$menu_exists) 
                $menu_id = wp_create_nav_menu($menu_name);
          
            // Register all menus locations
            register_nav_menus( array(
                $slug    => $menu_name,
            ) );                
        }
    }
    add_action( 'init', 'deli_register_menus' );
}
// Ajoute du html apres les <li> du menu
if(!function_exists('add_html_in_menu')){
    function add_html_in_menu( $items, $args ) {
        if( $args->menu == 'primary-menu')  {
            $items .=  '<div class="clearfix"></div>';
        }
        return $items;
    }
    add_filter('wp_nav_menu_items','add_html_in_menu', 10, 2);
}
// Mark parent navigation active when on custom post type
// source : https://gist.github.com/gerbenvandijk/5253921
if(!function_exists('add_current_nav_class')){
    add_action('nav_menu_css_class', 'add_current_nav_class', 10, 2 );
    function add_current_nav_class($classes, $item) {

        // Getting the current post details
        global $post;

        // Getting the post type of the current post
        $current_post_type = get_post_type_object(get_post_type($post->ID));
        $current_post_type_slug = $current_post_type->rewrite[slug];

        // Getting the URL of the menu item
        $menu_slug = strtolower(trim($item->url));

        // If the menu item URL contains the current post types slug add the current-menu-item class
        if (strpos($menu_slug,$current_post_type_slug) !== false) {

           $classes[] = 'current-menu-item';

        }

        // Return the corrected set of classes to be added to the menu item
        return $classes;
    }
}
?>