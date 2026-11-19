-- Run after schema.sql. Existing portfolio copy and project descriptions are retained.

INSERT INTO portfolio_profile
    (id, full_name, headline, introduction, biography, interests, goals, email, facebook, profile_image)
VALUES
    (1,
     'Kareza Jean Bol-anon',
     'IT Student & Future Technology Professional',
     'Hello! I’m Kareza Jean Bol-anon, an Information Technology student who enjoys learning about computers, programming, and technology. I enjoy working on creative projects and discovering how technology can be used to solve problems. As a student, I am continuously developing my skills through school projects and different activities.',
     'I am Kareza Jean Bol-anon, an Information Technology student who is interested in computers, programming, and technology. I enjoy learning new things and improving my skills through school projects and activities.',
     'I am interested in web development, computer technology, and learning new skills related to Information Technology.',
     'My goal is to continue improving my knowledge and skills in Information Technology and gain more experience through different projects and activities.',
     'jeankareza@gmail.com',
     'Kareza Bol-anon',
     '')
ON DUPLICATE KEY UPDATE id = VALUES(id);

INSERT INTO site_settings (id, site_title, logo_text, accent_color)
VALUES (1, 'Kareza Jean Bol-anon | Portfolio', 'Kareza.', '#2563eb')
ON DUPLICATE KEY UPDATE id = VALUES(id);

INSERT INTO skills (name, category, description, sort_order) VALUES
    ('HTML', 'Web Development', 'Basic knowledge in creating and organizing web pages.', 10),
    ('CSS', 'Web Development', 'Basic knowledge in designing and styling websites.', 20),
    ('Java', 'Programming', 'Basic knowledge in programming and creating simple programs.', 30),
    ('C++', 'Programming', 'Basic knowledge in programming and problem solving.', 40),
    ('Computer Skills', 'General', 'Familiar with basic computer applications and tools.', 50),
    ('Problem Solving', 'General', 'Willing to learn and find solutions to different problems.', 60),
    ('JavaScript', 'Web Development', 'Basic knowledge in adding simple functions and interactions to websites.', 70),
    ('Networking', 'Networking', 'Basic knowledge of IP addressing, network devices, and connectivity.', 80);

INSERT INTO projects (title, category, description, image_path, sort_order) VALUES
    ('Italian Food Business Project', 'web', 'A website project for an Italian food business.', 'images/Italian-Food.png', 10),
    ('Zestia Product Advertisement', 'animation', 'A product advertisement project for Zestia.', 'images/Zestia.png', 20),
    ('Personal Portfolio', 'web', 'The personal portfolio website, now being developed into a dynamic project.', 'images/portfolio.png', 30),
    ('Cisco Packet Tracer Network', 'networking', 'A network design created with Cisco Packet Tracer.', 'images/packet-tracer.png', 40);

INSERT INTO education (institution, program, details, sort_order) VALUES
    ('', 'Bachelor of Science in Information Technology', '3rd Year Information Technology Student. Currently studying and developing knowledge in programming, networking, and web development.', 10);

INSERT INTO experience (title, organization, description, start_date, end_date, sort_order) VALUES
    ('School Projects and Activities', '', 'Participated in school projects and activities related to Information Technology. Worked on programming, networking, and multimedia projects.', '', '', 10),
    ('Training and Practical Experience', '', 'Developing practical skills through school activities and hands-on projects. Practicing website development, programming, networking, and digital design.', '', '', 20);

-- Add certificates and awards later from the Admin Dashboard; none were listed on the existing site.
