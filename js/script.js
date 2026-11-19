var menuButton = document.getElementById("menu-btn");
var navLinks = document.querySelector(".nav-links");

if (menuButton && navLinks) {
    menuButton.addEventListener("click", function () {
        navLinks.classList.toggle("show");
        menuButton.setAttribute("aria-expanded", navLinks.classList.contains("show") ? "true" : "false");
    });
}

var themeButton = document.getElementById("theme-btn");
if (themeButton) {
    try {
        if (localStorage.getItem("portfolio-theme") === "dark") {
            document.body.classList.add("dark-mode");
            themeButton.textContent = "☀️";
        }
    } catch (error) {
        // Theme switching still works if browser storage is unavailable.
    }

    themeButton.addEventListener("click", function () {
        document.body.classList.toggle("dark-mode");
        var isDark = document.body.classList.contains("dark-mode");
        themeButton.textContent = isDark ? "☀️" : "🌙";
        try {
            localStorage.setItem("portfolio-theme", isDark ? "dark" : "light");
        } catch (error) {
            // Ignore storage errors; the current page theme has already changed.
        }
    });
}

function getApiBaseUrl() {
    if (!window.PORTFOLIO_CONFIG || !window.PORTFOLIO_CONFIG.apiBaseUrl) {
        return "";
    }
    return window.PORTFOLIO_CONFIG.apiBaseUrl.trim().replace(/\/+$/, "") + "/";
}

function getApiUrl(resource) {
    return getApiBaseUrl() + "api/index.php?resource=" + encodeURIComponent(resource);
}

function addTextElement(parent, tagName, className, text) {
    var element = document.createElement(tagName);
    if (className) {
        element.className = className;
    }
    element.textContent = text || "";
    parent.appendChild(element);
    return element;
}

function setText(selector, value) {
    var element = document.querySelector(selector);
    if (element && value !== null && value !== undefined) {
        element.textContent = value;
    }
}

function getSafeImagePath(path) {
    if (!path) {
        return "";
    }
    if (/^https:\/\//i.test(path)) {
        return path;
    }
    if (path.indexOf("images/") === 0) {
        return path;
    }
    if (path.indexOf("uploads/") === 0 && getApiBaseUrl()) {
        return new URL(path, getApiBaseUrl()).toString();
    }
    return "";
}

function updateProfile(profile, settings) {
    if (profile) {
        setText("[data-profile-name]", profile.full_name);
        setText("[data-profile-headline]", profile.headline);
        setText("[data-profile-introduction]", profile.introduction);
        setText("[data-profile-biography]", profile.biography);
        setText("[data-profile-interests]", profile.interests);
        setText("[data-profile-goals]", profile.goals);

        var profileImage = document.querySelector("[data-profile-image]");
        var imagePath = getSafeImagePath(profile.profile_image);
        if (profileImage && imagePath) {
            profileImage.src = imagePath;
            profileImage.alt = profile.full_name || "Portfolio profile";
        }

        var emailElement = document.querySelector("[data-profile-email]");
        if (emailElement && profile.email) {
            emailElement.textContent = profile.email;
            emailElement.href = "mailto:" + profile.email;
        }

        var facebookElement = document.querySelector("[data-profile-facebook]");
        if (facebookElement && profile.facebook) {
            facebookElement.textContent = profile.facebook;
            if (/^https:\/\//i.test(profile.facebook)) {
                facebookElement.href = profile.facebook;
            }
        }
    }

    if (settings) {
        if (settings.site_title) {
            document.title = settings.site_title;
        }
        var logos = document.querySelectorAll(".logo");
        for (var i = 0; i < logos.length; i += 1) {
            if (settings.logo_text) {
                logos[i].textContent = settings.logo_text;
            }
        }
        if (/^#[0-9a-f]{6}$/i.test(settings.accent_color || "")) {
            document.documentElement.style.setProperty("--accent-color", settings.accent_color);
        }
    }
}

function showEmptyMessage(parent, message) {
    var empty = document.createElement("p");
    empty.className = "empty-state";
    empty.textContent = message;
    parent.appendChild(empty);
}

function renderSkills(skills) {
    var container = document.getElementById("skills-container");
    if (!container || !Array.isArray(skills)) {
        return;
    }
    container.textContent = "";
    if (skills.length === 0) {
        showEmptyMessage(container, "No skills have been added yet.");
        return;
    }
    skills.forEach(function (skill) {
        var card = document.createElement("article");
        card.className = "skill";
        addTextElement(card, "h2", "", skill.name);
        if (skill.category) {
            addTextElement(card, "p", "skill-category", skill.category);
        }
        addTextElement(card, "p", "", skill.description);
        container.appendChild(card);
    });
}

function renderProjects(projects) {
    var container = document.getElementById("projects-container");
    if (!container || !Array.isArray(projects)) {
        return;
    }
    container.textContent = "";
    if (projects.length === 0) {
        showEmptyMessage(container, "No projects have been added yet.");
        return;
    }

    var categories = [];
    projects.forEach(function (project) {
        var category = (project.category || "other").toLowerCase();
        if (categories.indexOf(category) === -1) {
            categories.push(category);
        }
    });

    var filterContainer = document.querySelector(".filter-buttons");
    if (filterContainer) {
        filterContainer.textContent = "";
        var allButton = document.createElement("button");
        allButton.type = "button";
        allButton.className = "filter-btn active";
        allButton.setAttribute("data-filter", "all");
        allButton.textContent = "All";
        filterContainer.appendChild(allButton);
        categories.forEach(function (category) {
            var button = document.createElement("button");
            button.type = "button";
            button.className = "filter-btn";
            button.setAttribute("data-filter", category);
            button.textContent = category.charAt(0).toUpperCase() + category.slice(1);
            filterContainer.appendChild(button);
        });
    }

    projects.forEach(function (project, index) {
        var card = document.createElement("article");
        card.className = "project";
        card.setAttribute("data-category", (project.category || "other").toLowerCase());
        addTextElement(card, "h3", "project-number", "Project " + (index + 1));

        var imageBox = document.createElement("div");
        imageBox.className = "project-image";
        var imagePath = getSafeImagePath(project.image_path);
        if (imagePath) {
            var image = document.createElement("img");
            image.src = imagePath;
            image.alt = project.title || "Portfolio project";
            image.loading = "lazy";
            imageBox.appendChild(image);
        } else {
            addTextElement(imageBox, "span", "", project.title || "Project image");
        }
        card.appendChild(imageBox);

        var content = document.createElement("div");
        content.className = "project-content";
        addTextElement(content, "h2", "", project.title);
        addTextElement(content, "p", "", project.description);
        if (project.project_url && /^https?:\/\//i.test(project.project_url)) {
            var link = document.createElement("a");
            link.href = project.project_url;
            link.target = "_blank";
            link.rel = "noopener noreferrer";
            link.textContent = "View project";
            content.appendChild(link);
        }
        card.appendChild(content);
        container.appendChild(card);
    });
}

function renderResumeList(containerId, records, type) {
    var container = document.getElementById(containerId);
    if (!container || !Array.isArray(records)) {
        return;
    }
    container.textContent = "";
    if (records.length === 0) {
        showEmptyMessage(container, "No " + type + " have been added yet.");
        return;
    }
    records.forEach(function (record) {
        var card = document.createElement("article");
        card.className = "resume-box";
        var heading = record.program || record.title || "";
        addTextElement(card, "h3", "", heading);
        var organization = record.institution || record.organization || record.issuer || "";
        if (organization) {
            addTextElement(card, "p", "resume-organization", organization);
        }
        var years = "";
        if (record.start_year || record.end_year) {
            years = [record.start_year, record.end_year].filter(Boolean).join(" – ");
        } else if (record.start_date || record.end_date) {
            years = [record.start_date, record.end_date].filter(Boolean).join(" – ");
        } else if (record.achieved_on) {
            years = record.achieved_on;
        }
        if (years) {
            addTextElement(card, "p", "resume-dates", years);
        }
        addTextElement(card, "p", "", record.details || record.description || "");
        container.appendChild(card);
    });
}

function loadPortfolioFromDatabase() {
    var apiBaseUrl = getApiBaseUrl();
    if (!apiBaseUrl) {
        return;
    }

    fetch(getApiUrl("portfolio"), { headers: { "Accept": "application/json" } })
        .then(function (response) {
            if (!response.ok) {
                throw new Error("Portfolio API request failed.");
            }
            return response.json();
        })
        .then(function (result) {
            if (!result || !result.ok || !result.data) {
                throw new Error("Portfolio API response was not valid.");
            }
            var data = result.data;
            updateProfile(data.profile, data.settings);
            renderSkills(data.skills);
            renderProjects(data.projects);
            renderResumeList("education-list", data.education, "education records");
            renderResumeList("experience-list", data.experience, "experience records");
            renderResumeList("achievements-list", data.achievements, "achievements");
        })
        .catch(function (error) {
            console.log("Using the saved static portfolio content because the database is not available yet.");
        });
}

var filterContainer = document.querySelector(".filter-buttons");
if (filterContainer) {
    filterContainer.addEventListener("click", function (event) {
        var button = event.target.closest("button[data-filter]");
        if (!button) {
            return;
        }
        var filter = button.getAttribute("data-filter");
        var filterButtons = filterContainer.querySelectorAll("button[data-filter]");
        filterButtons.forEach(function (item) {
            item.classList.toggle("active", item === button);
        });
        var projects = document.querySelectorAll(".project[data-category]");
        projects.forEach(function (project) {
            var category = project.getAttribute("data-category");
            project.style.display = filter === "all" || category === filter ? "block" : "none";
        });
    });
}

var contactForm = document.getElementById("contact-form");
var formMessage = document.getElementById("form-message");
if (contactForm) {
    contactForm.addEventListener("submit", function (event) {
        event.preventDefault();
        if (!contactForm.reportValidity()) {
            return;
        }
        if (!getApiBaseUrl()) {
            if (formMessage) {
                formMessage.textContent = "The contact form will be available after the PHP/MySQL service is configured.";
            }
            return;
        }

        var submitButton = contactForm.querySelector("button[type=submit]");
        var originalButtonText = submitButton ? submitButton.textContent : "";
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.textContent = "Sending…";
        }
        if (formMessage) {
            formMessage.textContent = "";
        }

        fetch(getApiUrl("contact-csrf"), { headers: { "Accept": "application/json" } })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error("The contact form is not ready yet.");
                }
                return response.json();
            })
            .then(function (tokenResult) {
                var formData = new FormData(contactForm);
                var messageData = {
                    name: formData.get("name"),
                    email: formData.get("email"),
                    subject: formData.get("subject"),
                    message: formData.get("message"),
                    website: formData.get("website")
                };
                return fetch(getApiUrl("contact"), {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "Accept": "application/json",
                        "X-CSRF-Token": tokenResult.token
                    },
                    body: JSON.stringify(messageData)
                });
            })
            .then(function (response) {
                return response.json().then(function (data) {
                    if (!response.ok || !data.ok) {
                        throw new Error(data.error || "The message could not be sent.");
                    }
                    return data;
                });
            })
            .then(function (data) {
                if (formMessage) {
                    formMessage.textContent = data.message || "Your message has been sent.";
                }
                contactForm.reset();
            })
            .catch(function (error) {
                if (formMessage) {
                    formMessage.textContent = error.message || "The message could not be sent. Please try again later.";
                }
            })
            .finally(function () {
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.textContent = originalButtonText;
                }
            });
    });
}

loadPortfolioFromDatabase();
