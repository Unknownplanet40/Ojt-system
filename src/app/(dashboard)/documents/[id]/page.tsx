import React from "react";
import Link from "next/link";
import { notFound } from "next/navigation";
import { createClient } from "@/lib/supabase/server";
import { createAdminClient } from "@/lib/supabase/admin";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";
import { DocumentStatusBadge } from "@/components/documents/document-status-badge";
import { BudgetBuilder } from "@/components/documents/budget-builder";
import { ReviewControls } from "@/components/documents/review-action-modal";
import { resubmitDocument } from "@/actions/documents";
import type { DocumentRecord, DocumentVersionRecord, Profile, UserRole, ActivityProposalContent } from "@/lib/supabase/types";
import {
  ArrowLeft,
  Building2,
  Calendar,
  Clock,
  User,
  ShieldCheck,
  AlertCircle,
  FileCheck2,
  History,
  RotateCcw,
  Send,
} from "lucide-react";

interface DocumentDetailPageProps {
  params: Promise<{
    id: string;
  }>;
}

export default async function DocumentDetailPage({ params }: DocumentDetailPageProps) {
  const { id: documentId } = await params;

  const supabase = await createClient();
  const { data: { user } } = await supabase.auth.getUser();

  const admin = createAdminClient();

  // Fetch document with relations
  const { data: rawDoc } = await admin
    .from("documents")
    .select(`
      id,
      organization_id,
      author_id,
      title,
      type,
      status,
      content,
      attachment_urls,
      reviewer_id,
      reviewer_feedback,
      reviewed_at,
      current_version,
      created_at,
      updated_at,
      organizations ( id, name, acronym ),
      profiles!documents_author_id_fkey ( id, full_name, email, student_number ),
      reviewer:profiles!documents_reviewer_id_fkey ( id, full_name )
    `)
    .eq("id", documentId)
    .maybeSingle();

  if (!rawDoc) {
    notFound();
  }

  const doc = rawDoc as unknown as DocumentRecord & {
    organizations: { id: string; name: string; acronym: string } | null;
    author: { id: string; full_name: string; email: string; student_number: string } | null;
    reviewer: { id: string; full_name: string } | null;
  };

  // Fetch version snapshots
  const { data: rawVersions } = await admin
    .from("document_versions")
    .select(`
      id,
      version_number,
      title,
      change_summary,
      created_at,
      profiles!document_versions_author_id_fkey ( full_name )
    `)
    .eq("document_id", documentId)
    .order("version_number", { ascending: false });

  const versions = (rawVersions || []) as unknown as Array<{
    id: string;
    version_number: number;
    title: string;
    change_summary: string | null;
    created_at: string;
    profiles: { full_name: string } | null;
  }>;

  // Resolve current viewer role and permissions
  let userRole: UserRole = "member";
  let currentUserId = user?.id || null;

  if (user) {
    const { data: profile } = await admin
      .from("profiles")
      .select("role")
      .eq("id", user.id)
      .maybeSingle();
    if (profile) {
      userRole = (profile as { role: UserRole }).role;
    }
  }

  const isAuthor = currentUserId === doc.author_id;
  const isReviewer = userRole === "csg_officer" || userRole === "super_admin";
  const content = doc.content as ActivityProposalContent;

  return (
    <div className="max-w-4xl mx-auto space-y-6">
      {/* Navigation & Header */}
      <div className="flex items-center justify-between gap-4">
        <Button asChild variant="ghost" size="sm">
          <Link href="/dashboard/documents">
            <ArrowLeft className="w-4 h-4 mr-1" />
            Back to Documents
          </Link>
        </Button>

        <div className="flex items-center gap-2">
          <DocumentStatusBadge status={doc.status} />
          <span className="text-xs font-semibold px-2 py-0.5 rounded bg-muted">
            Version {doc.current_version}
          </span>
        </div>
      </div>

      {/* Institutional Header Banner (CvSU Letterhead per PRD §3.4) */}
      <div className="border rounded-xl bg-card p-6 shadow-sm border-emerald-800/20 text-center space-y-2">
        <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-100 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-300 text-xs font-semibold">
          <ShieldCheck className="w-4 h-4" />
          Cavite State University – Imus Campus
        </div>
        <h2 className="font-bold text-lg text-emerald-950 dark:text-emerald-50 tracking-tight">
          Central Student Government & Student Organizations Council
        </h2>
        <div className="flex items-center justify-center gap-2 text-xs text-muted-foreground">
          <span className="font-semibold text-foreground">
            {doc.organizations?.name} ({doc.organizations?.acronym})
          </span>
          <span>•</span>
          <span>Submitted on {new Date(doc.created_at).toLocaleDateString()}</span>
        </div>
      </div>

      {/* Returned Document Feedback Alert */}
      {doc.status === "returned" && (
        <Alert variant="destructive" className="border-destructive/40 shadow-sm">
          <RotateCcw className="w-4 h-4" />
          <AlertTitle className="font-bold">Proposal Returned for Revision</AlertTitle>
          <AlertDescription className="space-y-2 mt-1">
            <p className="text-sm font-medium">
              Reviewer Feedback: &ldquo;{doc.reviewer_feedback}&rdquo;
            </p>
            {doc.reviewer?.full_name && (
              <p className="text-xs text-destructive/80">
                Returned by: {doc.reviewer.full_name} on{" "}
                {doc.reviewed_at ? new Date(doc.reviewed_at).toLocaleDateString() : ""}
              </p>
            )}
          </AlertDescription>
        </Alert>
      )}

      {/* Reviewer Action Controls (for CSG / Admin) */}
      {isReviewer && (
        <Card className="border-emerald-800/30 bg-emerald-50/30 dark:bg-emerald-950/10 shadow-sm">
          <CardHeader className="py-4">
            <CardTitle className="text-sm font-semibold flex items-center gap-2 text-emerald-900 dark:text-emerald-200">
              <FileCheck2 className="w-4 h-4 text-emerald-700" />
              CSG Reviewer Actions
            </CardTitle>
          </CardHeader>
          <CardContent className="pt-0">
            <ReviewControls documentId={doc.id} currentStatus={doc.status} />
          </CardContent>
        </Card>
      )}

      {/* Author Resubmission Form (if document is returned and user is author) */}
      {doc.status === "returned" && isAuthor && (
        <Card className="border-amber-500/40 bg-amber-50/20 dark:bg-amber-950/10 shadow-sm">
          <CardHeader>
            <CardTitle className="text-base text-amber-900 dark:text-amber-200">
              Resubmit Revised Proposal
            </CardTitle>
            <CardDescription>
              Address the reviewer&apos;s feedback above and provide a summary of the changes made.
            </CardDescription>
          </CardHeader>
          <form
            action={async (formData: FormData) => {
              "use server";
              await resubmitDocument(formData);
            }}
          >
            <input type="hidden" name="document_id" value={doc.id} />
            <input type="hidden" name="title" value={doc.title} />
            <input type="hidden" name="venue" value={content?.venue || ""} />
            <input type="hidden" name="start_date" value={content?.start_date || ""} />
            <input type="hidden" name="end_date" value={content?.end_date || ""} />
            <input type="hidden" name="theme" value={content?.theme || ""} />

            <CardContent className="space-y-4">
              <div className="space-y-2">
                <label htmlFor="change_summary" className="text-xs font-semibold">
                  Mandatory Summary of Revisions *
                </label>
                <textarea
                  id="change_summary"
                  name="change_summary"
                  rows={3}
                  required
                  placeholder="Summarize the modifications made in response to the reviewer feedback (min 5 chars)..."
                  className="w-full rounded-md border border-input bg-background p-2.5 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-ring"
                />
              </div>

              <div className="space-y-2">
                <label className="text-xs font-semibold">Updated Budget Allocation</label>
                <BudgetBuilder initialItems={content?.budget_items || []} />
              </div>
            </CardContent>

            <div className="p-6 pt-0 flex justify-end">
              <Button type="submit" className="bg-emerald-800 hover:bg-emerald-900 text-white">
                <Send className="w-4 h-4 mr-2" />
                Submit Revision as Version {doc.current_version + 1}
              </Button>
            </div>
          </form>
        </Card>
      )}

      {/* Main Document Details */}
      <Card className="shadow-sm">
        <CardHeader>
          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
              <span className="text-xs font-semibold uppercase tracking-wider text-emerald-800 dark:text-emerald-400">
                {doc.type.replace("_", " ")}
              </span>
              <CardTitle className="text-xl font-bold text-foreground mt-1">
                {doc.title}
              </CardTitle>
            </div>
          </div>
          {content?.theme && (
            <p className="text-sm italic text-muted-foreground mt-1">
              &ldquo;{content.theme}&rdquo;
            </p>
          )}
        </CardHeader>

        <CardContent className="space-y-6 divide-y divide-border/60">
          {/* Metadata Grid */}
          <div className="grid gap-4 sm:grid-cols-2 pt-2 text-sm">
            <div className="space-y-1">
              <span className="text-xs text-muted-foreground block">Venue / Location</span>
              <span className="font-semibold text-foreground">{content?.venue || "N/A"}</span>
            </div>

            <div className="space-y-1">
              <span className="text-xs text-muted-foreground block">Implementation Period</span>
              <span className="font-semibold text-foreground">
                {content?.start_date ? new Date(content.start_date).toLocaleString() : "N/A"} —{" "}
                {content?.end_date ? new Date(content.end_date).toLocaleString() : "N/A"}
              </span>
            </div>

            <div className="space-y-1">
              <span className="text-xs text-muted-foreground block">Target Participants</span>
              <span className="font-semibold text-foreground">
                {content?.target_participants || "Students & Faculty"}{" "}
                {content?.expected_count ? `(${content.expected_count} expected)` : ""}
              </span>
            </div>

            <div className="space-y-1">
              <span className="text-xs text-muted-foreground block">Faculty Adviser</span>
              <span className="font-semibold text-foreground">{content?.adviser_name || "N/A"}</span>
            </div>
          </div>

          {/* Objectives List */}
          <div className="pt-4 space-y-2">
            <h4 className="font-semibold text-sm">Specific Objectives</h4>
            {content?.objectives && content.objectives.length > 0 ? (
              <ul className="list-disc list-inside space-y-1 text-sm text-muted-foreground">
                {content.objectives.map((obj, i) => (
                  <li key={i}>{obj}</li>
                ))}
              </ul>
            ) : (
              <p className="text-xs text-muted-foreground">No specific objectives declared.</p>
            )}
          </div>

          {/* Itemized Budget Breakdown */}
          <div className="pt-4 space-y-3">
            <div className="flex items-center justify-between">
              <h4 className="font-semibold text-sm">Itemized Budget Allocation</h4>
              {content?.total_budget !== undefined && (
                <span className="text-sm font-bold text-emerald-800 dark:text-emerald-400">
                  Grand Total: ₱{Number(content.total_budget).toLocaleString("en-PH", { minimumFractionDigits: 2 })}
                </span>
              )}
            </div>
            <BudgetBuilder initialItems={content?.budget_items || []} readOnly={true} />
          </div>
        </CardContent>
      </Card>

      {/* Version History & Audit Trail */}
      <Card className="shadow-sm">
        <CardHeader>
          <CardTitle className="text-base flex items-center gap-2">
            <History className="w-4 h-4 text-emerald-700" />
            Revision History & Visual Snapshots
          </CardTitle>
          <CardDescription>
            Immutable version snapshots and author change summaries across the lifecycle
          </CardDescription>
        </CardHeader>
        <CardContent>
          {versions.length === 0 ? (
            <p className="text-xs text-muted-foreground">No revision snapshots recorded.</p>
          ) : (
            <div className="space-y-4">
              {versions.map((v) => (
                <div key={v.id} className="flex items-start gap-3 p-3 rounded-lg border bg-muted/20">
                  <span className="font-bold text-xs bg-emerald-800 text-white px-2 py-1 rounded">
                    v{v.version_number}
                  </span>
                  <div className="flex-1 min-w-0 space-y-1">
                    <div className="flex items-center justify-between">
                      <h5 className="font-semibold text-xs text-foreground truncate">
                        {v.title}
                      </h5>
                      <span className="text-[11px] text-muted-foreground">
                        {new Date(v.created_at).toLocaleString()}
                      </span>
                    </div>
                    {v.change_summary && (
                      <p className="text-xs text-muted-foreground">
                        Change summary: &ldquo;{v.change_summary}&rdquo;
                      </p>
                    )}
                    {v.profiles?.full_name && (
                      <span className="text-[10px] text-muted-foreground block">
                        Committed by {v.profiles.full_name}
                      </span>
                    )}
                  </div>
                </div>
              ))}
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
}
