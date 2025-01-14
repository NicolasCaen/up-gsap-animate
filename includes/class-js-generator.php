<?php

class UP_GSAP_JS_Generator {
    private $animations;
    private $timelines;
    private $timeline_children;

    public function __construct($animations = array(), $timelines = array()) {
        $this->animations = array();
        $this->timelines = array();
        $this->timeline_children = array();
        $this->process_blocks($animations, $timelines);
    }

    private function process_blocks($animations, $timelines) {
        // Traiter d'abord les timelines parents
        foreach ($timelines as $timeline_id => $timeline) {
            if (!empty($timeline['timeline']['isTimelineParent'])) {
                $this->add_timeline($timeline_id, $timeline);
            }
        }

        // Traiter ensuite les animations
        foreach ($animations as $animation) {
            if (!empty($animation['timeline']['timelineParentId'])) {
                // C'est un enfant de timeline
                $this->add_to_timeline($animation['timeline']['timelineParentId'], $animation);
            } else {
                // C'est une animation standalone
                $this->add_standalone_animation($animation);
            }
        }
    }

    private function add_timeline($timeline_id, $timeline) {
        if (empty($timeline['anchor'])) {
            return;
        }

        $this->timelines[$timeline_id] = array(
            'anchor' => $timeline['anchor'],
            'trigger' => !empty($timeline['trigger']) ? $timeline['trigger'] : null,
            'options' => array(
                'defaults' => !empty($timeline['timeline']['defaults']) ? $timeline['timeline']['defaults'] : null,
                'stagger' => !empty($timeline['timeline']['stagger']) ? $timeline['timeline']['stagger'] : null
            )
        );
    }

    private function add_to_timeline($timeline_id, $animation) {
        if (empty($animation['anchor']) || empty($animation['animation'])) {
            return;
        }

        if (!isset($this->timeline_children[$timeline_id])) {
            $this->timeline_children[$timeline_id] = array();
        }

        $this->timeline_children[$timeline_id][] = array(
            'anchor' => $animation['anchor'],
            'animation' => array(
                'from' => !empty($animation['animation']['from']) ? $animation['animation']['from'] : null,
                'to' => !empty($animation['animation']['to']) ? $animation['animation']['to'] : null,
                'duration' => !empty($animation['animation']['duration']) ? $animation['animation']['duration'] : null,
                'ease' => !empty($animation['animation']['ease']) ? $animation['animation']['ease'] : null
            ),
            'position' => !empty($animation['timeline']['position']) ? $animation['timeline']['position'] : null
        );
    }

    private function add_standalone_animation($animation) {
        if (empty($animation['anchor']) || empty($animation['animation'])) {
            return;
        }

        $this->animations[] = array(
            'anchor' => $animation['anchor'],
            'animation' => array(
                'from' => !empty($animation['animation']['from']) ? $animation['animation']['from'] : null,
                'to' => !empty($animation['animation']['to']) ? $animation['animation']['to'] : null,
                'duration' => !empty($animation['animation']['duration']) ? $animation['animation']['duration'] : null,
                'ease' => !empty($animation['animation']['ease']) ? $animation['animation']['ease'] : null
            ),
            'trigger' => !empty($animation['trigger']) ? $animation['trigger'] : null
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
        if (empty($animation[$type])) {
            return array();
        }

        $props = array();
        foreach ($animation[$type] as $prop => $value) {
            $props[] = "            " . $prop . ": " . $this->sanitize_value($value);
        }
        return $props;
    }

    private function generate_trigger_props($trigger) {
        if (empty($trigger)) {
            return array();
        }

        $props = array();
        foreach ($trigger as $prop => $value) {
            if ($prop !== 'type') {
                $props[] = "                " . $prop . ": " . $this->sanitize_value($value);
            }
        }
        return $props;
    }

    private function generate_timeline_options($options) {
        $props = array();

        if (!empty($options['defaults'])) {
            $props[] = "            defaults: {";
            foreach ($options['defaults'] as $prop => $value) {
                $props[] = "                " . $prop . ": " . $this->sanitize_value($value) . ",";
            }
            $props[] = "            }";
        }

        if (!empty($options['stagger'])) {
            $props[] = "            stagger: " . $options['stagger'];
        }

        return $props;
    }

    public function generate() {
        $js = "/* GSAP Animations */\n\n";
        $js .= "if (typeof gsap === 'undefined') {\n";
        $js .= "    console.error('GSAP not loaded. Please make sure to include GSAP library.');\n";
        $js .= "} else {\n";
        $js .= "    document.addEventListener('DOMContentLoaded', function() {\n";

        // Générer les timelines
        foreach ($this->timelines as $timeline_id => $timeline) {
            $js .= "        // Timeline: " . $timeline_id . "\n";

            // Options de la timeline
            $js .= "        const timeline_" . str_replace('-', '_', $timeline_id) . " = gsap.timeline({\n";
            
            // Trigger
            if (!empty($timeline['trigger'])) {
                $js .= "            scrollTrigger: {\n";
                $trigger_props = $this->generate_trigger_props($timeline['trigger']);
                $js .= implode(",\n", $trigger_props) . "\n";
                $js .= "            },\n";
            }

            // Options de timeline
            if (!empty($timeline['options'])) {
                $timeline_props = $this->generate_timeline_options($timeline['options']);
                if (!empty($timeline_props)) {
                    $js .= implode(",\n", $timeline_props) . "\n";
                }
            }
            
            $js .= "        });\n\n";

            // Ajouter les enfants de la timeline
            if (isset($this->timeline_children[$timeline_id])) {
                foreach ($this->timeline_children[$timeline_id] as $child) {
                    // État initial
                    if (!empty($child['animation']['from'])) {
                        $js .= "        gsap.set('#" . $child['anchor'] . "', {\n";
                        $props = $this->generate_animation_props($child['animation'], 'from');
                        $js .= implode(",\n", $props) . "\n";
                        $js .= "        });\n\n";
                    }

                    // Animation
                    $js .= "        timeline_" . str_replace('-', '_', $timeline_id) . ".to('#" . $child['anchor'] . "', {\n";
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
                    $js .= "        });\n\n";
                }
            }
        }

        // Générer les animations standalone
        foreach ($this->animations as $animation) {
            $js .= "        // Standalone animation\n";
            
            // État initial
            if (!empty($animation['animation']['from'])) {
                $js .= "        gsap.set('#" . $animation['anchor'] . "', {\n";
                $props = $this->generate_animation_props($animation['animation'], 'from');
                $js .= implode(",\n", $props) . "\n";
                $js .= "        });\n\n";
            }

            // Animation
            $js .= "        gsap.to('#" . $animation['anchor'] . "', {\n";
            $props = $this->generate_animation_props($animation['animation'], 'to');
            
            if (!empty($animation['animation']['duration'])) {
                $props[] = "            duration: " . $animation['animation']['duration'];
            }
            if (!empty($animation['animation']['ease'])) {
                $props[] = "            ease: " . $this->sanitize_value($animation['animation']['ease']);
            }

            // Trigger
            if (!empty($animation['trigger'])) {
                $js .= implode(",\n", $props) . ",\n";
                $js .= "            scrollTrigger: {\n";
                $trigger_props = $this->generate_trigger_props($animation['trigger']);
                $js .= implode(",\n", $trigger_props) . "\n";
                $js .= "            }\n";
            } else {
                $js .= implode(",\n", $props) . "\n";
            }
            
            $js .= "        });\n\n";
        }

        $js .= "    });\n";
        $js .= "}\n";

        return $js;
    }
}
