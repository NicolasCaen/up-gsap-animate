<?php
/**
 * Générateur pour les animations standalone
 */
class UP_GSAP_Standalone_Generator extends UP_GSAP_Base_Generator {
    /**
     * Génère le code JS pour les animations standalone
     */
    public function generate() {
        $js = "";

        foreach ($this->animations as $animation) {
            if (
                empty($animation['type']) || 
                $animation['type'] === 'standalone'
            ) {
                $js .= $this->generate_standalone_animation($animation);
            }
        }

        return $js;
    }

    /**
     * Génère une animation standalone
     */
    protected function generate_standalone_animation($animation) {
        if (empty($animation['anchor'])) return "";

        $js = "    // Animation standalone\n";
        
        $animation_data = $this->generate_animation_props($animation['animation']);
        $animation_props = $animation_data['props'];

        // ScrollTrigger
        if (!empty($animation['trigger'])) {
            $trigger_props = $this->generate_scroll_trigger_options($animation['trigger'], $animation['anchor']);
            $trigger_props[] = "            toggleActions: 'play none none reverse'";
            $animation_props[] = "            scrollTrigger: {\n" . implode(",\n", $trigger_props) . "\n            }";
        }

        $js .= "    gsap." . $animation_data['method'] . "('#" . $animation['anchor'] . "', {\n";
        $js .= implode(",\n", $animation_props) . "\n";
        $js .= "    });\n\n";

        return $js;
    }
}
