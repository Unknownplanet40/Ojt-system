-- Seed Data for CvSU Imus Central Student Government Management System

-- 1. Seed Academic Programs (Courses)
INSERT INTO public.courses (code, name, is_active) VALUES
    ('BSIT', 'Bachelor of Science in Information Technology', true),
    ('BSCS', 'Bachelor of Science in Computer Science', true),
    ('BSBM', 'Bachelor of Science in Business Management', true),
    ('BSHM', 'Bachelor of Science in Hospitality Management', true),
    ('BSOA', 'Bachelor of Science in Office Administration', true),
    ('BSED', 'Bachelor of Secondary Education', true)
ON CONFLICT (code) DO NOTHING;

-- 2. Seed Recognized Student Organizations
INSERT INTO public.organizations (name, acronym, type, description, is_active) VALUES
    ('Central Student Government', 'CSG', 'governing', 'The supreme student governing body of Cavite State University - Imus Campus.', true),
    ('Association of Information Technology Students', 'AITS', 'academic', 'Official academic student organization for BS Information Technology students.', true),
    ('Association of Computer Science Students', 'ACSS', 'academic', 'Official academic student organization for BS Computer Science students.', true),
    ('Junior Financial Executives', 'JFINEX', 'academic', 'Official organization for finance and business management majors.', true),
    ('Junior Marketing Association', 'JMA', 'academic', 'Official student organization for marketing management majors.', true),
    ('Hospitality & Tourism Management Society', 'HTMS', 'academic', 'Official student organization for hospitality management students.', true)
ON CONFLICT (acronym) DO NOTHING;

-- 3. Seed Sample Sections for BSIT
DO $$
DECLARE
    it_id UUID;
    cs_id UUID;
BEGIN
    SELECT id INTO it_id FROM public.courses WHERE code = 'BSIT' LIMIT 1;
    SELECT id INTO cs_id FROM public.courses WHERE code = 'BSCS' LIMIT 1;

    IF it_id IS NOT NULL THEN
        INSERT INTO public.sections (course_id, year_level, section_name) VALUES
            (it_id, 1, 'BSIT 1-1'),
            (it_id, 1, 'BSIT 1-2'),
            (it_id, 2, 'BSIT 2-1'),
            (it_id, 2, 'BSIT 2-2'),
            (it_id, 3, 'BSIT 3-1'),
            (it_id, 3, 'BSIT 3-2'),
            (it_id, 4, 'BSIT 4-1')
        ON CONFLICT (course_id, year_level, section_name) DO NOTHING;
    END IF;

    IF cs_id IS NOT NULL THEN
        INSERT INTO public.sections (course_id, year_level, section_name) VALUES
            (cs_id, 1, 'BSCS 1-1'),
            (cs_id, 2, 'BSCS 2-1'),
            (cs_id, 3, 'BSCS 3-1'),
            (cs_id, 4, 'BSCS 4-1')
        ON CONFLICT (course_id, year_level, section_name) DO NOTHING;
    END IF;
END $$;
