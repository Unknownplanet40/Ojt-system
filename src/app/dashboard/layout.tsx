import React from "react";
import { createClient } from "@/lib/supabase/server";
import { Sidebar } from "@/components/dashboard/sidebar";
import { Header } from "@/components/dashboard/header";
import { OfflineBanner } from "@/components/common/offline-banner";
import type { Profile } from "@/lib/supabase/types";

// Default fallback profile for initial setup / local development before Supabase seed
const defaultProfile: Profile = {
  id: "demo-user",
  student_number: "202310001",
  full_name: "CSG Administrator",
  email: "admin@cvsu.edu.ph",
  role: "super_admin",
  organization_id: null,
  course_id: null,
  section_id: null,
  avatar_url: null,
  is_active: true,
  failed_login_attempts: 0,
  locked_until: null,
  created_at: new Date().toISOString(),
  updated_at: new Date().toISOString(),
};

export default async function DashboardLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  let profile: Profile = defaultProfile;

  try {
    const supabase = await createClient();
    const {
      data: { user },
    } = await supabase.auth.getUser();

    if (user) {
      const { data } = await supabase
        .from("profiles")
        .select("*")
        .eq("id", user.id)
        .maybeSingle();

      if (data) {
        profile = data as Profile;
      } else {
        profile = {
          ...defaultProfile,
          id: user.id,
          email: user.email || defaultProfile.email,
          full_name: user.user_metadata?.full_name || defaultProfile.full_name,
        };
      }
    }
  } catch {
    // If Supabase is not configured yet or during static analysis, use defaultProfile
  }

  return (
    <div className="min-h-screen flex bg-background">
      <Sidebar role={profile.role} className="hidden md:flex shrink-0" />
      <div className="flex-1 flex flex-col min-w-0">
        <OfflineBanner />
        <Header profile={profile} />
        <main className="flex-1 p-6 md:p-8 max-w-7xl w-full mx-auto">
          {children}
        </main>
      </div>
    </div>
  );
}
