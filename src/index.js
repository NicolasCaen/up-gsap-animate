/**
 * WordPress dependencies
 */
import { addFilter } from '@wordpress/hooks';
import { createHigherOrderComponent } from '@wordpress/compose';
import { Fragment } from '@wordpress/element';
import { InspectorControls } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { AnimationPanel } from './components/AnimationPanel';

// Fonction pour générer un ID unique
const generateUniqueId = () => {
    return 'gsap-' + Math.random().toString(36).substr(2, 9) + '-' + Date.now();
};

// Add animation attributes to all blocks
const addAnimationAttributes = (settings) => {
    settings.attributes = {
        ...settings.attributes,
        id: {
            type: 'string',
            default: generateUniqueId()
        },
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
                markers: false
            }
        }
    };

    return settings;
};

// Add custom inspector control to all blocks
const withAnimationControls = createHigherOrderComponent((BlockEdit) => {
    return (props) => {
        const { attributes, setAttributes } = props;

        // S'assurer qu'un ID unique est défini
        if (!attributes.id) {
            setAttributes({ id: generateUniqueId() });
        }

        return (
            <Fragment>
                <BlockEdit {...props} />
                <InspectorControls>
                    <AnimationPanel
                        attributes={attributes}
                        setAttributes={setAttributes}
                    />
                </InspectorControls>
            </Fragment>
        );
    };
}, 'withAnimationControls');

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
