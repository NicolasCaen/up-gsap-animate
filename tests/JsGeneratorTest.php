<?php

use PHPUnit\Framework\TestCase;

class JsGeneratorTest extends TestCase {
    private $js_generator;

    protected function setUp(): void {
        parent::setUp();
        require_once dirname(__DIR__) . '/includes/class-js-generator.php';
        $this->js_generator = new UP_GSAP_JS_Generator();
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

        $generator = new UP_GSAP_JS_Generator($animations, array());
        $js = $generator->generate();

        $this->assertStringContainsString('gsap.set(\'#test\\-element\'', $js);
        $this->assertStringContainsString('opacity: 0', $js);
        $this->assertStringContainsString('y: 50', $js);
        $this->assertStringContainsString('gsap.to(\'#test\\-element\'', $js);
        $this->assertStringContainsString('opacity: 1', $js);
        $this->assertStringContainsString('y: 0', $js);
        $this->assertStringContainsString('duration: 1', $js);
        $this->assertStringContainsString('ease: \'power2.out\'', $js);
    }

    public function test_generate_timeline() {
        $timelines = array(
            'timeline1' => array(
                'anchor' => 'timeline-parent',
                'animation' => array(
                    'from' => array('opacity' => 0),
                    'to' => array('opacity' => 1),
                    'duration' => 1.5,
                    'ease' => 'power3.inOut'
                ),
                'trigger' => array(
                    'type' => 'scroll',
                    'element_id' => 'timeline-parent',
                    'start' => 'top center',
                    'scrub' => true
                )
            )
        );

        $generator = new UP_GSAP_JS_Generator(array(), $timelines);
        $js = $generator->generate();

        $this->assertStringContainsString('const timeline_timeline1', $js);
        $this->assertStringContainsString('gsap.timeline', $js);
        $this->assertStringContainsString('scrollTrigger: {', $js);
        $this->assertStringContainsString('trigger: \'#timeline\\-parent\'', $js);
        $this->assertStringContainsString('start: \'top\\ center\'', $js);
        $this->assertStringContainsString('scrub: false', $js);
    }

    public function test_generate_hover_trigger() {
        $animations = array(
            array(
                'anchor' => 'hover-element',
                'animation' => array(
                    'to' => array('scale' => 1.2),
                    'duration' => 0.3,
                    'ease' => 'power2.out'
                ),
                'trigger' => array(
                    'type' => 'hover',
                    'element_id' => 'hover-element'
                )
            )
        );

        $generator = new UP_GSAP_JS_Generator($animations, array());
        $js = $generator->generate();

        $this->assertStringContainsString('paused: true', $js);
        $this->assertStringContainsString('addEventListener(\'mouseenter\'', $js);
        $this->assertStringContainsString('addEventListener(\'mouseleave\'', $js);
        $this->assertStringContainsString('scale: 1.2', $js);
    }

    public function test_generate_click_trigger() {
        $animations = array(
            array(
                'anchor' => 'click-element',
                'animation' => array(
                    'to' => array('rotation' => 360),
                    'duration' => 1,
                    'ease' => 'none'
                ),
                'trigger' => array(
                    'type' => 'click',
                    'element_id' => 'click-element'
                )
            )
        );

        $generator = new UP_GSAP_JS_Generator($animations, array());
        $js = $generator->generate();

        $this->assertStringContainsString('paused: true', $js);
        $this->assertStringContainsString('addEventListener(\'click\'', $js);
        $this->assertStringContainsString('rotation: 360', $js);
    }

    public function test_sanitize_values() {
        $animations = array(
            array(
                'anchor' => 'test-sanitize',
                'animation' => array(
                    'to' => array(
                        'x' => '100px',
                        'backgroundColor' => '#ff0000',
                        'custom' => '<script>alert("xss")</script>'
                    ),
                    'duration' => '1.5'
                )
            )
        );

        $generator = new UP_GSAP_JS_Generator($animations, array());
        $js = $generator->generate();

        $this->assertStringContainsString('x: \'100px\'', $js);
        $this->assertStringContainsString('backgroundColor: \'\\#ff0000\'', $js);
        $this->assertStringNotContainsString('<script>', $js);
        $this->assertStringContainsString('duration: 1.5', $js);
    }
}
