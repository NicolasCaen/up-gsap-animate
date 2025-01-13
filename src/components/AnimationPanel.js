import { __ } from '@wordpress/i18n';
import { 
    PanelBody, 
    SelectControl, 
    RangeControl,
    TextControl,
    ToggleControl,
    Button,
    Flex,
    FlexItem
} from '@wordpress/components';

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
        slideLeft: {
            label: __('Slide Left', 'up-gsap-animate'),
            from: { x: -100, opacity: 0 },
            to: { x: 0, opacity: 1 }
        },
        slideRight: {
            label: __('Slide Right', 'up-gsap-animate'),
            from: { x: 100, opacity: 0 },
            to: { x: 0, opacity: 1 }
        },
        slideUp: {
            label: __('Slide Up', 'up-gsap-animate'),
            from: { y: 100, opacity: 0 },
            to: { y: 0, opacity: 1 }
        },
        slideDown: {
            label: __('Slide Down', 'up-gsap-animate'),
            from: { y: -100, opacity: 0 },
            to: { y: 0, opacity: 1 }
        },
        scale: {
            label: __('Scale', 'up-gsap-animate'),
            from: { scale: 0, opacity: 0 },
            to: { scale: 1, opacity: 1 }
        },
        scaleX: {
            label: __('Scale Horizontal', 'up-gsap-animate'),
            from: { scaleX: 0, opacity: 0 },
            to: { scaleX: 1, opacity: 1 }
        },
        scaleY: {
            label: __('Scale Vertical', 'up-gsap-animate'),
            from: { scaleY: 0, opacity: 0 },
            to: { scaleY: 1, opacity: 1 }
        },
        rotate: {
            label: __('Rotate', 'up-gsap-animate'),
            from: { rotation: -180, opacity: 0 },
            to: { rotation: 0, opacity: 1 }
        },
        flip: {
            label: __('Flip', 'up-gsap-animate'),
            from: { rotationY: -180, opacity: 0 },
            to: { rotationY: 0, opacity: 1 }
        },
        custom: {
            label: __('Custom', 'up-gsap-animate'),
            from: {},
            to: {}
        },
        ...phpAnimations
    };

    // Fusionner les easings prédéfinis avec ceux de PHP
    const easingOptions = [
        { label: 'Power1.out', value: 'power1.out' },
        { label: 'Power2.out', value: 'power2.out' },
        { label: 'Power3.out', value: 'power3.out' },
        { label: 'Power4.out', value: 'power4.out' },
        { label: 'Back.out', value: 'back.out' },
        { label: 'Elastic.out', value: 'elastic.out' },
        { label: 'Bounce.out', value: 'bounce.out' },
        { label: 'Circ.out', value: 'circ.out' },
        { label: 'Expo.out', value: 'expo.out' },
        { label: 'Sine.out', value: 'sine.out' },
        ...phpEasings
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
                        label={__('Trigger Type', 'up-gsap-animate')}
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
                                help={__('Example: top center, 50% 75%', 'up-gsap-animate')}
                                value={trigger.start}
                                onChange={(value) => updateTrigger('start', value)}
                            />
                            <TextControl
                                label={__('End Position', 'up-gsap-animate')}
                                help={__('Optional: Define where the animation ends', 'up-gsap-animate')}
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
                                help={__('Debug mode: Show trigger positions', 'up-gsap-animate')}
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
                            <TextControl
                                label={__('X Position', 'up-gsap-animate')}
                                type="number"
                                value={gsapAnimation.from?.x || 0}
                                onChange={(value) => updateAnimation('from', { ...gsapAnimation.from, x: parseFloat(value) })}
                            />
                            <TextControl
                                label={__('Y Position', 'up-gsap-animate')}
                                type="number"
                                value={gsapAnimation.from?.y || 0}
                                onChange={(value) => updateAnimation('from', { ...gsapAnimation.from, y: parseFloat(value) })}
                            />
                            <TextControl
                                label={__('Scale', 'up-gsap-animate')}
                                type="number"
                                value={gsapAnimation.from?.scale || 1}
                                onChange={(value) => updateAnimation('from', { ...gsapAnimation.from, scale: parseFloat(value) })}
                            />
                            <TextControl
                                label={__('Rotation', 'up-gsap-animate')}
                                type="number"
                                value={gsapAnimation.from?.rotation || 0}
                                onChange={(value) => updateAnimation('from', { ...gsapAnimation.from, rotation: parseFloat(value) })}
                            />
                            <TextControl
                                label={__('Opacity', 'up-gsap-animate')}
                                type="number"
                                value={gsapAnimation.from?.opacity || 0}
                                onChange={(value) => updateAnimation('from', { ...gsapAnimation.from, opacity: parseFloat(value) })}
                                min={0}
                                max={1}
                                step={0.1}
                            />

                            <h3>{__('Final State (To)', 'up-gsap-animate')}</h3>
                            <TextControl
                                label={__('X Position', 'up-gsap-animate')}
                                type="number"
                                value={gsapAnimation.to?.x || 0}
                                onChange={(value) => updateAnimation('to', { ...gsapAnimation.to, x: parseFloat(value) })}
                            />
                            <TextControl
                                label={__('Y Position', 'up-gsap-animate')}
                                type="number"
                                value={gsapAnimation.to?.y || 0}
                                onChange={(value) => updateAnimation('to', { ...gsapAnimation.to, y: parseFloat(value) })}
                            />
                            <TextControl
                                label={__('Scale', 'up-gsap-animate')}
                                type="number"
                                value={gsapAnimation.to?.scale || 1}
                                onChange={(value) => updateAnimation('to', { ...gsapAnimation.to, scale: parseFloat(value) })}
                            />
                            <TextControl
                                label={__('Rotation', 'up-gsap-animate')}
                                type="number"
                                value={gsapAnimation.to?.rotation || 0}
                                onChange={(value) => updateAnimation('to', { ...gsapAnimation.to, rotation: parseFloat(value) })}
                            />
                            <TextControl
                                label={__('Opacity', 'up-gsap-animate')}
                                type="number"
                                value={gsapAnimation.to?.opacity || 1}
                                onChange={(value) => updateAnimation('to', { ...gsapAnimation.to, opacity: parseFloat(value) })}
                                min={0}
                                max={1}
                                step={0.1}
                            />
                        </div>
                    )}
                </>
            )}
        </PanelBody>
    );
};
