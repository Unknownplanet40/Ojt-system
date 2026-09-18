"use server";

import { z } from "zod";
import { createClient } from "@/lib/supabase/server";
import { createAdminClient } from "@/lib/supabase/admin";
import type { Profile } from "@/lib/supabase/types";
import { revalidatePath } from "next/cache";
import { redirect } from "next/navigation";

// Validation Schemas
const LoginSchema = z.object({
  identifier: z.string().min(3, "Student number or email is required"),
  password: z.string().min(6, "Password must be at least 6 characters"),
});

const ActivationSchema = z.object({
  email: z.string().email("Invalid email address"),
  studentNumber: z.string().min(5, "Valid student number is required"),
  fullName: z.string().min(2, "Full name is required"),
  newPassword: z.string().min(8, "Password must be at least 8 characters"),
  confirmPassword: z.string().min(8, "Password must be at least 8 characters"),
}).refine((data) => data.newPassword === data.confirmPassword, {
  message: "Passwords do not match",
  path: ["confirmPassword"],
});

const ResetRequestSchema = z.object({
  email: z.string().email("Valid institutional email is required"),
});

export type AuthActionResult = {
  success: boolean;
  error?: string;
  fieldErrors?: Record<string, string[]>;
  lockedUntil?: string;
};

export async function loginUser(prevState: any, formData: FormData): Promise<AuthActionResult> {
  const rawData = {
    identifier: formData.get("identifier"),
    password: formData.get("password"),
  };

  const parsed = LoginSchema.safeParse(rawData);
  if (!parsed.success) {
    return {
      success: false,
      fieldErrors: parsed.error.flatten().fieldErrors,
      error: "Please provide valid credentials.",
    };
  }

  const { identifier, password } = parsed.data;
  const admin = createAdminClient();
  const supabase = await createClient();

  // Find profile by email or student_number
  let targetEmail = identifier;
  const isEmail = identifier.includes("@");

  const { data: rawProfile } = await admin
    .from("profiles")
    .select("id, email, full_name, is_active, failed_login_attempts, locked_until")
    .or(isEmail ? `email.eq.${identifier}` : `student_number.eq.${identifier}`)
    .maybeSingle();

  const profile = rawProfile as (Pick<
    Profile,
    "id" | "email" | "full_name" | "is_active" | "failed_login_attempts" | "locked_until"
  > | null);

  if (profile) {
    targetEmail = profile.email;

    // Check account lockout
    if (profile.locked_until && new Date(profile.locked_until) > new Date()) {
      const lockExpiry = new Date(profile.locked_until).toLocaleTimeString();
      return {
        success: false,
        error: `Account is temporarily locked due to repeated failed attempts. Please try again after ${lockExpiry}.`,
        lockedUntil: profile.locked_until,
      };
    }
  }

  // Attempt authentication
  const { data: authData, error: authError } = await supabase.auth.signInWithPassword({
    email: targetEmail,
    password,
  });

  if (authError || !authData.user) {
    if (profile) {
      const newAttempts = (profile.failed_login_attempts || 0) + 1;
      const isLocked = newAttempts >= 5;
      const lockedUntil = isLocked
        ? new Date(Date.now() + 15 * 60 * 1000).toISOString()
        : null;

      await admin
        .from("profiles")
        .update({
          failed_login_attempts: newAttempts,
          locked_until: lockedUntil,
        })
        .eq("id", profile.id);

      await admin.from("audit_logs").insert({
        actor_id: profile.id,
        action: isLocked ? "account_locked" : "login_failed",
        target_entity: "profiles",
        target_id: profile.id,
        details: {
          attempt_count: newAttempts,
          reason: authError?.message || "Invalid credentials",
        },
      });

      if (isLocked) {
        return {
          success: false,
          error: "Account has been temporarily locked for 15 minutes due to 5 failed login attempts.",
        };
      }
    }

    return {
      success: false,
      error: "Invalid email/student number or password.",
    };
  }

  // Login successful: reset attempts and record audit log
  if (profile) {
    await admin
      .from("profiles")
      .update({
        failed_login_attempts: 0,
        locked_until: null,
      })
      .eq("id", profile.id);

    await admin.from("audit_logs").insert({
      actor_id: profile.id,
      action: "login_success",
      target_entity: "profiles",
      target_id: profile.id,
      details: { email: profile.email },
    });
  }

  revalidatePath("/", "layout");
  redirect("/dashboard");
}

export async function activateAccount(prevState: any, formData: FormData): Promise<AuthActionResult> {
  const rawData = {
    email: formData.get("email"),
    studentNumber: formData.get("studentNumber"),
    fullName: formData.get("fullName"),
    newPassword: formData.get("newPassword"),
    confirmPassword: formData.get("confirmPassword"),
  };

  const parsed = ActivationSchema.safeParse(rawData);
  if (!parsed.success) {
    return {
      success: false,
      fieldErrors: parsed.error.flatten().fieldErrors,
      error: "Please check all fields.",
    };
  }

  const { email, studentNumber, fullName, newPassword } = parsed.data;
  const admin = createAdminClient();

  const { data: rawProfile } = await admin
    .from("profiles")
    .select("id, is_active")
    .eq("email", email)
    .maybeSingle();

  const profile = rawProfile as (Pick<Profile, "id" | "is_active"> | null);

  if (!profile) {
    return {
      success: false,
      error: "No provisioned account found for this email. Contact your CSG administrator.",
    };
  }

  if (profile.is_active) {
    return {
      success: false,
      error: "Account is already active. Please sign in directly.",
    };
  }

  // Update password in Supabase Auth
  const { error: passError } = await admin.auth.admin.updateUserById(profile.id, {
    password: newPassword,
  });

  if (passError) {
    return {
      success: false,
      error: passError.message,
    };
  }

  // Activate profile
  await admin
    .from("profiles")
    .update({
      student_number: studentNumber,
      full_name: fullName,
      is_active: true,
      failed_login_attempts: 0,
      locked_until: null,
    })
    .eq("id", profile.id);

  await admin.from("audit_logs").insert({
    actor_id: profile.id,
    action: "account_activated",
    target_entity: "profiles",
    target_id: profile.id,
    details: { student_number: studentNumber },
  });

  return {
    success: true,
  };
}

export async function requestPasswordReset(prevState: any, formData: FormData): Promise<AuthActionResult> {
  const rawData = {
    email: formData.get("email"),
  };

  const parsed = ResetRequestSchema.safeParse(rawData);
  if (!parsed.success) {
    return {
      success: false,
      fieldErrors: parsed.error.flatten().fieldErrors,
      error: "Please provide a valid email.",
    };
  }

  const supabase = await createClient();
  const { error } = await supabase.auth.resetPasswordForEmail(parsed.data.email, {
    redirectTo: `${process.env.NEXT_PUBLIC_APP_URL || "http://localhost:3000"}/reset-password`,
  });

  if (error) {
    return {
      success: false,
      error: error.message,
    };
  }

  return {
    success: true,
  };
}

export async function signOutUser() {
  const supabase = await createClient();
  await supabase.auth.signOut();
  redirect("/login");
}
