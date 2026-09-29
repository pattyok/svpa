var _ = require("lodash");
import React from "react";

const FilterCheckbox = ({ options, selectedOptions, setSelectedOptions, label }) => {
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
			if (selectedOptions.includes(value)) {
			setSelectedOptions(selectedOptions.filter((option) => option !== value));
			} else {
			setSelectedOptions([...selectedOptions, value]);
		}
  	};


        return (
            <li className={`filter-${value}`}>
                <input
                    checked={selectedOptions.includes(value)}
                    type="checkbox"
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
		<fieldset className="archive-filters archive-filters-checkbox">
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
    );
};

export default FilterCheckbox;
