import { __ } from '@wordpress/i18n';
import { PanelBody, ToggleControl, SelectControl, TextControl, RangeControl } from '@wordpress/components';

// Récupérer les animations depuis PHP
const { animations: phpAnimations = {}, easings: phpEasings = [] } = window.upGsapAnimateSettings || {};

export const AnimationPanel = ({ attributes, setAttributes }) => {
    const { gsapAnimation, trigger } = attributes;

    const updateAnimation = (key, value) => {
        setAttributes({
            gsapAnimation: {
                ...gsapAnimation,
                [key]: value
            }
        });
    };

    const updateTrigger = (key, value) => {
        setAttributes({
            trigger: {
                ...trigger,
                [key]: value
            }
        });
    };

    // Fusionner les animations prédéfinies avec celles de PHP
    const presetAnimations = {
        fade: {
            label: __('Fade', 'up-gsap-animate'),
            from: { opacity: 0 },
            to: { opacity: 1 }
        },
        slideUp: {
            label: __('Slide Up', 'up-gsap-animate'),
            from: { opacity: 0, y: 50 },
            to: { opacity: 1, y: 0 }
        },
        slideDown: {
            label: __('Slide Down', 'up-gsap-animate'),
            from: { opacity: 0, y: -50 },
            to: { opacity: 1, y: 0 }
        },
        slideLeft: {
            label: __('Slide Left', 'up-gsap-animate'),
            from: { opacity: 0, x: -50 },
            to: { opacity: 1, x: 0 }
        },
        slideRight: {
            label: __('Slide Right', 'up-gsap-animate'),
            from: { opacity: 0, x: 50 },
            to: { opacity: 1, x: 0 }
        },
        scale: {
            label: __('Scale', 'up-gsap-animate'),
            from: { opacity: 0, scale: 0.5 },
            to: { opacity: 1, scale: 1 }
        },
        rotate: {
            label: __('Rotate', 'up-gsap-animate'),
            from: { opacity: 0, rotation: 180 },
            to: { opacity: 1, rotation: 0 }
        },
        custom: {
            label: __('Custom', 'up-gsap-animate'),
            from: { opacity: 0 },
            to: { opacity: 1 }
        },
        ...phpAnimations
    };

    // Fusionner les easings prédéfinis avec ceux de PHP
    const easingOptions = [
        { label: 'Power1 Out', value: 'power1.out' },
        { label: 'Power2 Out', value: 'power2.out' },
        { label: 'Power3 Out', value: 'power3.out' },
        { label: 'Back Out', value: 'back.out' },
        { label: 'Elastic Out', value: 'elastic.out' },
        { label: 'Bounce Out', value: 'bounce.out' },
        ...phpEasings.map(easing => ({
            label: easing.label || easing.value,
            value: easing.value
        }))
    ];

    return (
        <PanelBody title={__('Animation Settings', 'up-gsap-animate')} initialOpen={false}>
            <ToggleControl
                label={__('Enable Animation', 'up-gsap-animate')}
                checked={gsapAnimation.enabled}
                onChange={(enabled) => setAttributes({
                    gsapAnimation: {
                        ...gsapAnimation,
                        enabled
                    }
                })}
            />

            {gsapAnimation.enabled && (
                <>
                    <SelectControl
                        label={__('Animation Type', 'up-gsap-animate')}
                        value={gsapAnimation.type}
                        options={Object.entries(presetAnimations).map(([value, { label }]) => ({
                            label,
                            value
                        }))}
                        onChange={(type) => {
                            const preset = presetAnimations[type];
                            setAttributes({
                                gsapAnimation: {
                                    ...gsapAnimation,
                                    type,
                                    from: preset.from,
                                    to: preset.to
                                }
                            });
                        }}
                    />

                    <RangeControl
                        label={__('Duration (seconds)', 'up-gsap-animate')}
                        value={gsapAnimation.duration}
                        onChange={(duration) => updateAnimation('duration', duration)}
                        min={0.1}
                        max={5}
                        step={0.1}
                    />

                    <SelectControl
                        label={__('Easing', 'up-gsap-animate')}
                        value={gsapAnimation.ease}
                        options={easingOptions}
                        onChange={(ease) => updateAnimation('ease', ease)}
                    />

                    <SelectControl
                        label={__('Trigger', 'up-gsap-animate')}
                        value={trigger.type}
                        options={[
                            { label: 'Scroll', value: 'scroll' },
                            { label: 'Load', value: 'load' },
                            { label: 'Click', value: 'click' },
                            { label: 'Hover', value: 'hover' },
                            { label: 'Custom', value: 'custom' }
                        ]}
                        onChange={(value) => updateTrigger('type', value)}
                    />

                    {trigger.type === 'scroll' && (
                        <>
                            <TextControl
                                label={__('Start Position', 'up-gsap-animate')}
                                help={__('Example: top center, center center', 'up-gsap-animate')}
                                value={trigger.start}
                                onChange={(value) => updateTrigger('start', value)}
                            />
                            <TextControl
                                label={__('End Position', 'up-gsap-animate')}
                                help={__('Optional. Leave empty for default', 'up-gsap-animate')}
                                value={trigger.end}
                                onChange={(value) => updateTrigger('end', value)}
                            />
                            <SelectControl
                                label={__('Scrub Type', 'up-gsap-animate')}
                                value={trigger.scrubType}
                                options={[
                                    { label: 'None', value: 'none' },
                                    { label: 'True', value: 'true' },
                                    { label: 'Smooth', value: 'smooth' }
                                ]}
                                onChange={(value) => updateTrigger('scrubType', value)}
                            />
                            {trigger.scrubType === 'smooth' && (
                                <RangeControl
                                    label={__('Smoothness', 'up-gsap-animate')}
                                    value={trigger.smoothness}
                                    onChange={(value) => updateTrigger('smoothness', value)}
                                    min={0.1}
                                    max={5}
                                    step={0.1}
                                />
                            )}
                            <ToggleControl
                                label={__('Pin Element', 'up-gsap-animate')}
                                checked={trigger.pin}
                                onChange={(value) => updateTrigger('pin', value)}
                            />
                            <ToggleControl
                                label={__('Show Markers', 'up-gsap-animate')}
                                checked={trigger.markers}
                                onChange={(value) => updateTrigger('markers', value)}
                            />
                        </>
                    )}

                    {trigger.type === 'hover' && (
                        <ToggleControl
                            label={__('Reverse on Leave', 'up-gsap-animate')}
                            checked={trigger.reverse}
                            onChange={(value) => updateTrigger('reverse', value)}
                        />
                    )}

                    {trigger.type === 'custom' && (
                        <TextControl
                            label={__('Custom Trigger', 'up-gsap-animate')}
                            help={__('CSS selector or element ID', 'up-gsap-animate')}
                            value={trigger.customTrigger}
                            onChange={(value) => updateTrigger('customTrigger', value)}
                        />
                    )}

                    {gsapAnimation.type === 'custom' && (
                        <div className="gsap-custom-animation">
                            <h3>{__('Initial State (From)', 'up-gsap-animate')}</h3>
                            <RangeControl
                                label={__('Opacity', 'up-gsap-animate')}
                                value={gsapAnimation.from?.opacity || 0}
                                onChange={(value) => updateAnimation('from', { ...gsapAnimation.from, opacity: value })}
                                min={0}
                                max={1}
                                step={0.1}
                            />
                            <RangeControl
                                label={__('Y Position (px)', 'up-gsap-animate')}
                                value={gsapAnimation.from?.y || 0}
                                onChange={(value) => updateAnimation('from', { ...gsapAnimation.from, y: value })}
                                min={-100}
                                max={100}
                                step={1}
                            />
                            <h3>{__('Final State (To)', 'up-gsap-animate')}</h3>
                            <RangeControl
                                label={__('Opacity', 'up-gsap-animate')}
                                value={gsapAnimation.to?.opacity || 1}
                                onChange={(value) => updateAnimation('to', { ...gsapAnimation.to, opacity: value })}
                                min={0}
                                max={1}
                                step={0.1}
                            />
                            <RangeControl
                                label={__('Y Position (px)', 'up-gsap-animate')}
                                value={gsapAnimation.to?.y || 0}
                                onChange={(value) => updateAnimation('to', { ...gsapAnimation.to, y: value })}
                                min={-100}
                                max={100}
                                step={1}
                            />
                        </div>
                    )}
                </>
            )}
        </PanelBody>
    );
};
