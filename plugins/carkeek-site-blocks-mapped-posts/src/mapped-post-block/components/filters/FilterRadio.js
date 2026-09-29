var _ = require("lodash");
import React from "react";

const FilterRadio = ({ options, selectedOption, setSelectedOption, groupName, label }) => {
	const ListItem = ({ label, value, slug }) => {
        let icon = "";
		if (slug) {
			icon = (
				<span
					aria-hidden="true"
					className={`archive-filter-icon ${slug}`}
				></span>
			);
		}
        const handleOptionChange = (event) => {
			const value = event.target.value;
			setSelectedOption(value);
		}

        return (
            <li className={`filter-${value}`}>
                <input
                    checked={selectedOption === value}
                    type="radio"
					name={groupName}
                    onChange={handleOptionChange}
                    value={value}
                    id={`cat-${value}`}
                />
                <label htmlFor={`cat-${value}`}>
                    {icon}
                    <span dangerouslySetInnerHTML={{ __html: label }}></span>
                </label>
            </li>
        );
    };

    return (
			<>
            <fieldset className={`archive-filters archive-filters-radio radio-${groupName}`}>
				{label && <legend className="filter-label">{label}</legend>}
				<ul className="archive-filters-list">
                {options.map((item, index) => {
						return (
							<ListItem
								key={index}
								label={`${item.label}`}
								value={item.value}
								slug={item.slug}
							/>
						);
                })}
				</ul>
            </fieldset>

			</>
    );
};

export default FilterRadio;
