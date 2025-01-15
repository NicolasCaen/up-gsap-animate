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
                error_log('Processing block: ' . print_r($block['blockName'], true));
                $this->collect_animations($block);
            }
            if (!empty($block['innerBlocks'])) {
                $this->collect_animations_from_blocks($block['innerBlocks']);
            }
        }
    }

    public function collect_animations($block) {
        error_log('Checking block attributes: ' . print_r($block['attrs'], true));
        
        if (empty($block['attrs']['gsapAnimation']) || empty($block['attrs']['gsapAnimation']['enabled'])) {
            error_log('Block skipped: no animation or not enabled');
            return;
        }

        $animation_data = $block['attrs']['gsapAnimation'];
        error_log('Animation data found: ' . print_r($animation_data, true));
        
        // Extraire l'ID du HTML
        if (empty($block['innerHTML'])) {
            error_log('Block skipped: no innerHTML');
            return;
        }

        // Utiliser une expression régulière pour extraire l'ID
        if (!preg_match('/id="([^"]+)"/', $block['innerHTML'], $matches)) {
            error_log('Block skipped: no ID found in HTML');
            return;
        }

        $element_id = $matches[1];
        error_log('Element ID found: ' . $element_id);
        
        // Ajouter à la timeline ou comme animation standalone
        if (!empty($block['attrs']['timeline']['isTimelineParent'])) {
            error_log('Adding timeline parent');
            $timeline_id = $element_id;
            $this->timelines[$timeline_id] = array(
                'anchor' => $element_id,
                'animation' => array(
                    'from' => isset($animation_data['from']) ? $animation_data['from'] : array(),
                    'duration' => isset($animation_data['duration']) ? $animation_data['duration'] : 1,
                    'ease' => isset($animation_data['ease']) ? $animation_data['ease'] : 'power2.out'
                ),
                'trigger' => isset($block['attrs']['trigger']) ? $block['attrs']['trigger'] : null,
                'options' => array(
                    'defaults' => isset($block['attrs']['timeline']['defaults']) ? $block['attrs']['timeline']['defaults'] : null,
                    'stagger' => isset($block['attrs']['timeline']['stagger']) ? $block['attrs']['timeline']['stagger'] : null
                ),
                'children' => array()
            );
        } else if (!empty($block['attrs']['timeline']['timelineParentId'])) {
            error_log('Adding timeline child');
            $parent_id = $block['attrs']['timeline']['timelineParentId'];
            $animation = array(
                'anchor' => $element_id,
                'animation' => array(
                    'from' => isset($animation_data['from']) ? $animation_data['from'] : array(),
                    'duration' => isset($animation_data['duration']) ? $animation_data['duration'] : 1,
                    'ease' => isset($animation_data['ease']) ? $animation_data['ease'] : 'power2.out'
                ),
                'position' => isset($block['attrs']['timeline']['position']) ? $block['attrs']['timeline']['position'] : null
            );
            
            if (!isset($this->timelines[$parent_id]['children'])) {
                $this->timelines[$parent_id]['children'] = array();
            }
            $this->timelines[$parent_id]['children'][] = $animation;
        } else {
            error_log('Adding standalone animation');
            $this->animations[] = array(
                'anchor' => $element_id,
                'animation' => array(
                    'from' => isset($animation_data['from']) ? $animation_data['from'] : array(),
                    'duration' => isset($animation_data['duration']) ? $animation_data['duration'] : 1,
                    'ease' => isset($animation_data['ease']) ? $animation_data['ease'] : 'power2.out'
                ),
                'trigger' => isset($block['attrs']['trigger']) ? $block['attrs']['trigger'] : null
            );
        }
    }

    public function output_animations() {
        // Ne pas générer le script inline si on utilise des fichiers JS
        if (!empty($this->admin->get_option('generate_js_files'))) {
            return;
        }

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

        // Réinitialiser les animations et timelines
        $this->animations = array();
        $this->timelines = array();

        // Collecter les animations
        $blocks = parse_blocks($content);
        
        // Debug
        error_log('Content: ' . $content);
        error_log('Blocks: ' . print_r($blocks, true));
        
        $this->collect_animations_from_blocks($blocks);
        
        // Debug
        error_log('Animations: ' . print_r($this->animations, true));
        error_log('Timelines: ' . print_r($this->timelines, true));

        // Générer le code JS
        $generator = new UP_GSAP_JS_Generator($this->animations, $this->timelines);
        $js_code = $generator->generate();
        
        // Debug
        error_log('Generated JS: ' . $js_code);

        // Sauvegarder dans un fichier
        $file_path = $gsap_dir . '/page-' . $post_id . '.js';
        file_put_contents($file_path, $js_code);
    }
}

new UP_GSAP_Animate();
