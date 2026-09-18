import React from "react";
import Link from "next/link";
import { createClient } from "@/lib/supabase/server";
import { createAdminClient } from "@/lib/supabase/admin";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { DocumentStatusBadge } from "@/components/documents/document-status-badge";
import type { DocumentRecord, Profile, UserRole, DocumentStatus } from "@/lib/supabase/types";
import { PlusCircle, Search, FileText, Calendar, Building2, User } from "lucide-react";

interface DocumentsPageProps {
  searchParams: Promise<{
    status?: string;
    q?: string;
  }>;
}

export default async function DocumentsPage({ searchParams }: DocumentsPageProps) {
  const { status: filterStatus, q: searchQuery } = await searchParams;

  const supabase = await createClient();
  const { data: { user } } = await supabase.auth.getUser();

  const admin = createAdminClient();

  let userRole: UserRole = "member";
  let userOrgId: string | null = null;

  if (user) {
    const { data: profile } = await admin
      .from("profiles")
      .select("role, organization_id")
      .eq("id", user.id)
      .maybeSingle();

    if (profile) {
      userRole = (profile as Pick<Profile, "role" | "organization_id">).role;
      userOrgId = (profile as Pick<Profile, "role" | "organization_id">).organization_id;
    }
  }

  // Fetch documents per role-based access rules (AGENTS.md §9 & §10)
  let query = admin
    .from("documents")
    .select(`
      id,
      title,
      type,
      status,
      content,
      current_version,
      created_at,
      organization_id,
      author_id,
      organizations ( name, acronym ),
      profiles!documents_author_id_fkey ( full_name )
    `)
    .order("created_at", { ascending: false });

  if (userRole === "sub_org_officer" && userOrgId) {
    query = query.eq("organization_id", userOrgId);
  } else if (userRole === "member") {
    query = query.eq("status", "approved");
  }

  if (filterStatus && filterStatus !== "all") {
    query = query.eq("status", filterStatus);
  }

  const { data: rawDocs } = await query;
  const docs = (rawDocs || []) as unknown as Array<{
    id: string;
    title: string;
    type: string;
    status: DocumentStatus;
    content: { total_budget?: number; venue?: string };
    current_version: number;
    created_at: string;
    organizations: { name: string; acronym: string } | null;
    profiles: { full_name: string } | null;
  }>;

  const filteredDocs = docs.filter((doc) => {
    if (!searchQuery) return true;
    return doc.title.toLowerCase().includes(searchQuery.toLowerCase());
  });

  const statuses = [
    { label: "All Documents", value: "all" },
    { label: "Drafts", value: "draft" },
    { label: "Submitted", value: "submitted" },
    { label: "Under Review", value: "under_review" },
    { label: "Approved", value: "approved" },
    { label: "Returned", value: "returned" },
  ];

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold tracking-tight text-foreground">
            Document Management & Workflow
          </h1>
          <p className="text-sm text-muted-foreground">
            Official proposals, memoranda, and organizational activity reviews
          </p>
        </div>

        {userRole !== "member" && (
          <Button asChild className="bg-emerald-800 hover:bg-emerald-900 text-white shadow-sm shrink-0">
            <Link href="/dashboard/documents/new">
              <PlusCircle className="w-4 h-4 mr-2" />
              New Activity Proposal
            </Link>
          </Button>
        )}
      </div>

      {/* Filter Tabs */}
      <div className="flex items-center gap-2 overflow-x-auto pb-2 border-b">
        {statuses.map((s) => {
          const isActive = (!filterStatus && s.value === "all") || filterStatus === s.value;
          return (
            <Link
              key={s.value}
              href={`/dashboard/documents?status=${s.value}${searchQuery ? `&q=${searchQuery}` : ""}`}
              className={`px-3 py-1.5 text-xs font-semibold rounded-lg transition-colors whitespace-nowrap ${
                isActive
                  ? "bg-emerald-800 text-white shadow-xs"
                  : "text-muted-foreground hover:text-foreground hover:bg-muted"
              }`}
            >
              {s.label}
            </Link>
          );
        })}
      </div>

      {/* Documents List */}
      {filteredDocs.length === 0 ? (
        <Card className="border-dashed py-12 text-center">
          <CardContent className="space-y-3">
            <div className="w-12 h-12 rounded-full bg-muted flex items-center justify-center mx-auto text-muted-foreground">
              <FileText className="w-6 h-6" />
            </div>
            <h3 className="font-semibold text-base">No documents found</h3>
            <p className="text-sm text-muted-foreground max-w-sm mx-auto">
              {filterStatus && filterStatus !== "all"
                ? `There are currently no proposals matching the '${filterStatus}' status filter.`
                : "No documents have been recorded yet. Click 'New Activity Proposal' to create one."}
            </p>
          </CardContent>
        </Card>
      ) : (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {filteredDocs.map((doc) => (
            <Link key={doc.id} href={`/dashboard/documents/${doc.id}`} className="group block">
              <Card className="h-full border hover:border-emerald-800/40 hover:shadow-md transition-all">
                <CardHeader className="p-5 pb-3">
                  <div className="flex items-start justify-between gap-2 mb-2">
                    <DocumentStatusBadge status={doc.status} />
                    <span className="text-[11px] font-semibold text-muted-foreground bg-muted px-2 py-0.5 rounded">
                      v{doc.current_version}
                    </span>
                  </div>
                  <CardTitle className="text-base font-semibold group-hover:text-emerald-800 dark:group-hover:text-emerald-400 transition-colors line-clamp-2">
                    {doc.title}
                  </CardTitle>
                  <CardDescription className="flex items-center gap-1.5 text-xs mt-1">
                    <Building2 className="w-3.5 h-3.5 text-emerald-700" />
                    <span className="font-medium text-foreground">
                      {doc.organizations?.acronym || "Organization"}
                    </span>
                    <span>•</span>
                    <span className="truncate">{doc.organizations?.name}</span>
                  </CardDescription>
                </CardHeader>
                <CardContent className="p-5 pt-0 space-y-3">
                  <div className="flex items-center justify-between text-xs text-muted-foreground pt-2 border-t">
                    <div className="flex items-center gap-1">
                      <User className="w-3 h-3" />
                      <span>{doc.profiles?.full_name || "Author"}</span>
                    </div>
                    {doc.content?.total_budget !== undefined && (
                      <span className="font-bold text-emerald-800 dark:text-emerald-400">
                        ₱{Number(doc.content.total_budget).toLocaleString("en-PH", { minimumFractionDigits: 2 })}
                      </span>
                    )}
                  </div>
                  <div className="text-[11px] text-muted-foreground flex items-center gap-1">
                    <Calendar className="w-3 h-3" />
                    <span>{new Date(doc.created_at).toLocaleDateString()}</span>
                  </div>
                </CardContent>
              </Card>
            </Link>
          ))}
        </div>
      )}
    </div>
  );
}
