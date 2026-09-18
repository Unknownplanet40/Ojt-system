"use server";

import { createServerClient } from "@/lib/supabase/server";
import { revalidatePath } from "next/cache";
import type { EventRecord } from "@/lib/supabase/types";

export interface ConflictCheckResult {
  hasConflict: boolean;
  conflicts: Array<{
    id: string;
    title: string;
    location: string;
    start_at: string;
    end_at: string;
    organization?: { name: string; acronym: string };
  }>;
}

export async function checkVenueConflicts(
  location: string,
  startAt: string,
  endAt: string,
  excludeEventId?: string
): Promise<ConflictCheckResult> {
  const supabase = await createServerClient();

  let query = supabase
    .from("events")
    .select("id, title, location, start_at, end_at, organization:organizations(name, acronym)")
    .eq("location", location.trim())
    .neq("status", "cancelled")
    .lt("start_at", endAt)
    .gt("end_at", startAt);

  if (excludeEventId) {
    query = query.neq("id", excludeEventId);
  }

  const { data, error } = await query;

  if (error || !data || data.length === 0) {
    return { hasConflict: false, conflicts: [] };
  }

  return {
    hasConflict: true,
    conflicts: data as any,
  };
}

export async function createEvent(formData: FormData): Promise<{
  success: boolean;
  error?: string;
  hasConflict?: boolean;
  conflicts?: ConflictCheckResult["conflicts"];
}> {
  const supabase = await createServerClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    return { success: false, error: "Authentication required" };
  }

  const { data: profile } = await supabase
    .from("profiles")
    .select("id, role, organization_id, full_name")
    .eq("id", user.id)
    .single();

  if (!profile || !["super_admin", "csg_officer", "sub_org_officer"].includes(profile.role)) {
    return { success: false, error: "Unauthorized. Officers only." };
  }

  const title = (formData.get("title") as string)?.trim();
  const description = (formData.get("description") as string)?.trim() || null;
  const location = (formData.get("location") as string)?.trim();
  const startAt = formData.get("start_at") as string;
  const endAt = formData.get("end_at") as string;
  const conflictAcknowledged = formData.get("conflict_acknowledged") === "true";
  let organizationId = (formData.get("organization_id") as string)?.trim();

  if (!title || !location || !startAt || !endAt) {
    return { success: false, error: "Please fill in all required fields (title, location, start and end dates)." };
  }

  const startDate = new Date(startAt);
  const endDate = new Date(endAt);

  if (isNaN(startDate.getTime()) || isNaN(endDate.getTime())) {
    return { success: false, error: "Invalid date format." };
  }

  if (endDate <= startDate) {
    return { success: false, error: "Event end time must be after the start time." };
  }

  // Determine authorized organization
  if (profile.role === "sub_org_officer") {
    if (!profile.organization_id) {
      return { success: false, error: "No organization assigned to officer account." };
    }
    organizationId = profile.organization_id;
  } else {
    // CSG or Admin: If none selected, fallback to CSG org
    if (!organizationId) {
      const { data: csgOrg } = await supabase
        .from("organizations")
        .select("id")
        .eq("acronym", "CSG")
        .single();
      organizationId = csgOrg?.id || profile.organization_id || "";
    }
  }

  if (!organizationId) {
    return { success: false, error: "Target organization could not be determined." };
  }

  // Soft conflict check
  const conflictResult = await checkVenueConflicts(location, startAt, endAt);
  if (conflictResult.hasConflict && !conflictAcknowledged) {
    return {
      success: false,
      hasConflict: true,
      conflicts: conflictResult.conflicts,
      error: `Venue overlap detected: ${conflictResult.conflicts.length} scheduled event(s) already occupy '${location}' during this timeslot.`,
    };
  }

  const { error: insertError } = await supabase.from("events").insert({
    title,
    description,
    location,
    start_at: startAt,
    end_at: endAt,
    organization_id: organizationId,
    created_by: user.id,
    status: "scheduled",
    conflict_acknowledged: conflictAcknowledged,
  });

  if (insertError) {
    return { success: false, error: insertError.message };
  }

  // Audit log
  await supabase.from("audit_logs").insert({
    actor_id: user.id,
    action: "event_created",
    target_table: "events",
    metadata: {
      title,
      location,
      start_at: startAt,
      end_at: endAt,
      organization_id: organizationId,
      had_conflict: conflictResult.hasConflict,
    },
  });

  revalidatePath("/dashboard/events");
  revalidatePath("/dashboard");
  return { success: true };
}

export async function updateEventStatus(
  eventId: string,
  status: "scheduled" | "completed" | "cancelled"
): Promise<{ success: boolean; error?: string }> {
  const supabase = await createServerClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) return { success: false, error: "Authentication required" };

  const { data: profile } = await supabase
    .from("profiles")
    .select("id, role, organization_id")
    .eq("id", user.id)
    .single();

  if (!profile || !["super_admin", "csg_officer", "sub_org_officer"].includes(profile.role)) {
    return { success: false, error: "Unauthorized." };
  }

  const { error } = await supabase
    .from("events")
    .update({ status })
    .eq("id", eventId);

  if (error) {
    return { success: false, error: error.message };
  }

  revalidatePath("/dashboard/events");
  revalidatePath("/dashboard");
  return { success: true };
}

export async function deleteEvent(eventId: string): Promise<{ success: boolean; error?: string }> {
  const supabase = await createServerClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) return { success: false, error: "Authentication required" };

  const { error } = await supabase
    .from("events")
    .delete()
    .eq("id", eventId);

  if (error) return { success: false, error: error.message };

  revalidatePath("/dashboard/events");
  revalidatePath("/dashboard");
  return { success: true };
}
