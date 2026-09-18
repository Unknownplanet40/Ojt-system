-- Migration 20260919000004_chat_and_events_schema.sql
-- Events and Live Chat for CvSU Imus CSG Management System

-- 1. Create Events Table
CREATE TABLE IF NOT EXISTS public.events (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    title VARCHAR(255) NOT NULL,
    description TEXT,
    organization_id UUID NOT NULL REFERENCES public.organizations(id) ON DELETE CASCADE,
    created_by UUID NOT NULL REFERENCES public.profiles(id) ON DELETE CASCADE,
    location VARCHAR(255) NOT NULL,
    start_at TIMESTAMPTZ NOT NULL,
    end_at TIMESTAMPTZ NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'scheduled' CHECK (status IN ('scheduled', 'completed', 'cancelled')),
    conflict_acknowledged BOOLEAN NOT NULL DEFAULT false,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    CONSTRAINT chk_event_dates CHECK (end_at > start_at)
);

CREATE TRIGGER update_events_updated_at
    BEFORE UPDATE ON public.events
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

-- Indexes for Events
CREATE INDEX IF NOT EXISTS idx_events_org ON public.events(organization_id);
CREATE INDEX IF NOT EXISTS idx_events_location ON public.events(location);
CREATE INDEX IF NOT EXISTS idx_events_start_at ON public.events(start_at);
CREATE INDEX IF NOT EXISTS idx_events_end_at ON public.events(end_at);
CREATE INDEX IF NOT EXISTS idx_events_status ON public.events(status);

-- 2. Create Conversations Table
CREATE TABLE IF NOT EXISTS public.conversations (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    title VARCHAR(255) NOT NULL,
    type VARCHAR(30) NOT NULL DEFAULT 'organization' CHECK (type IN ('organization', 'council', 'direct')),
    organization_id UUID REFERENCES public.organizations(id) ON DELETE CASCADE,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TRIGGER update_conversations_updated_at
    BEFORE UPDATE ON public.conversations
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

CREATE INDEX IF NOT EXISTS idx_conversations_type ON public.conversations(type);
CREATE INDEX IF NOT EXISTS idx_conversations_org ON public.conversations(organization_id);

-- 3. Create Conversation Participants Table
CREATE TABLE IF NOT EXISTS public.conversation_participants (
    conversation_id UUID NOT NULL REFERENCES public.conversations(id) ON DELETE CASCADE,
    profile_id UUID NOT NULL REFERENCES public.profiles(id) ON DELETE CASCADE,
    last_read_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    joined_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    PRIMARY KEY (conversation_id, profile_id)
);

CREATE INDEX IF NOT EXISTS idx_participants_profile ON public.conversation_participants(profile_id);

-- 4. Create Messages Table
CREATE TABLE IF NOT EXISTS public.messages (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    conversation_id UUID NOT NULL REFERENCES public.conversations(id) ON DELETE CASCADE,
    sender_id UUID REFERENCES public.profiles(id) ON DELETE SET NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    edited_at TIMESTAMPTZ
);

CREATE INDEX IF NOT EXISTS idx_messages_conversation ON public.messages(conversation_id);
CREATE INDEX IF NOT EXISTS idx_messages_sender ON public.messages(sender_id);
CREATE INDEX IF NOT EXISTS idx_messages_created_at ON public.messages(created_at ASC);

-- 5. Row Level Security Setup
ALTER TABLE public.events ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.conversations ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.conversation_participants ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.messages ENABLE ROW LEVEL SECURITY;

-- Events Policies:
-- All authenticated users can view calendar events
CREATE POLICY "Users can view calendar events"
    ON public.events FOR SELECT
    TO authenticated
    USING (true);

-- Event Creation: CSG officers/admins can create for any org; sub-org officers for their own
CREATE POLICY "Authorized officers can schedule events"
    ON public.events FOR INSERT
    TO authenticated
    WITH CHECK (
        created_by = auth.uid() AND (
            get_current_user_role() IN ('super_admin', 'csg_officer') OR
            (get_current_user_role() = 'sub_org_officer' AND organization_id = get_current_user_org_id())
        )
    );

CREATE POLICY "Officers can update own events"
    ON public.events FOR UPDATE
    TO authenticated
    USING (
        get_current_user_role() IN ('super_admin', 'csg_officer') OR
        (get_current_user_role() = 'sub_org_officer' AND organization_id = get_current_user_org_id())
    );

CREATE POLICY "Officers can delete own events"
    ON public.events FOR DELETE
    TO authenticated
    USING (
        get_current_user_role() IN ('super_admin', 'csg_officer') OR
        (get_current_user_role() = 'sub_org_officer' AND organization_id = get_current_user_org_id())
    );

-- Conversation Policies:
-- Hierarchical communication:
-- 1. 'council': Accessible to super_admin, csg_officer, and sub_org_officer
-- 2. 'organization': Accessible to members & officers of that org, or super_admin / csg_officer
CREATE POLICY "Users can view permitted conversations"
    ON public.conversations FOR SELECT
    TO authenticated
    USING (
        get_current_user_role() IN ('super_admin', 'csg_officer') OR
        (type = 'council' AND get_current_user_role() = 'sub_org_officer') OR
        (type = 'organization' AND organization_id = get_current_user_org_id())
    );

-- Participants Policies:
CREATE POLICY "Users can view conversation participants"
    ON public.conversation_participants FOR SELECT
    TO authenticated
    USING (
        profile_id = auth.uid() OR
        get_current_user_role() IN ('super_admin', 'csg_officer')
    );

CREATE POLICY "Users can manage own participant record"
    ON public.conversation_participants FOR ALL
    TO authenticated
    USING (profile_id = auth.uid())
    WITH CHECK (profile_id = auth.uid());

-- Messages Policies:
CREATE POLICY "Users can view messages in permitted conversations"
    ON public.messages FOR SELECT
    TO authenticated
    USING (
        EXISTS (
            SELECT 1 FROM public.conversations c
            WHERE c.id = messages.conversation_id
            AND (
                get_current_user_role() IN ('super_admin', 'csg_officer') OR
                (c.type = 'council' AND get_current_user_role() = 'sub_org_officer') OR
                (c.type = 'organization' AND c.organization_id = get_current_user_org_id())
            )
        )
    );

CREATE POLICY "Users can post messages in permitted conversations"
    ON public.messages FOR INSERT
    TO authenticated
    WITH CHECK (
        sender_id = auth.uid() AND
        EXISTS (
            SELECT 1 FROM public.conversations c
            WHERE c.id = messages.conversation_id
            AND (
                get_current_user_role() IN ('super_admin', 'csg_officer') OR
                (c.type = 'council' AND get_current_user_role() = 'sub_org_officer') OR
                (c.type = 'organization' AND c.organization_id = get_current_user_org_id())
            )
        )
    );

-- Add messages to Realtime publication
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_publication_tables
        WHERE pubname = 'supabase_realtime' AND tablename = 'messages'
    ) THEN
        ALTER PUBLICATION supabase_realtime ADD TABLE public.messages;
    END IF;
EXCEPTION
    WHEN OTHERS THEN
        NULL; -- Ignore if publication doesn't exist in local/mock environment
END $$;
