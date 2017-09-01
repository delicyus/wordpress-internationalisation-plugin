<?php
/*
Plugin Name: Deli JS et CSS
Plugin URI: http://delicyus.com
Description: Ajout des scripts et des styles
Version: 2017-08
Author: delicyus
Author URI: http://delicyus.com
License: GPL2
*/
if ( ! defined( 'WPINC' ) ) die;
if(! class_exists('Deli_Css_Js_Plugin') ){

    class Deli_Css_Js_Plugin
    {
        public function __construct()
        {
            // Charge les CSS et JS 
            add_action( 'wp_enqueue_scripts', array($this, 'Deli_Css_Js_load') , 0 ); // PUBLIC
            add_action( 'admin_enqueue_scripts', array($this, 'Deli_Css_Js_Admin_load')  ); // ADMIN 

            // Retire les EMOJI ICONS
            remove_action('wp_head', 'print_emoji_detection_script', 7);
            remove_action('wp_print_styles', 'print_emoji_styles');
        }

        // Charge les CSS et JS localement ou CDN
        public function Deli_Css_Js_Admin_load(){
            // admin theme
            wp_enqueue_style( 'admin-theme-css', plugin_dir_url(__FILE__) . 'css/admin.css' );

            // JS app
            wp_enqueue_script( 'admin-scripts', plugin_dir_url(__FILE__) . '/js/scripts-admin.js' );

            // DATE PICKER 
            wp_enqueue_script( 'jquery-ui-datepicker' , array( 'jquery'), "1.2.2" ,  true );
            wp_enqueue_style( 'jquery-ui-datepicker', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.18/themes/smoothness/jquery-ui.css' );
        }

        // Charge les CSS et JS localement ou CDN
        public function Deli_Css_Js_load(){

            // CSS BOILERPLATE (cdn)
            wp_enqueue_style( 'bootstrap-css', '//maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css' , '' ,  '3.3.7' , 'screen' );
            // CSS ICONES (cdn)
            wp_enqueue_style( 'fontawesome-css', '//maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css' , '' , "4.7.0" , 'screen' );
            // CSS FONTS (cdn)
            wp_enqueue_style( 'work-sans-css', '//fonts.googleapis.com/css?family=Work+Sans:400,900' , '' , '' , 'screen' );
            wp_enqueue_style( 'pt-mono-css', '//fonts.googleapis.com/css?family=PT+Mono:400' , '' , '' , 'screen' );

            // Plugin Magnific popup
            wp_enqueue_style(
                'jquery-magnific-popup-css',
                plugin_dir_url(__FILE__) . '/js/jquery-magnific-popup/magnific-popup.css' ,
                '' ,
                '' ,
                'screen' );
            wp_enqueue_script(
                'jquery-magnific-popup-script',
                plugin_dir_url(__FILE__) . '/js/jquery-magnific-popup/jquery.magnific-popup.min.js' ,
                 array( 'jquery' ),
                 '1.1.0',
                 false );

            // JS DU THEME  
            wp_enqueue_script(
                'theme-script',
                get_stylesheet_directory_uri() . '/scripts.js' ,
                array( 'jquery' ),
                strtotime(date('Y-m-d H:i:s') ) ,
                false );

            // Theme stylesheet.
            wp_enqueue_style( 'theme-css', get_stylesheet_directory_uri() . '/style.css' , '' ,  strtotime(date('Y-m-d H:i:s') )  , 'screen' );
        }

    } // Endof class
    new Deli_Css_Js_Plugin();
}
?>