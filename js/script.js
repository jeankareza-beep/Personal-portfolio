const menuBtn = document.getElementById("menu-btn");
const navLinks = document.querySelector(".nav-links");

if (menuBtn && navLinks) {
    menuBtn.addEventListener("click", function () {
        navLinks.classList.toggle("show");
    });
}
const contactForm = document.getElementById("contact-form");
const formMessage = document.getElementById("form-message");

if (contactForm) {
    contactForm.addEventListener("submit", function(event) {
        event.preventDefault();

        const name = document.getElementById("name").value.trim();
        const email = document.getElementById("email").value.trim();
        const message = document.getElementById("message").value.trim();

        if (name === "" || email === "" || message === "") {
            formMessage.textContent = "Please fill in all fields.";
        } else {
            formMessage.textContent = "Message sent successfully!";
            contactForm.reset();
        }
    });
}

const themeBtn = document.getElementById("theme-btn");

if (themeBtn) {
    themeBtn.addEventListener("click", function() {
        document.body.classList.toggle("dark-mode");

        if (document.body.classList.contains("dark-mode")) {
            themeBtn.textContent = "☀️";
        } else {
            themeBtn.textContent = "🌙";
        }
    });
}


const filterButtons = document.querySelectorAll(".filter-btn");
const projects = document.querySelectorAll(".project");

filterButtons.forEach(function(button) {
    button.addEventListener("click", function() {

        const filter = button.getAttribute("data-filter");

        filterButtons.forEach(function(btn) {
            btn.classList.remove("active");
        });

        button.classList.add("active");

        projects.forEach(function(project) {
            const category = project.getAttribute("data-category");

            if (filter === "all" || category === filter) {
                project.style.display = "block";
            } else {
                project.style.display = "none";
            }
        });
    });
});

const typingText = document.getElementById("typing-text");

if (typingText) {
    const text = typingText.textContent;
    typingText.textContent = "";

    let index = 0;

    function typeText() {
        if (index < text.length) {
            typingText.textContent += text.charAt(index);
            index++;
            setTimeout(typeText, 80);
        }
    }

    typeText();
}
