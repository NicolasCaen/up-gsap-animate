<?php

use PHPUnit\Framework\TestCase;

class JsGeneratorTest extends TestCase {
    private $js_generator;

    protected function setUp(): void {
        parent::setUp();
        require_once dirname(__DIR__) . '/includes/generators/class-base-generator.php';
        require_once dirname(__DIR__) . '/includes/generators/class-timeline-generator.php';
        require_once dirname(__DIR__) . '/includes/generators/class-standalone-generator.php';
        require_once dirname(__DIR__) . '/includes/generators/class-factory-generator.php';
        require_once dirname(__DIR__) . '/includes/class-js-generator.php';
        $this->js_generator = new UP_GSAP_JS_Generator();
    }

    private function normalize_whitespace($str) {
        // Normaliser les guillemets
        $str = str_replace(array('\"', '"'), "'", $str);
        // Normaliser les espaces
        return preg_replace('/\s+/', ' ', trim($str));
    }

    private function assert_js_equal($expected, $actual) {
        $this->assertEquals(
            $this->normalize_whitespace($expected),
            $this->normalize_whitespace($actual)
        );
    }

    public function test_generate_empty_animations() {
        $generator = new UP_GSAP_JS_Generator(array(), array());
        $js = $generator->generate();

        $expected = "document.addEventListener('DOMContentLoaded', function() {
            gsap.registerPlugin(ScrollTrigger);
        });";

        $this->assert_js_equal($expected, $js);
    }

    public function test_generate_simple_animation() {
        $animations = array(
            array(
                'type' => 'standalone',
                'anchor' => 'test-element',
                'animation' => array(
                    'from' => array('opacity' => 0, 'y' => 50),
                    'duration' => 1,
                    'ease' => 'power2.out'
                )
            )
        );

        $generator = new UP_GSAP_JS_Generator($animations);
        $js = $generator->generate();

        $expected = "document.addEventListener('DOMContentLoaded', function() {
            gsap.registerPlugin(ScrollTrigger);

            // Animation standalone
            gsap.from('#test-element', {
                opacity: 0,
                y: 50,
                duration: 1,
                ease: 'power2.out'
            });
        });";

        $this->assert_js_equal($expected, $js);
    }

    public function test_generate_timeline_with_children() {
        $timelines = array(
            'timeline1' => array(
                'trigger' => array(
                    'start' => 'top center',
                    'pin' => true,
                    'markers' => true
                )
            )
        );

        $animations = array(
            array(
                'type' => 'timeline-child',
                'timelineParentId' => 'timeline1',
                'anchor' => 'timeline1b',
                'animation' => array(
                    'from' => array(
                        'opacity' => 0
                    ),
                    'duration' => 1,
                    'ease' => 'power2.out'
                )
            ),
            array(
                'type' => 'timeline-child',
                'timelineParentId' => 'timeline1',
                'anchor' => 'img1',
                'animation' => array(
                    'from' => array(
                        'opacity' => 0,
                        'x' => 100
                    ),
                    'duration' => 3.5,
                    'ease' => 'power2.out'
                )
            )
        );

        $generator = new UP_GSAP_JS_Generator($animations, $timelines);
        $js = $generator->generate();

        $expected = "document.addEventListener('DOMContentLoaded', function() {
            gsap.registerPlugin(ScrollTrigger);

            // Timeline: timeline1
            const timeline_timeline1 = gsap.timeline({
                scrollTrigger: {
                    trigger: '#timeline1',
                    start: 'top center',
                    pin: true,
                    pinSpacing: true,
                    markers: true
                }
            });

            // Animations de la timeline
            timeline_timeline1
                .from('#timeline1b', {
                    opacity: 0,
                    duration: 1,
                    ease: 'power2.out'
                })
                .from('#img1', {
                    opacity: 0,
                    x: 100,
                    duration: 3.5,
                    ease: 'power2.out'
                }, '-=0.5');
        });";

        $this->assert_js_equal($expected, $js);
    }

    public function test_generate_timeline_with_stagger() {
        $timelines = array(
            'timeline1' => array(
                'options' => array(
                    'defaults' => array(
                        'duration' => 1,
                        'ease' => 'power2.out'
                    ),
                    'stagger' => 0.2
                )
            )
        );

        $animations = array(
            array(
                'type' => 'timeline-child',
                'timelineParentId' => 'timeline1',
                'anchor' => 'element1',
                'animation' => array(
                    'from' => array('opacity' => 0)
                )
            ),
            array(
                'type' => 'timeline-child',
                'timelineParentId' => 'timeline1',
                'anchor' => 'element2',
                'animation' => array(
                    'from' => array('opacity' => 0, 'y' => 50)
                )
            )
        );

        $generator = new UP_GSAP_JS_Generator($animations, $timelines);
        $js = $generator->generate();

        $expected = "document.addEventListener('DOMContentLoaded', function() {
            gsap.registerPlugin(ScrollTrigger);

            // Timeline: timeline1
            const timeline_timeline1 = gsap.timeline({
                defaults: {
                    duration: 1,
                    ease: 'power2.out'
                },
                stagger: 0.2
            });

            // Animations de la timeline
            timeline_timeline1
                .from('#element1', {
                    opacity: 0
                })
                .from('#element2', {
                    opacity: 0,
                    y: 50
                }, '-=0.5');
        });";

        $this->assert_js_equal($expected, $js);
    }

    public function test_generate_scroll_trigger_animation() {
        $animations = array(
            array(
                'type' => 'standalone',
                'anchor' => 'test-element',
                'animation' => array(
                    'from' => array('opacity' => 0, 'y' => 100),
                    'duration' => 1,
                    'ease' => 'power2.out'
                ),
                'trigger' => array(
                    'start' => 'top center',
                    'pin' => true
                )
            )
        );

        $generator = new UP_GSAP_JS_Generator($animations);
        $js = $generator->generate();

        $expected = "document.addEventListener('DOMContentLoaded', function() {
            gsap.registerPlugin(ScrollTrigger);

            // Animation standalone
            gsap.from('#test-element', {
                opacity: 0,
                y: 100,
                duration: 1,
                ease: 'power2.out',
                scrollTrigger: {
                    trigger: '#test-element',
                    start: 'top center',
                    pin: true,
                    pinSpacing: true,
                    toggleActions: 'play none none reverse'
                }
            });
        });";

        $this->assert_js_equal($expected, $js);
    }

    public function test_sanitize_values() {
        $animations = array(
            array(
                'type' => 'standalone',
                'anchor' => 'test-element',
                'animation' => array(
                    'from' => array(
                        'content' => '<script>alert("XSS")</script>',
                        'enabled' => true,
                        'count' => 42
                    ),
                    'duration' => 1,
                    'ease' => 'power2.out'
                )
            )
        );

        $generator = new UP_GSAP_JS_Generator($animations);
        $js = $generator->generate();

        $expected = "document.addEventListener('DOMContentLoaded', function() {
            gsap.registerPlugin(ScrollTrigger);

            // Animation standalone
            gsap.from('#test-element', {
                content: 'alert(\"XSS\")',
                enabled: true,
                count: 42,
                duration: 1,
                ease: 'power2.out'
            });
        });";

        $this->assert_js_equal($expected, $js);
    }
}
