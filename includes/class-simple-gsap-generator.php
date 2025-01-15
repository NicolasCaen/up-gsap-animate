<?php 

class GSAP_Animation_Generator {
    /**
     * Parse les blocks et génère le code JavaScript
     */
    public function generate_animations($content, $debug = false) {
        // Extraction des blocs
        $blocks = $this->extract_blocks($content);
        
        // Génération de la structure JSON intermédiaire
        $json = $this->generate_json_structure($blocks);
        
        // Débogage si nécessaire
        if ($debug) {
            $this->debug_json_structure($json);
            $this->debug_blocks($blocks);
        }
        
        // Génération du code JavaScript à partir du JSON
        return $this->generate_js_from_json($json);
    }

    /**
     * Extrait les configurations d'animation des blocs
     */
    private function extract_blocks($content) {
        $blocks = [];
        
        error_log("=== Analyzing Content ===");
        error_log($content);
        
        // Recherche tous les blocs avec gsapAnimation
        preg_match_all('/<!-- wp:[^\s]+ ({.*?"gsapAnimation".*?}) -->/', $content, $matches, PREG_SET_ORDER);
        
        error_log("=== Found Blocks ===");
        error_log(print_r($matches, true));
        
        foreach ($matches as $match) {
            $config = json_decode($match[1], true);
            
            if ($config === null || !isset($config['gsapAnimation'])) {
                continue;
            }
            
            // Chercher l'ID dans le HTML qui suit immédiatement
            $pos = strpos($content, $match[0]) + strlen($match[0]);
            $next_html = substr($content, $pos, 200); // On regarde les 200 prochains caractères
            
            if (preg_match('/<[^>]*?\bid=["\']([^"\']+)["\']/', $next_html, $id_match)) {
                $elementId = $id_match[1];
            } else {
                continue;
            }
            
            $block = [
                'gsapAnimation' => $config['gsapAnimation'],
                'timeline' => $config['timeline'] ?? [],
                'elementId' => $elementId,
                'trigger' => $config['trigger'] ?? null
            ];
            
            $blocks[] = $block;
        }
        
        error_log("=== Extracted Animations ===");
        error_log(print_r($blocks, true));
        
        return $blocks;
    }

    /**
     * Affiche la structure JSON pour le débogage
     */
    private function debug_json_structure($json) {
        echo "Structure JSON générée :\n";
        echo json_encode($json, JSON_PRETTY_PRINT);
        echo "\n";
    }

    /**
     * Affiche les blocs extraits pour le débogage
     */
    private function debug_blocks($blocks) {
        echo "Blocs extraits :\n";
        echo json_encode($blocks, JSON_PRETTY_PRINT);
        echo "\n";
    }

    /**
     * Affiche la structure JSON dans le fichier error.log
     */
    private function debug_json_structure_log($json) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('=== GSAP Animation Generator - JSON Structure ===');
            error_log(print_r($json, true));
            error_log('============================================');
        }
    }

    /**
     * Sauvegarde la structure JSON dans un fichier temporaire
     */
    private function save_json_structure($json, $post_id) {
        // Créer le dossier temp s'il n'existe pas
        $temp_dir = WP_CONTENT_DIR . '/temp';
        if (!file_exists($temp_dir)) {
            mkdir($temp_dir, 0755, true);
        }

        // Sauvegarder la structure dans un fichier JSON
        $file_path = $temp_dir . '/structure-' . $post_id . '.json';
        file_put_contents($file_path, json_encode($json, JSON_PRETTY_PRINT));
    }

    /**
     * Génère une structure JSON intermédiaire
     */
    private function generate_json_structure($blocks) {
        $structure = [
            'timelines' => [],
            'standaloneAnimations' => []
        ];

        // Première passe : identifier les timelines et les animations standalone
        foreach ($blocks as $block) {
            if ($block['gsapAnimation']['role'] === 'timeline-parent') {
                $timelineId = $block['timeline']['timelineId'];
                $structure['timelines'][$timelineId] = [
                    'id' => $timelineId,
                    'name' => $block['timeline']['name'] ?? '',
                    'defaults' => $block['timeline']['defaults'] ?? null,
                    'scrollTrigger' => isset($block['trigger']) ? [
                        'trigger' => "#{$block['elementId']}",
                        'start' => $block['trigger']['start'] ?? 'top center',
                        'end' => !empty($block['trigger']['end']) ? $block['trigger']['end'] : null,
                        'scrub' => isset($block['trigger']['scrubType']) && $block['trigger']['scrubType'] !== 'none',
                        'markers' => $block['trigger']['markers'] ?? false,
                        'pin' => $block['trigger']['pin'] ?? false
                    ] : [
                        'trigger' => "#{$block['elementId']}",
                        'start' => 'top center'
                    ],
                    'animations' => []
                ];

                // Nettoyer les valeurs null du scrollTrigger
                $structure['timelines'][$timelineId]['scrollTrigger'] = array_filter(
                    $structure['timelines'][$timelineId]['scrollTrigger'],
                    function($value) { return $value !== null; }
                );
            } elseif ($block['gsapAnimation']['role'] === 'standalone') {
                $animation = $this->create_standalone_animation($block);
                $structure['standaloneAnimations'][] = $animation;
            }
        }

        // Deuxième passe : ajouter les animations aux timelines
        foreach ($blocks as $block) {
            if ($block['gsapAnimation']['role'] === 'timeline-child') {
                $parentId = $block['timeline']['timelineParentId'];
                
                foreach ($structure['timelines'] as $timelineId => $timeline) {
                    if ($timelineId === $parentId || $timeline['id'] === $parentId) {
                        if (!isset($structure['timelines'][$timelineId]['animations'])) {
                            $structure['timelines'][$timelineId]['animations'] = [];
                        }

                        $structure['timelines'][$timelineId]['animations'][] = [
                            'elementId' => $block['elementId'],
                            'from' => $block['gsapAnimation']['from'],
                            'to' => $block['gsapAnimation']['to'],
                            'duration' => $block['gsapAnimation']['duration'],
                            'ease' => $block['gsapAnimation']['ease'],
                            'position' => $block['timeline']['position'] ?? '+=0'
                        ];
                        break;
                    }
                }
            }
        }

        // Débogage de la structure JSON
        $this->debug_json_structure_log($structure);

        // Sauvegarder la structure dans un fichier temporaire
        global $post;
        if ($post) {
            $this->save_json_structure($structure, $post->ID);
        }

        return $structure;
    }

    private function create_standalone_animation($block) {
        $animation = [
            'elementId' => $block['elementId'],
            'type' => 'fromTo',
            'from' => $block['gsapAnimation']['from'],
            'to' => $block['gsapAnimation']['to'],
            'duration' => $block['gsapAnimation']['duration'],
            'ease' => $block['gsapAnimation']['ease']
        ];

        // Ajouter le scrollTrigger s'il existe
        if (isset($block['trigger'])) {
            $animation['scrollTrigger'] = [
                'trigger' => "#{$block['elementId']}",
                'start' => $block['trigger']['start'] ?? 'top center',
                'end' => !empty($block['trigger']['end']) ? $block['trigger']['end'] : null,
                'scrub' => isset($block['trigger']['scrubType']) && $block['trigger']['scrubType'] !== 'none',
                'markers' => $block['trigger']['markers'] ?? false,
                'pin' => $block['trigger']['pin'] ?? false
            ];

            // Nettoyer les valeurs null du scrollTrigger
            $animation['scrollTrigger'] = array_filter(
                $animation['scrollTrigger'],
                function($value) { return $value !== null; }
            );
        }

        return $animation;
    }

    /**
     * Génère le code JavaScript à partir de la structure JSON
     */
    private function generate_js_from_json($json) {
        $js = "document.addEventListener('DOMContentLoaded', function() {\n";
        $js .= "    gsap.registerPlugin(ScrollTrigger);\n\n";

        // Générer les timelines
        foreach ($json['timelines'] as $timeline) {
            if (!empty($timeline['name'])) {
                $js .= "    // Timeline: {$timeline['name']}\n";
            }
            
            $js .= "    const timeline_{$timeline['id']} = gsap.timeline({\n";
            
            if (isset($timeline['defaults'])) {
                $js .= "        defaults: " . $this->format_json($timeline['defaults']) . ",\n";
            }
            
            $js .= "        scrollTrigger: " . $this->format_json($timeline['scrollTrigger']) . "\n";
            $js .= "    });\n\n";

            foreach ($timeline['animations'] as $animation) {
                $js .= "    timeline_{$timeline['id']} .fromTo('#{$animation['elementId']}', ";
                $js .= $this->format_json($animation['from']) . ", ";
                
                $to = array_merge(
                    $animation['to'],
                    array_filter([
                        'duration' => $animation['duration'] ?? null,
                        'ease' => $animation['ease'] ?? null
                    ])
                );
                $js .= $this->format_json($to);
                
                if (isset($animation['position'])) {
                    $js .= ", " . $animation['position'];
                }
                
                $js .= " );\n";
            }
            
            $js .= "\n";
        }

        // Générer les animations standalone
        foreach ($json['standaloneAnimations'] as $animation) {
            if ($animation['type'] === 'from') {
                $js .= "    gsap.from('#{$animation['elementId']}', {\n";
                foreach ($animation['from'] as $prop => $value) {
                    $js .= "        $prop: " . json_encode($value) . ",\n";
                }
                $js .= "        duration: " . $animation['duration'] . ",\n";
                $js .= "        ease: '" . $animation['ease'] . "'";
                
                if ($animation['scrollTrigger']) {
                    $js .= ",\n        scrollTrigger: " . $this->format_json($animation['scrollTrigger']);
                }
                
                $js .= "\n    });\n\n";
            } else {
                $js .= "    // Standalone animation\n";
                $js .= "    gsap.fromTo('#{$animation['elementId']}', ";
                $js .= $this->format_json($animation['from']) . ", ";
                $js .= "{ ..." . $this->format_json($animation['to']) . ",\n";
                if ($animation['duration']) {
                    $js .= "        duration: " . $animation['duration'] . ",\n";
                }
                if ($animation['ease']) {
                    $js .= "        ease: '" . $animation['ease'] . "'";
                }
                
                if ($animation['scrollTrigger']) {
                    $js .= ",\n        scrollTrigger: " . $this->format_json($animation['scrollTrigger']);
                }
                
                $js .= "\n    });\n\n";
            }
        }

        $js .= "});";
        return $js;
    }

    /**
     * Formate un objet JSON pour le JavaScript
     */
    private function format_json($obj, $compact = false) {
        if (is_array($obj)) {
            $parts = [];
            foreach ($obj as $key => $value) {
                if (is_bool($value)) {
                    $parts[] = "$key: " . ($value ? "true" : "false");
                } elseif (is_string($value)) {
                    $parts[] = "$key: '$value'";
                } elseif (is_array($value)) {
                    $parts[] = "$key: " . $this->format_json($value, true);
                } else {
                    $parts[] = "$key: $value";
                }
            }
            return "{" . implode(", ", $parts) . "}";
        }
        return json_encode($obj);
    }
}