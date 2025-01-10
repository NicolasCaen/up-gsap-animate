import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';

const PreviewButton = ({ animation, clientId }) => {
    const [isPlaying, setIsPlaying] = useState(false);
    const [gsapLoaded, setGsapLoaded] = useState(false);

    useEffect(() => {
        const checkGsap = () => {
            if (window.gsap) {
                setGsapLoaded(true);
            } else {
                setTimeout(checkGsap, 100);
            }
        };
        checkGsap();
    }, []);

    const getBlockElement = (clientId) => {
        const selectors = [
            `[data-block="${clientId}"]`,
            `[data-type][data-block="${clientId}"]`,
            `.block-editor-block-list__block[data-block="${clientId}"]`,
            `#block-${clientId}`
        ];

        for (const selector of selectors) {
            const element = document.querySelector(selector);
            if (element) {
                console.log('Found block with selector:', selector);
                return element;
            }
        }

        const editor = document.querySelector('iframe[name="editor-canvas"]');
        if (editor?.contentDocument) {
            for (const selector of selectors) {
                const element = editor.contentDocument.querySelector(selector);
                if (element) {
                    console.log('Found block in iframe with selector:', selector);
                    return element;
                }
            }
        }

        return null;
    };

    const getAnimationProperties = (type) => {
        const defaultProperties = {
            fade: {
                from: { opacity: 0 },
                to: { opacity: 1 }
            },
            slide: {
                from: { opacity: 0, x: -100 },
                to: { opacity: 1, x: 0 }
            },
            scale: {
                from: { opacity: 0, scale: 0 },
                to: { opacity: 1, scale: 1 }
            },
            rotate: {
                from: { opacity: 0, rotation: -180 },
                to: { opacity: 1, rotation: 0 }
            }
        };

        return defaultProperties[type] || defaultProperties.fade;
    };

    const playPreview = () => {
        if (!animation?.enabled || isPlaying || !gsapLoaded || !window.gsap) {
            console.warn('Animation not ready:', {
                enabled: animation?.enabled,
                isPlaying,
                gsapLoaded,
                gsapExists: !!window.gsap,
                animation
            });
            return;
        }

        const gsap = window.gsap;
        const blockElement = getBlockElement(clientId);
        
        if (!blockElement) {
            console.warn('Block element not found. Details:', {
                clientId,
                availableBlocks: document.querySelectorAll('[data-block]').length,
                iframeExists: !!document.querySelector('iframe[name="editor-canvas"]')
            });
            return;
        }

        setIsPlaying(true);

        // Sauvegarder les propriétés initiales
        const originalProps = {
            transform: blockElement.style.transform || 'none',
            opacity: blockElement.style.opacity || '1',
            transition: blockElement.style.transition || 'none',
            visibility: blockElement.style.visibility || 'visible'
        };

        // Désactiver les transitions CSS
        blockElement.style.transition = 'none';
        blockElement.style.visibility = 'visible';

        // Obtenir les propriétés d'animation
        const properties = getAnimationProperties(animation.type);

        try {
            // Appliquer l'animation
            gsap.set(blockElement, {
                ...properties.from,
                clearProps: 'all'
            });

            gsap.to(blockElement, {
                ...properties.to,
                duration: animation.duration || 1,
                ease: animation.ease || 'power2.out',
                onComplete: () => {
                    setIsPlaying(false);
                    Object.assign(blockElement.style, originalProps);
                }
            });
        } catch (error) {
            console.error('Animation error:', error);
            setIsPlaying(false);
            Object.assign(blockElement.style, originalProps);
        }
    };

    if (!gsapLoaded) {
        return (
            <Button
                variant="secondary"
                disabled
                className="upgsap-preview-button"
            >
                {__('Loading GSAP...', 'up-gsap-animate')}
            </Button>
        );
    }

    return (
        <Button
            variant="secondary"
            icon={isPlaying ? 'controls-pause' : 'controls-play'}
            onClick={playPreview}
            disabled={isPlaying || !animation?.enabled}
            className="upgsap-preview-button"
        >
            {isPlaying ? __('Playing...', 'up-gsap-animate') : __('Preview Animation', 'up-gsap-animate')}
        </Button>
    );
};

export default PreviewButton;
