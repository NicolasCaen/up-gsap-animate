<?php
/**
 * Générateur pour les timelines
 */
class UP_GSAP_Timeline_Generator extends UP_GSAP_Base_Generator {
    /**
     * Timelines à traiter
     */
    protected $timelines = array();

    /**
     * Constructeur
     */
    public function __construct($animations, $timelines) {
        parent::__construct($animations);
        $this->timelines = $timelines;
    }

    /**
     * Génère le code JS pour les timelines
     */
    public function generate() {
        $js = "";

        foreach ($this->timelines as $timeline_id => $timeline) {
            $js .= $this->generate_timeline($timeline_id, $timeline);
        }

        return $js;
    }

    /**
     * Génère une timeline spécifique
     */
    protected function generate_timeline($timeline_id, $timeline) {
        $js = "    // Timeline: " . $timeline_id . "\n";
        $js .= "    const timeline_" . str_replace('-', '_', $timeline_id) . " = gsap.timeline({\n";
        
        $timeline_options = array();
        
        // ScrollTrigger
        if (!empty($timeline['trigger'])) {
            $trigger_options = $this->generate_scroll_trigger_options($timeline['trigger'], $timeline_id);
            $timeline_options[] = "        scrollTrigger: {\n" . implode(",\n", $trigger_options) . "\n        }";
        }

        // Options de la timeline
        if (!empty($timeline['options'])) {
            if (!empty($timeline['options']['defaults'])) {
                $defaults = array();
                foreach ($timeline['options']['defaults'] as $key => $value) {
                    $defaults[] = "            " . $key . ": " . $this->sanitize_value($value);
                }
                if (!empty($defaults)) {
                    $timeline_options[] = "        defaults: {\n" . implode(",\n", $defaults) . "\n        }";
                }
            }
            
            if (!empty($timeline['options']['stagger'])) {
                $timeline_options[] = "        stagger: " . $timeline['options']['stagger'];
            }
        }

        $js .= !empty($timeline_options) ? implode(",\n", $timeline_options) . "\n    });\n\n" : "    });\n\n";

        // Trouver les enfants de cette timeline
        $timeline_children = $this->get_timeline_children($timeline_id);

        // Ajouter les animations enfants
        if (!empty($timeline_children)) {
            $js .= "    // Animations de la timeline\n";
            $js .= "    timeline_" . str_replace('-', '_', $timeline_id) . "\n";
            
            foreach ($timeline_children as $index => $child) {
                if (empty($child['anchor'])) continue;

                $animation = $this->generate_animation_props($child['animation']);
                
                // Position dans la timeline
                $position = "";
                if ($index > 0) {
                    $position = ", '-=0.5'"; // Chevauchement par défaut
                }

                $js .= "        ." . $animation['method'] . "('#" . $child['anchor'] . "', {\n";
                $js .= implode(",\n", $animation['props']) . "\n";
                $js .= "        }" . $position . ")" . ($index < count($timeline_children) - 1 ? "\n" : ";\n\n");
            }
        }

        return $js;
    }

    /**
     * Récupère les enfants d'une timeline
     */
    protected function get_timeline_children($timeline_id) {
        $children = array();
        foreach ($this->animations as $animation) {
            if (
                !empty($animation['type']) && 
                $animation['type'] === 'timeline-child' && 
                !empty($animation['timelineParentId']) && 
                $animation['timelineParentId'] === $timeline_id
            ) {
                $children[] = $animation;
            }
        }
        return $children;
    }
}
