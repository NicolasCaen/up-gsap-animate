<?php
/**
 * Plugin Name: UP GSAP Animate
 * Description: Add GSAP animations to your blocks
 * Version: 0.2.0
 * Author: UP
 */

if (!defined('ABSPATH')) {
    exit;
}

class UP_GSAP_Animate {
    private $animations = array();
    private $timelines = array();
    private $admin;
    private $js_generator;

    public function __construct() {
        // Charger les dépendances
        require_once plugin_dir_path(__FILE__) . 'includes/class-js-generator.php';
        require_once plugin_dir_path(__FILE__) . 'includes/class-admin.php';

        // Initialiser les composants
        $this->admin = new UP_GSAP_Admin();
        
        // Actions WordPress
        add_action('init', array($this, 'register_block_attributes'));
        add_action('wp_footer', array($this, 'output_animations'));
        add_action('save_post', array($this, 'maybe_generate_animation_file'), 10, 3);
    }

    public function register_block_attributes() {
        $registered_blocks = WP_Block_Type_Registry::get_instance()->get_all_registered();
        
        foreach ($registered_blocks as $block_name => $block_type) {
            $block_type->attributes['gsapAnimation'] = array(
                'type' => 'object',
                'default' => null
            );
        }
    }

    public function collect_animations_from_blocks($blocks) {
        foreach ($blocks as $block) {
            if (!empty($block['attrs'])) {
                $this->collect_animations($block['innerHTML'], $block);
            }
            if (!empty($block['innerBlocks'])) {
                $this->collect_animations_from_blocks($block['innerBlocks']);
            }
        }
    }

    public function collect_animations($content, $block) {
        if (empty($block['attrs']['gsapAnimation'])) {
            return;
        }

        $animation_data = $block['attrs']['gsapAnimation'];
        
        // Vérifier si on a un ID
        if (empty($block['attrs']['anchor'])) {
            return;
        }

        $element_id = $block['attrs']['anchor'];
        
        // Ajouter à la timeline ou comme animation standalone
        if (!empty($animation_data['timeline'])) {
            if (!isset($this->timelines[$animation_data['timeline']])) {
                $this->timelines[$animation_data['timeline']] = array(
                    'anchor' => $element_id,
                    'animation' => $animation_data,
                    'trigger' => $animation_data['trigger'] ?? null
                );
            }
        } else {
            $this->animations[] = array(
                'anchor' => $element_id,
                'animation' => $animation_data,
                'trigger' => $animation_data['trigger'] ?? null
            );
        }
    }

    public function output_animations() {
        if (empty($this->animations) && empty($this->timelines)) {
            return;
        }

        $generator = new UP_GSAP_JS_Generator($this->animations, $this->timelines);
        $script = $generator->generate();

        echo '<script>' . $script . '</script>';
    }

    public function maybe_generate_animation_file($post_id, $post, $update) {
        // Vérifier si on doit générer les fichiers JS
        if (empty($this->admin->get_option('generate_js_files'))) {
            return;
        }

        // Vérifier si c'est une révision ou un auto-save
        if (wp_is_post_revision($post_id) || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)) {
            return;
        }

        // Vérifier si le contenu a des animations
        $content = $post->post_content;
        if (strpos($content, 'gsapAnimation') === false) {
            return;
        }

        // Créer le dossier dans le thème si nécessaire
        $theme_dir = get_stylesheet_directory();
        $gsap_dir = $theme_dir . '/assets/js/gsap';
        if (!file_exists($gsap_dir)) {
            wp_mkdir_p($gsap_dir);
        }

        // Collecter les animations
        $blocks = parse_blocks($content);
        $this->collect_animations_from_blocks($blocks);

        // Générer le code JS
        $generator = new UP_GSAP_JS_Generator($this->animations, $this->timelines);
        $js_code = $generator->generate();

        // Sauvegarder dans un fichier
        $file_path = $gsap_dir . '/page-' . $post_id . '.js';
        file_put_contents($file_path, $js_code);
    }
}

new UP_GSAP_Animate();
