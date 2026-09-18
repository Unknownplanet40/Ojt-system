"use server";

import { z } from "zod";
import { createClient } from "@/lib/supabase/server";
import { createAdminClient } from "@/lib/supabase/admin";
import { revalidatePath } from "next/cache";
import type { UserRole, Profile } from "@/lib/supabase/types";

export type AdminActionResult = {
  success: boolean;
  error?: string;
  fieldErrors?: Record<string, string[]>;
};

// Guard helper to ensure caller is Super Admin
async function requireSuperAdmin() {
  const supabase = await createClient();
  const { data: { user } } = await supabase.auth.getUser();

  if (!user) {
    throw new Error("Unauthorized. Please sign in.");
  }

  const admin = createAdminClient();
  const { data: rawProfile } = await admin
    .from("profiles")
    .select("id, role, full_name")
    .eq("id", user.id)
    .maybeSingle();

  const profile = rawProfile as (Pick<Profile, "id" | "role" | "full_name"> | null);

  if (!profile || profile.role !== "super_admin") {
    throw new Error("Forbidden. Super Admin access required.");
  }

  return profile;
}

// 1. Provision New User Account
const ProvisionUserSchema = z.object({
  email: z.string().email("Invalid institutional email"),
  studentNumber: z.string().min(5, "Student number is required"),
  fullName: z.string().min(2, "Full name is required"),
  role: z.enum(["super_admin", "csg_officer", "sub_org_officer", "member"]),
  organizationId: z.string().uuid().optional().or(z.literal("")),
  courseId: z.string().uuid().optional().or(z.literal("")),
  sectionId: z.string().uuid().optional().or(z.literal("")),
});

export async function provisionUser(formData: FormData): Promise<AdminActionResult> {
  let actor: Pick<Profile, "id" | "role" | "full_name">;
  try {
    actor = await requireSuperAdmin();
  } catch (err: any) {
    return { success: false, error: err.message };
  }

  const rawData = {
    email: formData.get("email"),
    studentNumber: formData.get("studentNumber"),
    fullName: formData.get("fullName"),
    role: formData.get("role"),
    organizationId: formData.get("organizationId") || "",
    courseId: formData.get("courseId") || "",
    sectionId: formData.get("sectionId") || "",
  };

  const parsed = ProvisionUserSchema.safeParse(rawData);
  if (!parsed.success) {
    return {
      success: false,
      fieldErrors: parsed.error.flatten().fieldErrors,
      error: "Please correct all form fields.",
    };
  }

  const { email, studentNumber, fullName, role, organizationId, courseId, sectionId } = parsed.data;
  const admin = createAdminClient();

  // Generate a random temporary password for initial provisioning
  const tempPassword = "Temp@" + Math.random().toString(36).substring(2, 10) + "2026!";

  // Create user in Supabase Auth
  const { data: authUser, error: authError } = await admin.auth.admin.createUser({
    email,
    password: tempPassword,
    email_confirm: true,
    user_metadata: {
      full_name: fullName,
      role,
      is_active: false,
    },
  });

  if (authError || !authUser.user) {
    return {
      success: false,
      error: authError?.message || "Failed to provision user in authentication service.",
    };
  }

  const userId = authUser.user.id;

  // Ensure profile record is updated with provided details
  await admin.from("profiles").upsert({
    id: userId,
    email,
    student_number: studentNumber,
    full_name: fullName,
    role,
    organization_id: organizationId ? organizationId : null,
    course_id: courseId ? courseId : null,
    section_id: sectionId ? sectionId : null,
    is_active: false,
    failed_login_attempts: 0,
    locked_until: null,
  });

  // Log to audit trail
  await admin.from("audit_logs").insert({
    actor_id: actor.id,
    action: "user_created",
    target_entity: "profiles",
    target_id: userId,
    details: {
      email,
      student_number: studentNumber,
      role,
      provisioned_by: actor.full_name,
    },
  });

  revalidatePath("/dashboard/users");
  return { success: true };
}

// 2. Immediate Account Unlock
export async function unlockUserAccount(targetUserId: string): Promise<AdminActionResult> {
  let actor: Pick<Profile, "id" | "role" | "full_name">;
  try {
    actor = await requireSuperAdmin();
  } catch (err: any) {
    return { success: false, error: err.message };
  }

  const admin = createAdminClient();

  // Reset failed login attempts and clear locked_until
  const { error } = await admin
    .from("profiles")
    .update({
      failed_login_attempts: 0,
      locked_until: null,
      updated_at: new Date().toISOString(),
    })
    .eq("id", targetUserId);

  if (error) {
    return { success: false, error: error.message };
  }

  // Record audit log
  await admin.from("audit_logs").insert({
    actor_id: actor.id,
    action: "account_unlocked",
    target_entity: "profiles",
    target_id: targetUserId,
    details: {
      unlocked_by: actor.full_name,
      timestamp: new Date().toISOString(),
    },
  });

  revalidatePath("/dashboard/users");
  return { success: true };
}

// 3. Update User Role & Organization Assignment
export async function updateUserRoleAndOrg(formData: FormData): Promise<AdminActionResult> {
  let actor: Pick<Profile, "id" | "role" | "full_name">;
  try {
    actor = await requireSuperAdmin();
  } catch (err: any) {
    return { success: false, error: err.message };
  }

  const userId = formData.get("userId") as string;
  const newRole = formData.get("role") as UserRole;
  const newOrgId = formData.get("organizationId") as string;

  if (!userId || !newRole) {
    return { success: false, error: "User ID and Role are required." };
  }

  const admin = createAdminClient();

  const { error } = await admin
    .from("profiles")
    .update({
      role: newRole,
      organization_id: newOrgId ? newOrgId : null,
      updated_at: new Date().toISOString(),
    })
    .eq("id", userId);

  if (error) {
    return { success: false, error: error.message };
  }

  // Audit logging
  await admin.from("audit_logs").insert({
    actor_id: actor.id,
    action: "role_changed",
    target_entity: "profiles",
    target_id: userId,
    details: {
      new_role: newRole,
      new_organization_id: newOrgId || null,
      changed_by: actor.full_name,
    },
  });

  revalidatePath("/dashboard/users");
  return { success: true };
}

// 4. Create Academic Degree Program (Course)
export async function createCourse(formData: FormData): Promise<AdminActionResult> {
  let actor: Pick<Profile, "id" | "role" | "full_name">;
  try {
    actor = await requireSuperAdmin();
  } catch (err: any) {
    return { success: false, error: err.message };
  }

  const code = (formData.get("code") as string)?.toUpperCase().trim();
  const name = (formData.get("name") as string)?.trim();

  if (!code || !name || code.length < 2 || name.length < 3) {
    return { success: false, error: "Valid course code (e.g. BSIT) and full degree title are required." };
  }

  const admin = createAdminClient();

  const { error } = await admin.from("courses").insert({
    code,
    name,
    is_active: true,
  });

  if (error) {
    return { success: false, error: error.message };
  }

  revalidatePath("/dashboard/academics");
  return { success: true };
}

// 5. Toggle Course Active Status
export async function toggleCourseStatus(courseId: string, currentStatus: boolean): Promise<AdminActionResult> {
  try {
    await requireSuperAdmin();
  } catch (err: any) {
    return { success: false, error: err.message };
  }

  const admin = createAdminClient();

  const { error } = await admin
    .from("courses")
    .update({
      is_active: !currentStatus,
      updated_at: new Date().toISOString(),
    })
    .eq("id", courseId);

  if (error) {
    return { success: false, error: error.message };
  }

  revalidatePath("/dashboard/academics");
  return { success: true };
}

// 6. Create Academic Section
export async function createSection(formData: FormData): Promise<AdminActionResult> {
  try {
    await requireSuperAdmin();
  } catch (err: any) {
    return { success: false, error: err.message };
  }

  const courseId = formData.get("courseId") as string;
  const yearLevel = parseInt(formData.get("yearLevel") as string, 10);
  const sectionName = (formData.get("sectionName") as string)?.trim();

  if (!courseId || !yearLevel || !sectionName || yearLevel < 1 || yearLevel > 5) {
    return { success: false, error: "Course, Year level (1-5), and Section Name are required." };
  }

  const admin = createAdminClient();

  const { error } = await admin.from("sections").insert({
    course_id: courseId,
    year_level: yearLevel,
    section_name: sectionName,
  });

  if (error) {
    return { success: false, error: error.message };
  }

  revalidatePath("/dashboard/academics");
  return { success: true };
}
