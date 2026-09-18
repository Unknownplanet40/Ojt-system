import React from "react";
import Link from "next/link";
import { notFound } from "next/navigation";
import { createClient } from "@/lib/supabase/server";
import { createAdminClient } from "@/lib/supabase/admin";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { toggleOrganizationStatus } from "@/actions/organizations";
import type { Organization, Profile, DocumentRecord, UserRole } from "@/lib/supabase/types";
import {
  ArrowLeft,
  Building2,
  Users,
  ShieldCheck,
  CheckCircle2,
  Clock,
  FileText,
  Calendar,
  Power,
} from "lucide-react";

interface OrgDetailPageProps {
  params: Promise<{
    id: string;
  }>;
}

export default async function OrganizationDetailPage({ params }: OrgDetailPageProps) {
  const { id: orgId } = await params;

  const supabase = await createClient();
  const { data: { user } } = await supabase.auth.getUser();

  const admin = createAdminClient();

  // Fetch organization
  const { data: rawOrg } = await admin
    .from("organizations")
    .select("*")
    .eq("id", orgId)
    .maybeSingle();

  if (!rawOrg) {
    notFound();
  }

  const org = rawOrg as Organization;

  // Fetch current officers for this organization
  const { data: rawOfficers } = await admin
    .from("profiles")
    .select("id, full_name, email, student_number, role")
    .eq("organization_id", orgId)
    .in("role", ["sub_org_officer", "csg_officer"])
    .order("full_name");

  const officers = (rawOfficers || []) as Profile[];

  // Fetch approved documents from this organization
  const { data: rawDocs } = await admin
    .from("documents")
    .select("id, title, type, created_at, current_version")
    .eq("organization_id", orgId)
    .eq("status", "approved")
    .order("created_at", { ascending: false });

  const approvedDocs = (rawDocs || []) as unknown as Array<{
    id: string;
    title: string;
    type: string;
    created_at: string;
    current_version: number;
  }>;

  // Check viewer role
  let isSuperAdminOrCSG = false;
  if (user) {
    const { data: profile } = await admin
      .from("profiles")
      .select("role")
      .eq("id", user.id)
      .maybeSingle();

    if (profile) {
      const role = (profile as { role: UserRole }).role;
      isSuperAdminOrCSG = role === "super_admin" || role === "csg_officer";
    }
  }

  return (
    <div className="max-w-5xl mx-auto space-y-6">
      <div className="flex items-center justify-between gap-4">
        <Button asChild variant="ghost" size="sm">
          <Link href="/dashboard/organizations">
            <ArrowLeft className="w-4 h-4 mr-1" />
            Back to Directory
          </Link>
        </Button>

        {isSuperAdminOrCSG && (
          <form
            action={async () => {
              "use server";
              await toggleOrganizationStatus(org.id, org.is_active);
            }}
          >
            <Button
              type="submit"
              size="sm"
              variant={org.is_active ? "outline" : "default"}
              className="text-xs"
            >
              <Power className="w-3.5 h-3.5 mr-1.5" />
              {org.is_active ? "Mark as Inactive / Suspended" : "Grant Active Recognition"}
            </Button>
          </form>
        )}
      </div>

      {/* Official Co-Branded Header Banner */}
      <div className="border rounded-2xl bg-card p-6 sm:p-8 shadow-sm border-emerald-800/20 text-center space-y-3 relative overflow-hidden">
        <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-100 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-300 text-xs font-semibold">
          <ShieldCheck className="w-4 h-4" />
          Cavite State University – Imus Campus
        </div>
        <div>
          <h1 className="text-2xl sm:text-3xl font-extrabold text-foreground tracking-tight">
            {org.name}
          </h1>
          <span className="inline-block mt-1 font-mono text-sm font-bold text-emerald-800 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/50 px-2.5 py-0.5 rounded-full border border-emerald-200 dark:border-emerald-800">
            {org.acronym}
          </span>
        </div>
        <p className="max-w-2xl mx-auto text-sm text-muted-foreground leading-relaxed">
          {org.description || "Official recognized student organization operating under the governance of the Central Student Government."}
        </p>

        <div className="pt-2 flex items-center justify-center gap-2">
          {org.is_active ? (
            <Badge variant="emerald" className="text-xs px-3 py-1">
              <CheckCircle2 className="w-3.5 h-3.5 mr-1.5" />
              Officially Recognized & Active
            </Badge>
          ) : (
            <Badge variant="secondary" className="text-xs px-3 py-1">
              <Clock className="w-3.5 h-3.5 mr-1.5" />
              Recognition Pending / Suspended
            </Badge>
          )}
        </div>
      </div>

      <div className="grid gap-6 md:grid-cols-2">
        {/* Officers Roster */}
        <Card className="shadow-sm">
          <CardHeader>
            <CardTitle className="text-base flex items-center gap-2">
              <Users className="w-4 h-4 text-emerald-700" />
              Current Executive Officers ({officers.length})
            </CardTitle>
            <CardDescription>
              Registered student leaders authorized for this academic year
            </CardDescription>
          </CardHeader>
          <CardContent>
            {officers.length === 0 ? (
              <p className="text-xs text-muted-foreground py-4 text-center">
                No executive officers registered yet.
              </p>
            ) : (
              <div className="divide-y divide-border/60">
                {officers.map((officer) => (
                  <div key={officer.id} className="py-3 flex items-center justify-between gap-3">
                    <div>
                      <h4 className="font-semibold text-sm text-foreground">{officer.full_name}</h4>
                      <p className="text-xs text-muted-foreground">{officer.email}</p>
                      {officer.student_number && (
                        <span className="text-[10px] font-mono text-muted-foreground">
                          SN: {officer.student_number}
                        </span>
                      )}
                    </div>
                    <Badge variant="secondary" className="text-[10px] capitalize">
                      {officer.role.replace("_", " ")}
                    </Badge>
                  </div>
                ))}
              </div>
            )}
          </CardContent>
        </Card>

        {/* Approved Public Documents */}
        <Card className="shadow-sm">
          <CardHeader>
            <CardTitle className="text-base flex items-center gap-2">
              <FileText className="w-4 h-4 text-emerald-700" />
              Approved Activities & Records ({approvedDocs.length})
            </CardTitle>
            <CardDescription>
              Official proposals and accomplishment reports cleared by CSG
            </CardDescription>
          </CardHeader>
          <CardContent>
            {approvedDocs.length === 0 ? (
              <p className="text-xs text-muted-foreground py-4 text-center">
                No approved documents released for public view.
              </p>
            ) : (
              <div className="divide-y divide-border/60">
                {approvedDocs.map((doc) => (
                  <Link
                    key={doc.id}
                    href={`/dashboard/documents/${doc.id}`}
                    className="py-3 flex items-center justify-between gap-3 group hover:bg-muted/20 px-2 rounded transition-colors"
                  >
                    <div>
                      <h4 className="font-semibold text-sm text-foreground group-hover:text-emerald-800 dark:group-hover:text-emerald-400">
                        {doc.title}
                      </h4>
                      <div className="flex items-center gap-2 text-xs text-muted-foreground mt-0.5">
                        <Calendar className="w-3 h-3" />
                        <span>{new Date(doc.created_at).toLocaleDateString()}</span>
                        <span>•</span>
                        <span className="capitalize">{doc.type.replace("_", " ")}</span>
                      </div>
                    </div>
                    <span className="text-xs font-semibold text-emerald-700 dark:text-emerald-400">
                      View
                    </span>
                  </Link>
                ))}
              </div>
            )}
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
