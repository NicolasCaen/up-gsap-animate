<?php

use PHPUnit\Framework\TestCase;

class StandaloneGeneratorTest extends TestCase {
    private function normalize_whitespace($str) {
        return preg_replace('/\s+/', ' ', trim($str));
    }

    private function assert_js_equal($expected, $actual) {
        $this->assertEquals(
            $this->normalize_whitespace($expected),
            $this->normalize_whitespace($actual)
        );
    }

    protected function setUp(): void {
        parent::setUp();
        require_once dirname(dirname(__DIR__)) . '/includes/generators/class-base-generator.php';
        require_once dirname(dirname(__DIR__)) . '/includes/generators/class-standalone-generator.php';
    }

    public function test_generate_standalone_animations() {
        $animations = array(
            array(
                'type' => 'standalone',
                'anchor' => 'timeline1a',
                'animation' => array(
                    'from' => array(
                        'opacity' => 0
                    ),
                    'duration' => 2.7,
                    'ease' => 'power2.out'
                )
            ),
            array(
                'type' => 'standalone',
                'anchor' => 'az5bwh',
                'animation' => array(
                    'from' => array(
                        'opacity' => 0,
                        'y' => 100
                    ),
                    'duration' => 1,
                    'ease' => 'power2.out'
                ),
                'trigger' => array(
                    'start' => 'top center',
                    'pin' => true
                )
            )
        );

        $generator = new UP_GSAP_Standalone_Generator($animations);
        $js = $generator->generate();

        $expected = "
    // Animation standalone
    gsap.from('#timeline1a', {
        opacity: 0,
        duration: 2.7,
        ease: 'power2.out'
    });

    // Animation standalone
    gsap.from('#az5bwh', {
        opacity: 0,
        y: 100,
        duration: 1,
        ease: 'power2.out',
        scrollTrigger: {
            trigger: '#az5bwh',
            start: 'top center',
            pin: true,
            pinSpacing: true,
            toggleActions: 'play none none reverse'
        }
    });
        ";

        $this->assert_js_equal($expected, $js);
    }

    public function test_ignore_timeline_child() {
        $animations = array(
            array(
                'type' => 'timeline-child',
                'timelineParentId' => 'timeline1',
                'anchor' => 'child1',
                'animation' => array(
                    'from' => array('opacity' => 0)
                )
            ),
            array(
                'type' => 'standalone',
                'anchor' => 'standalone1',
                'animation' => array(
                    'from' => array('opacity' => 0)
                )
            )
        );

        $generator = new UP_GSAP_Standalone_Generator($animations);
        $js = $generator->generate();

        $expected = "
    // Animation standalone
    gsap.from('#standalone1', {
        opacity: 0
    });
        ";

        $this->assert_js_equal($expected, $js);
    }

    public function test_empty_animations() {
        $animations = array();

        $generator = new UP_GSAP_Standalone_Generator($animations);
        $js = $generator->generate();

        $this->assertEquals("", $js);
    }
}
