import { __ } from '@wordpress/i18n';
import { TabPanel } from '@wordpress/components';
import { AnimationPanel } from './AnimationPanel';

const AnimationTab = ({ attributes, setAttributes }) => {
    return (
        <div className="up-gsap-animation-tab">
            <AnimationPanel 
                attributes={attributes} 
                setAttributes={setAttributes} 
            />
        </div>
    );
};

export default AnimationTab;
