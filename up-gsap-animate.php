<?php
/**
 * Plugin Name:       Up Gsap Animate
 * Description:       Add GSAP animations to any block in WordPress
 * Version:           0.1.0
 * Requires at least: 6.7
 * Requires PHP:      7.4
 * Author:            The WordPress Contributors
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       up-gsap-animate
 *
 * @package           up-gsap-animate
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Enqueue editor assets
 */
function up_gsap_animate_editor_assets() {
    wp_enqueue_script(
        'up-gsap-animate-editor',
        plugins_url( 'build/index.js', __FILE__ ),
        array( 'wp-blocks', 'wp-dom-ready', 'wp-edit-post' ),
        filemtime( plugin_dir_path( __FILE__ ) . 'build/index.js' )
    );
}
add_action( 'enqueue_block_editor_assets', 'up_gsap_animate_editor_assets' );

/**
 * Enqueue frontend assets
 */
function up_gsap_animate_frontend_assets() {
    // Enqueue GSAP core
    wp_enqueue_script(
        'gsap-core',
        'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.4/gsap.min.js',
        array(),
        '3.12.4',
        true
    );

    // Enqueue ScrollTrigger plugin
    wp_enqueue_script(
        'gsap-scrolltrigger',
        'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.4/ScrollTrigger.min.js',
        array('gsap-core'),
        '3.12.4',
        true
    );

    // Register and enqueue our frontend script
    wp_enqueue_script(
        'up-gsap-animate-frontend',
        plugins_url( 'build/frontend.js', __FILE__ ),
        array( 'gsap-core', 'gsap-scrolltrigger' ),
        filemtime( plugin_dir_path( __FILE__ ) . 'build/frontend.js' ),
        true
    );
}
add_action( 'wp_enqueue_scripts', 'up_gsap_animate_frontend_assets' );

/**
 * Add animation data to block HTML
 */
function up_gsap_animate_render_block( $block_content, $block ) {
    if ( empty( $block['attrs']['gsapAnimation'] ) || ! $block['attrs']['gsapAnimation']['enabled'] ) {
        return $block_content;
    }

    // Get the first HTML tag
    if ( ! preg_match( '/^(<[^>]+>)/', $block_content, $matches ) ) {
        return $block_content;
    }

    $tag = $matches[1];
    $animation_data = wp_json_encode( $block['attrs']['gsapAnimation'] );
    
    // Add our attributes to the first HTML tag
    $new_tag = str_replace( 
        '>', 
        ' data-gsap-animation=\'' . esc_attr( $animation_data ) . '\' class="has-gsap-animation">', 
        $tag 
    );
    
    return str_replace( $tag, $new_tag, $block_content );
}
add_filter( 'render_block', 'up_gsap_animate_render_block', 10, 2 );
