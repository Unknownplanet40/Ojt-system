"use server";

import { z } from "zod";
import { createClient } from "@/lib/supabase/server";
import { createAdminClient } from "@/lib/supabase/admin";
import { revalidatePath } from "next/cache";
import type { Profile } from "@/lib/supabase/types";

export type OrgActionResult = {
  success: boolean;
  error?: string;
};

// 1. Update Organization Profile (Sub-Org Officer or CSG/Admin)
export async function updateOrganizationProfile(formData: FormData): Promise<OrgActionResult> {
  const supabase = await createClient();
  const { data: { user } } = await supabase.auth.getUser();

  if (!user) {
    return { success: false, error: "Unauthorized. Please sign in." };
  }

  const organizationId = formData.get("organizationId") as string;
  const description = (formData.get("description") as string)?.trim();
  const type = (formData.get("type") as string) || "academic";

  if (!organizationId) {
    return { success: false, error: "Organization ID is required." };
  }

  const admin = createAdminClient();
  const { data: rawProfile } = await admin
    .from("profiles")
    .select("id, role, organization_id")
    .eq("id", user.id)
    .maybeSingle();

  const profile = rawProfile as (Pick<Profile, "id" | "role" | "organization_id"> | null);

  if (!profile) {
    return { success: false, error: "User profile not found." };
  }

  // Security authorization check: Must be Super Admin, CSG Officer, or Sub-Org Officer assigned to this org
  const isAuthorized =
    profile.role === "super_admin" ||
    profile.role === "csg_officer" ||
    (profile.role === "sub_org_officer" && profile.organization_id === organizationId);

  if (!isAuthorized) {
    return { success: false, error: "You are not authorized to update this organization." };
  }

  const { error } = await admin
    .from("organizations")
    .update({
      description,
      type,
      updated_at: new Date().toISOString(),
    })
    .eq("id", organizationId);

  if (error) {
    return { success: false, error: error.message };
  }

  revalidatePath(`/dashboard/organizations/${organizationId}`);
  revalidatePath("/dashboard/my-organization");
  revalidatePath("/dashboard/organizations");
  return { success: true };
}

// 2. Toggle Recognition Status (Super Admin / CSG Officer)
export async function toggleOrganizationStatus(organizationId: string, currentStatus: boolean): Promise<OrgActionResult> {
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

  if (!profile || (profile.role !== "super_admin" && profile.role !== "csg_officer")) {
    return { success: false, error: "Only Super Admins and CSG Officers can toggle recognition status." };
  }

  const { error } = await admin
    .from("organizations")
    .update({
      is_active: !currentStatus,
      updated_at: new Date().toISOString(),
    })
    .eq("id", organizationId);

  if (error) {
    return { success: false, error: error.message };
  }

  revalidatePath(`/dashboard/organizations/${organizationId}`);
  revalidatePath("/dashboard/organizations");
  return { success: true };
}
