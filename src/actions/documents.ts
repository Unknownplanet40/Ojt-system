"use server";

import { z } from "zod";
import { createClient } from "@/lib/supabase/server";
import { createAdminClient } from "@/lib/supabase/admin";
import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";
import { ActivityProposalSchema, ReviewActionSchema, ResubmitDocumentSchema } from "@/lib/validations/document";
import type { DocumentRecord, Profile, UserRole, ActivityProposalContent } from "@/lib/supabase/types";

export type DocumentActionResult = {
  success: boolean;
  error?: string;
  fieldErrors?: Record<string, string[]>;
  documentId?: string;
};

// 1. Create Document (Draft or Submit)
export async function createDocument(formData: FormData): Promise<DocumentActionResult> {
  const supabase = await createClient();
  const { data: { user } } = await supabase.auth.getUser();

  if (!user) {
    return { success: false, error: "Unauthorized. Please sign in." };
  }

  // Resolve profile server-side (Strict Security Boundary - never trust client input)
  const admin = createAdminClient();
  const { data: rawProfile } = await admin
    .from("profiles")
    .select("id, role, organization_id, full_name")
    .eq("id", user.id)
    .maybeSingle();

  const profile = rawProfile as (Pick<Profile, "id" | "role" | "organization_id" | "full_name"> | null);

  if (!profile) {
    return { success: false, error: "User profile not found." };
  }

  // Check role authorization
  if (profile.role === "member") {
    return { success: false, error: "Regular members cannot submit official activity proposals." };
  }

  // Resolve target organization
  let targetOrgId = profile.organization_id;
  if (profile.role === "super_admin" || profile.role === "csg_officer") {
    const selectedOrgId = formData.get("organization_id") as string;
    if (selectedOrgId) {
      targetOrgId = selectedOrgId;
    } else if (!targetOrgId) {
      // Default to CSG organization if CSG officer has no sub-org
      const { data: csgOrg } = await admin
        .from("organizations")
        .select("id")
        .eq("acronym", "CSG")
        .maybeSingle();
      targetOrgId = (csgOrg as { id: string } | null)?.id || null;
    }
  }

  if (!targetOrgId) {
    return { success: false, error: "You must belong to an active organization to submit documents." };
  }

  // Parse structured payload
  const rawBudget = formData.get("budget_items") as string;
  let parsedBudget = [];
  try {
    parsedBudget = rawBudget ? JSON.parse(rawBudget) : [];
  } catch {
    parsedBudget = [];
  }

  const rawObjectives = formData.getAll("objectives") as string[];
  const objectives = rawObjectives.filter((obj) => obj && obj.trim().length > 0);

  const rawData = {
    title: formData.get("title"),
    type: formData.get("type") || "activity_proposal",
    theme: formData.get("theme") || "",
    objectives: objectives.length > 0 ? objectives : [""],
    target_participants: formData.get("target_participants") || "",
    expected_count: formData.get("expected_count") ? Number(formData.get("expected_count")) : undefined,
    venue: formData.get("venue"),
    start_date: formData.get("start_date"),
    end_date: formData.get("end_date"),
    budget_items: parsedBudget,
    funding_source: formData.get("funding_source") || "",
    adviser_name: formData.get("adviser_name") || "",
    action: formData.get("action") || "submit",
  };

  const parsed = ActivityProposalSchema.safeParse(rawData);
  if (!parsed.success) {
    return {
      success: false,
      fieldErrors: parsed.error.flatten().fieldErrors,
      error: "Please correct the errors in the proposal form.",
    };
  }

  const { title, type, action, ...contentFields } = parsed.data;

  // Calculate total budget
  const totalBudget = contentFields.budget_items.reduce(
    (sum, item) => sum + (Number(item.quantity) * Number(item.unit_cost)),
    0
  );

  const proposalContent: ActivityProposalContent = {
    ...contentFields,
    total_budget: totalBudget,
  };

  const initialStatus = action === "draft" ? "draft" : "submitted";

  // Insert Document
  const { data: newDoc, error: insertError } = await admin
    .from("documents")
    .insert({
      organization_id: targetOrgId,
      author_id: profile.id,
      title,
      type,
      status: initialStatus,
      content: proposalContent,
      attachment_urls: [],
      current_version: 1,
    })
    .select("id")
    .single();

  if (insertError || !newDoc) {
    return { success: false, error: insertError?.message || "Failed to create document." };
  }

  const docId = (newDoc as { id: string }).id;

  // Record initial version snapshot
  await admin.from("document_versions").insert({
    document_id: docId,
    version_number: 1,
    author_id: profile.id,
    title,
    content: proposalContent,
    attachment_urls: [],
    change_summary: action === "draft" ? "Initial draft" : "Initial submission",
  });

  // Security Audit Log
  await admin.from("audit_logs").insert({
    actor_id: profile.id,
    action: "document_submitted",
    target_entity: "documents",
    target_id: docId,
    details: {
      title,
      status: initialStatus,
      organization_id: targetOrgId,
      total_budget: totalBudget,
    },
  });

  revalidatePath("/dashboard/documents");
  redirect(`/dashboard/documents/${docId}`);
}

// 2. Resubmit / Update Document (returned -> submitted with Change Summary)
export async function resubmitDocument(formData: FormData): Promise<DocumentActionResult> {
  const supabase = await createClient();
  const { data: { user } } = await supabase.auth.getUser();

  if (!user) {
    return { success: false, error: "Unauthorized. Please sign in." };
  }

  const documentId = formData.get("document_id") as string;
  const changeSummary = formData.get("change_summary") as string;

  if (!documentId || !changeSummary || changeSummary.trim().length < 5) {
    return { success: false, error: "Document ID and a detailed Summary of Changes (min 5 chars) are required." };
  }

  const admin = createAdminClient();

  // Fetch current document state
  const { data: existingDoc } = await admin
    .from("documents")
    .select("id, author_id, organization_id, status, current_version")
    .eq("id", documentId)
    .maybeSingle();

  const doc = existingDoc as (Pick<DocumentRecord, "id" | "author_id" | "organization_id" | "status" | "current_version"> | null);

  if (!doc) {
    return { success: false, error: "Document not found." };
  }

  // AGENTS.md §10: Only documents in 'draft' or 'returned' status can be modified by author
  if (doc.status !== "returned" && doc.status !== "draft") {
    return {
      success: false,
      error: `Cannot edit or resubmit a document in '${doc.status}' status. Only drafts or returned documents can be updated.`,
    };
  }

  // Check author ownership or org officer
  const { data: rawProfile } = await admin
    .from("profiles")
    .select("id, role, organization_id")
    .eq("id", user.id)
    .maybeSingle();

  const profile = rawProfile as (Pick<Profile, "id" | "role" | "organization_id"> | null);

  if (!profile || (doc.author_id !== profile.id && profile.role !== "super_admin")) {
    return { success: false, error: "You are not authorized to update this document." };
  }

  // Parse updated form fields
  const rawBudget = formData.get("budget_items") as string;
  let parsedBudget = [];
  try {
    parsedBudget = rawBudget ? JSON.parse(rawBudget) : [];
  } catch {
    parsedBudget = [];
  }

  const rawObjectives = formData.getAll("objectives") as string[];
  const objectives = rawObjectives.filter((obj) => obj && obj.trim().length > 0);

  const title = (formData.get("title") as string) || "Activity Proposal";
  const newVersion = (doc.current_version || 1) + 1;

  const totalBudget = parsedBudget.reduce(
    (sum: number, item: any) => sum + (Number(item.quantity) * Number(item.unit_cost)),
    0
  );

  const proposalContent: ActivityProposalContent = {
    theme: (formData.get("theme") as string) || "",
    objectives: objectives.length > 0 ? objectives : ["Proposal objective"],
    target_participants: (formData.get("target_participants") as string) || "",
    expected_count: formData.get("expected_count") ? Number(formData.get("expected_count")) : undefined,
    venue: (formData.get("venue") as string) || "Campus Venue",
    start_date: (formData.get("start_date") as string) || new Date().toISOString(),
    end_date: (formData.get("end_date") as string) || new Date().toISOString(),
    budget_items: parsedBudget,
    total_budget: totalBudget,
    funding_source: (formData.get("funding_source") as string) || "",
    adviser_name: (formData.get("adviser_name") as string) || "",
  };

  // Update Document state to 'submitted'
  await admin
    .from("documents")
    .update({
      title,
      status: "submitted",
      content: proposalContent,
      current_version: newVersion,
      reviewed_at: null,
      updated_at: new Date().toISOString(),
    })
    .eq("id", documentId);

  // Append new version snapshot
  await admin.from("document_versions").insert({
    document_id: documentId,
    version_number: newVersion,
    author_id: profile.id,
    title,
    content: proposalContent,
    change_summary: changeSummary,
  });

  // Log audit trail
  await admin.from("audit_logs").insert({
    actor_id: profile.id,
    action: "document_submitted",
    target_entity: "documents",
    target_id: documentId,
    details: {
      version: newVersion,
      previous_status: doc.status,
      change_summary: changeSummary,
      total_budget: totalBudget,
    },
  });

  revalidatePath(`/dashboard/documents/${documentId}`);
  revalidatePath("/dashboard/documents");
  redirect(`/dashboard/documents/${documentId}`);
}

// 3. Move document to 'under_review' (CSG Reviewer)
export async function setUnderReview(documentId: string): Promise<DocumentActionResult> {
  const supabase = await createClient();
  const { data: { user } } = await supabase.auth.getUser();

  if (!user) {
    return { success: false, error: "Unauthorized." };
  }

  const admin = createAdminClient();
  const { data: rawProfile } = await admin
    .from("profiles")
    .select("id, role")
    .eq("id", user.id)
    .maybeSingle();

  const profile = rawProfile as (Pick<Profile, "id" | "role"> | null);

  if (!profile || (profile.role !== "csg_officer" && profile.role !== "super_admin")) {
    return { success: false, error: "Only CSG Officers or Super Admins can review documents." };
  }

  const { data: existingDoc } = await admin
    .from("documents")
    .select("status")
    .eq("id", documentId)
    .maybeSingle();

  const doc = existingDoc as { status: string } | null;
  if (!doc || doc.status !== "submitted") {
    return { success: false, error: "Only submitted documents can be placed under review." };
  }

  await admin
    .from("documents")
    .update({
      status: "under_review",
      reviewer_id: profile.id,
      updated_at: new Date().toISOString(),
    })
    .eq("id", documentId);

  revalidatePath(`/dashboard/documents/${documentId}`);
  revalidatePath("/dashboard/documents");
  return { success: true };
}

// 4. Approve Document (CSG Officer / Super Admin)
export async function approveDocument(documentId: string): Promise<DocumentActionResult> {
  const supabase = await createClient();
  const { data: { user } } = await supabase.auth.getUser();

  if (!user) {
    return { success: false, error: "Unauthorized." };
  }

  const admin = createAdminClient();
  const { data: rawProfile } = await admin
    .from("profiles")
    .select("id, role, full_name")
    .eq("id", user.id)
    .maybeSingle();

  const profile = rawProfile as (Pick<Profile, "id" | "role" | "full_name"> | null);

  if (!profile || (profile.role !== "csg_officer" && profile.role !== "super_admin")) {
    return { success: false, error: "Only CSG Officers or Super Admins can approve documents." };
  }

  // AGENTS.md §10: Valid transitions to approved: under_review (or submitted)
  const { data: existingDoc } = await admin
    .from("documents")
    .select("title, status, organization_id")
    .eq("id", documentId)
    .maybeSingle();

  const doc = existingDoc as { title: string; status: string; organization_id: string } | null;
  if (!doc || (doc.status !== "under_review" && doc.status !== "submitted")) {
    return { success: false, error: `Cannot approve document in '${doc?.status || "unknown"}' status.` };
  }

  await admin
    .from("documents")
    .update({
      status: "approved",
      reviewer_id: profile.id,
      reviewed_at: new Date().toISOString(),
      updated_at: new Date().toISOString(),
    })
    .eq("id", documentId);

  // Security Audit Log
  await admin.from("audit_logs").insert({
    actor_id: profile.id,
    action: "document_approved",
    target_entity: "documents",
    target_id: documentId,
    details: {
      document_title: doc.title,
      organization_id: doc.organization_id,
      approved_by: profile.full_name,
    },
  });

  revalidatePath(`/dashboard/documents/${documentId}`);
  revalidatePath("/dashboard/documents");
  return { success: true };
}

// 5. Return Document with Revision Feedback (CSG Officer / Super Admin)
export async function returnDocument(formData: FormData): Promise<DocumentActionResult> {
  const supabase = await createClient();
  const { data: { user } } = await supabase.auth.getUser();

  if (!user) {
    return { success: false, error: "Unauthorized." };
  }

  const documentId = formData.get("document_id") as string;
  const reviewerFeedback = formData.get("reviewer_feedback") as string;

  if (!documentId || !reviewerFeedback || reviewerFeedback.trim().length < 5) {
    return {
      success: false,
      error: "Detailed reviewer feedback explaining the required revisions is mandatory (min 5 characters).",
    };
  }

  const admin = createAdminClient();
  const { data: rawProfile } = await admin
    .from("profiles")
    .select("id, role, full_name")
    .eq("id", user.id)
    .maybeSingle();

  const profile = rawProfile as (Pick<Profile, "id" | "role" | "full_name"> | null);

  if (!profile || (profile.role !== "csg_officer" && profile.role !== "super_admin")) {
    return { success: false, error: "Only CSG Officers or Super Admins can return documents." };
  }

  const { data: existingDoc } = await admin
    .from("documents")
    .select("title, status, organization_id")
    .eq("id", documentId)
    .maybeSingle();

  const doc = existingDoc as { title: string; status: string; organization_id: string } | null;
  if (!doc || (doc.status !== "under_review" && doc.status !== "submitted")) {
    return { success: false, error: `Cannot return document in '${doc?.status || "unknown"}' status.` };
  }

  await admin
    .from("documents")
    .update({
      status: "returned",
      reviewer_id: profile.id,
      reviewer_feedback: reviewerFeedback,
      reviewed_at: new Date().toISOString(),
      updated_at: new Date().toISOString(),
    })
    .eq("id", documentId);

  // Security Audit Log
  await admin.from("audit_logs").insert({
    actor_id: profile.id,
    action: "document_returned",
    target_entity: "documents",
    target_id: documentId,
    details: {
      document_title: doc.title,
      organization_id: doc.organization_id,
      feedback: reviewerFeedback,
      returned_by: profile.full_name,
    },
  });

  revalidatePath(`/dashboard/documents/${documentId}`);
  revalidatePath("/dashboard/documents");
  return { success: true };
}
