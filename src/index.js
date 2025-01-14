/**
 * WordPress dependencies
 */
import { addFilter } from '@wordpress/hooks';
import { createHigherOrderComponent } from '@wordpress/compose';
import { Fragment } from '@wordpress/element';
import { InspectorControls } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { registerPlugin } from '@wordpress/plugins';
import { PluginSidebar, PluginSidebarMoreMenuItem } from '@wordpress/edit-post';
import { PanelBody, Icon } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { store as blockEditorStore } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { AnimationPanel } from './components/AnimationPanel';

// Fonction pour générer un ID unique
const generateUniqueId = () => {
    return 'a' + Math.random().toString(36).substring(2, 7);
};

// Icône d'animation
const animationIcon = (
    <svg width="24" height="24" viewBox="0 0 36 36" xmlns="http://www.w3.org/2000/svg">
        <g>
            <path d="M10.16,31.71a4.4,4.4,0,0,1-4.64-1A4.34,4.34,0,0,1,4.23,27.6a4.41,4.41,0,0,1,.18-1.2,11.61,11.61,0,0,1-1-2.56,6.4,6.4,0,0,0,9.33,8.63A11.55,11.55,0,0,1,10.16,31.71Z"/>
            <path d="M18.41,27.68a7.61,7.61,0,0,1-9.08-1.26,7.58,7.58,0,0,1-1.27-9.06,14.26,14.26,0,0,1-.37-2.85,9.58,9.58,0,0,0,.22,13.33,9.63,9.63,0,0,0,13.35.22A14.46,14.46,0,0,1,18.41,27.68Z"/>
            <path d="M21.66,26.21a12.1,12.1,0,1,1,8.57-3.54h0A12.11,12.11,0,0,1,21.66,26.21ZM21.66,4A10.11,10.11,0,0,0,11.54,14.11a10,10,0,0,0,3,7.14,10.12,10.12,0,0,0,14.31,0A10.11,10.11,0,0,0,21.66,4Zm7.86,18h0Z"/>
        </g>
    </svg>
);

// Add animation attributes to all blocks
const addAnimationAttributes = (settings) => {
    // Make sure we have the anchor support
    if (!settings.supports) {
        settings.supports = {};
    }
    settings.supports.anchor = true;

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
                markers: false
            }
        },
        timeline: {
            type: 'object',
            default: {
                isTimelineParent: false,
                timelineId: '',
                timelineParentId: '',
                position: '+=0',
                stagger: 0.2,
                globalDuration: 1,
                defaults: {
                    ease: 'power2.out',
                    duration: 1
                }
            }
        }
    };
    return settings;
};

// Composant pour la sidebar du plugin
const PluginAnimationSidebar = () => {
    // Récupérer le bloc sélectionné
    const selectedBlock = useSelect((select) => {
        const { getSelectedBlock, getBlocks } = select(blockEditorStore);
        const currentBlock = getSelectedBlock();
        const allBlocks = getBlocks();

        // Debug log
        console.log('Current Block:', currentBlock?.attributes?.timeline);

        // Trouver tous les blocs parents de timeline disponibles
        const availableParents = allBlocks.filter(block => 
            block.attributes?.timeline?.isTimelineParent && 
            block !== currentBlock
        ).map(block => ({
            timelineId: block.attributes.timeline.timelineId,
            name: block.attributes.timeline.name || `Timeline ${block.attributes.timeline.timelineId}`,
            blockName: block.name && block.name.replace('core/', '').replace('-', ' ').replace(/\b\w/g, l => l.toUpperCase())
        }));

        let timelineChildren = [];
        let timelineParent = null;

        // Si le bloc sélectionné est un parent de timeline, récupérer ses enfants
        if (currentBlock?.attributes?.timeline?.isTimelineParent && currentBlock?.attributes?.timeline?.timelineId) {
            const timelineId = currentBlock.attributes.timeline.timelineId;
            timelineChildren = allBlocks.filter(block => 
                block.attributes?.timeline?.timelineParentId === timelineId
            );
            console.log('Timeline Parent - ID:', timelineId);
            console.log('Timeline Parent - Children:', timelineChildren);
        }

        // Si le bloc sélectionné est un enfant de timeline, récupérer son parent
        if (currentBlock?.attributes?.timeline?.timelineParentId) {
            const parentTimelineId = currentBlock.attributes.timeline.timelineParentId;
            timelineParent = allBlocks.find(block => 
                block.attributes?.timeline?.timelineId === parentTimelineId
            );
            // Get siblings (other children of the same parent)
            if (timelineParent) {
                timelineChildren = allBlocks.filter(block => 
                    block.attributes?.timeline?.timelineParentId === parentTimelineId &&
                    block.clientId !== currentBlock.clientId
                );
                console.log('Timeline Child - Parent ID:', parentTimelineId);
                console.log('Timeline Child - Parent:', timelineParent);
                console.log('Timeline Child - Siblings:', timelineChildren);
            }
        }

        // Debug final values
        console.log('Final timelineChildren:', timelineChildren?.length);
        console.log('Final timelineParent:', timelineParent?.attributes?.timeline?.timelineId);

        return { 
            currentBlock, 
            timelineChildren,
            timelineParent,
            availableParents 
        };
    }, []);

    // Récupérer la fonction pour mettre à jour les attributs du bloc
    const { updateBlockAttributes } = useDispatch(blockEditorStore);

    // Fonction pour mettre à jour les attributs
    const setAttributes = (attributes) => {
        if (selectedBlock.currentBlock) {
            updateBlockAttributes(selectedBlock.currentBlock.clientId, attributes);
        }
    };

    return (
        <Fragment>
            <PluginSidebarMoreMenuItem
                target="up-gsap-animate-sidebar"
            >
                {__('Animation Settings', 'up-gsap-animate')}
            </PluginSidebarMoreMenuItem>
            <PluginSidebar
                name="up-gsap-animate-sidebar"
                title={__('Animation Settings', 'up-gsap-animate')}
                icon={<Icon icon={animationIcon} />}
            >
                {selectedBlock.currentBlock ? (
                    <div className="up-gsap-animate-sidebar-content">
                        <AnimationPanel
                            attributes={selectedBlock.currentBlock.attributes}
                            setAttributes={setAttributes}
                            timelineInfo={{
                                isTimelineParent: selectedBlock.currentBlock.attributes?.timeline?.isTimelineParent,
                                timelineChildren: selectedBlock.timelineChildren,
                                timelineParent: selectedBlock.timelineParent,
                                availableParents: selectedBlock.availableParents
                            }}
                        />
                    </div>
                ) : (
                    <div className="up-gsap-animate-sidebar-content">
                        <p>{__('Select a block to configure its animation settings.', 'up-gsap-animate')}</p>
                    </div>
                )}
            </PluginSidebar>
        </Fragment>
    );
};

// Wrap block edit to add animation controls
const withAnimationControls = createHigherOrderComponent((BlockEdit) => {
    return (props) => {
        const { attributes, setAttributes } = props;
        const { gsapAnimation } = attributes;

        // Si l'animation est activée et qu'il n'y a pas d'ancre, en générer une
        React.useEffect(() => {
            if (gsapAnimation?.enabled && !attributes.anchor) {
                setAttributes({ anchor: generateUniqueId() });
            }
        }, [gsapAnimation?.enabled]);

        return <BlockEdit {...props} />;
    };
}, 'withAnimationControls');

// Add animation controls to all blocks
addFilter('editor.BlockEdit', 'up-gsap-animate/with-animation-controls', withAnimationControls);

// Enregistrer le plugin
registerPlugin('up-gsap-animate', {
    icon: <Icon icon={animationIcon} />,
    render: PluginAnimationSidebar
});

// Add the animation attributes to all blocks
addFilter('blocks.registerBlockType', 'up-gsap-animate/add-attributes', addAnimationAttributes);
