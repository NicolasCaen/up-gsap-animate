<?php
/**
 * Classe de base pour les générateurs d'animations
 */
abstract class UP_GSAP_Base_Generator {
    /**
     * Animations à traiter
     */
    protected $animations = array();

    /**
     * Constructeur
     */
    public function __construct($animations) {
        $this->animations = $animations;
    }

    /**
     * Méthode abstraite pour générer le code JS
     */
    abstract public function generate();

    /**
     * Sanitize une valeur pour le JS
     */
    protected function sanitize_value($value) {
        if (is_string($value)) {
            $value = strip_tags($value);
            $value = str_replace(
                array("\r", "\n", '"'),
                array('', '', '\"'),
                $value
            );
            return "'" . $value . "'";
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        return $value;
    }

    /**
     * Génère les options ScrollTrigger
     */
    protected function generate_scroll_trigger_options($trigger, $trigger_element_id) {
        if (empty($trigger)) return array();

        $trigger_options = array();
        $trigger_options[] = "            trigger: '#" . $trigger_element_id . "'";
        
        if (!empty($trigger['start'])) {
            $trigger_options[] = "            start: '" . $trigger['start'] . "'";
        }
        if (!empty($trigger['end'])) {
            $trigger_options[] = "            end: '" . $trigger['end'] . "'";
        }
        if (!empty($trigger['scrubType']) && $trigger['scrubType'] !== 'none') {
            $trigger_options[] = "            scrub: " . ($trigger['scrubType'] === 'smooth' ? $trigger['smoothness'] : 'true');
        }
        if (!empty($trigger['pin'])) {
            $trigger_options[] = "            pin: true";
            $trigger_options[] = "            pinSpacing: true";
        }
        if (!empty($trigger['markers'])) {
            $trigger_options[] = "            markers: true";
        }
        
        return $trigger_options;
    }

    /**
     * Génère les propriétés d'animation
     */
    protected function generate_animation_props($animation) {
        $animation_props = array();
        $method = "to";
        $props = array();

        if (!empty($animation['from'])) {
            $method = "from";
            $props = $animation['from'];
        } else if (!empty($animation['to'])) {
            $method = "to";
            $props = $animation['to'];
        }

        foreach ($props as $prop => $value) {
            $animation_props[] = "            " . $prop . ": " . $this->sanitize_value($value);
        }

        if (!empty($animation['duration'])) {
            $animation_props[] = "            duration: " . $animation['duration'];
        }
        if (!empty($animation['ease'])) {
            $animation_props[] = "            ease: '" . $animation['ease'] . "'";
        }

        return array(
            'method' => $method,
            'props' => $animation_props
        );
    }
}
