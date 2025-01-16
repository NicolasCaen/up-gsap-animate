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
 * Filter hook to add custom GSAP triggers
 * 
 * @param array $triggers Array of triggers
 * @return array Modified array of triggers
 */
function up_gsap_get_triggers($triggers = []) {
    return apply_filters('up_gsap_triggers', $triggers);
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
 * Filter hook to modify timeline settings
 * 
 * @param array $settings Timeline settings
 * @param string $timeline_id Timeline ID
 * @return array Modified settings
 */
function up_gsap_get_timeline_settings($settings, $timeline_id) {
    return apply_filters('up_gsap_timeline_settings', $settings, $timeline_id);
}

/**
 * Example of how to add custom animations
 */
add_filter('up_gsap_animations', function($animations) {
    // Example custom animation
    $animations['custom-bounce'] = [
        'label' => __('Custom Bounce 2', 'up-gsap-animate'),
        'from' => [
            'y' => -1000,
            'opacity' => 0,
            'rotation' => 45
        ],
        'to' => [
            'y' => 0,
            'opacity' => 1,
            'rotation' => 90
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
        'label' => __('Custom Elastic', 'up-gsap-animate'),
        'value' => 'elastic.out(1.5, 0.3)'
    ];
    
    return $easings;
});

/**
 * Example of how to add custom triggers
 */
add_filter('up_gsap_triggers', function($triggers) {
    // Add custom trigger
    $triggers[] = [
        'label' => __('Custom Trigger', 'up-gsap-animate'),
        'value' => 'custom-trigger'
    ];
    
    $triggers[] = [
        'label' => __('On Form Submit', 'up-gsap-animate'),
        'value' => 'form-submit',
        'settings' => [
            'formId' => '#contact-form',  // ID du formulaire à surveiller
            'event' => 'submit'           // Événement à écouter
        ]
    ];
    
    $triggers[] = [
        'label' => __('On Custom Scroll', 'up-gsap-animate'),
        'value' => 'custom-scroll',
        'settings' => [
            'start' => '20%',              // Déclencher quand l'élément est à 20% de la vue
            'end' => '80%',                // Finir quand l'élément est à 80% de la vue
            'scrub' => true                // Animation fluide pendant le scroll
        ]
    ];
    
    return $triggers;
});
