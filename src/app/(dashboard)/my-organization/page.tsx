import React from "react";
import Link from "next/link";
import { createClient } from "@/lib/supabase/server";
import { createAdminClient } from "@/lib/supabase/admin";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { DocumentStatusBadge } from "@/components/documents/document-status-badge";
import { updateOrganizationProfile } from "@/actions/organizations";
import type { Organization, Profile, DocumentRecord, UserRole } from "@/lib/supabase/types";
import {
  Building2,
  Users,
  PlusCircle,
  FileText,
  Save,
  ShieldCheck,
  CheckCircle2,
  Clock,
  ArrowRight,
} from "lucide-react";

export default async function MyOrganizationPage() {
  const supabase = await createClient();
  const { data: { user } } = await supabase.auth.getUser();

  if (!user) {
    return (
      <div className="p-8 text-center">
        <p>Please sign in to view your organization.</p>
      </div>
    );
  }

  const admin = createAdminClient();

  // Resolve user profile
  const { data: rawProfile } = await admin
    .from("profiles")
    .select("id, full_name, role, organization_id")
    .eq("id", user.id)
    .maybeSingle();

  const profile = rawProfile as (Pick<Profile, "id" | "full_name" | "role" | "organization_id"> | null);

  if (!profile || !profile.organization_id) {
    return (
      <Card className="max-w-md mx-auto my-12 text-center p-6 space-y-4">
        <Building2 className="w-12 h-12 text-muted-foreground mx-auto" />
        <CardTitle>No Organization Assigned</CardTitle>
        <CardDescription>
          Your account is currently not assigned as an officer to any recognized student organization. Please contact the CSG Administrator.
        </CardDescription>
      </Card>
    );
  }

  // Fetch organization
  const { data: rawOrg } = await admin
    .from("organizations")
    .select("*")
    .eq("id", profile.organization_id)
    .maybeSingle();

  const org = rawOrg as Organization;

  // Fetch organization's documents
  const { data: rawDocs } = await admin
    .from("documents")
    .select("id, title, type, status, current_version, created_at, content")
    .eq("organization_id", org.id)
    .order("created_at", { ascending: false });

  const docs = (rawDocs || []) as unknown as Array<{
    id: string;
    title: string;
    type: string;
    status: any;
    current_version: number;
    created_at: string;
    content: { total_budget?: number };
  }>;

  // Fetch fellow officers
  const { data: rawOfficers } = await admin
    .from("profiles")
    .select("id, full_name, email, role")
    .eq("organization_id", org.id);

  const officers = (rawOfficers || []) as Profile[];

  return (
    <div className="max-w-5xl mx-auto space-y-8">
      {/* Header Banner */}
      <div className="border rounded-2xl bg-card p-6 sm:p-8 shadow-sm border-emerald-800/20 text-center space-y-3 relative overflow-hidden">
        <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-100 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-300 text-xs font-semibold">
          <ShieldCheck className="w-4 h-4" />
          Sub-Organization Administration
        </div>
        <h1 className="text-2xl sm:text-3xl font-extrabold text-foreground tracking-tight">
          {org.name} ({org.acronym})
        </h1>
        <p className="max-w-2xl mx-auto text-sm text-muted-foreground">
          Manage your organization profile, track activity proposals, and collaborate with executive officers.
        </p>
        <div className="pt-2 flex items-center justify-center gap-2">
          {org.is_active ? (
            <Badge variant="emerald" className="text-xs px-3 py-1">
              <CheckCircle2 className="w-3.5 h-3.5 mr-1.5" />
              Active CSG Recognition
            </Badge>
          ) : (
            <Badge variant="secondary" className="text-xs px-3 py-1">
              <Clock className="w-3.5 h-3.5 mr-1.5" />
              Recognition Suspended / Pending
            </Badge>
          )}
        </div>
      </div>

      <div className="grid gap-6 md:grid-cols-3">
        {/* Left Column: Edit Organization Profile */}
        <Card className="md:col-span-1 shadow-sm">
          <CardHeader>
            <CardTitle className="text-base flex items-center gap-2">
              <Building2 className="w-4 h-4 text-emerald-700" />
              Organization Profile
            </CardTitle>
            <CardDescription>
              Update public details visible in the campus directory
            </CardDescription>
          </CardHeader>
          <CardContent>
            <form
              action={async (formData: FormData) => {
                "use server";
                await updateOrganizationProfile(formData);
              }}
              className="space-y-4"
            >
              <input type="hidden" name="organizationId" value={org.id} />

              <div className="space-y-1">
                <Label htmlFor="type" className="text-xs">Category</Label>
                <select
                  id="type"
                  name="type"
                  defaultValue={org.type}
                  className="w-full h-8 rounded-md border border-input bg-background px-2 text-xs focus:outline-none focus:ring-1 focus:ring-ring capitalize"
                >
                  <option value="academic">Academic</option>
                  <option value="governing">Governing</option>
                  <option value="special_interest">Special Interest</option>
                </select>
              </div>

              <div className="space-y-1">
                <Label htmlFor="description" className="text-xs">About / Description</Label>
                <textarea
                  id="description"
                  name="description"
                  rows={4}
                  defaultValue={org.description || ""}
                  placeholder="Describe your organization's mission and campus objectives..."
                  className="w-full rounded-md border border-input bg-background p-2 text-xs shadow-sm focus:outline-none focus:ring-1 focus:ring-ring"
                />
              </div>

              <Button type="submit" size="sm" className="w-full bg-emerald-800 hover:bg-emerald-900 text-white text-xs h-8">
                <Save className="w-3.5 h-3.5 mr-1.5" />
                Save Changes
              </Button>
            </form>

            <div className="pt-6 border-t mt-6 space-y-3">
              <h4 className="font-semibold text-xs text-muted-foreground uppercase tracking-wider">
                Executive Officers ({officers.length})
              </h4>
              <div className="space-y-2">
                {officers.map((off) => (
                  <div key={off.id} className="text-xs flex items-center justify-between">
                    <span className="font-medium text-foreground">{off.full_name}</span>
                    <span className="text-[10px] text-muted-foreground capitalize">
                      {off.role.replace("_", " ")}
                    </span>
                  </div>
                ))}
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Right Column: Organization Documents */}
        <div className="md:col-span-2 space-y-6">
          <div className="flex items-center justify-between">
            <h3 className="font-bold text-lg text-foreground flex items-center gap-2">
              <FileText className="w-5 h-5 text-emerald-700" />
              Activity Proposals & Submissions ({docs.length})
            </h3>
            <Button asChild size="sm" className="bg-emerald-800 hover:bg-emerald-900 text-white text-xs">
              <Link href="/dashboard/documents/new">
                <PlusCircle className="w-3.5 h-3.5 mr-1.5" />
                New Proposal
              </Link>
            </Button>
          </div>

          {docs.length === 0 ? (
            <Card className="border-dashed p-8 text-center space-y-2">
              <p className="text-sm font-medium">No documents submitted yet.</p>
              <p className="text-xs text-muted-foreground">
                Your organization has not submitted any activity proposals this academic year.
              </p>
            </Card>
          ) : (
            <div className="space-y-3">
              {docs.map((doc) => (
                <Link
                  key={doc.id}
                  href={`/dashboard/documents/${doc.id}`}
                  className="block group"
                >
                  <Card className="hover:border-emerald-800/40 transition-all">
                    <CardHeader className="p-4 pb-2 flex flex-row items-center justify-between">
                      <CardTitle className="text-sm font-semibold group-hover:text-emerald-800 dark:group-hover:text-emerald-400">
                        {doc.title}
                      </CardTitle>
                      <div className="flex items-center gap-2">
                        <DocumentStatusBadge status={doc.status} />
                        <span className="text-[10px] font-semibold text-muted-foreground">
                          v{doc.current_version}
                        </span>
                      </div>
                    </CardHeader>
                    <CardContent className="p-4 pt-0 flex items-center justify-between text-xs text-muted-foreground">
                      <span>Submitted on {new Date(doc.created_at).toLocaleDateString()}</span>
                      {doc.content?.total_budget !== undefined && (
                        <span className="font-bold text-emerald-800 dark:text-emerald-400">
                          ₱{Number(doc.content.total_budget).toLocaleString("en-PH", { minimumFractionDigits: 2 })}
                        </span>
                      )}
                    </CardContent>
                  </Card>
                </Link>
              ))}
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
