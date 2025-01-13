import { __ } from '@wordpress/i18n';
import { 
    PanelBody, 
    ToggleControl, 
    SelectControl, 
    TextControl, 
    RangeControl,
    Button,
    TextareaControl,
    Panel
} from '@wordpress/components';

// Récupérer les animations depuis PHP
const { animations: phpAnimations = {}, easings: phpEasings = [] } = window.upGsapAnimateSettings || {};

export const AnimationPanel = ({ attributes, setAttributes, timelineInfo }) => {
    const { gsapAnimation, trigger, timeline } = attributes;
    const { isTimelineParent, timelineChildren, timelineParent } = timelineInfo || {};

    const updateGsapAnimation = (value) => {
        setAttributes({
            gsapAnimation: {
                ...gsapAnimation,
                ...value
            }
        });
    };

    const updateTrigger = (value) => {
        setAttributes({
            trigger: {
                ...trigger,
                ...value
            }
        });
    };

    const updateTimeline = (value) => {
        const newTimeline = {
            ...timeline,
            ...value
        };

        // Si on active le mode timeline parent, générer un ID unique
        if (value.isTimelineParent && !timeline.timelineId) {
            newTimeline.timelineId = 'timeline-' + Math.random().toString(36).substr(2, 9);
        }

        setAttributes({
            timeline: newTimeline
        });
    };

    // Animations prédéfinies
    const presetAnimations = {
        fade: {
            label: __('Fade', 'up-gsap-animate'),
            from: { opacity: 0 },
            to: { opacity: 1 }
        },
        slideUp: {
            label: __('Slide Up', 'up-gsap-animate'),
            from: { opacity: 0, y: 100 },
            to: { opacity: 1, y: 0 }
        },
        slideDown: {
            label: __('Slide Down', 'up-gsap-animate'),
            from: { opacity: 0, y: -100 },
            to: { opacity: 1, y: 0 }
        },
        slideLeft: {
            label: __('Slide Left', 'up-gsap-animate'),
            from: { opacity: 0, x: -100 },
            to: { opacity: 1, x: 0 }
        },
        slideRight: {
            label: __('Slide Right', 'up-gsap-animate'),
            from: { opacity: 0, x: 100 },
            to: { opacity: 1, x: 0 }
        },
        scale: {
            label: __('Scale', 'up-gsap-animate'),
            from: { opacity: 0, scale: 0.5 },
            to: { opacity: 1, scale: 1 }
        },
        ...phpAnimations
    };

    // Options d'easing
    const easingOptions = [
        { label: 'Power1 Out', value: 'power1.out' },
        { label: 'Power2 Out', value: 'power2.out' },
        { label: 'Power3 Out', value: 'power3.out' },
        { label: 'Power4 Out', value: 'power4.out' },
        { label: 'Back Out', value: 'back.out(1.7)' },
        { label: 'Elastic Out', value: 'elastic.out(1, 0.3)' },
        { label: 'Bounce Out', value: 'bounce.out' },
        ...phpEasings
    ];

    return (
        <>
            <PanelBody
                title={__('Timeline Settings', 'up-gsap-animate')}
                initialOpen={true}
                className="up-gsap-timeline-section"
            >
                <ToggleControl
                    label={__('Timeline Parent', 'up-gsap-animate')}
                    help={__('Make this block a timeline parent', 'up-gsap-animate')}
                    checked={timeline.isTimelineParent}
                    onChange={(isTimelineParent) => updateTimeline({ isTimelineParent })}
                />

                {timeline.isTimelineParent && (
                    <>
                        <RangeControl
                            label={__('Global Duration', 'up-gsap-animate')}
                            value={timeline.globalDuration}
                            onChange={(globalDuration) => updateTimeline({ globalDuration })}
                            min={0.1}
                            max={10}
                            step={0.1}
                        />
                        <RangeControl
                            label={__('Stagger', 'up-gsap-animate')}
                            help={__('Delay between child animations', 'up-gsap-animate')}
                            value={timeline.stagger}
                            onChange={(stagger) => updateTimeline({ stagger })}
                            min={0}
                            max={2}
                            step={0.1}
                        />
                        {timelineChildren && timelineChildren.length > 0 && (
                            <Panel>
                                <PanelBody title={__('Timeline Children', 'up-gsap-animate')} initialOpen={false}>
                                    {timelineChildren.map((child, index) => (
                                        <div key={child.clientId} className="timeline-child-item">
                                            <strong>{__('Block', 'up-gsap-animate')} {index + 1}</strong>
                                            <p>{child.name}</p>
                                        </div>
                                    ))}
                                </PanelBody>
                            </Panel>
                        )}
                    </>
                )}

                {!timeline.isTimelineParent && !timeline.timelineParentId && (
                    <Button
                        variant="secondary"
                        onClick={() => {
                            if (timelineParent) {
                                updateTimeline({ timelineParentId: timelineParent.attributes.timeline.timelineId });
                            }
                        }}
                        disabled={!timelineParent}
                    >
                        {__('Add to Timeline', 'up-gsap-animate')}
                    </Button>
                )}

                {timeline.timelineParentId && (
                    <>
                        <TextControl
                            label={__('Position in Timeline', 'up-gsap-animate')}
                            help={__('Example: +=0.5, >, <', 'up-gsap-animate')}
                            value={timeline.position}
                            onChange={(position) => updateTimeline({ position })}
                        />
                        <Button
                            variant="secondary"
                            onClick={() => updateTimeline({ timelineParentId: '' })}
                        >
                            {__('Remove from Timeline', 'up-gsap-animate')}
                        </Button>
                    </>
                )}
            </PanelBody>

            <PanelBody
                title={__('Animation', 'up-gsap-animate')}
                initialOpen={true}
                className="up-gsap-animation-section"
            >
                <ToggleControl
                    label={__('Enable Animation', 'up-gsap-animate')}
                    checked={gsapAnimation.enabled}
                    onChange={(enabled) => updateGsapAnimation({ enabled })}
                />

                {gsapAnimation.enabled && (
                    <>
                        <SelectControl
                            label={__('Animation Type', 'up-gsap-animate')}
                            value={gsapAnimation.type}
                            options={Object.entries(presetAnimations).map(([value, { label }]) => ({
                                label,
                                value
                            }))}
                            onChange={(type) => {
                                const preset = presetAnimations[type];
                                updateGsapAnimation({
                                    type,
                                    from: preset.from,
                                    to: preset.to
                                });
                            }}
                        />

                        <RangeControl
                            label={__('Duration (seconds)', 'up-gsap-animate')}
                            value={gsapAnimation.duration}
                            onChange={(duration) => updateGsapAnimation({ duration })}
                            min={0.1}
                            max={10}
                            step={0.1}
                        />

                        <SelectControl
                            label={__('Easing', 'up-gsap-animate')}
                            value={gsapAnimation.ease}
                            options={easingOptions}
                            onChange={(ease) => updateGsapAnimation({ ease })}
                        />
                    </>
                )}
            </PanelBody>

            {gsapAnimation.enabled && !timeline.timelineParentId && (
                <PanelBody
                    title={__('Trigger', 'up-gsap-animate')}
                    initialOpen={false}
                    className="up-gsap-trigger-section"
                >
                    <SelectControl
                        label={__('Trigger Type', 'up-gsap-animate')}
                        value={trigger.type}
                        options={[
                            { label: __('On Scroll', 'up-gsap-animate'), value: 'scroll' },
                            { label: __('On Load', 'up-gsap-animate'), value: 'load' },
                            { label: __('On Hover', 'up-gsap-animate'), value: 'hover' },
                            { label: __('On Click', 'up-gsap-animate'), value: 'click' }
                        ]}
                        onChange={(type) => updateTrigger({ type })}
                    />

                    {trigger.type === 'scroll' && (
                        <>
                            <TextControl
                                label={__('Start Position', 'up-gsap-animate')}
                                value={trigger.start}
                                onChange={(start) => updateTrigger({ start })}
                                help={__('Example: "top center"', 'up-gsap-animate')}
                            />

                            <TextControl
                                label={__('End Position', 'up-gsap-animate')}
                                value={trigger.end}
                                onChange={(end) => updateTrigger({ end })}
                                help={__('Optional. Leave empty for default', 'up-gsap-animate')}
                            />

                            <SelectControl
                                label={__('Scrub Type', 'up-gsap-animate')}
                                value={trigger.scrubType}
                                options={[
                                    { label: __('None', 'up-gsap-animate'), value: 'none' },
                                    { label: __('Smooth', 'up-gsap-animate'), value: 'smooth' },
                                    { label: __('Instant', 'up-gsap-animate'), value: 'instant' }
                                ]}
                                onChange={(scrubType) => updateTrigger({ scrubType })}
                            />

                            {trigger.scrubType === 'smooth' && (
                                <RangeControl
                                    label={__('Smoothness', 'up-gsap-animate')}
                                    value={trigger.smoothness}
                                    onChange={(smoothness) => updateTrigger({ smoothness })}
                                    min={0.1}
                                    max={10}
                                    step={0.1}
                                />
                            )}

                            <ToggleControl
                                label={__('Pin Element', 'up-gsap-animate')}
                                checked={trigger.pin}
                                onChange={(pin) => updateTrigger({ pin })}
                            />

                            <ToggleControl
                                label={__('Show Debug Markers', 'up-gsap-animate')}
                                checked={trigger.markers}
                                onChange={(markers) => updateTrigger({ markers })}
                            />
                        </>
                    )}

                    {trigger.type === 'hover' && (
                        <ToggleControl
                            label={__('Reverse on Leave', 'up-gsap-animate')}
                            checked={trigger.reverse}
                            onChange={(reverse) => updateTrigger({ reverse })}
                        />
                    )}
                </PanelBody>
            )}

            {gsapAnimation.enabled && gsapAnimation.type === 'custom' && (
                <PanelBody
                    title={__('Advanced Settings', 'up-gsap-animate')}
                    initialOpen={false}
                    className="up-gsap-advanced-section"
                >
                    <TextareaControl
                        label={__('From Properties (JSON)', 'up-gsap-animate')}
                        value={JSON.stringify(gsapAnimation.from, null, 2)}
                        onChange={(from) => {
                            try {
                                updateGsapAnimation({ from: JSON.parse(from) });
                            } catch (e) {
                                console.error('Invalid JSON');
                            }
                        }}
                        help={__('Example: {"opacity": 0, "y": 100}', 'up-gsap-animate')}
                    />

                    <TextareaControl
                        label={__('To Properties (JSON)', 'up-gsap-animate')}
                        value={JSON.stringify(gsapAnimation.to, null, 2)}
                        onChange={(to) => {
                            try {
                                updateGsapAnimation({ to: JSON.parse(to) });
                            } catch (e) {
                                console.error('Invalid JSON');
                            }
                        }}
                        help={__('Example: {"opacity": 1, "y": 0}', 'up-gsap-animate')}
                    />
                </PanelBody>
            )}
        </>
    );
};
