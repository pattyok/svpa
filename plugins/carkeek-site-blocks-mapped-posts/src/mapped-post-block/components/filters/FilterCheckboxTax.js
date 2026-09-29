var _ = require("lodash");
import React from "react";

const FilterCheckboxTax = ({ options, selectedOptions, setSelectedOptions, label }) => {
	const ListItem = ({ label, value, slug }) => {
        const selected = _.includes(selectedOptions, value);
        let icon = "";
		icon = (
			<span
				aria-hidden="true"
				className={`archive-filter-icon ${slug}`}
			></span>
		);
        const handleCheckboxClicked = e => {

            if (e.target && e.target.type === "checkbox") {
                const selectedOptionId = parseInt(e.target.value);
                let newSelectedOptions = _.cloneDeep(selectedOptions);
                // is currently selected
                if (_.includes(selectedOptions, selectedOptionId)) {
                    // remove selected value from options list
                    _.pull(newSelectedOptions, selectedOptionId);
                } else {
                    // is not currently selected
                    // Add selected key to optionsList
                    //newSelectedOptions.push(selectedOptionId);
                    newSelectedOptions = _.concat(
                        selectedOptions,
                        selectedOptionId,
                    );
                }

                // call onChange function pulled in from Map to track state
                setSelectedOptions(newSelectedOptions);
            }
        };


        return (
            <li className={`filter-${slug}`}>
                <input
                    checked={selected}
                    type="checkbox"
                    onChange={e => handleCheckboxClicked(e)}
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
			<fieldset className="archive-filters archive-filters-checkbox">
				{label && <legend className="filter-label">{label}</legend>}
				<ul className="archive-filters-list">
					{options.map(cat => {
							return (
								<ListItem
									key={cat.id}
									label={`${cat.name}`}
									value={parseInt(cat.id)}
									slug={cat.slug}
								/>
							);
					})}
				</ul>
			</fieldset>
			</>
    );
};

export default FilterCheckboxTax;
