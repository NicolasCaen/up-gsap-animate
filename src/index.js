/**
 * WordPress dependencies
 */
import { addFilter } from '@wordpress/hooks';
import { createHigherOrderComponent } from '@wordpress/compose';
import { Fragment } from '@wordpress/element';
import { InspectorControls } from '@wordpress/block-editor';
import {
    PanelBody,
    SelectControl,
    ToggleControl,
    RangeControl
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

// Add animation attributes to all blocks
const addAnimationAttributes = (settings) => {
    settings.attributes = {
        ...settings.attributes,
        gsapAnimation: {
            type: 'object',
            default: {
                enabled: false,
                type: 'fade',
                duration: 1,
                ease: 'power2.out'
            }
        }
    };
    return settings;
};

// Add custom inspector control to all blocks
const withAnimationControls = createHigherOrderComponent((BlockEdit) => {
    return (props) => {
        const { attributes, setAttributes } = props;
        const { gsapAnimation = { enabled: false, type: 'fade', duration: 1, ease: 'power2.out' } } = attributes;

        // Update animation settings
        const updateAnimation = (updates) => {
            setAttributes({
                gsapAnimation: {
                    ...gsapAnimation,
                    ...updates
                }
            });
        };

        return (
            <Fragment>
                <BlockEdit {...props} />
                <InspectorControls>
                    <PanelBody
                        title={__('Animation Settings', 'up-gsap-animate')}
                        initialOpen={false}
                    >
                        <ToggleControl
                            label={__('Enable Animation', 'up-gsap-animate')}
                            checked={gsapAnimation.enabled}
                            onChange={(enabled) => updateAnimation({ enabled })}
                            __nextHasNoMarginBottom
                        />

                        {gsapAnimation.enabled && (
                            <>
                                <SelectControl
                                    label={__('Animation Type', 'up-gsap-animate')}
                                    value={gsapAnimation.type}
                                    options={[
                                        { label: __('Fade', 'up-gsap-animate'), value: 'fade' },
                                        { label: __('Slide', 'up-gsap-animate'), value: 'slide' },
                                        { label: __('Scale', 'up-gsap-animate'), value: 'scale' },
                                        { label: __('Rotate', 'up-gsap-animate'), value: 'rotate' }
                                    ]}
                                    onChange={(type) => updateAnimation({ type })}
                                    __nextHasNoMarginBottom
                                />

                                <RangeControl
                                    label={__('Duration (seconds)', 'up-gsap-animate')}
                                    value={gsapAnimation.duration}
                                    onChange={(duration) => updateAnimation({ duration })}
                                    min={0.1}
                                    max={5}
                                    step={0.1}
                                    __nextHasNoMarginBottom
                                />
                            </>
                        )}
                    </PanelBody>
                </InspectorControls>
            </Fragment>
        );
    };
}, 'withAnimationControls');

// Add animation data to saved content
const addAnimationData = (extraProps, blockType, attributes) => {
    const { gsapAnimation } = attributes;

    if (gsapAnimation && gsapAnimation.enabled) {
        return {
            ...extraProps,
            'data-gsap-animation': JSON.stringify(gsapAnimation),
            'className': `${extraProps.className || ''} has-gsap-animation`
        };
    }

    return extraProps;
};

// Add the animation attributes to all blocks
addFilter(
    'blocks.registerBlockType',
    'up-gsap-animate/add-attributes',
    addAnimationAttributes
);

// Add the inspector control to all blocks
addFilter(
    'editor.BlockEdit',
    'up-gsap-animate/with-inspector-control',
    withAnimationControls
);

// Add animation data to saved content
addFilter(
    'blocks.getSaveContent.extraProps',
    'up-gsap-animate/add-animation-data',
    addAnimationData
);
