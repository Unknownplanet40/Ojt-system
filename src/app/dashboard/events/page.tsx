import { createServerClient } from "@/lib/supabase/server";
import { redirect } from "next/navigation";
import { EventsView } from "@/components/events/events-view";
import type { EventRecord, Organization, Profile } from "@/lib/supabase/types";

export const metadata = {
  title: "Calendar & Campus Events | CvSU Imus CSG",
  description: "Official campus activities and student organization event calendar.",
};

export default async function EventsPage() {
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

  // Fetch all organizations for filtering and host assignment
  const { data: orgs } = await supabase
    .from("organizations")
    .select("*")
    .eq("is_active", true)
    .order("name", { ascending: true });

  // Fetch events
  const { data: events } = await supabase
    .from("events")
    .select("*, organization:organizations(name, acronym)")
    .order("start_at", { ascending: true });

  return (
    <div className="space-y-6">
      {/* Page Title & Breadcrumb */}
      <div>
        <h1 className="text-2xl font-bold tracking-tight text-foreground">
          Calendar & Campus Events
        </h1>
        <p className="text-sm text-muted-foreground mt-1">
          Coordinated schedule of student organization activities, assemblies, and official campus gatherings.
        </p>
      </div>

      <EventsView
        initialEvents={(events as any) || []}
        organizations={(orgs as any) || []}
        currentProfile={profile as Profile}
      />
    </div>
  );
}
