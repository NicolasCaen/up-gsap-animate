import gsap from 'gsap';
import ScrollTrigger from 'gsap/ScrollTrigger';
import TimelineManager from './timeline-manager';

gsap.registerPlugin(ScrollTrigger);

class GsapAnimator {
    constructor() {
        this.timelineManager = null;
        this.init();
    }

    init() {
        // Initialiser les animations individuelles
        this.initializeStandaloneAnimations();
        
        // Initialiser le gestionnaire de timeline
        this.timelineManager = new TimelineManager();
    }

    initializeStandaloneAnimations() {
        // Trouver tous les éléments avec des animations qui ne font pas partie d'une timeline
        const animatedElements = document.querySelectorAll('[data-gsap-animation]:not([data-gsap-timeline-parent-id])');

        animatedElements.forEach(element => {
            if (element.dataset.gsapTimelineParent === 'true') return; // Ignorer les parents de timeline

            try {
                const settings = {
                    animation: JSON.parse(element.dataset.gsapAnimation || '{}'),
                    trigger: JSON.parse(element.dataset.gsapTrigger || '{}')
                };

                if (!settings.animation.enabled) return;

                // Appliquer l'état initial
                gsap.set(element, settings.animation.from);

                // Créer l'animation
                const animation = gsap.to(element, {
                    ...settings.animation.to,
                    duration: settings.animation.duration,
                    ease: settings.animation.ease,
                    paused: settings.trigger.type !== 'load'
                });

                // Configurer le trigger
                this.setupTrigger(animation, element, settings.trigger);

            } catch (e) {
                console.error('Error initializing animation:', e);
            }
        });
    }

    setupTrigger(animation, element, trigger) {
        switch (trigger.type) {
            case 'scroll':
                ScrollTrigger.create({
                    trigger: element,
                    start: trigger.start || 'top center',
                    end: trigger.end || null,
                    scrub: trigger.scrubType === 'none' ? false : 
                           trigger.scrubType === 'instant' ? true : 
                           trigger.smoothness,
                    pin: trigger.pin,
                    markers: trigger.markers,
                    onEnter: () => animation.play()
                });
                break;

            case 'load':
                animation.play();
                break;

            case 'hover':
                element.addEventListener('mouseenter', () => animation.play());
                if (trigger.reverse) {
                    element.addEventListener('mouseleave', () => animation.reverse());
                }
                break;

            case 'click':
                let isPlaying = false;
                element.addEventListener('click', () => {
                    if (isPlaying) {
                        if (trigger.reverse) {
                            animation.reverse();
                        }
                    } else {
                        animation.play();
                    }
                    isPlaying = !isPlaying;
                });
                break;
        }
    }
}

// Initialiser l'animateur quand le DOM est chargé
document.addEventListener('DOMContentLoaded', () => {
    new GsapAnimator();
});

export default GsapAnimator;
