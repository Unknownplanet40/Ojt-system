import { createServerClient } from "@/lib/supabase/server";
import { redirect } from "next/navigation";
import { ReportsView } from "@/components/reports/reports-view";
import type { DocumentRecord, EventRecord, Organization, Profile } from "@/lib/supabase/types";

export const metadata = {
  title: "Reports & Analytics | CvSU Imus CSG",
  description: "Official institutional reporting, analytics visualizations, and CSV/PDF export.",
};

export default async function ReportsPage() {
  const supabase = await createServerClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    redirect("/login");
  }

  const { data: profile } = await supabase
    .from("profiles")
    .select("*")
    .eq("id", user.id)
    .single();

  if (!profile) {
    redirect("/login");
  }

  // Fetch organizations
  const { data: orgs } = await supabase
    .from("organizations")
    .select("*")
    .eq("is_active", true)
    .order("name", { ascending: true });

  // Fetch documents based on role
  let docsQuery = supabase
    .from("documents")
    .select("*, organization:organizations(name, acronym), author:profiles(full_name)")
    .order("created_at", { ascending: false });

  if (profile.role === "sub_org_officer" && profile.organization_id) {
    docsQuery = docsQuery.eq("organization_id", profile.organization_id);
  }

  const { data: docs } = await docsQuery;

  // Fetch events
  let eventsQuery = supabase
    .from("events")
    .select("*, organization:organizations(name, acronym)")
    .order("start_at", { ascending: false });

  if (profile.role === "sub_org_officer" && profile.organization_id) {
    eventsQuery = eventsQuery.eq("organization_id", profile.organization_id);
  }

  const { data: events } = await eventsQuery;

  return (
    <div className="space-y-6">
      {/* Page Header */}
      <div className="print:hidden">
        <h1 className="text-2xl font-bold tracking-tight text-foreground">
          Reports & Analytics
        </h1>
        <p className="text-sm text-muted-foreground mt-1">
          Institutional accountability dashboards, term activity metrics, and official signatory-endorsed report exports.
        </p>
      </div>

      <ReportsView
        documents={(docs as any) || []}
        events={(events as any) || []}
        organizations={(orgs as any) || []}
        currentProfile={profile as Profile}
      />
    </div>
  );
}
