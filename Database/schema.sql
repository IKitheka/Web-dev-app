-- Enable UUID extension
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- Administrators table (Moved to top for FK dependencies)
CREATE TABLE "Administrators" (
    admin_id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    location VARCHAR(100),
    role_description TEXT DEFAULT 'System Administrator',
    admin_level VARCHAR(20) DEFAULT 'admin',
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);

-- Students table (Enhanced with User Management)
CREATE TABLE "Students" (
    student_id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    department VARCHAR(50),
    academic_year VARCHAR(20),
    password_hash VARCHAR(255) NOT NULL,
    
    -- User Management Fields
    status VARCHAR(20) DEFAULT 'active' CHECK (status IN ('active', 'disabled', 'suspended')),
    disabled_at TIMESTAMPTZ,
    disabled_by UUID REFERENCES "Administrators"(admin_id),
    disabled_reason TEXT,
    deleted_at TIMESTAMPTZ,
    deleted_by UUID REFERENCES "Administrators"(admin_id),
    
    -- Timestamps
    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);

-- Employers table (Enhanced with User Management)
CREATE TABLE "Employers" (
    employer_id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    company_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    industry VARCHAR(50),
    location TEXT,
    website VARCHAR(255),
    company_size VARCHAR(50) DEFAULT 'medium',
    about_company TEXT,
    password_hash VARCHAR(255) NOT NULL,
    
    -- User Management Fields
    status VARCHAR(20) DEFAULT 'active' CHECK (status IN ('active', 'disabled', 'suspended')),
    disabled_at TIMESTAMPTZ,
    disabled_by UUID REFERENCES "Administrators"(admin_id),
    disabled_reason TEXT,
    deleted_at TIMESTAMPTZ,
    deleted_by UUID REFERENCES "Administrators"(admin_id),
    
    -- Timestamps
    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);

-- Admin Actions Audit Table (NEW)
CREATE TABLE "AdminActions" (
    action_id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    admin_id UUID NOT NULL REFERENCES "Administrators"(admin_id) ON DELETE CASCADE,
    action_type VARCHAR(50) NOT NULL CHECK (action_type IN ('disable', 'enable', 'suspend', 'edit', 'delete', 'restore')),
    target_user_id UUID NOT NULL,
    target_user_type VARCHAR(20) NOT NULL CHECK (target_user_type IN ('student', 'employer', 'admin')),
    reason TEXT,
    old_values JSONB,
    new_values JSONB,
    ip_address INET,
    user_agent TEXT,
    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP
);

-- Internships table
CREATE TABLE "Internships" (
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
    FOREIGN KEY (employer_id) REFERENCES "Employers"(employer_id) ON DELETE CASCADE
);

-- Applications table
CREATE TABLE "Applications" (
    application_id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    student_id UUID NOT NULL,
    internship_id UUID NOT NULL,
    application_date TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(20) DEFAULT 'Pending' CHECK (status IN ('Pending', 'Approved', 'Rejected', 'Interview Scheduled')),
    cover_letter TEXT,
    resume_url VARCHAR(255),
    updated_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES "Students"(student_id) ON DELETE CASCADE,
    FOREIGN KEY (internship_id) REFERENCES "Internships"(internship_id) ON DELETE CASCADE,
    UNIQUE (student_id, internship_id)
);

-- Certificates table
CREATE TABLE "Certificates" (
    certificate_id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    application_id UUID NOT NULL,
    admin_id UUID,
    issue_date DATE NOT NULL,
    certificate_url VARCHAR(255) NOT NULL,
    verification_code VARCHAR(50) UNIQUE,
    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES "Applications"(application_id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES "Administrators"(admin_id) ON DELETE SET NULL
);

-- Results table
CREATE TABLE "Results" (
    result_id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    application_id UUID NOT NULL,
    employer_feedback TEXT,
    student_feedback TEXT,
    rating INT CHECK (rating BETWEEN 1 AND 5),
    completion_date DATE,
    created_at TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES "Applications"(application_id) ON DELETE CASCADE
);

-- =====================================================
-- INDEXES FOR PERFORMANCE (Enhanced)
-- =====================================================

-- Existing indexes
CREATE INDEX idx_internships_employer ON "Internships"(employer_id);
CREATE INDEX idx_applications_student ON "Applications"(student_id);
CREATE INDEX idx_applications_internship ON "Applications"(internship_id);
CREATE INDEX idx_applications_status ON "Applications"(status);
CREATE INDEX idx_certificates_application ON "Certificates"(application_id);
CREATE INDEX idx_certificates_admin ON "Certificates"(admin_id);
CREATE INDEX idx_results_application ON "Results"(application_id);
CREATE INDEX idx_employers_company_name ON "Employers"(company_name);
CREATE INDEX idx_employers_industry ON "Employers"(industry);
CREATE INDEX idx_administrators_admin_level ON "Administrators"(admin_level);

-- New indexes for user management
CREATE INDEX idx_students_status ON "Students"(status);
CREATE INDEX idx_students_deleted_at ON "Students"(deleted_at);
CREATE INDEX idx_employers_status ON "Employers"(status);
CREATE INDEX idx_employers_deleted_at ON "Employers"(deleted_at);
CREATE INDEX idx_admin_actions_admin_id ON "AdminActions"(admin_id);
CREATE INDEX idx_admin_actions_target_user ON "AdminActions"(target_user_id, target_user_type);
CREATE INDEX idx_admin_actions_action_type ON "AdminActions"(action_type);
CREATE INDEX idx_admin_actions_created_at ON "AdminActions"(created_at);

-- Composite indexes for common queries
CREATE INDEX idx_students_status_deleted ON "Students"(status, deleted_at);
CREATE INDEX idx_employers_status_deleted ON "Employers"(status, deleted_at);


-- Application user with CRUD permissions
CREATE ROLE app_user WITH LOGIN PASSWORD 'secure_app_password_2025';
GRANT USAGE ON SCHEMA public TO app_user;
GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA public TO app_user;
GRANT USAGE ON ALL SEQUENCES IN SCHEMA public TO app_user;

-- Read-only user for reporting
CREATE ROLE read_user WITH LOGIN PASSWORD 'secure_read_password_2025';
GRANT USAGE ON SCHEMA public TO read_user;
GRANT SELECT ON ALL TABLES IN SCHEMA public TO read_user;


-- Active Students View
CREATE VIEW "ActiveStudents" AS
SELECT * FROM "Students" 
WHERE deleted_at IS NULL 
ORDER BY created_at DESC;

-- Active Employers View
CREATE VIEW "ActiveEmployers" AS
SELECT * FROM "Employers" 
WHERE deleted_at IS NULL 
ORDER BY created_at DESC;

-- User Management Summary View
CREATE VIEW "UserManagementSummary" AS
SELECT 
    'students' as user_type,
    COUNT(*) as total_count,
    COUNT(CASE WHEN status = 'active' THEN 1 END) as active_count,
    COUNT(CASE WHEN status = 'disabled' THEN 1 END) as disabled_count,
    COUNT(CASE WHEN status = 'suspended' THEN 1 END) as suspended_count,
    COUNT(CASE WHEN deleted_at IS NOT NULL THEN 1 END) as deleted_count
FROM "Students"
UNION ALL
SELECT 
    'employers' as user_type,
    COUNT(*) as total_count,
    COUNT(CASE WHEN status = 'active' THEN 1 END) as active_count,
    COUNT(CASE WHEN status = 'disabled' THEN 1 END) as disabled_count,
    COUNT(CASE WHEN status = 'suspended' THEN 1 END) as suspended_count,
    COUNT(CASE WHEN deleted_at IS NOT NULL THEN 1 END) as deleted_count
FROM "Employers";

-- Function to update updated_at timestamp
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

-- Triggers for updated_at
CREATE TRIGGER update_students_updated_at BEFORE UPDATE ON "Students"
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_employers_updated_at BEFORE UPDATE ON "Employers"
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_administrators_updated_at BEFORE UPDATE ON "Administrators"
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();


-- Sample Students (with varied statuses for testing)
INSERT INTO "Students" (name, email, phone, department, academic_year, password_hash, status)
VALUES
  ('Alice Johnson', 'alice.johnson@strathmore.edu', '+254-701-234567', 'Computer Science', '3rd Year', '$2y$10$uVJqYLnDXMAhVpzYNYtaZu8VAV1k0Zjc5k83vOJzc0/eZLlqCNwIq', 'active'),
  ('Bob Smith', 'bob.smith@strathmore.edu', '+254-701-345678', 'Electrical Engineering', '2nd Year', '$2y$10$uVJqYLnDXMAhVpzYNYtaZu8VAV1k0Zjc5k83vOJzc0/eZLlqCNwIq', 'active'),
  ('Carol Lee', 'carol.lee@strathmore.edu', '+254-701-456789', 'Business Administration', '4th Year', '$2y$10$uVJqYLnDXMAhVpzYNYtaZu8VAV1k0Zjc5k83vOJzc0/eZLlqCNwIq', 'active'),
  ('David Wilson', 'david.wilson@strathmore.edu', '+254-701-567890', 'Information Technology', '1st Year', '$2y$10$uVJqYLnDXMAhVpzYNYtaZu8VAV1k0Zjc5k83vOJzc0/eZLlqCNwIq', 'disabled'),
  ('Emma Brown', 'emma.brown@strathmore.edu', '+254-701-678901', 'Marketing', '3rd Year', '$2y$10$uVJqYLnDXMAhVpzYNYtaZu8VAV1k0Zjc5k83vOJzc0/eZLlqCNwIq', 'suspended');

-- Sample Employers (with varied statuses for testing)
INSERT INTO "Employers" (company_name, email, phone, industry, location, website, company_size, about_company, password_hash, status)
VALUES
  ('TechNova Solutions', 'hr@technova.co.ke', '+254-20-1234567', 'Technology', 'Nairobi, Kenya', 'https://technova.co.ke', 'medium', 'Leading software development company in East Africa', '$2y$10$uVJqYLnDXMAhVpzYNYtaZu8VAV1k0Zjc5k83vOJzc0/eZLlqCNwIq', 'active'),
  ('GreenFields Agriculture', 'contact@greenfields.co.ke', '+254-20-2345678', 'Agriculture', 'Nakuru, Kenya', 'https://greenfields.co.ke', 'large', 'Sustainable farming and agricultural innovation company', '$2y$10$uVJqYLnDXMAhVpzYNYtaZu8VAV1k0Zjc5k83vOJzc0/eZLlqCNwIq', 'active'),
  ('Kenya Commercial Bank', 'careers@kcb.co.ke', '+254-20-3456789', 'Banking', 'Nairobi, Kenya', 'https://kcb.co.ke', 'large', 'Leading financial institution in Kenya', '$2y$10$uVJqYLnDXMAhVpzYNYtaZu8VAV1k0Zjc5k83vOJzc0/eZLlqCNwIq', 'disabled');

-- Sample Administrators
INSERT INTO "Administrators" (full_name, email, phone, location, role_description, admin_level, password_hash)
VALUES
  ('Diana Prince', 'admin.diana@strathmore.edu', '+254-20-3456789', 'Nairobi, Kenya', 'System Administrator', 'admin', '$2y$10$uVJqYLnDXMAhVpzYNYtaZu8VAV1k0Zjc5k83vOJzc0/eZLlqCNwIq'),
  ('Clark Kent', 'admin.clark@strathmore.edu', '+254-20-4567890', 'Nairobi, Kenya', 'Senior Administrator', 'super_admin', '$2y$10$uVJqYLnDXMAhVpzYNYtaZu8VAV1k0Zjc5k83vOJzc0/eZLlqCNwIq'),
  ('Bruce Wayne', 'admin.bruce@strathmore.edu', '+254-20-5678901', 'Nairobi, Kenya', 'Security Administrator', 'admin', '$2y$10$uVJqYLnDXMAhVpzYNYtaZu8VAV1k0Zjc5k83vOJzc0/eZLlqCNwIq');

-- Sample Admin Actions (for testing audit trail)
INSERT INTO "AdminActions" (admin_id, action_type, target_user_id, target_user_type, reason, old_values, new_values)
VALUES
  ((SELECT admin_id FROM "Administrators" WHERE email = 'admin.diana@strathmore.edu'),
   'disable',
   (SELECT student_id FROM "Students" WHERE email = 'david.wilson@strathmore.edu'),
   'student',
   'Violation of terms of service',
   '{"status": "active"}',
   '{"status": "disabled"}'),
  
  ((SELECT admin_id FROM "Administrators" WHERE email = 'admin.clark@strathmore.edu'),
   'suspend',
   (SELECT student_id FROM "Students" WHERE email = 'emma.brown@strathmore.edu'),
   'student',
   'Temporary suspension pending investigation',
   '{"status": "active"}',
   '{"status": "suspended"}'),
   
  ((SELECT admin_id FROM "Administrators" WHERE email = 'admin.bruce@strathmore.edu'),
   'disable',
   (SELECT employer_id FROM "Employers" WHERE email = 'careers@kcb.co.ke'),
   'employer',
   'Account verification required',
   '{"status": "active"}',
   '{"status": "disabled"}');

-- Update disabled users with admin tracking
UPDATE "Students" SET 
    disabled_at = CURRENT_TIMESTAMP,
    disabled_by = (SELECT admin_id FROM "Administrators" WHERE email = 'admin.diana@strathmore.edu'),
    disabled_reason = 'Violation of terms of service'
WHERE email = 'david.wilson@strathmore.edu';

UPDATE "Students" SET 
    disabled_at = CURRENT_TIMESTAMP,
    disabled_by = (SELECT admin_id FROM "Administrators" WHERE email = 'admin.clark@strathmore.edu'),
    disabled_reason = 'Temporary suspension pending investigation'
WHERE email = 'emma.brown@strathmore.edu';

UPDATE "Employers" SET 
    disabled_at = CURRENT_TIMESTAMP,
    disabled_by = (SELECT admin_id FROM "Administrators" WHERE email = 'admin.bruce@strathmore.edu'),
    disabled_reason = 'Account verification required'
WHERE email = 'careers@kcb.co.ke';

-- Continue with original sample data...
-- Sample Internships
INSERT INTO "Internships" (employer_id, title, description, requirements, duration, start_date, end_date, location)
VALUES
  ((SELECT employer_id FROM "Employers" WHERE company_name = 'TechNova Solutions'), 
   'Software Development Intern', 
   'Join our development team to build innovative web applications using modern technologies.', 
   'Knowledge of JavaScript, Python, or PHP. Understanding of database concepts. Strong problem-solving skills.', 
   '3 months', 
   '2025-06-01', 
   '2025-08-31', 
   'Nairobi, Kenya'),

  ((SELECT employer_id FROM "Employers" WHERE company_name = 'GreenFields Agriculture'), 
   'Agricultural Research Intern', 
   'Work with our research team on sustainable farming techniques and crop optimization projects.', 
   'Background in agriculture, biology, or environmental science. Research experience preferred.', 
   '6 months', 
   '2025-05-01', 
   '2025-10-31', 
   'Nakuru, Kenya');

-- Sample Applications
INSERT INTO "Applications" (student_id, internship_id, application_date, status, cover_letter, resume_url)
VALUES
  ((SELECT student_id FROM "Students" WHERE email = 'alice.johnson@strathmore.edu'),
   (SELECT internship_id FROM "Internships" WHERE title = 'Software Development Intern'),
   '2025-03-15 10:30:00+00',
   'Approved',
   'I am passionate about software development and excited to contribute to TechNova''s innovative projects.',
   'https://drive.google.com/resumes/alice_johnson_resume.pdf'),

  ((SELECT student_id FROM "Students" WHERE email = 'bob.smith@strathmore.edu'),
   (SELECT internship_id FROM "Internships" WHERE title = 'Agricultural Research Intern'),
   '2025-03-20 14:15:00+00',
   'Approved',
   'My academic background in agricultural engineering aligns perfectly with GreenFields'' mission.',
   'https://drive.google.com/resumes/bob_smith_resume.pdf'),

  ((SELECT student_id FROM "Students" WHERE email = 'carol.lee@strathmore.edu'),
   (SELECT internship_id FROM "Internships" WHERE title = 'Software Development Intern'),
   '2025-03-25 09:45:00+00',
   'Pending',
   'As a business administration student, I bring unique insights to technology projects and user experience.',
   'https://drive.google.com/resumes/carol_lee_resume.pdf');

-- Sample Results
INSERT INTO "Results" (application_id, employer_feedback, student_feedback, rating, completion_date)
VALUES
  ((SELECT application_id FROM "Applications" a 
    JOIN "Students" s ON a.student_id = s.student_id 
    JOIN "Internships" i ON a.internship_id = i.internship_id
    WHERE s.email = 'alice.johnson@strathmore.edu' AND i.title = 'Software Development Intern'),
   'Alice demonstrated exceptional programming skills and was a valuable team member.',
   'This internship provided invaluable hands-on experience in full-stack development.',
   5,
   '2025-08-30'),

  ((SELECT application_id FROM "Applications" a 
    JOIN "Students" s ON a.student_id = s.student_id 
    JOIN "Internships" i ON a.internship_id = i.internship_id
    WHERE s.email = 'bob.smith@strathmore.edu' AND i.title = 'Agricultural Research Intern'),
   'Bob showed strong analytical skills and contributed significantly to our research.',
   'Working with GreenFields gave me deep insights into sustainable agriculture practices.',
   4,
   '2025-10-28');

-- Sample Certificates
INSERT INTO "Certificates" (application_id, admin_id, issue_date, certificate_url, verification_code)
VALUES
  ((SELECT application_id FROM "Applications" a 
    JOIN "Students" s ON a.student_id = s.student_id 
    JOIN "Internships" i ON a.internship_id = i.internship_id
    WHERE s.email = 'alice.johnson@strathmore.edu' AND i.title = 'Software Development Intern'),
   (SELECT admin_id FROM "Administrators" WHERE email = 'admin.diana@strathmore.edu'),
   '2025-09-05',
   'https://certificates.strathmore.edu/2025/alice_johnson_technova_cert.pdf',
   'CERT-2025-001-AJ-TN'),

  ((SELECT application_id FROM "Applications" a 
    JOIN "Students" s ON a.student_id = s.student_id 
    JOIN "Internships" i ON a.internship_id = i.internship_id
    WHERE s.email = 'bob.smith@strathmore.edu' AND i.title = 'Agricultural Research Intern'),
   (SELECT admin_id FROM "Administrators" WHERE email = 'admin.clark@strathmore.edu'),
   '2025-11-02',
   'https://certificates.strathmore.edu/2025/bob_smith_greenfields_cert.pdf',
   'CERT-2025-002-BS-GF');


-- Grant permissions to app_user for new tables
GRANT SELECT, INSERT, UPDATE, DELETE ON "AdminActions" TO app_user;
GRANT SELECT ON "ActiveStudents" TO app_user;
GRANT SELECT ON "ActiveEmployers" TO app_user;
GRANT SELECT ON "UserManagementSummary" TO app_user;

-- Grant read permissions to read_user for new tables  
GRANT SELECT ON "AdminActions" TO read_user;
GRANT SELECT ON "ActiveStudents" TO read_user;
GRANT SELECT ON "ActiveEmployers" TO read_user;
GRANT SELECT ON "UserManagementSummary" TO read_user;


-- Analyze tables for optimal query performance
ANALYZE "Students";
ANALYZE "Employers";
ANALYZE "AdminActions";

-- Final verification query
SELECT 
    schemaname,
    tablename,
    tableowner,
    hasindexes,
    hasrules,
    hastriggers
FROM pg_tables 
WHERE schemaname = 'public'
ORDER BY tablename;

-- Success message
SELECT 'Enhanced database schema with user management capabilities created successfully!' as status;
