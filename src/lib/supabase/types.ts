export type UserRole = 'super_admin' | 'csg_officer' | 'sub_org_officer' | 'member';
export type DocumentStatus = 'draft' | 'submitted' | 'under_review' | 'approved' | 'returned';
export type DocumentType = 'activity_proposal' | 'liquidation' | 'accomplishment_report' | 'resolution' | 'memorandum' | 'other';
export type AuditAction =
  | 'login_success'
  | 'login_failed'
  | 'password_reset_request'
  | 'password_reset_success'
  | 'account_activated'
  | 'account_locked'
  | 'account_unlocked'
  | 'document_submitted'
  | 'document_approved'
  | 'document_returned'
  | 'role_changed'
  | 'user_created'
  | 'organization_created';

export interface Course {
  id: string;
  code: string;
  name: string;
  is_active: boolean;
  created_at: string;
  updated_at: string;
}

export interface Section {
  id: string;
  course_id: string;
  year_level: number;
  section_name: string;
  created_at: string;
}

export interface Organization {
  id: string;
  name: string;
  acronym: string;
  type: string;
  description: string | null;
  logo_url: string | null;
  is_active: boolean;
  created_at: string;
  updated_at: string;
}

export interface Profile {
  id: string;
  student_number: string | null;
  full_name: string;
  email: string;
  role: UserRole;
  organization_id: string | null;
  course_id: string | null;
  section_id: string | null;
  avatar_url: string | null;
  is_active: boolean;
  failed_login_attempts: number;
  locked_until: string | null;
  created_at: string;
  updated_at: string;
}

export interface AuditLog {
  id: string;
  actor_id: string | null;
  action: AuditAction;
  target_entity: string;
  target_id: string | null;
  details: Record<string, any>;
  ip_address: string | null;
  created_at: string;
}

export interface BudgetItem {
  id: string;
  description: string;
  quantity: number;
  unit_cost: number;
  total_cost: number;
  category: 'materials' | 'food' | 'honorarium' | 'transportation' | 'equipment' | 'other';
}

export interface ActivityProposalContent {
  theme?: string;
  objectives: string[];
  target_participants?: string;
  expected_count?: number;
  venue: string;
  start_date: string;
  end_date: string;
  budget_items: BudgetItem[];
  total_budget: number;
  funding_source?: string;
  adviser_name?: string;
}

export interface DocumentRecord {
  id: string;
  organization_id: string;
  author_id: string;
  title: string;
  type: DocumentType;
  status: DocumentStatus;
  content: ActivityProposalContent | Record<string, any>;
  attachment_urls: string[];
  reviewer_id: string | null;
  reviewer_feedback: string | null;
  reviewed_at: string | null;
  current_version: number;
  created_at: string;
  updated_at: string;
  organization?: Organization;
  author?: Profile;
  reviewer?: Profile;
}

export interface DocumentVersionRecord {
  id: string;
  document_id: string;
  version_number: number;
  author_id: string;
  title: string;
  content: ActivityProposalContent | Record<string, any>;
  attachment_urls: string[];
  change_summary: string | null;
  created_at: string;
  author?: Profile;
}

export interface AnnouncementRecord {
  id: string;
  title: string;
  content: string;
  priority: 'high' | 'normal' | 'low';
  visibility: 'campus_wide' | 'organization_only';
  organization_id: string | null;
  author_id: string;
  is_pinned: boolean;
  created_at: string;
  updated_at: string;
  organization?: Organization;
  author?: Profile;
}

export type Database = {
  public: {
    Tables: {
      announcements: {
        Row: AnnouncementRecord;
        Insert: Partial<AnnouncementRecord>;
        Update: Partial<AnnouncementRecord>;
        Relationships: [];
      };
      courses: {
        Row: Course;
        Insert: Partial<Course>;
        Update: Partial<Course>;
        Relationships: [];
      };
      sections: {
        Row: Section;
        Insert: Partial<Section>;
        Update: Partial<Section>;
        Relationships: [];
      };
      organizations: {
        Row: Organization;
        Insert: Partial<Organization>;
        Update: Partial<Organization>;
        Relationships: [];
      };
      profiles: {
        Row: Profile;
        Insert: Partial<Profile>;
        Update: Partial<Profile>;
        Relationships: [];
      };
      audit_logs: {
        Row: AuditLog;
        Insert: Partial<AuditLog>;
        Update: Partial<AuditLog>;
        Relationships: [];
      };
      documents: {
        Row: DocumentRecord;
        Insert: Partial<DocumentRecord>;
        Update: Partial<DocumentRecord>;
        Relationships: [];
      };
      document_versions: {
        Row: DocumentVersionRecord;
        Insert: Partial<DocumentVersionRecord>;
        Update: Partial<DocumentVersionRecord>;
        Relationships: [];
      };
    };
    Views: Record<string, never>;
    Functions: Record<string, never>;
    Enums: {
      user_role: UserRole;
      document_status: DocumentStatus;
      document_type: DocumentType;
      audit_action: AuditAction;
    };
    CompositeTypes: Record<string, never>;
  };
};
