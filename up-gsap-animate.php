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
    private $admin;
    private $js_generator;

    public function __construct() {
        // Charger les dépendances
        require_once plugin_dir_path(__FILE__) . 'includes/class-simple-gsap-generator.php';
        require_once plugin_dir_path(__FILE__) . 'includes/class-admin.php';

        // Initialiser les composants
        $this->admin = new UP_GSAP_Admin();
        $this->js_generator = new GSAP_Animation_Generator();
        
        // Actions WordPress
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_editor_assets'));
        add_action('wp_footer', array($this, 'output_animations'));
        add_action('save_post', array($this, 'maybe_generate_animation_file'), 10, 3);
    }

    public function init() {
        $this->register_block_attributes();
        
        wp_register_script(
            'up-gsap-animate-editor',
            plugins_url('build/index.js', __FILE__),
            array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n', 'wp-data')
        );
    }

    public function register_block_attributes() {
        $registered_blocks = WP_Block_Type_Registry::get_instance()->get_all_registered();
        
        foreach ($registered_blocks as $block_name => $block_type) {
            $block_type->attributes['gsapAnimation'] = array(
                'type' => 'object',
                'default' => null
            );
            
            $block_type->attributes['timeline'] = array(
                'type' => 'object',
                'default' => null
            );
        }
    }

    public function enqueue_frontend_assets() {
        wp_enqueue_script(
            'gsap',
            'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js',
            array(),
            '3.12.2',
            true
        );

        wp_enqueue_script(
            'gsap-scroll-trigger',
            'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js',
            array('gsap'),
            '3.12.2',
            true
        );

        // Charger le fichier d'animation spécifique à la page si disponible
        if (is_singular()) {
            $post_id = get_the_ID();
            $theme_dir = get_stylesheet_directory();
            $gsap_dir = $theme_dir . '/assets/js/gsap';
            $file_path = $gsap_dir . '/page-' . $post_id . '.js';
            
            if (file_exists($file_path)) {
                wp_enqueue_script(
                    'gsap-animations-' . $post_id,
                    get_stylesheet_directory_uri() . '/assets/js/gsap/page-' . $post_id . '.js',
                    array('gsap', 'gsap-scroll-trigger'),
                    filemtime($file_path),
                    true
                );
            }
        }
    }

    public function enqueue_editor_assets() {
        wp_enqueue_script('up-gsap-animate-editor');
        
        wp_add_inline_script(
            'up-gsap-animate-editor',
            'window.upGsapAnimateSettings = ' . json_encode(array(
                'animations' => apply_filters('up_gsap_animate_animations', array()),
                'easings' => apply_filters('up_gsap_animate_easings', array())
            )),
            'before'
        );
    }

    public function output_animations() {
        $post_id = get_the_ID();
        if (!$post_id) {
            return;
        }

        $content = get_post_field('post_content', $post_id, 'raw');

        if (empty($content)) {
            return;
        }

        $script = $this->js_generator->generate_animations($content);
        if ($script) {
            echo '<script>' . $script . '</script>';
        }
    }

    public function maybe_generate_animation_file($post_id, $post, $update) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $content = get_post_field('post_content', $post_id, 'raw');
        if (empty($content)) {
            return;
        }

        // Générer le code JS
        $js_code = $this->js_generator->generate_animations($content);
        if (!$js_code) {
            return;
        }

        // Créer le dossier dans le thème si nécessaire
        $theme_dir = get_stylesheet_directory();
        $gsap_dir = $theme_dir . '/assets/js/gsap';
        if (!file_exists($gsap_dir)) {
            wp_mkdir_p($gsap_dir);
        }

        // Sauvegarder le fichier JS
        $js_file = $gsap_dir . '/page-' . $post_id . '.js';
        file_put_contents($js_file, $js_code);

        // Extraire la structure pour le fichier JSON
        $blocks = parse_blocks($content);
        $animations = array();
        $timelines = array();
        
        foreach ($blocks as $block) {
            if (!empty($block['attrs']['gsapAnimation'])) {
                $animation = $block['attrs']['gsapAnimation'];
                if (!empty($block['attrs']['trigger'])) {
                    $animation['trigger'] = $block['attrs']['trigger'];
                }
                $animations[] = $animation;
            }
            if (!empty($block['attrs']['timeline'])) {
                $timeline = $block['attrs']['timeline'];
                if (!empty($block['attrs']['trigger'])) {
                    $timeline['trigger'] = $block['attrs']['trigger'];
                }
                $timelines[] = $timeline;
            }
        }

        // Créer le dossier structure si nécessaire
        $structure_dir = $theme_dir . '/assets/js/structure';
        if (!file_exists($structure_dir)) {
            wp_mkdir_p($structure_dir);
        }

        // Sauvegarder le fichier structure
        $structure_file = $structure_dir . '/page-' . $post_id . '.json';
        $json_content = json_encode(array(
            'timelines' => $timelines,
            'standaloneAnimations' => $animations
        ), JSON_PRETTY_PRINT);

        file_put_contents($structure_file, $json_content);
    }
}

new UP_GSAP_Animate();
