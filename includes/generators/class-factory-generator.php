<?php
/**
 * Factory pour créer les générateurs
 */
class UP_GSAP_Generator_Factory {
    /**
     * Crée un générateur en fonction du type
     */
    public static function create($type, $animations, $timelines = array()) {
        switch ($type) {
            case 'timeline':
                return new UP_GSAP_Timeline_Generator($animations, $timelines);
            case 'standalone':
                return new UP_GSAP_Standalone_Generator($animations);
            default:
                throw new Exception("Type de générateur inconnu: " . $type);
        }
    }
}
