<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Filter hook to add custom GSAP animations
 * 
 * @param array $animations Array of animations
 * @return array Modified array of animations
 */
function up_gsap_get_animations($animations = []) {
    return apply_filters('up_gsap_animations', $animations);
}

/**
 * Filter hook to add custom GSAP easings
 * 
 * @param array $easings Array of easings
 * @return array Modified array of easings
 */
function up_gsap_get_easings($easings = []) {
    return apply_filters('up_gsap_easings', $easings);
}

/**
 * Filter hook to modify animation settings before they are applied
 * 
 * @param array $settings Animation settings
 * @param string $block_id Block ID
 * @return array Modified settings
 */
function up_gsap_get_animation_settings($settings, $block_id) {
    return apply_filters('up_gsap_animation_settings', $settings, $block_id);
}

/**
 * Example of how to add custom animations
 */
add_filter('up_gsap_animations', function($animations) {
    // Example custom animation
    $animations['bounce'] = [
        'label' => __('Bounce', 'up-gsap-animate'),
        'from' => [
            'y' => -100,
            'opacity' => 0
        ],
        'to' => [
            'y' => 0,
            'opacity' => 1,
            'ease' => 'bounce.out'
        ]
    ];
    
    return $animations;
});

/**
 * Example of how to add custom easings
 */
add_filter('up_gsap_easings', function($easings) {
    // Add custom easing
    $easings[] = [
        'label' => 'Custom Bounce',
        'value' => 'custom.bounce'
    ];
    
    return $easings;
});
