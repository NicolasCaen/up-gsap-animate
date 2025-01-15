<?php

class UP_GSAP_JS_Generator {
    private $animations;
    private $timelines;

    public function __construct($animations = array(), $timelines = array()) {
        $this->animations = $animations;
        $this->timelines = $timelines;
    }

    private function process_blocks($blocks) {
        foreach ($blocks as $block) {
            $this->process_block($block);
        }
    }

    private function get_animation_type($block_attributes) {
        $gsap_animation = isset($block_attributes['gsapAnimation']) ? $block_attributes['gsapAnimation'] : array();
        
        if (empty($gsap_animation['enabled'])) {
            return 'none';
        }
        
        return $gsap_animation['type'] ?? 'none';
    }

    private function process_block($block) {
        if (!isset($block['attrs'])) {
            return;
        }

        // Ajouter l'innerHTML aux attributs pour l'extraction de l'ID
        if (isset($block['innerHTML'])) {
            $block['attrs']['innerHTML'] = $block['innerHTML'];
        }

        $animation_type = $this->get_animation_type($block['attrs']);
        
        if ($animation_type === 'none') {
            return;
        }

        // Extraire l'ID de l'élément HTML depuis innerHTML
        $anchor = null;
        if (!empty($block['attrs']['innerHTML'])) {
            preg_match('/id="([^"]+)"/', $block['attrs']['innerHTML'], $matches);
            if (!empty($matches[1])) {
                $anchor = $matches[1];
            }
        }

        $animation_data = array(
            'anchor' => $anchor,
            'animation' => array(
                'from' => !empty($block['attrs']['gsapAnimation']['from']) ? $block['attrs']['gsapAnimation']['from'] : null,
                'to' => !empty($block['attrs']['gsapAnimation']['to']) ? $block['attrs']['gsapAnimation']['to'] : null,
                'duration' => !empty($block['attrs']['gsapAnimation']['duration']) ? $block['attrs']['gsapAnimation']['duration'] : null,
                'ease' => !empty($block['attrs']['gsapAnimation']['ease']) ? $block['attrs']['gsapAnimation']['ease'] : null
            ),
            'trigger' => !empty($block['attrs']['trigger']) ? $block['attrs']['trigger'] : null
        );

        if ($animation_type === 'timeline-parent') {
            $timeline_id = $anchor;
            $this->timelines[$timeline_id] = array(
                'timelineId' => $timeline_id,
                'trigger' => $animation_data['trigger'],
                'options' => array(
                    'defaults' => !empty($block['attrs']['timeline']['defaults']) ? $block['attrs']['timeline']['defaults'] : null,
                    'stagger' => !empty($block['attrs']['timeline']['stagger']) ? $block['attrs']['timeline']['stagger'] : null
                ),
                'children' => array()
            );
        } else if ($animation_type === 'timeline-child') {
            $parent_id = $block['attrs']['timeline']['timelineParentId'];
            if (!isset($this->timelines[$parent_id]['children'])) {
                $this->timelines[$parent_id]['children'] = array();
            }
            $animation_data['position'] = !empty($block['attrs']['timeline']['position']) ? $block['attrs']['timeline']['position'] : null;
            $this->timelines[$parent_id]['children'][] = $animation_data;
        } else {
            $this->animations[] = $animation_data;
        }

        if (isset($block['innerBlocks'])) {
            foreach ($block['innerBlocks'] as $inner_block) {
                $this->process_block($inner_block);
            }
        }
    }

    private function sanitize_value($value) {
        if (is_string($value)) {
            return "'" . addslashes(strip_tags($value)) . "'";
        } elseif (is_bool($value)) {
            return $value ? 'true' : 'false';
        } else {
            return $value;
        }
    }

    private function generate_animation_props($animation, $type = 'to') {
        $props = array();
        
        if ($type === 'from' && !empty($animation['from'])) {
            foreach ($animation['from'] as $prop => $value) {
                $props[] = "            $prop: " . $this->sanitize_value($value);
            }
        } else if ($type === 'to' && !empty($animation['to'])) {
            foreach ($animation['to'] as $prop => $value) {
                $props[] = "            $prop: " . $this->sanitize_value($value);
            }
        }
        
        return $props;
    }

    private function generate_timeline_options($options) {
        $props = array();

        if (!empty($options['defaults'])) {
            $defaults_props = array();
            foreach ($options['defaults'] as $prop => $value) {
                $defaults_props[] = "                $prop: " . $this->sanitize_value($value);
            }
            if (!empty($defaults_props)) {
                $props[] = "            defaults: {\n" . implode(",\n", $defaults_props) . "\n            }";
            }
        }

        if (!empty($options['stagger'])) {
            $props[] = "            stagger: " . $options['stagger'];
        }

        return $props;
    }

    public function generate() {
        return $this->process_js();
    }

    private function process_js() {
        // Chargement des classes de générateurs
        $base_path = defined('ABSPATH') ? plugin_dir_path(__FILE__) : dirname(__FILE__) . '/';
        require_once $base_path . 'generators/class-base-generator.php';
        require_once $base_path . 'generators/class-timeline-generator.php';
        require_once $base_path . 'generators/class-standalone-generator.php';
        require_once $base_path . 'generators/class-factory-generator.php';

        $js = "document.addEventListener('DOMContentLoaded', function() {\n";
        $js .= "    gsap.registerPlugin(ScrollTrigger);\n\n";

        // Génération des timelines
        $timeline_generator = UP_GSAP_Generator_Factory::create('timeline', $this->animations, $this->timelines);
        $js .= $timeline_generator->generate();

        // Génération des animations standalone
        $standalone_generator = UP_GSAP_Generator_Factory::create('standalone', $this->animations);
        $js .= $standalone_generator->generate();

        $js .= "});\n";
        return $js;
    }
}
