<?php

use PHPUnit\Framework\TestCase;

class JsGeneratorTest extends TestCase {
    private $js_generator;

    protected function setUp(): void {
        parent::setUp();
        require_once dirname(__DIR__) . '/includes/class-js-generator.php';
        $this->js_generator = new UP_GSAP_JS_Generator();
    }

    private function normalize_whitespace($str) {
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

        $this->assertStringContainsString('if (typeof gsap === \'undefined\')', $js);
        $this->assertStringContainsString('document.addEventListener(\'DOMContentLoaded\'', $js);
    }

    public function test_generate_simple_animation() {
        $animations = array(
            array(
                'anchor' => 'test-element',
                'animation' => array(
                    'from' => array('opacity' => 0, 'y' => 50),
                    'to' => array('opacity' => 1, 'y' => 0),
                    'duration' => 1,
                    'ease' => 'power2.out'
                )
            )
        );

        $generator = new UP_GSAP_JS_Generator($animations);
        $js = $generator->generate();

        $expected = "
/* GSAP Animations */

if (typeof gsap === 'undefined') {
    console.error('GSAP not loaded. Please make sure to include GSAP library.');
} else {
    document.addEventListener('DOMContentLoaded', function() {
        // Standalone animation
        gsap.set('#test-element', {
            opacity: 0,
            y: 50
        });

        gsap.to('#test-element', {
            opacity: 1,
            y: 0,
            duration: 1,
            ease: 'power2.out'
        });

    });
}";

        $this->assert_js_equal($expected, $js);
    }

    public function test_generate_timeline_with_children() {
        $timelines = array(
            'timeline-1' => array(
                'anchor' => 'parent-element',
                'animation' => array(
                    'from' => array(
                        'opacity' => 0,
                        'y' => 50
                    ),
                    'to' => array(
                        'opacity' => 1,
                        'y' => 0
                    ),
                    'duration' => 1,
                    'ease' => 'power2.out'
                ),
                'trigger' => array(
                    'trigger' => '#parent-element',
                    'start' => 'top center',
                    'toggleActions' => 'play none none reverse'
                )
            )
        );

        $animations = array(
            array(
                'anchor' => 'child-element-1',
                'animation' => array(
                    'from' => array(
                        'opacity' => 0,
                        'x' => -50
                    ),
                    'to' => array(
                        'opacity' => 1,
                        'x' => 0
                    ),
                    'duration' => 0.5,
                    'ease' => 'power1.out'
                ),
                'timeline' => array(
                    'timelineParentId' => 'timeline-1',
                    'position' => '+=0.2'
                )
            )
        );

        $generator = new UP_GSAP_JS_Generator($animations, $timelines);
        $js = $generator->generate();

        $expected = "
/* GSAP Animations */

if (typeof gsap === 'undefined') {
    console.error('GSAP not loaded. Please make sure to include GSAP library.');
} else {
    document.addEventListener('DOMContentLoaded', function() {
        // Timeline: timeline-1
        gsap.set('#parent-element', {
            opacity: 0,
            y: 50
        });

        const timeline_timeline_1 = gsap.timeline({
            scrollTrigger: {
                trigger: '#parent-element',
                start: 'top center',
                toggleActions: 'play none none reverse'
            },
        });

        timeline_timeline_1.to('#parent-element', {
            opacity: 1,
            y: 0,
            duration: 1,
            ease: 'power2.out'
        });

        gsap.set('#child-element-1', {
            opacity: 0,
            x: -50
        });

        timeline_timeline_1.to('#child-element-1', {
            opacity: 1,
            x: 0,
            duration: 0.5,
            ease: 'power1.out',
            position: '+=0.2'
        });

    });
}";

        $this->assert_js_equal($expected, $js);
    }

    public function test_generate_timeline_with_stagger() {
        $timelines = array(
            'timeline-1' => array(
                'anchor' => 'parent-element',
                'animation' => array(
                    'from' => array(
                        'opacity' => 0,
                        'y' => 50
                    ),
                    'to' => array(
                        'opacity' => 1,
                        'y' => 0
                    ),
                    'duration' => 1,
                    'ease' => 'power2.out'
                ),
                'timeline' => array(
                    'stagger' => 0.2,
                    'defaults' => array(
                        'duration' => 0.5,
                        'ease' => 'power1.out'
                    )
                )
            )
        );

        $generator = new UP_GSAP_JS_Generator(array(), $timelines);
        $js = $generator->generate();

        $expected = "
/* GSAP Animations */

if (typeof gsap === 'undefined') {
    console.error('GSAP not loaded. Please make sure to include GSAP library.');
} else {
    document.addEventListener('DOMContentLoaded', function() {
        // Timeline: timeline-1
        gsap.set('#parent-element', {
            opacity: 0,
            y: 50
        });

        const timeline_timeline_1 = gsap.timeline({
            stagger: 0.2,
            defaults: {
                duration: 0.5,
                ease: 'power1.out',
            },
        });

        timeline_timeline_1.to('#parent-element', {
            opacity: 1,
            y: 0,
            duration: 1,
            ease: 'power2.out'
        });

    });
}";

        $this->assert_js_equal($expected, $js);
    }

    public function test_generate_scroll_trigger_animation() {
        $animations = array(
            array(
                'anchor' => 'scroll-element',
                'animation' => array(
                    'from' => array('opacity' => 0, 'y' => 100),
                    'to' => array('opacity' => 1, 'y' => 0),
                    'duration' => 1,
                    'ease' => 'power2.out'
                ),
                'trigger' => array(
                    'type' => 'scroll',
                    'start' => 'top 80%',
                    'end' => 'top 20%',
                    'scrub' => 1,
                    'markers' => true
                )
            )
        );

        $generator = new UP_GSAP_JS_Generator($animations, array());
        $js = $generator->generate();

        $this->assertStringContainsString('scrollTrigger: {', $js);
        $this->assertStringContainsString('start: \'top 80%\'', $js);
        $this->assertStringContainsString('end: \'top 20%\'', $js);
        $this->assertStringContainsString('scrub: 1', $js);
        $this->assertStringContainsString('markers: true', $js);
    }

    public function test_sanitize_values() {
        $animations = array(
            array(
                'anchor' => 'test-element',
                'animation' => array(
                    'from' => array(
                        'content' => '<script>alert("XSS")</script>',
                        'enabled' => true,
                        'count' => 42
                    ),
                    'to' => array(
                        'content' => 'Safe content',
                        'enabled' => false,
                        'count' => 100
                    ),
                    'duration' => 1,
                    'ease' => 'power2.out'
                )
            )
        );

        $generator = new UP_GSAP_JS_Generator($animations);
        $js = $generator->generate();

        $expected = "
/* GSAP Animations */

if (typeof gsap === 'undefined') {
    console.error('GSAP not loaded. Please make sure to include GSAP library.');
} else {
    document.addEventListener('DOMContentLoaded', function() {
        // Standalone animation
        gsap.set('#test-element', {
            content: 'alert(\\\"XSS\\\")',
            enabled: true,
            count: 42
        });

        gsap.to('#test-element', {
            content: 'Safe content',
            enabled: false,
            count: 100,
            duration: 1,
            ease: 'power2.out'
        });

    });
}";

        $this->assert_js_equal($expected, $js);
    }
}
