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
    private $processed_ids = array();

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_editor_assets'));
        add_filter('render_block', array($this, 'collect_animations'), 10, 2);
        add_action('wp_footer', array($this, 'render_animations_script'));
    }

    public function init() {
        wp_register_script(
            'up-gsap-animate-editor',
            plugins_url('build/index.js', __FILE__),
            array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n', 'wp-data')
        );

        wp_register_style(
            'up-gsap-animate-editor',
            plugins_url('build/editor.css', __FILE__),
            array('wp-edit-blocks')
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
    }

    public function enqueue_editor_assets() {
        wp_enqueue_script('up-gsap-animate-editor');
        wp_enqueue_style('up-gsap-animate-editor');

        wp_add_inline_script(
            'up-gsap-animate-editor',
            'window.upGsapAnimateSettings = ' . json_encode(array(
                'animations' => apply_filters('up_gsap_animate_animations', array()),
                'easings' => apply_filters('up_gsap_animate_easings', array())
            )),
            'before'
        );
    }

    public function collect_animations($block_content, $block) {
        if (empty($block['attrs'])) {
            return $block_content;
        }

        $gsap_animation = $block['attrs']['gsapAnimation'] ?? null;
        $trigger = $block['attrs']['trigger'] ?? null;
        $timeline = $block['attrs']['timeline'] ?? null;

        // Générer un ID unique pour le bloc s'il n'en a pas déjà un
        $block_id = '';
        if (preg_match('/id="([^"]*)"/', $block_content, $matches)) {
            $block_id = $matches[1];
        }
        if (empty($block_id)) {
            $block_id = 'gsap-' . uniqid();
            $block_content = preg_replace('/^(<\w+)/', '$1 id="' . esc_attr($block_id) . '"', $block_content, 1);
        }

        // Si cet ID a déjà été traité, ne pas le traiter à nouveau
        if (in_array($block_id, $this->processed_ids)) {
            return $block_content;
        }
        $this->processed_ids[] = $block_id;

        // Si c'est un parent de timeline
        if ($timeline && $timeline['isTimelineParent']) {
            // Trouver tous les éléments enfants directs avec leurs IDs
            preg_match_all('/<(p|div|h[1-6])[^>]*id="([^"]+)"[^>]*>/', $block_content, $matches);
            
            $children = array();
            if (!empty($matches[2])) {
                foreach ($matches[2] as $child_id) {
                    // Ajouter l'ID de l'enfant à la liste des IDs traités
                    $this->processed_ids[] = $child_id;
                    
                    $children[] = array(
                        'id' => $child_id,
                        'animation' => array(
                            'from' => array('opacity' => 0),
                            'to' => array('opacity' => 1),
                            'duration' => $timeline['defaults']['duration'] ?? 1,
                            'ease' => $timeline['defaults']['ease'] ?? 'power2.out'
                        ),
                        'position' => $timeline['position'] ?? '+=0'
                    );
                }
            }

            $this->timelines[$timeline['timelineId']] = array(
                'id' => $block_id,
                'settings' => array(
                    'defaults' => $timeline['defaults'] ?? array(),
                    'stagger' => $timeline['stagger'] ?? 0,
                    'globalDuration' => $timeline['globalDuration'] ?? 1,
                    'animation' => $gsap_animation
                ),
                'children' => $children,
                'trigger' => $trigger
            );
        }
        // Animation standalone (seulement si ce n'est pas un enfant de timeline)
        elseif ($gsap_animation && $gsap_animation['enabled'] && empty($timeline['timelineParentId'])) {
            $this->animations[] = array(
                'id' => $block_id,
                'animation' => $gsap_animation,
                'trigger' => $trigger
            );
        }

        return $block_content;
    }

    public function render_animations_script() {
        if (empty($this->animations) && empty($this->timelines)) {
            return;
        }

        $script = "
        document.addEventListener('DOMContentLoaded', function() {
            gsap.registerPlugin(ScrollTrigger);
        ";

        // Générer le code pour les timelines
        foreach ($this->timelines as $timeline_id => $timeline_data) {
            $script .= $this->generate_timeline_code($timeline_id, $timeline_data);
        }

        // Générer le code pour les animations standalone
        foreach ($this->animations as $animation_data) {
            $script .= $this->generate_animation_code($animation_data);
        }

        $script .= "
        });
        ";

        // Afficher le script
        echo '<script>' . $script . '</script>';
    }

    private function sanitize_js_var_name($name) {
        // Remplacer les tirets par des underscores et supprimer les caractères non autorisés
        return preg_replace('/[^a-zA-Z0-9_]/', '_', $name);
    }

    private function generate_timeline_code($timeline_id, $timeline_data) {
        if (empty($timeline_data['settings']) || empty($timeline_data['id'])) {
            return '';
        }

        $timeline_var = $this->sanitize_js_var_name('timeline_' . $timeline_id);

        // Configuration de base de la timeline
        $script = "
            let {$timeline_var} = gsap.timeline({
                defaults: " . json_encode($timeline_data['settings']['defaults'] ?? []) . "
            });
        ";

        // Animation du parent si elle existe
        if (!empty($timeline_data['settings']['animation'])) {
            $animation = $timeline_data['settings']['animation'];
            if (!empty($animation['from'])) {
                $script .= "
                gsap.set('#" . esc_js($timeline_data['id']) . "', {
                    " . $this->format_animation_props($animation['from']) . "
                });\n";
            }

            $script .= "
            {$timeline_var}.to('#" . esc_js($timeline_data['id']) . "', {
                " . $this->format_animation_props($animation['to']) . "
                duration: " . floatval($animation['duration']) . ",
                ease: '" . esc_js($animation['ease']) . "'
            });\n";
        }

        // Animation des enfants
        if (!empty($timeline_data['children'])) {
            foreach ($timeline_data['children'] as $index => $child) {
                if (empty($child['id'])) continue;

                // État initial
                $script .= "
                gsap.set('#" . esc_js($child['id']) . "', {
                    opacity: 0,
                    y: " . ($index * 20) . "
                });\n";

                // Animation
                $script .= "
                {$timeline_var}.to('#" . esc_js($child['id']) . "', {
                    opacity: 1,
                    y: 0,
                    duration: " . floatval($timeline_data['settings']['defaults']['duration'] ?? 1) . ",
                    ease: '" . esc_js($timeline_data['settings']['defaults']['ease'] ?? 'power2.out') . "'
                }, '" . ($index === 0 ? '>' : '+=0.3') . "');\n";
            }
        }

        // Ajouter les triggers
        if (!empty($timeline_data['trigger'])) {
            $script .= $this->generate_trigger_code($timeline_var, $timeline_data['id'], $timeline_data['trigger']);
        }

        return $script;
    }

    private function generate_animation_code($animation_data) {
        if (empty($animation_data['id']) || empty($animation_data['animation'])) {
            return '';
        }

        $animation_var = $this->sanitize_js_var_name('animation_' . $animation_data['id']);
        $script = "";
        
        // État initial
        if (!empty($animation_data['animation']['from'])) {
            $script .= "
            gsap.set('#" . esc_js($animation_data['id']) . "', {
                " . $this->format_animation_props($animation_data['animation']['from']) . "
            });\n";
        }

        // Animation
        $script .= "
            let {$animation_var} = gsap.to('#" . esc_js($animation_data['id']) . "',
                {
                    " . $this->format_animation_props($animation_data['animation']['to']) . "
                    duration: " . floatval($animation_data['animation']['duration']) . ",
                    ease: '" . esc_js($animation_data['animation']['ease']) . "',
                    paused: " . (!empty($animation_data['trigger']) && $animation_data['trigger']['type'] !== 'load' ? 'true' : 'false') . "
                }
            );\n";

        // Trigger
        if (!empty($animation_data['trigger'])) {
            $script .= $this->generate_trigger_code(
                $animation_var,
                $animation_data['id'],
                $animation_data['trigger']
            );
        }

        return $script;
    }

    private function generate_trigger_code($animation_var, $element_id, $trigger) {
        if (empty($trigger['type'])) {
            return '';
        }

        $script = "";

        switch ($trigger['type']) {
            case 'scroll':
                $script .= "
                    ScrollTrigger.create({
                        trigger: '#" . esc_js($element_id) . "',
                        start: '" . esc_js($trigger['start'] ?? 'top center') . "',";
                
                if (!empty($trigger['end'])) {
                    $script .= "\n                        end: '" . esc_js($trigger['end']) . "',";
                }
                
                $script .= "
                        scrub: " . $this->get_scrub_value($trigger) . ",
                        " . (!empty($trigger['pin']) ? "pin: true," : "") . "
                        " . (!empty($trigger['markers']) ? "markers: true," : "") . "
                        onEnter: () => {$animation_var}.play()
                    });\n";
                break;

            case 'hover':
                $script .= "
                    document.querySelector('#" . esc_js($element_id) . "').addEventListener('mouseenter', () => {
                        {$animation_var}.play();
                    });\n";
                if (!empty($trigger['reverse'])) {
                    $script .= "
                    document.querySelector('#" . esc_js($element_id) . "').addEventListener('mouseleave', () => {
                        {$animation_var}.reverse();
                    });\n";
                }
                break;

            case 'click':
                $is_playing_var = $this->sanitize_js_var_name('isPlaying_' . $element_id);
                $script .= "
                    let {$is_playing_var} = false;
                    document.querySelector('#" . esc_js($element_id) . "').addEventListener('click', () => {
                        if ({$is_playing_var}) {
                            " . (!empty($trigger['reverse']) ? "{$animation_var}.reverse();" : "") . "
                        } else {
                            {$animation_var}.play();
                        }
                        {$is_playing_var} = !{$is_playing_var};
                    });\n";
                break;
        }

        return $script;
    }

    private function get_scrub_value($trigger) {
        if (empty($trigger['scrubType']) || $trigger['scrubType'] === 'none') {
            return 'false';
        }
        if ($trigger['scrubType'] === 'instant') {
            return 'true';
        }
        return floatval($trigger['smoothness'] ?? 1);
    }

    private function format_animation_props($props) {
        if (empty($props) || !is_array($props)) {
            return '';
        }

        $formatted = '';
        foreach ($props as $key => $value) {
            if ($value === null) continue;
            $formatted .= esc_js($key) . ': ' . json_encode($value) . ",\n                    ";
        }
        return $formatted;
    }
}

new UP_GSAP_Animate();
