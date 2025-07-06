-- Enable uuid-ossp extension for UUID generation
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- Students table
CREATE TABLE Students (
    student_id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    department VARCHAR(50),
    academic_year VARCHAR(20),
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);

-- Employers table
CREATE TABLE Employers (
    employer_id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    company_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    industry VARCHAR(50),
    address TEXT,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);

-- Administrators table
CREATE TABLE Administrators (
    admin_id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);

-- Internships table
CREATE TABLE Internships (
    internship_id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    employer_id UUID NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    requirements TEXT,
    duration VARCHAR(50),
    start_date DATE,
    end_date DATE,
    location VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    posted_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employer_id) REFERENCES Employers(employer_id) ON DELETE CASCADE
);

CREATE INDEX idx_internships_employer ON Internships(employer_id);

-- Applications table
CREATE TABLE Applications (
    application_id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    student_id UUID NOT NULL,
    internship_id UUID NOT NULL,
    application_date TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(20) DEFAULT 'Pending' CHECK (status IN ('Pending', 'Approved', 'Rejected', 'Interview Scheduled')),
    cover_letter TEXT,
    resume_url VARCHAR(255),
    updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES Students(student_id) ON DELETE CASCADE,
    FOREIGN KEY (internship_id) REFERENCES Internships(internship_id) ON DELETE CASCADE,
    UNIQUE (student_id, internship_id)
);

CREATE INDEX idx_applications_student ON Applications(student_id);
CREATE INDEX idx_applications_internship ON Applications(internship_id);
CREATE INDEX idx_applications_status ON Applications(status);

-- Certificates table
CREATE TABLE Certificates (
    certificate_id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    application_id UUID NOT NULL,
    admin_id UUID,
    issue_date DATE NOT NULL,
    certificate_url VARCHAR(255) NOT NULL,
    verification_code VARCHAR(50) UNIQUE,
    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES Applications(application_id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES Administrators(admin_id) ON DELETE SET NULL
);

CREATE INDEX idx_certificates_application ON Certificates(application_id);
CREATE INDEX idx_certificates_admin ON Certificates(admin_id);

-- Results table
CREATE TABLE Results (
    result_id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    application_id UUID NOT NULL,
    employer_feedback TEXT,
    student_feedback TEXT,
    rating INT CHECK (rating BETWEEN 1 AND 5),
    completion_date DATE,
    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES Applications(application_id) ON DELETE CASCADE
);

CREATE INDEX idx_results_application ON Results(application_id);

-- Create application user with limited permissions
CREATE ROLE IA WITH LOGIN PASSWORD 'jBaJLaikZRbmTQyAvEqELJjzLKWFsotY';
GRANT USAGE ON SCHEMA public TO IA;
GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA public TO IA;
GRANT USAGE ON ALL SEQUENCES IN SCHEMA public TO IA;

-- Create read-only user for reporting
CREATE ROLE IR WITH LOGIN PASSWORD 'absOuBAfAnOVsiCWYQPZsFoemmusNAcz';
GRANT USAGE ON SCHEMA public TO IR;
GRANT SELECT ON ALL TABLES IN SCHEMA public TO IR;


-- Insert sample Students
INSERT INTO Students (name, email, phone, department, academic_year, password_hash)
VALUES
  ('Alice Johnson', 'alice.johnson@strathmore.edu', '123-456-7890', 'Computer Science', '1st Year', '$2a$12$I2GG7TKGHvcLLtChf3Be.eWG9LczrmzeYtZSl4TRyB/UJOxwT7q.a'),
  ('Bob Smith', 'bob.smith@strathmore.edu', '234-567-8901', 'Electrical Engineering', '2nd Year', '$2a$12$I2GG7TKGHvcLLtChf3Be.eWG9LczrmzeYtZSl4TRyB/UJOxwT7q.a'),
  ('Carol Lee', 'carol.lee@strathmore.edu', '345-678-9012', 'Business Administration', '3rd Year', '$2a$12$I2GG7TKGHvcLLtChf3Be.eWG9LczrmzeYtZSl4TRyB/UJOxwT7q.a');

-- Insert sample Employers
INSERT INTO Employers (company_name, email, phone, industry, address, password_hash)
VALUES
  ('TechNova Inc.', 'contact@technova.com', '555-123-4567', 'Technology', '123 Tech Park, Silicon City', '$2a$12$I2GG7TKGHvcLLtChf3Be.eWG9LczrmzeYtZSl4TRyB/UJOxwT7q.a'),
  ('GreenFields Ltd.', 'hr@greenfields.com', '555-234-5678', 'Agriculture', '456 Green Valley, Farming Town', '$2a$12$I2GG7TKGHvcLLtChf3Be.eWG9LczrmzeYtZSl4TRyB/UJOxwT7q.a');

-- Insert sample Administrators
INSERT INTO Administrators (name, email, password_hash)
VALUES
  ('Diana Prince', 'admin.diana@strathmore.edu', '$2a$12$I2GG7TKGHvcLLtChf3Be.eWG9LczrmzeYtZSl4TRyB/UJOxwT7q.a'),
  ('Clark Kent', 'admin.clark@strathmore.edu', '$2a$12$I2GG7TKGHvcLLtChf3Be.eWG9LczrmzeYtZSl4TRyB/UJOxwT7q.a');

-- Insert sample Internships
INSERT INTO Internships (employer_id, title, description, requirements, duration, start_date, end_date, location)
VALUES
  ((SELECT employer_id FROM Employers WHERE company_name = 'TechNova Inc.'), 
   'Software Development Intern', 
   'Assist in developing web applications.', 
   'Knowledge of JavaScript, Python', 
   '3 months', 
   '2025-06-01', 
   '2025-08-31', 
   'Remote'),

  ((SELECT employer_id FROM Employers WHERE company_name = 'GreenFields Ltd.'), 
   'Agricultural Research Intern', 
   'Work on sustainable farming research projects.', 
   'Background in biology or agriculture', 
   '6 months', 
   '2025-05-01', 
   '2025-10-31', 
   'Onsite - Farming Town');

-- Insert sample Applications
INSERT INTO Applications (student_id, internship_id, cover_letter, resume_url)
VALUES
  ((SELECT student_id FROM Students WHERE email = 'alice.johnson@strathmore.edu'),
   (SELECT internship_id FROM Internships WHERE title = 'Software Development Intern'),
   'I am passionate about software development and would love to intern at TechNova.',
   'https://example.com/resumes/alice_resume.pdf'),

  ((SELECT student_id FROM Students WHERE email = 'bob.smith@strathmore.edu'),
   (SELECT internship_id FROM Internships WHERE title = 'Agricultural Research Intern'),
   'I have a strong background in agricultural studies and research.',
   'https://example.com/resumes/bob_resume.pdf');