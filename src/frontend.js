document.addEventListener('DOMContentLoaded', () => {
    // Initialize ScrollTrigger
    gsap.registerPlugin(ScrollTrigger);

    // Find all elements with GSAP animations
    const animatedElements = document.querySelectorAll('[data-gsap-animation]');

    animatedElements.forEach(element => {
        try {
            // Parse animation data
            const animationData = JSON.parse(element.dataset.gsapAnimation);
            
            if (!animationData || !animationData.enabled) return;

            const { type, duration, ease } = animationData;

            // Set initial state
            switch (type) {
                case 'fade':
                    gsap.set(element, { opacity: 0 });
                    break;
                case 'slide':
                    gsap.set(element, { x: -100, opacity: 0 });
                    break;
                case 'scale':
                    gsap.set(element, { scale: 0, opacity: 0 });
                    break;
                case 'rotate':
                    gsap.set(element, { rotation: -180, opacity: 0 });
                    break;
            }

            // Create animation
            let animation;
            switch (type) {
                case 'fade':
                    animation = { opacity: 1 };
                    break;
                case 'slide':
                    animation = { x: 0, opacity: 1 };
                    break;
                case 'scale':
                    animation = { scale: 1, opacity: 1 };
                    break;
                case 'rotate':
                    animation = { rotation: 0, opacity: 1 };
                    break;
            }

            // Add ScrollTrigger
            gsap.to(element, {
                ...animation,
                duration,
                ease,
                scrollTrigger: {
                    trigger: element,
                    start: "top 80%", // Démarre quand le haut de l'élément atteint 80% de la hauteur de la fenêtre
                    end: "bottom 20%",
                    toggleActions: "play none none reverse" // play on enter, reverse on leave
                }
            });

        } catch (error) {
            console.error('Error initializing animation:', error);
        }
    });
});
