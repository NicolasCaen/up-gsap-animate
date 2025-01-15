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
        try {
            // Créer le dossier temp s'il n'existe pas
            $temp_dir = WP_CONTENT_DIR . '/temp';
            if (!file_exists($temp_dir)) {
                if (!mkdir($temp_dir, 0755, true)) {
                    error_log("Erreur lors de la création du dossier temp: " . $temp_dir);
                    return false;
                }
            }

            // Vérifier les permissions du dossier
            if (!is_writable($temp_dir)) {
                error_log("Le dossier temp n'est pas accessible en écriture: " . $temp_dir);
                return false;
            }

            // Sauvegarder la structure dans un fichier JSON
            $file_path = $temp_dir . '/structure-' . $post_id . '.json';
            $json_content = json_encode($json, JSON_PRETTY_PRINT);
            
            if ($json_content === false) {
                error_log("Erreur lors de l'encodage JSON: " . json_last_error_msg());
                return false;
            }

            $result = file_put_contents($file_path, $json_content);
            if ($result === false) {
                error_log("Erreur lors de l'écriture du fichier: " . $file_path);
                return false;
            }

            return true;
        } catch (Exception $e) {
            error_log("Exception lors de la sauvegarde de la structure JSON: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Génère une structure JSON intermédiaire
     */
    private function generate_json_structure($blocks) {
        $structure = [
            'timelines' => [],
            'standaloneAnimations' => []
        ];

        foreach ($blocks as $block) {
            $animation = $this->extract_animation_data($block);
            
            if ($animation['gsapAnimation']['role'] === 'timeline-parent') {
                $timeline = [
                    'id' => $animation['timeline']['timelineId'],
                    'name' => $animation['timeline']['name'],
                    'defaults' => $animation['timeline']['defaults'],
                    'animations' => []
                ];

                // Gestion des différents types de triggers
                if (!empty($animation['trigger'])) {
                    $timeline['trigger'] = $this->handle_trigger($animation['trigger']);
                }

                $structure['timelines'][$animation['timeline']['timelineId']] = $timeline;
            }
            elseif ($animation['gsapAnimation']['role'] === 'timeline-child') {
                if (isset($structure['timelines'][$animation['timeline']['timelineParentId']])) {
                    $structure['timelines'][$animation['timeline']['timelineParentId']]['animations'][] = [
                        'elementId' => $animation['elementId'],
                        'from' => $animation['gsapAnimation']['from'],
                        'to' => $animation['gsapAnimation']['to'],
                        'duration' => $animation['gsapAnimation']['duration'],
                        'ease' => $animation['gsapAnimation']['ease'],
                        'position' => $animation['timeline']['position']
                    ];
                }
            }
            elseif ($animation['gsapAnimation']['role'] === 'standalone') {
                $standalone = [
                    'elementId' => $animation['elementId'],
                    'type' => 'fromTo',
                    'from' => $animation['gsapAnimation']['from'],
                    'to' => $animation['gsapAnimation']['to'],
                    'duration' => $animation['gsapAnimation']['duration'],
                    'ease' => $animation['gsapAnimation']['ease']
                ];

                // Gestion des différents types de triggers pour les animations standalone
                if (!empty($animation['trigger'])) {
                    $standalone['trigger'] = $this->handle_trigger($animation['trigger']);
                }

                $structure['standaloneAnimations'][] = $standalone;
            }
        }

        return $structure;
    }

    private function handle_trigger($trigger_data) {
        if (empty($trigger_data) || empty($trigger_data['type'])) {
            return null;
        }

        $trigger = ['type' => $trigger_data['type']];

        switch ($trigger_data['type']) {
            case 'scroll':
                $trigger = [
                    'type' => 'scroll',
                    'start' => $trigger_data['start'] ?? 'top center',
                    'end' => $trigger_data['end'] ?? 'bottom center',
                    'scrubType' => $trigger_data['scrubType'] ?? 'none',
                    'smoothness' => isset($trigger_data['smoothness']) ? floatval($trigger_data['smoothness']) : 1,
                    'pin' => !empty($trigger_data['pin']),
                    'markers' => !empty($trigger_data['markers']),
                    'reverse' => !empty($trigger_data['reverse'])
                ];
                break;
                
            case 'hover':
            case 'click':
                $trigger = [
                    'type' => $trigger_data['type'],
                    'reverse' => !empty($trigger_data['reverse'])
                ];
                break;
                
            case 'load':
                // Pour load, pas besoin de paramètres supplémentaires
                break;
        }

        return $trigger;
    }

    private function extract_animation_data($block) {
        return [
            'gsapAnimation' => $block['gsapAnimation'],
            'timeline' => $block['timeline'],
            'elementId' => $block['elementId'],
            'trigger' => $block['trigger']
        ];
    }

    private function handle_trigger_js($timeline_id, $trigger, $animation_var = null) {
        if (empty($trigger) || empty($trigger['type'])) {
            return '';
        }

        $js = '';
        switch ($trigger['type']) {
            case 'hover':
                $var_name = $animation_var ?? $timeline_id;
                $js .= "    document.querySelector('#{$timeline_id}').addEventListener('mouseenter', function() {\n";
                $js .= "        " . $var_name . ".play();\n";
                $js .= "    });\n";
                $js .= "    document.querySelector('#{$timeline_id}').addEventListener('mouseleave', function() {\n";
                if (!empty($trigger['reverse'])) {
                    $js .= "        " . $var_name . ".reverse();\n";
                } else {
                    $js .= "        " . $var_name . ".pause();\n";
                }
                $js .= "    });\n";
                break;

            case 'click':
                $var_name = $animation_var ?? $timeline_id;
                $js .= "    document.querySelector('#{$timeline_id}').addEventListener('click', function() {\n";
                if (!empty($trigger['reverse'])) {
                    $js .= "        if (" . $var_name . ".reversed()) {\n";
                    $js .= "            " . $var_name . ".play();\n";
                    $js .= "        } else {\n";
                    $js .= "            " . $var_name . ".reverse();\n";
                    $js .= "        }\n";
                } else {
                    $js .= "        " . $var_name . ".play();\n";
                }
                $js .= "    });\n";
                break;

            case 'scroll':
                $scroll_config = [
                    'trigger' => "#{$timeline_id}",
                    'start' => $trigger['start'] ?? 'top center',
                    'end' => $trigger['end'] ?? 'bottom center',
                    'markers' => !empty($trigger['markers']),
                    'pin' => !empty($trigger['pin'])
                ];

                // Gestion du scrub
                if (!empty($trigger['scrubType'])) {
                    if ($trigger['scrubType'] === 'instant') {
                        $scroll_config['scrub'] = true;
                    } elseif ($trigger['scrubType'] === 'smooth') {
                        $scroll_config['scrub'] = true;
                        $scroll_config['scrubType'] = 'smooth';
                        if (isset($trigger['smoothness'])) {
                            $scroll_config['smoothness'] = floatval($trigger['smoothness']);
                        }
                    } elseif ($trigger['scrubType'] === 'none') {
                        $scroll_config['scrubType'] = 'none';
                    }
                }

                return json_encode($scroll_config);
        }

        return $js;
    }

    private function generate_js_from_json($json) {
        $js = "document.addEventListener('DOMContentLoaded', function() {\n";
        $js .= "    // Initialisation de GSAP et ScrollTrigger\n";
        $js .= "    gsap.registerPlugin(ScrollTrigger);\n\n";

        // Génération des timelines
        if (!empty($json['timelines'])) {
            foreach ($json['timelines'] as $timeline) {
                $js .= "    // " . $timeline['name'] . "\n";
                $js .= "    const " . $timeline['id'] . " = gsap.timeline(";
                
                $timeline_config = [];
                
                // Defaults
                if (!empty($timeline['defaults'])) {
                    $timeline_config['defaults'] = $timeline['defaults'];
                }
                
                // ScrollTrigger
                if (!empty($timeline['trigger']) && $timeline['trigger']['type'] === 'scroll') {
                    $scroll_trigger = $this->handle_trigger_js($timeline['id'], $timeline['trigger']);
                    if ($scroll_trigger) {
                        $timeline_config['scrollTrigger'] = json_decode($scroll_trigger, true);
                    }
                }
                
                $js .= !empty($timeline_config) ? json_encode($timeline_config) : '';
                $js .= ");\n\n";

                // Animations de la timeline
                if (!empty($timeline['animations'])) {
                    foreach ($timeline['animations'] as $animation) {
                        $js .= "    " . $timeline['id'] . "\n";
                        $js .= "        .fromTo(\"#" . $animation['elementId'] . "\",\n";
                        $js .= "            " . json_encode($animation['from']) . ",\n";
                        $js .= "            " . json_encode(array_merge(
                            $animation['to'],
                            [
                                'duration' => $animation['duration'],
                                'ease' => $animation['ease']
                            ]
                        )) . ",\n";
                        $js .= "            \"" . $animation['position'] . "\"\n";
                        $js .= "        )\n";
                    }
                    $js .= "    ;\n\n";
                }

                // Gestion des triggers non-scroll
                if (!empty($timeline['trigger']) && $timeline['trigger']['type'] !== 'scroll') {
                    $js .= $this->handle_trigger_js($timeline['id'], $timeline['trigger'], $timeline['id']);
                }
            }
        }

        // Animations standalone
        if (!empty($json['standaloneAnimations'])) {
            foreach ($json['standaloneAnimations'] as $animation) {
                // Créer une constante pour l'animation
                $animation_var = $animation['elementId'] . "Animation";
                $js .= "    const " . $animation_var . " = gsap.fromTo(\"#" . $animation['elementId'] . "\",\n";
                $js .= "        " . json_encode($animation['from']) . ",\n";
                
                $to_config = array_merge(
                    $animation['to'],
                    [
                        'duration' => $animation['duration'],
                        'ease' => $animation['ease']
                    ]
                );
                
                // ScrollTrigger pour les animations standalone
                if (!empty($animation['trigger']) && $animation['trigger']['type'] === 'scroll') {
                    $scroll_trigger = $this->handle_trigger_js($animation['elementId'], $animation['trigger']);
                    if ($scroll_trigger) {
                        $to_config['scrollTrigger'] = json_decode($scroll_trigger, true);
                    }
                }
                
                $js .= "        " . json_encode($to_config) . "\n";
                $js .= "    );\n\n";

                // Gestion des triggers non-scroll pour les animations standalone
                if (!empty($animation['trigger']) && $animation['trigger']['type'] !== 'scroll') {
                    $js .= $this->handle_trigger_js($animation['elementId'], $animation['trigger'], $animation_var);
                }
            }
        }

        $js .= "});\n";
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