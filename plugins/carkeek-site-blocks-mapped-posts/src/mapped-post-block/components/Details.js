import React, { useState, useEffect } from "react";
import classNames from "classnames";
import { getDetails } from "./getData";

const Details = props => {
    const { projectId, location, showDetails, setShowDetails } = props;
    const [project, setProject] = useState();
    const [loading, setLoading] = useState(true);
    const detailsClass = classNames({
        "show-details": showDetails,
        "map-details": true,
        "is-loading": loading
    });

    const resolveData = data => {
        const newsite = data[0].project;
        setProject(newsite);
        setLoading(false);
    };

    const clearData = () => {
        setProject();
    };

    //http://trc-sound-water-stewards.local/wp-json/wp/v2/sws_projects/420/?_fields=project

    useEffect(() => {
        if (projectId) {
            const url = window.location.origin;
            const dataUrl = `${url}/wp-json/wp/v2/sws_projects/${projectId}/?_fields=project`;
            setLoading(true);
            getDetails(dataUrl, resolveData);
        } else {
            clearData();
        }
    }, [projectId]);

    return (
        <div className={detailsClass}>
            {project && (
                <>
                    <header>
                        <span className="details-cat">{project.category}</span>
                        <button
                            onClick={() => {
                                setShowDetails(false);
                            }}
                        >
                            <span className="fas fa-times"></span>
                            <span className="screen-reader-text">Close</span>
                        </button>
                    </header>
                    {project.img && (
                        <figure>
                            <img src={project.img} />
                        </figure>
                    )}
                    <h2 className="project-title">
                        <a href={project.link}>{project.title}</a>
                    </h2>
                    <div className="project-details">
                        <p
                            className="project-location"
                            dangerouslySetInnerHTML={{ __html: location }}
                        ></p>
                        <p
                            className="project-excerpt"
                            dangerouslySetInnerHTML={{
                                __html: project.excerpt
                            }}
                        ></p>
                        {project.facts && (
                            <section className="project-facts">
                                <p className="project-facts-title">Fun Fact</p>
                                <p className="project-facts-number">
                                    {parseInt(
                                        project.facts.metric
                                    ).toLocaleString()}
                                </p>
                                <p
                                    className="project-facts-text"
                                    dangerouslySetInnerHTML={{
                                        __html: project.facts.text
                                    }}
                                ></p>
                            </section>
                        )}
                    </div>
                </>
            )}
        </div>
    );
};

export default Details;
