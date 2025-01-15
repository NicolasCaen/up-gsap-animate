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
        if (!empty($timeline['children'])) {
            $js .= "    // Animations de la timeline\n";
            $js .= "    timeline_" . str_replace('-', '_', $timeline_id) . "\n";
            
            foreach ($timeline['children'] as $index => $child) {
                if (empty($child['anchor'])) continue;

                $animation_code = $this->generate_animation_code($child);
                
                // Position dans la timeline
                $position = "";
                if (!empty($child['position'])) {
                    $position = ", '" . $child['position'] . "'";
                }

                $js .= "        " . $animation_code . $position . ($index < count($timeline['children']) - 1 ? "\n" : ";\n\n");
            }
        }

        return $js;
    }

    /**
     * Génère le code d'animation pour un élément
     */
    protected function generate_animation_code($animation) {
        $code = '';
        
        // Déterminer le type d'animation en fonction des propriétés disponibles
        if (!empty($animation['animation']['from']) && !empty($animation['animation']['to'])) {
            // Si on a from et to, utiliser fromTo
            $code .= sprintf(
                ".fromTo('#%s', %s, %s, %s)",
                $animation['anchor'],
                json_encode($animation['animation']['from']),
                json_encode($animation['animation']['to']),
                json_encode($animation['animation']['duration'])
            );
        } else if (!empty($animation['animation']['to'])) {
            // Si on a uniquement to, utiliser to
            $code .= sprintf(
                ".to('#%s', %s, %s)",
                $animation['anchor'],
                json_encode($animation['animation']['to']),
                json_encode($animation['animation']['duration'])
            );
        } else {
            // Par défaut ou si on a uniquement from, utiliser from
            $code .= sprintf(
                ".from('#%s', %s, %s)",
                $animation['anchor'],
                json_encode($animation['animation']['from']),
                json_encode($animation['animation']['duration'])
            );
        }

        if (!empty($animation['animation']['ease'])) {
            $code .= sprintf(".ease('%s')", $animation['animation']['ease']);
        }

        return $code;
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
