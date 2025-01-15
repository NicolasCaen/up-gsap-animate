<?php

use PHPUnit\Framework\TestCase;

class TimelineGeneratorTest extends TestCase {
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
        require_once dirname(dirname(__DIR__)) . '/includes/generators/class-timeline-generator.php';
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
            ),
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
            )
        );

        $generator = new UP_GSAP_Timeline_Generator($animations, $timelines);
        $js = $generator->generate();

        $expected = "
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
        ";

        $this->assert_js_equal($expected, $js);
    }

    public function test_generate_timeline_with_options() {
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

        $generator = new UP_GSAP_Timeline_Generator($animations, $timelines);
        $js = $generator->generate();

        $expected = "
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
        ";

        $this->assert_js_equal($expected, $js);
    }

    public function test_empty_timeline() {
        $timelines = array(
            'timeline1' => array()
        );

        $animations = array();

        $generator = new UP_GSAP_Timeline_Generator($animations, $timelines);
        $js = $generator->generate();

        $expected = "
    // Timeline: timeline1
    const timeline_timeline1 = gsap.timeline({
    });
        ";

        $this->assert_js_equal($expected, $js);
    }
}
