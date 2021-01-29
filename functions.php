<?php

require_once  get_template_directory() . '/inc/admin-customizer.php';

require_once  get_template_directory() . '/inc/post-customizer.php';

require_once  get_template_directory() . '/inc/class-wp-bootstrap-navwalker.php';

require_once  get_template_directory() . '/inc/languages.php';

require_once get_template_directory() . '/inc/kama-thumbnail/kama_thumbnail.php';



if ( ! function_exists( 'mytheme_setup' ) ) :
	/**
	 * Sets up theme defaults and registers support for various WordPress features.
	 *
	 * Note that this function is hooked into the after_setup_theme hook, which
	 * runs before the init hook. The init hook is too late for some features, such
	 * as indicating support for post thumbnails.
	 */
	function mytheme_setup() {
		/*
		 * Make theme available for translation.
		 * Translations can be filed in the /languages/ directory.
		 * If you're building a theme based on MyTheme, use a find and replace
		 * to change 'mytheme' to the name of your theme in all the template files.
		 */
		load_theme_textdomain( 'mytheme', get_template_directory() . '/languages' );

		// Add default posts and comments RSS feed links to head.
		add_theme_support( 'automatic-feed-links' );

		/*
		 * Let WordPress manage the document title.
		 * By adding theme support, we declare that this theme does not use a
		 * hard-coded <title> tag in the document head, and expect WordPress to
		 * provide it for us.
		 */
		add_theme_support( 'title-tag' );

		/*
		 * Enable support for Post Thumbnails on posts and pages.
		 *
		 * @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
		 */
		add_theme_support( 'post-thumbnails' );

		// This theme uses wp_nav_menu() in one location.
		register_nav_menus(
			array(
				'main-menu' => 'Главное меню',
			)
		);

		
		
		
	}
endif;
add_action( 'after_setup_theme', 'mytheme_setup' );


/**
 * Enqueue scripts and styles.
 */
function mytheme_scripts() {


	wp_deregister_script( 'jquery' );
	wp_register_script( 'jquery', get_template_directory_uri() . '/js/jquery-3.5.1.min.js');
	wp_enqueue_script( 'jquery' );



	wp_enqueue_style( 'mytheme-style', get_stylesheet_uri());

	//wp_enqueue_style( 'kholomyanskiystudio-mystyle', get_template_directory_uri() . '/css/style.css');

	//wp_enqueue_script( 'kholomyanskiystudio-script.js', get_template_directory_uri() . '/js/script.js', array(), false, true );


	
}
add_action( 'wp_enqueue_scripts', 'mytheme_scripts' );





/**
 * Load WooCommerce compatibility file.
 */
if ( class_exists( 'WooCommerce' ) ) {
	require get_template_directory() . '/inc/woocommerce.php';
}
