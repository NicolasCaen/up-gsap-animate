import gsap from 'gsap';
import ScrollTrigger from 'gsap/ScrollTrigger';

gsap.registerPlugin(ScrollTrigger);

class TimelineManager {
    constructor() {
        this.timelines = new Map();
        this.init();
    }

    init() {
        // Trouver tous les blocs parents de timeline
        const timelineParents = document.querySelectorAll('[data-gsap-timeline-parent]');
        
        timelineParents.forEach(parent => {
            const timelineId = parent.dataset.gsapTimelineId;
            const settings = this.getTimelineSettings(parent);
            
            // Créer la timeline
            const timeline = gsap.timeline({
                defaults: settings.defaults,
                scrollTrigger: settings.trigger.type === 'scroll' ? {
                    trigger: parent,
                    start: settings.trigger.start || 'top center',
                    end: settings.trigger.end || null,
                    scrub: settings.trigger.scrubType === 'none' ? false : 
                           settings.trigger.scrubType === 'instant' ? true : 
                           settings.trigger.smoothness,
                    pin: settings.trigger.pin,
                    markers: settings.trigger.markers,
                } : null
            });

            // Ajouter la timeline à notre map
            this.timelines.set(timelineId, {
                timeline,
                parent,
                settings
            });

            // Si ce n'est pas un scroll trigger, configurer d'autres types de trigger
            if (settings.trigger.type !== 'scroll') {
                this.setupTrigger(timeline, parent, settings.trigger);
            }

            // Trouver et animer les enfants
            this.animateChildren(timelineId);
        });
    }

    getTimelineSettings(element) {
        try {
            return {
                defaults: JSON.parse(element.dataset.gsapTimelineDefaults || '{}'),
                trigger: JSON.parse(element.dataset.gsapTrigger || '{}'),
                stagger: parseFloat(element.dataset.gsapTimelineStagger || 0.2),
                globalDuration: parseFloat(element.dataset.gsapTimelineGlobalDuration || 1)
            };
        } catch (e) {
            console.error('Error parsing timeline settings:', e);
            return {
                defaults: {},
                trigger: {},
                stagger: 0.2,
                globalDuration: 1
            };
        }
    }

    getAnimationSettings(element) {
        try {
            return {
                from: JSON.parse(element.dataset.gsapFrom || '{}'),
                to: JSON.parse(element.dataset.gsapTo || '{}'),
                duration: parseFloat(element.dataset.gsapDuration || 1),
                ease: element.dataset.gsapEase || 'power2.out',
                position: element.dataset.gsapPosition || '+=0'
            };
        } catch (e) {
            console.error('Error parsing animation settings:', e);
            return {
                from: {},
                to: {},
                duration: 1,
                ease: 'power2.out',
                position: '+=0'
            };
        }
    }

    setupTrigger(timeline, element, trigger) {
        switch (trigger.type) {
            case 'load':
                // Jouer automatiquement
                timeline.play();
                break;

            case 'hover':
                element.addEventListener('mouseenter', () => timeline.play());
                if (trigger.reverse) {
                    element.addEventListener('mouseleave', () => timeline.reverse());
                }
                break;

            case 'click':
                let isPlaying = false;
                element.addEventListener('click', () => {
                    if (isPlaying) {
                        if (trigger.reverse) {
                            timeline.reverse();
                        }
                    } else {
                        timeline.play();
                    }
                    isPlaying = !isPlaying;
                });
                break;
        }
    }

    animateChildren(timelineId) {
        const { timeline, parent, settings } = this.timelines.get(timelineId);
        const children = document.querySelectorAll(`[data-gsap-timeline-parent-id="${timelineId}"]`);

        children.forEach((child, index) => {
            const animation = this.getAnimationSettings(child);
            
            // Appliquer l'état initial
            gsap.set(child, animation.from);

            // Ajouter à la timeline
            timeline.to(
                child,
                {
                    ...animation.to,
                    duration: animation.duration,
                    ease: animation.ease,
                },
                animation.position === '>' ? '>' :
                animation.position === '<' ? '<' :
                animation.position || `+=${settings.stagger * index}`
            );
        });
    }
}

// Initialiser le gestionnaire de timeline quand le DOM est chargé
document.addEventListener('DOMContentLoaded', () => {
    new TimelineManager();
});

export default TimelineManager;
