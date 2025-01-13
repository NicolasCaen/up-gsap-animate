<?php
/**
 * Plugin Name: UP GSAP Animate
 * Description: Add GSAP animations to your blocks
 * Version: 0.1.1
 * Author: GEHIN Nicolas
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: up-gsap-animate
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('UP_GSAP_ANIMATE_VERSION', '1.0.0');
define('UP_GSAP_ANIMATE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('UP_GSAP_ANIMATE_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once UP_GSAP_ANIMATE_PLUGIN_DIR . 'includes/hooks.php';

/**
 * Initialize the plugin
 */
function up_gsap_animate_init() {
    // Enqueue scripts and styles
    add_action('enqueue_block_editor_assets', 'up_gsap_animate_editor_assets');
    add_action('wp_enqueue_scripts', 'up_gsap_animate_frontend_assets');
    
    // Save post hook
    add_action('save_post', 'up_gsap_animate_save_post_animations', 10, 3);
}
add_action('init', 'up_gsap_animate_init');

/**
 * Add animation ID to block content if animation is enabled
 */
function up_gsap_animate_render_block($block_content, $block) {
    // Skip if no content or no animation settings
    if (empty($block_content) || empty($block['attrs']['gsapAnimation'])) {
        return $block_content;
    }

    // Skip if animation is not enabled
    if (empty($block['attrs']['gsapAnimation']['enabled'])) {
        return $block_content;
    }

    // Generate a unique ID if not already set
    $block_id = !empty($block['attrs']['id']) ? $block['attrs']['id'] : 'gsap-' . wp_unique_id();

    // Check if the block already has an ID attribute
    if (strpos($block_content, 'id="') === false) {
        // Add ID to the first HTML tag
        $block_content = preg_replace('/^<(\w+)/', '<$1 id="' . esc_attr($block_id) . '"', $block_content);
    } else {
        // If ID exists, replace it with our unique ID
        $block_content = preg_replace('/id="[^"]*"/', 'id="' . esc_attr($block_id) . '"', $block_content, 1);
    }

    // Debug output in development
    if (defined('WP_DEBUG') && WP_DEBUG) {
        $debug_output = sprintf(
            "\n<!-- GSAP Block Debug\nBlock: %s\nID: %s\nAnimation: %s\nTrigger: %s\n-->\n",
            $block['blockName'],
            $block_id,
            print_r($block['attrs']['gsapAnimation'], true),
            !empty($block['attrs']['trigger']) ? print_r($block['attrs']['trigger'], true) : 'No trigger'
        );
        $block_content .= $debug_output;
    }

    return $block_content;
}
remove_filter('render_block', 'up_gsap_animate_ensure_block_id');
add_filter('render_block', 'up_gsap_animate_render_block', 999, 2);

/**
 * Process a single block and its inner blocks for animations
 */
function up_gsap_process_block($block, &$animations) {
    // Check if block has animation settings
    if (!empty($block['attrs']['gsapAnimation']) && !empty($block['attrs']['id'])) {
        // Vérifier que cet ID n'a pas déjà été utilisé
        $block_id = $block['attrs']['id'];
        $counter = 1;
        
        // Si l'ID existe déjà, ajouter un suffixe numérique
        while (isset($animations[$block_id])) {
            $block_id = $block['attrs']['id'] . '-' . $counter;
            $counter++;
        }
        
        $animations[$block_id] = [
            'animation' => $block['attrs']['gsapAnimation'],
            'trigger' => !empty($block['attrs']['trigger']) ? $block['attrs']['trigger'] : null
        ];
    }
    
    // Process inner blocks if they exist
    if (!empty($block['innerBlocks'])) {
        foreach ($block['innerBlocks'] as $inner_block) {
            up_gsap_process_block($inner_block, $animations);
        }
    }
}

/**
 * Save animations to post meta when post is saved
 */
function up_gsap_animate_save_post_animations($post_id, $post, $update) {
    // Vérifications de sécurité
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    if (wp_is_post_revision($post_id)) return;
    
    try {
        // Get post content
        $content = $post->post_content;
        
        // Parse blocks to find animations
        $blocks = parse_blocks($content);
        $animations = [];
        
        // Process blocks and collect animations
        foreach ($blocks as $block) {
            up_gsap_process_block($block, $animations);
        }
        
        // Generate JavaScript code for animations
        if (!empty($animations)) {
            $js_code = "document.addEventListener('DOMContentLoaded', function() {\n";
            
            foreach ($animations as $id => $data) {
                $animation = $data['animation'];
                $trigger = $data['trigger'];
                
                if (!empty($animation['enabled'])) {
                    // Animation de base
                    $js_code .= sprintf(
                        "  gsap.from('#%s', %s);\n",
                        esc_js($id),
                        wp_json_encode([
                            'duration' => floatval($animation['duration']),
                            'ease' => sanitize_text_field($animation['ease']),
                            ...(array)$animation['from']
                        ])
                    );
                    
                    // Configuration du trigger
                    if (!empty($trigger['type'])) {
                        switch ($trigger['type']) {
                            case 'scroll':
                                $js_code .= sprintf(
                                    "  ScrollTrigger.create({\n    trigger: '#%s',\n    start: '%s',\n    end: '%s',\n    scrub: %s,\n    pin: %s,\n    markers: %s\n  });\n",
                                    esc_js($id),
                                    esc_js($trigger['start']),
                                    !empty($trigger['end']) ? esc_js($trigger['end']) : 'bottom center',
                                    $trigger['scrubType'] === 'none' ? 'false' : ($trigger['scrubType'] === 'smooth' ? floatval($trigger['smoothness']) : 'true'),
                                    !empty($trigger['pin']) ? 'true' : 'false',
                                    !empty($trigger['markers']) ? 'true' : 'false'
                                );
                                break;
                                
                            case 'hover':
                                $js_code .= sprintf(
                                    "  document.querySelector('#%s').addEventListener('mouseenter', function() {\n    gsap.to(this, %s);\n  });\n",
                                    esc_js($id),
                                    wp_json_encode([
                                        'duration' => floatval($animation['duration']),
                                        'ease' => sanitize_text_field($animation['ease']),
                                        ...(array)$animation['to']
                                    ])
                                );
                                
                                if (!empty($trigger['reverse'])) {
                                    $js_code .= sprintf(
                                        "  document.querySelector('#%s').addEventListener('mouseleave', function() {\n    gsap.to(this, %s);\n  });\n",
                                        esc_js($id),
                                        wp_json_encode([
                                            'duration' => floatval($animation['duration']),
                                            'ease' => sanitize_text_field($animation['ease']),
                                            ...(array)$animation['from']
                                        ])
                                    );
                                }
                                break;
                                
                            case 'click':
                                $js_code .= sprintf(
                                    "  document.querySelector('#%s').addEventListener('click', function() {\n    gsap.to(this, %s);\n  });\n",
                                    esc_js($id),
                                    wp_json_encode([
                                        'duration' => floatval($animation['duration']),
                                        'ease' => sanitize_text_field($animation['ease']),
                                        ...(array)$animation['to']
                                    ])
                                );
                                break;
                                
                            case 'custom':
                                if (!empty($trigger['customTrigger'])) {
                                    $js_code .= sprintf(
                                        "  document.querySelector('%s').addEventListener('click', function() {\n    gsap.to('#%s', %s);\n  });\n",
                                        esc_js($trigger['customTrigger']),
                                        esc_js($id),
                                        wp_json_encode([
                                            'duration' => floatval($animation['duration']),
                                            'ease' => sanitize_text_field($animation['ease']),
                                            ...(array)$animation['to']
                                        ])
                                    );
                                }
                                break;
                        }
                    }
                }
            }
            
            $js_code .= "});\n";
            
            // Save the JavaScript code to post meta
            update_post_meta($post_id, '_gsap_animations', $js_code);
        } else {
            // Delete the meta if no animations are found
            delete_post_meta($post_id, '_gsap_animations');
        }
    } catch (Exception $e) {
        // Log l'erreur pour le débogage
        error_log('GSAP Animation Error: ' . $e->getMessage());
    }
}

/**
 * Enqueue editor assets
 */
function up_gsap_animate_editor_assets() {
    $asset_file = include(UP_GSAP_ANIMATE_PLUGIN_DIR . 'build/index.asset.php');
    
    wp_enqueue_script(
        'up-gsap-animate-editor',
        UP_GSAP_ANIMATE_PLUGIN_URL . 'build/index.js',
        $asset_file['dependencies'],
        $asset_file['version']
    );
    
    // Pass animations and easings to JavaScript
    wp_localize_script('up-gsap-animate-editor', 'upGsapAnimateSettings', [
        'animations' => up_gsap_get_animations(),
        'easings' => up_gsap_get_easings()
    ]);
}

/**
 * Enqueue frontend assets
 */
function up_gsap_animate_frontend_assets() {
    global $post;
    
    if (!is_singular() || !$post) return;
    
    // Get animations JavaScript code
    $animations_js = get_post_meta($post->ID, '_gsap_animations', true);
    
    if (empty($animations_js)) return;
    
    // Enqueue GSAP
    wp_enqueue_script(
        'gsap',
        'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js',
        [],
        '3.12.2'
    );
    
    wp_enqueue_script(
        'gsap-scroll-trigger',
        'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js',
        ['gsap'],
        '3.12.2'
    );
    
    // Add animations code
    wp_add_inline_script('gsap-scroll-trigger', $animations_js);
}

/**
 * Add debug output before </body>
 */
function up_gsap_animate_debug_output() {
    if (!current_user_can('edit_posts')) return;
    
    global $post;
    if (!is_singular() || !$post) return;
    
    $animations_js = get_post_meta($post->ID, '_gsap_animations', true);
    if (empty($animations_js)) return;
    
    echo "\n<!-- GSAP Animations Debug Output\n";
    echo "Post ID: " . $post->ID . "\n";
    echo "Generated JavaScript:\n";
    echo $animations_js;
    echo "-->\n";
    
    // Afficher aussi les blocs et leurs attributs
    $blocks = parse_blocks($post->post_content);
    echo "\n<!-- GSAP Blocks Debug Output\n";
    foreach ($blocks as $block) {
        if (!empty($block['attrs']['gsapAnimation'])) {
            echo "Block: " . $block['blockName'] . "\n";
            echo "ID: " . ($block['attrs']['id'] ?? 'No ID') . "\n";
            echo "Animation: " . print_r($block['attrs']['gsapAnimation'], true) . "\n";
            echo "Trigger: " . print_r($block['attrs']['trigger'] ?? [], true) . "\n";
            echo "-------------------\n";
        }
    }
    echo "-->\n";
}
add_action('wp_footer', 'up_gsap_animate_debug_output', 999);
