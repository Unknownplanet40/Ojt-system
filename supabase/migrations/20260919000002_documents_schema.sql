-- Migration 20260919000002_documents_schema.sql
-- Documents and Document Versions Schema for CvSU Imus CSG Management System

-- 1. Documents Table
CREATE TABLE IF NOT EXISTS public.documents (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    organization_id UUID NOT NULL REFERENCES public.organizations(id) ON DELETE CASCADE,
    author_id UUID NOT NULL REFERENCES public.profiles(id) ON DELETE CASCADE,
    title VARCHAR(255) NOT NULL,
    type document_type NOT NULL DEFAULT 'activity_proposal',
    status document_status NOT NULL DEFAULT 'draft',
    content JSONB NOT NULL DEFAULT '{}'::jsonb,
    attachment_urls TEXT[] NOT NULL DEFAULT '{}',
    reviewer_id UUID REFERENCES public.profiles(id) ON DELETE SET NULL,
    reviewer_feedback TEXT,
    reviewed_at TIMESTAMPTZ,
    current_version INT NOT NULL DEFAULT 1,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TRIGGER update_documents_updated_at
    BEFORE UPDATE ON public.documents
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

-- 2. Document Versions Table (Immutable Snapshots)
CREATE TABLE IF NOT EXISTS public.document_versions (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    document_id UUID NOT NULL REFERENCES public.documents(id) ON DELETE CASCADE,
    version_number INT NOT NULL,
    author_id UUID NOT NULL REFERENCES public.profiles(id) ON DELETE CASCADE,
    title VARCHAR(255) NOT NULL,
    content JSONB NOT NULL,
    attachment_urls TEXT[] NOT NULL DEFAULT '{}',
    change_summary TEXT,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    UNIQUE(document_id, version_number)
);

-- 3. Indexes for Performance
CREATE INDEX IF NOT EXISTS idx_documents_org ON public.documents(organization_id);
CREATE INDEX IF NOT EXISTS idx_documents_author ON public.documents(author_id);
CREATE INDEX IF NOT EXISTS idx_documents_status ON public.documents(status);
CREATE INDEX IF NOT EXISTS idx_documents_type ON public.documents(type);
CREATE INDEX IF NOT EXISTS idx_document_versions_doc ON public.document_versions(document_id);

-- 4. Row Level Security (RLS) Setup
ALTER TABLE public.documents ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.document_versions ENABLE ROW LEVEL SECURITY;

-- Helper to check user's organization
CREATE OR REPLACE FUNCTION get_current_user_org_id()
RETURNS UUID AS $$
    SELECT organization_id FROM public.profiles WHERE id = auth.uid();
$$ LANGUAGE sql STABLE SECURITY DEFINER;

-- RLS: Documents
-- Select Policy
CREATE POLICY "Users can view relevant documents"
    ON public.documents FOR SELECT
    TO authenticated
    USING (
        -- Super Admin and CSG Officers see all non-drafts (and their own drafts)
        (get_current_user_role() IN ('super_admin', 'csg_officer') AND (status != 'draft' OR author_id = auth.uid())) OR
        -- Sub-Org Officers see all documents for their organization
        (get_current_user_role() = 'sub_org_officer' AND organization_id = get_current_user_org_id()) OR
        -- Authors always see their own documents
        (author_id = auth.uid()) OR
        -- Regular members see approved public documents
        (status = 'approved')
    );

-- Insert Policy
CREATE POLICY "Officers can insert documents for their organization"
    ON public.documents FOR INSERT
    TO authenticated
    WITH CHECK (
        author_id = auth.uid() AND
        (
            get_current_user_role() IN ('super_admin', 'csg_officer') OR
            (get_current_user_role() = 'sub_org_officer' AND organization_id = get_current_user_org_id())
        )
    );

-- Update Policy
CREATE POLICY "Authors and Reviewers can update documents per role"
    ON public.documents FOR UPDATE
    TO authenticated
    USING (
        -- Authors can update their own drafts or returned documents
        (author_id = auth.uid() AND status IN ('draft', 'returned')) OR
        -- Reviewers (Super Admin & CSG) can update status/review fields on submitted/under_review docs
        (get_current_user_role() IN ('super_admin', 'csg_officer'))
    );

-- RLS: Document Versions (Read-only snapshots)
CREATE POLICY "Users can view relevant document versions"
    ON public.document_versions FOR SELECT
    TO authenticated
    USING (
        EXISTS (
            SELECT 1 FROM public.documents d
            WHERE d.id = document_versions.document_id
        )
    );

CREATE POLICY "Authors can insert document versions"
    ON public.document_versions FOR INSERT
    TO authenticated
    WITH CHECK (author_id = auth.uid());
