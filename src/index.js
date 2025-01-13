/**
 * WordPress dependencies
 */
import { addFilter } from '@wordpress/hooks';
import { createHigherOrderComponent } from '@wordpress/compose';
import { Fragment } from '@wordpress/element';
import { InspectorControls } from '@wordpress/block-editor';
import { ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { AnimationPanel } from './components/AnimationPanel';

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
                ease: 'power2.out',
                from: { opacity: 0 },
                to: { opacity: 1 }
            }
        },
        trigger: {
            type: 'object',
            default: {
                type: 'scroll',
                start: 'top center',
                end: '',
                scrubType: 'none',
                smoothness: 1,
                pin: false,
                markers: false,
                reverse: true,
                customTrigger: ''
            }
        }
    };
    return settings;
};

// Add custom inspector control to all blocks
const withAnimationControls = createHigherOrderComponent((BlockEdit) => {
    return (props) => {
        const { attributes, setAttributes } = props;
        const { 
            gsapAnimation = { 
                enabled: false, 
                type: 'fade', 
                duration: 1, 
                ease: 'power2.out',
                from: { opacity: 0 },
                to: { opacity: 1 }
            }
        } = attributes;

        return (
            <Fragment>
                <BlockEdit {...props} />
                <InspectorControls>
                    <div className="gsap-animation-controls">
                        <AnimationPanel
                            attributes={attributes}
                            setAttributes={setAttributes}
                        />
                    </div>
                </InspectorControls>
            </Fragment>
        );
    };
}, 'withAnimationControls');

// Add animation data to saved content
const addAnimationData = (extraProps, blockType, attributes) => {
    const { gsapAnimation, trigger } = attributes;

    if (gsapAnimation && gsapAnimation.enabled) {
        extraProps['data-gsap-animation'] = JSON.stringify(gsapAnimation);
        extraProps['data-gsap-trigger'] = JSON.stringify(trigger);
    }

    return extraProps;
};

// Add the animation attributes to all blocks
addFilter(
    'blocks.registerBlockType',
    'up-gsap-animate/add-attributes',
    addAnimationAttributes
);

// Add the animation controls to all blocks
addFilter(
    'editor.BlockEdit',
    'up-gsap-animate/with-animation-controls',
    withAnimationControls
);

// Add the animation data to the saved content
addFilter(
    'blocks.getSaveContent.extraProps',
    'up-gsap-animate/add-animation-data',
    addAnimationData
);
