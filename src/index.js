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
    return 'gsap-' + Math.random().toString(36).substr(2, 9) + '-' + Date.now();
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

// Composant pour la sidebar du plugin
const PluginAnimationSidebar = () => {
    // Récupérer le bloc sélectionné
    const selectedBlock = useSelect((select) => {
        const { getSelectedBlock } = select(blockEditorStore);
        return getSelectedBlock();
    }, []);

    // Récupérer la fonction pour mettre à jour les attributs du bloc
    const { updateBlockAttributes } = useDispatch(blockEditorStore);

    // Fonction pour mettre à jour les attributs
    const setAttributes = (attributes) => {
        if (selectedBlock) {
            updateBlockAttributes(selectedBlock.clientId, attributes);
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
                {selectedBlock ? (
                    <div className="up-gsap-animate-sidebar-content">
                        <AnimationPanel
                            attributes={selectedBlock.attributes}
                            setAttributes={setAttributes}
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

// Enregistrer le plugin
registerPlugin('up-gsap-animate', {
    icon: <Icon icon={animationIcon} />,
    render: PluginAnimationSidebar
});

// Add the animation attributes to all blocks
addFilter('blocks.registerBlockType', 'up-gsap-animate/add-attributes', addAnimationAttributes);
