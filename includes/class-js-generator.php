<?php

class UP_GSAP_JS_Generator {
    private $animations;
    private $timelines;

    public function __construct($blocks = array()) {
        $this->animations = array();
        $this->timelines = array();
        $this->process_blocks($blocks);
    }

    private function process_blocks($blocks) {
        foreach ($blocks as $block) {
            $this->process_block($block);
        }
    }

    private function get_animation_type($block_attributes) {
        $gsap_animation = isset($block_attributes['gsapAnimation']) ? $block_attributes['gsapAnimation'] : array();
        $timeline = isset($block_attributes['timeline']) ? $block_attributes['timeline'] : array();

        if (empty($gsap_animation['enabled'])) {
            return 'none';
        }
        if (!empty($timeline['isTimelineParent'])) {
            return 'timeline-parent';
        }
        if (!empty($timeline['timelineParentId'])) {
            return 'timeline-child';
        }
        return 'standalone';
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

        if ($animation_type === 'timeline-parent') {
            $timeline = $this->extract_timeline_data($block['attrs']);
            $this->timelines[$timeline['timelineId']] = $timeline;
        } else if ($animation_type === 'timeline-child') {
            $animation = $this->extract_animation_data($block['attrs']);
            $parent_id = $block['attrs']['timeline']['timelineParentId'];
            if (!isset($this->timelines[$parent_id]['children'])) {
                $this->timelines[$parent_id]['children'] = array();
            }
            $this->timelines[$parent_id]['children'][] = $animation;
        } else if ($animation_type === 'standalone') {
            $animation = $this->extract_standalone_animation_data($block['attrs']);
            $this->animations[] = $animation;
        }

        if (isset($block['innerBlocks'])) {
            foreach ($block['innerBlocks'] as $inner_block) {
                $this->process_block($inner_block);
            }
        }
    }

    private function extract_timeline_data($attrs) {
        return array(
            'timelineId' => !empty($attrs['timeline']['timelineId']) ? $attrs['timeline']['timelineId'] : null,
            'trigger' => !empty($attrs['trigger']) ? $attrs['trigger'] : null,
            'options' => array(
                'defaults' => !empty($attrs['timeline']['defaults']) ? $attrs['timeline']['defaults'] : null,
                'stagger' => !empty($attrs['timeline']['stagger']) ? $attrs['timeline']['stagger'] : null
            ),
            'children' => array()
        );
    }

    private function extract_animation_data($attrs) {
        // Extraire l'ID de l'élément HTML depuis innerHTML si disponible
        $anchor = null;
        if (!empty($attrs['innerHTML'])) {
            preg_match('/id="([^"]+)"/', $attrs['innerHTML'], $matches);
            if (!empty($matches[1])) {
                $anchor = $matches[1];
            }
        }
        
        return array(
            'anchor' => $anchor,
            'animation' => array(
                'from' => !empty($attrs['gsapAnimation']['from']) ? $attrs['gsapAnimation']['from'] : null,
                'to' => !empty($attrs['gsapAnimation']['to']) ? $attrs['gsapAnimation']['to'] : null,
                'duration' => !empty($attrs['gsapAnimation']['duration']) ? $attrs['gsapAnimation']['duration'] : null,
                'ease' => !empty($attrs['gsapAnimation']['ease']) ? $attrs['gsapAnimation']['ease'] : null
            ),
            'position' => !empty($attrs['timeline']['position']) ? $attrs['timeline']['position'] : null
        );
    }

    private function extract_standalone_animation_data($attrs) {
        // Extraire l'ID de l'élément HTML depuis innerHTML si disponible
        $anchor = null;
        if (!empty($attrs['innerHTML'])) {
            preg_match('/id="([^"]+)"/', $attrs['innerHTML'], $matches);
            if (!empty($matches[1])) {
                $anchor = $matches[1];
            }
        }
        
        return array(
            'anchor' => $anchor,
            'animation' => array(
                'from' => !empty($attrs['gsapAnimation']['from']) ? $attrs['gsapAnimation']['from'] : null,
                'to' => !empty($attrs['gsapAnimation']['to']) ? $attrs['gsapAnimation']['to'] : null,
                'duration' => !empty($attrs['gsapAnimation']['duration']) ? $attrs['gsapAnimation']['duration'] : null,
                'ease' => !empty($attrs['gsapAnimation']['ease']) ? $attrs['gsapAnimation']['ease'] : null
            ),
            'trigger' => !empty($attrs['trigger']) ? $attrs['trigger'] : null
        );
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

    public function generate_js() {
        $js = "document.addEventListener('DOMContentLoaded', function() {\n";
        $js .= "    gsap.registerPlugin(ScrollTrigger);\n\n";

        // Générer les timelines
        foreach ($this->timelines as $timeline_id => $timeline) {
            $js .= "    // Timeline: " . $timeline_id . "\n";

            // Options de la timeline
            $js .= "    const timeline_" . str_replace('-', '_', $timeline_id) . " = gsap.timeline({\n";
            
            // ScrollTrigger si défini
            if (!empty($timeline['trigger'])) {
                $js .= "        scrollTrigger: {\n";
                if (!empty($timeline['trigger']['start'])) {
                    $js .= "            trigger: '#" . $timeline_id . "',\n";
                    $js .= "            start: '" . $timeline['trigger']['start'] . "',\n";
                }
                if (!empty($timeline['trigger']['end'])) {
                    $js .= "            end: '" . $timeline['trigger']['end'] . "',\n";
                }
                if (!empty($timeline['trigger']['scrubType']) && $timeline['trigger']['scrubType'] !== 'none') {
                    $js .= "            scrub: " . ($timeline['trigger']['scrubType'] === 'smooth' ? $timeline['trigger']['smoothness'] : 'true') . ",\n";
                }
                if (!empty($timeline['trigger']['pin'])) {
                    $js .= "            pin: true,\n";
                }
                if (!empty($timeline['trigger']['markers'])) {
                    $js .= "            markers: true,\n";
                }
                $js .= "        }";
            }

            // Options de la timeline
            $timeline_options = $this->generate_timeline_options($timeline['options']);
            if (!empty($timeline_options)) {
                if (!empty($timeline['trigger'])) {
                    $js .= ",\n";
                }
                $js .= implode(",\n", $timeline_options);
            }
            
            $js .= "\n    });\n\n";

            // Générer les animations des enfants
            if (!empty($timeline['children'])) {
                foreach ($timeline['children'] as $child) {
                    $js .= "    timeline_" . str_replace('-', '_', $timeline_id) . ".to('#" . $child['anchor'] . "', {\n";
                    $props = $this->generate_animation_props($child['animation'], 'to');
                    
                    if (!empty($child['animation']['duration'])) {
                        $props[] = "            duration: " . $child['animation']['duration'];
                    }
                    if (!empty($child['animation']['ease'])) {
                        $props[] = "            ease: " . $this->sanitize_value($child['animation']['ease']);
                    }
                    if (!empty($child['position'])) {
                        $props[] = "            position: " . $this->sanitize_value($child['position']);
                    }
                    
                    $js .= implode(",\n", $props) . "\n";
                    $js .= "    });\n\n";
                }
            }
        }

        // Générer les animations standalone
        foreach ($this->animations as $animation) {
            $js .= "    // Standalone animation\n";
            
            // État initial
            if (!empty($animation['animation']['from'])) {
                $js .= "    gsap.set('#" . $animation['anchor'] . "', {\n";
                $props = $this->generate_animation_props($animation['animation'], 'from');
                $js .= implode(",\n", $props) . "\n";
                $js .= "    });\n\n";
            }

            // Animation
            $js .= "    gsap.to('#" . $animation['anchor'] . "', {\n";
            $props = $this->generate_animation_props($animation['animation'], 'to');
            
            if (!empty($animation['animation']['duration'])) {
                $props[] = "            duration: " . $animation['animation']['duration'];
            }
            if (!empty($animation['animation']['ease'])) {
                $props[] = "            ease: " . $this->sanitize_value($animation['animation']['ease']);
            }

            // ScrollTrigger pour les animations standalone
            if (!empty($animation['trigger'])) {
                $scroll_trigger = array();
                $scroll_trigger[] = "            trigger: '#" . $animation['anchor'] . "'";
                
                if (!empty($animation['trigger']['start'])) {
                    $scroll_trigger[] = "            start: '" . $animation['trigger']['start'] . "'";
                }
                if (!empty($animation['trigger']['end'])) {
                    $scroll_trigger[] = "            end: '" . $animation['trigger']['end'] . "'";
                }
                if (!empty($animation['trigger']['scrubType']) && $animation['trigger']['scrubType'] !== 'none') {
                    $scroll_trigger[] = "            scrub: " . ($animation['trigger']['scrubType'] === 'smooth' ? $animation['trigger']['smoothness'] : 'true');
                }
                if (!empty($animation['trigger']['pin'])) {
                    $scroll_trigger[] = "            pin: true";
                }
                if (!empty($animation['trigger']['markers'])) {
                    $scroll_trigger[] = "            markers: true";
                }
                
                $props[] = "            scrollTrigger: {\n" . implode(",\n", $scroll_trigger) . "\n            }";
            }
            
            $js .= implode(",\n", $props) . "\n";
            $js .= "    });\n\n";
        }

        $js .= "});\n";
        return $js;
    }
}
