<?php

class UP_GSAP_JS_Generator {
    private $animations = array();
    private $timelines = array();

    public function __construct($animations = array(), $timelines = array()) {
        $this->animations = $animations;
        $this->timelines = $timelines;
    }

    private function escape_js($string) {
        if (function_exists('esc_js')) {
            return esc_js($string);
        }
        
        // Version simplifiée de esc_js pour les tests
        $string = strtr($string, array(
            "\r" => '',
            "\n" => '\\n',
            "\t" => '\\t',
            "'" => "\\'",
            '"' => '\\"',
            '\\' => '\\\\',
            '</' => '<\/',
            "\xe2\x80\xa8" => '\\u2028',
            "\xe2\x80\xa9" => '\\u2029'
        ));
        
        return preg_replace('/[^a-zA-Z0-9,._]/u', '\\\\$0', $string);
    }

    public function generate() {
        $js = "/* GSAP Animations */\n\n";
        
        // Vérifier GSAP
        $js .= "if (typeof gsap === 'undefined') {\n";
        $js .= "    console.error('GSAP not loaded. Please make sure to include GSAP library.');\n";
        $js .= "} else {\n";
        
        // Vérifier ScrollTrigger si nécessaire
        if ($this->needs_scroll_trigger()) {
            $js .= "    if (typeof ScrollTrigger !== 'undefined') {\n";
            $js .= "        gsap.registerPlugin(ScrollTrigger);\n";
            $js .= "    } else {\n";
            $js .= "        console.error('ScrollTrigger not loaded. Please make sure to include ScrollTrigger plugin.');\n";
            $js .= "    }\n\n";
        }

        // Wrapper DOMContentLoaded
        $js .= "    document.addEventListener('DOMContentLoaded', function() {\n";

        // Timelines
        foreach ($this->timelines as $timeline_id => $timeline_data) {
            $js .= $this->generate_timeline($timeline_id, $timeline_data);
        }

        // Animations standalone
        foreach ($this->animations as $animation_data) {
            $js .= $this->generate_animation($animation_data);
        }

        $js .= "    });\n";
        $js .= "}\n";

        return $js;
    }

    private function generate_timeline($timeline_id, $timeline_data) {
        if (empty($timeline_data['anchor']) || empty($timeline_data['animation'])) {
            return '';
        }

        $js = "        // Timeline: " . $timeline_id . "\n";
        $timeline_var = $this->sanitize_js_var_name('timeline_' . $timeline_id);
        
        // État initial
        if (!empty($timeline_data['animation']['from'])) {
            $js .= "        gsap.set('#" . $this->escape_js($timeline_data['anchor']) . "', {\n";
            $js .= "            " . $this->format_animation_props($timeline_data['animation']['from']) . "\n";
            $js .= "        });\n\n";
        }

        // Timeline initialization
        $js .= "        const " . $timeline_var . " = gsap.timeline({\n";
        if (!empty($timeline_data['trigger'])) {
            $js .= "            " . $this->generate_trigger($timeline_data['trigger']) . ",\n";
        }
        $js .= "        });\n\n";

        // Animation
        $js .= "        " . $timeline_var . ".to('#" . $this->escape_js($timeline_data['anchor']) . "', {\n";
        $js .= "            " . $this->format_animation_props($timeline_data['animation']['to']) . "\n";
        $js .= "            duration: " . floatval($timeline_data['animation']['duration']) . ",\n";
        $js .= "            ease: '" . $this->escape_js($timeline_data['animation']['ease']) . "'\n";
        $js .= "        });\n\n";

        return $js;
    }

    private function generate_animation($animation_data) {
        if (empty($animation_data['anchor']) || empty($animation_data['animation'])) {
            return '';
        }

        $js = "        // Standalone animation\n";
        
        // État initial
        if (!empty($animation_data['animation']['from'])) {
            $js .= "        gsap.set('#" . $this->escape_js($animation_data['anchor']) . "', {\n";
            $js .= "            " . $this->format_animation_props($animation_data['animation']['from']) . "\n";
            $js .= "        });\n\n";
        }

        // Animation
        $js .= "        gsap.to('#" . $this->escape_js($animation_data['anchor']) . "', {\n";
        $js .= "            " . $this->format_animation_props($animation_data['animation']['to']) . "\n";
        $js .= "            duration: " . floatval($animation_data['animation']['duration']) . ",\n";
        $js .= "            ease: '" . $this->escape_js($animation_data['animation']['ease']) . "'";

        if (!empty($animation_data['trigger'])) {
            $js .= ",\n            " . $this->generate_trigger($animation_data['trigger']);
        }

        $js .= "\n        });\n\n";

        return $js;
    }

    private function generate_trigger($trigger) {
        $js = "";
        
        switch ($trigger['type']) {
            case 'scroll':
                $js .= "scrollTrigger: {\n";
                $js .= "                trigger: '#" . $this->escape_js($trigger['element_id']) . "',\n";
                $js .= "                start: '" . $this->escape_js($trigger['start']) . "',\n";
                if (!empty($trigger['end'])) {
                    $js .= "                end: '" . $this->escape_js($trigger['end']) . "',\n";
                }
                if (!empty($trigger['scrub'])) {
                    $js .= "                scrub: " . $this->get_scrub_value($trigger) . ",\n";
                }
                if (!empty($trigger['pin'])) {
                    $js .= "                pin: true,\n";
                }
                if (!empty($trigger['markers'])) {
                    $js .= "                markers: true,\n";
                }
                $js .= "            }";
                break;

            case 'hover':
                $js .= "paused: true,\n";
                $js .= "            onComplete: function() {\n";
                $js .= "                document.querySelector('#" . $this->escape_js($trigger['element_id']) . "').addEventListener('mouseenter', () => this.play());\n";
                $js .= "                document.querySelector('#" . $this->escape_js($trigger['element_id']) . "').addEventListener('mouseleave', () => this.reverse());\n";
                $js .= "            }";
                break;

            case 'click':
                $js .= "paused: true,\n";
                $js .= "            onComplete: function() {\n";
                $js .= "                document.querySelector('#" . $this->escape_js($trigger['element_id']) . "').addEventListener('click', () => this.restart());\n";
                $js .= "            }";
                break;
        }

        return $js;
    }

    private function needs_scroll_trigger() {
        foreach ($this->animations as $animation) {
            if (!empty($animation['trigger']) && $animation['trigger']['type'] === 'scroll') {
                return true;
            }
        }
        foreach ($this->timelines as $timeline) {
            if (!empty($timeline['trigger']) && $timeline['trigger']['type'] === 'scroll') {
                return true;
            }
        }
        return false;
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
        $formatted = array();
        foreach ($props as $key => $value) {
            if (is_numeric($value)) {
                $formatted[] = $key . ": " . $value;
            } else {
                $formatted[] = $key . ": '" . $this->escape_js($value) . "'";
            }
        }
        return implode(",\n            ", $formatted);
    }

    private function sanitize_js_var_name($name) {
        return preg_replace('/[^a-zA-Z0-9_]/', '_', $name);
    }
}
