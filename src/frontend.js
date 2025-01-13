document.addEventListener('DOMContentLoaded', () => {
    // Initialize ScrollTrigger
    gsap.registerPlugin(ScrollTrigger);

    // Find all elements with GSAP animations
    const animatedElements = document.querySelectorAll('[data-gsap-animation]');

    animatedElements.forEach(element => {
        try {
            // Parse animation and trigger data
            const animationData = JSON.parse(element.dataset.gsapAnimation);
            const triggerData = JSON.parse(element.dataset.gsapTrigger || '{}');
            
            if (!animationData || !animationData.enabled) return;

            const { type, duration, ease, from, to } = animationData;

            // Set initial state
            gsap.set(element, from);

            // Create animation configuration
            const animationConfig = {
                ...to,
                duration,
                ease
            };

            // Configure trigger based on type
            switch (triggerData.type) {
                case 'scroll':
                    animationConfig.scrollTrigger = {
                        trigger: element,
                        start: triggerData.start || 'top 80%',
                        end: triggerData.end || 'bottom 20%',
                        scrub: triggerData.scrubType === 'none' ? false : 
                               triggerData.scrubType === 'smooth' ? triggerData.smoothness : true,
                        pin: triggerData.pin,
                        markers: triggerData.markers,
                        toggleActions: "play none none reverse"
                    };
                    gsap.to(element, animationConfig);
                    break;

                case 'load':
                    gsap.to(element, animationConfig);
                    break;

                case 'click':
                    element.addEventListener('click', () => {
                        gsap.to(element, animationConfig);
                    });
                    break;

                case 'hover':
                    const hoverAnimation = gsap.to(element, animationConfig);
                    hoverAnimation.pause();

                    element.addEventListener('mouseenter', () => {
                        hoverAnimation.play();
                    });

                    if (triggerData.reverse) {
                        element.addEventListener('mouseleave', () => {
                            hoverAnimation.reverse();
                        });
                    }
                    break;

                case 'custom':
                    if (triggerData.customTrigger) {
                        const customTrigger = document.querySelector(triggerData.customTrigger);
                        if (customTrigger) {
                            animationConfig.scrollTrigger = {
                                trigger: customTrigger,
                                start: triggerData.start || 'top 80%',
                                end: triggerData.end || 'bottom 20%',
                                scrub: triggerData.scrubType === 'none' ? false : 
                                       triggerData.scrubType === 'smooth' ? triggerData.smoothness : true,
                                markers: triggerData.markers,
                                toggleActions: "play none none reverse"
                            };
                            gsap.to(element, animationConfig);
                        }
                    }
                    break;
            }

        } catch (error) {
            console.error('Error initializing animation:', error);
        }
    });
});
