"use server";

import { z } from "zod";
import { createClient } from "@/lib/supabase/server";
import { createAdminClient } from "@/lib/supabase/admin";
import { revalidatePath } from "next/cache";
import type { Profile } from "@/lib/supabase/types";

export type AnnouncementActionResult = {
  success: boolean;
  error?: string;
  fieldErrors?: Record<string, string[]>;
};

const CreateAnnouncementSchema = z.object({
  title: z.string().min(5, "Title must be at least 5 characters"),
  content: z.string().min(10, "Content must be at least 10 characters"),
  priority: z.enum(["high", "normal", "low"]).default("normal"),
  visibility: z.enum(["campus_wide", "organization_only"]).default("campus_wide"),
  organizationId: z.string().uuid().optional().or(z.literal("")),
  isPinned: z.boolean().default(false),
});

export async function createAnnouncement(formData: FormData): Promise<AnnouncementActionResult> {
  const supabase = await createClient();
  const { data: { user } } = await supabase.auth.getUser();

  if (!user) {
    return { success: false, error: "Unauthorized. Please sign in." };
  }

  const admin = createAdminClient();
  const { data: rawProfile } = await admin
    .from("profiles")
    .select("id, role, organization_id, full_name")
    .eq("id", user.id)
    .maybeSingle();

  const profile = rawProfile as (Pick<Profile, "id" | "role" | "organization_id" | "full_name"> | null);

  if (!profile) {
    return { success: false, error: "Profile not found." };
  }

  if (profile.role === "member") {
    return { success: false, error: "Members cannot broadcast announcements." };
  }

  const rawData = {
    title: formData.get("title"),
    content: formData.get("content"),
    priority: formData.get("priority") || "normal",
    visibility: formData.get("visibility") || "campus_wide",
    organizationId: formData.get("organizationId") || "",
    isPinned: formData.get("isPinned") === "true",
  };

  const parsed = CreateAnnouncementSchema.safeParse(rawData);
  if (!parsed.success) {
    return {
      success: false,
      fieldErrors: parsed.error.flatten().fieldErrors,
      error: "Please correct the announcement fields.",
    };
  }

  let { title, content, priority, visibility, organizationId, isPinned } = parsed.data;

  // PRD §3.3 Scope Rule: Sub-Org Officers can only publish for their own organization
  if (profile.role === "sub_org_officer") {
    visibility = "organization_only";
    organizationId = profile.organization_id || "";
    if (!organizationId) {
      return { success: false, error: "You must belong to an active organization to post announcements." };
    }
  }

  // Insert Announcement
  const { data: newPost, error: insertError } = await admin.from("announcements").insert({
    title,
    content,
    priority,
    visibility,
    organization_id: organizationId ? organizationId : null,
    author_id: profile.id,
    is_pinned: isPinned,
  }).select("id").single();

  if (insertError) {
    return { success: false, error: insertError.message };
  }

  revalidatePath("/dashboard/announcements");
  revalidatePath("/dashboard");
  return { success: true };
}

export async function togglePinAnnouncement(announcementId: string, currentPinStatus: boolean): Promise<AnnouncementActionResult> {
  const supabase = await createClient();
  const { data: { user } } = await supabase.auth.getUser();

  if (!user) {
    return { success: false, error: "Unauthorized." };
  }

  const admin = createAdminClient();
  const { error } = await admin
    .from("announcements")
    .update({ is_pinned: !currentPinStatus })
    .eq("id", announcementId);

  if (error) {
    return { success: false, error: error.message };
  }

  revalidatePath("/dashboard/announcements");
  return { success: true };
}

export async function deleteAnnouncement(announcementId: string): Promise<AnnouncementActionResult> {
  const supabase = await createClient();
  const { data: { user } } = await supabase.auth.getUser();

  if (!user) {
    return { success: false, error: "Unauthorized." };
  }

  const admin = createAdminClient();
  const { error } = await admin
    .from("announcements")
    .delete()
    .eq("id", announcementId);

  if (error) {
    return { success: false, error: error.message };
  }

  revalidatePath("/dashboard/announcements");
  return { success: true };
}
