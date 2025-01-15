<?php

use PHPUnit\Framework\TestCase;

class GeneratorFactoryTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();
        require_once dirname(dirname(__DIR__)) . '/includes/generators/class-base-generator.php';
        require_once dirname(dirname(__DIR__)) . '/includes/generators/class-timeline-generator.php';
        require_once dirname(dirname(__DIR__)) . '/includes/generators/class-standalone-generator.php';
        require_once dirname(dirname(__DIR__)) . '/includes/generators/class-factory-generator.php';
    }

    public function test_create_timeline_generator() {
        $generator = UP_GSAP_Generator_Factory::create('timeline', array(), array());
        $this->assertInstanceOf(UP_GSAP_Timeline_Generator::class, $generator);
    }

    public function test_create_standalone_generator() {
        $generator = UP_GSAP_Generator_Factory::create('standalone', array());
        $this->assertInstanceOf(UP_GSAP_Standalone_Generator::class, $generator);
    }

    public function test_invalid_type() {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Type de générateur inconnu: invalid');
        UP_GSAP_Generator_Factory::create('invalid', array());
    }
}
