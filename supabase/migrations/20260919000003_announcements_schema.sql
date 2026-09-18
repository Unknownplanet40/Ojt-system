-- Migration 20260919000003_announcements_schema.sql
-- Announcements Table with Dual-Level Broadcasting for CvSU Imus CSG System

-- 1. Create Announcements Table
CREATE TABLE IF NOT EXISTS public.announcements (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    priority VARCHAR(20) NOT NULL DEFAULT 'normal' CHECK (priority IN ('high', 'normal', 'low')),
    visibility VARCHAR(30) NOT NULL DEFAULT 'campus_wide' CHECK (visibility IN ('campus_wide', 'organization_only')),
    organization_id UUID REFERENCES public.organizations(id) ON DELETE CASCADE,
    author_id UUID NOT NULL REFERENCES public.profiles(id) ON DELETE CASCADE,
    is_pinned BOOLEAN NOT NULL DEFAULT false,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TRIGGER update_announcements_updated_at
    BEFORE UPDATE ON public.announcements
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

-- 2. Indexes
CREATE INDEX IF NOT EXISTS idx_announcements_priority ON public.announcements(priority);
CREATE INDEX IF NOT EXISTS idx_announcements_visibility ON public.announcements(visibility);
CREATE INDEX IF NOT EXISTS idx_announcements_org ON public.announcements(organization_id);
CREATE INDEX IF NOT EXISTS idx_announcements_pinned ON public.announcements(is_pinned);
CREATE INDEX IF NOT EXISTS idx_announcements_created_at ON public.announcements(created_at DESC);

-- 3. Row Level Security (RLS) Setup
ALTER TABLE public.announcements ENABLE ROW LEVEL SECURITY;

-- Select Policy: Campus-wide or User's Own Organization
CREATE POLICY "Users can view relevant announcements"
    ON public.announcements FOR SELECT
    TO authenticated
    USING (
        visibility = 'campus_wide' OR
        get_current_user_role() IN ('super_admin', 'csg_officer') OR
        (visibility = 'organization_only' AND organization_id = get_current_user_org_id())
    );

-- Insert Policy: Dual-Level Broadcasting
CREATE POLICY "Authorized officers can publish announcements"
    ON public.announcements FOR INSERT
    TO authenticated
    WITH CHECK (
        author_id = auth.uid() AND (
            (get_current_user_role() IN ('super_admin', 'csg_officer')) OR
            (get_current_user_role() = 'sub_org_officer' AND organization_id = get_current_user_org_id() AND visibility = 'organization_only')
        )
    );

-- Update/Delete Policy
CREATE POLICY "Authors and Admins can manage announcements"
    ON public.announcements FOR UPDATE
    TO authenticated
    USING (
        author_id = auth.uid() OR
        get_current_user_role() IN ('super_admin', 'csg_officer')
    );

CREATE POLICY "Authors and Admins can delete announcements"
    ON public.announcements FOR DELETE
    TO authenticated
    USING (
        author_id = auth.uid() OR
        get_current_user_role() IN ('super_admin', 'csg_officer')
    );
