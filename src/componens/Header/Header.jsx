import { useState } from 'react';
import classNames from 'classnames';

export const Header = (props) => {
    const [isActive, setIsActive] = useState(props.active);
    
    const headerClass = classNames({
        'conf-step__header': true,
        'conf-step__header_opened': isActive,
        'conf-step__header_closed': !isActive,
    });

    const handleOnSelectHeader = e => {
        setIsActive(current => !current);
    };
    
    return (
        <>    
            <header className={headerClass} onClick={handleOnSelectHeader}>
                <h2 className="conf-step__title">{props.title}</h2>
            </header>
        </>    
    )
}
