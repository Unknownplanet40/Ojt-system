"use client";

import React, { useState } from "react";
import { format, parseISO } from "date-fns";
import type { DocumentRecord, EventRecord, Organization, Profile } from "@/lib/supabase/types";
import {
  BarChart3,
  FileText,
  Calendar,
  Download,
  Printer,
  Building2,
  CheckCircle2,
  Clock,
  RotateCcw,
  FileSpreadsheet,
  Layers,
  Filter,
} from "lucide-react";

interface ReportsViewProps {
  documents: DocumentRecord[];
  events: EventRecord[];
  organizations: Organization[];
  currentProfile: Profile;
}

export function ReportsView({
  documents,
  events,
  organizations,
  currentProfile,
}: ReportsViewProps) {
  const [selectedAY, setSelectedAY] = useState("2026-2027");
  const [selectedSemester, setSelectedSemester] = useState("1st Semester");
  const [selectedOrgId, setSelectedOrgId] = useState<string>(
    currentProfile.role === "sub_org_officer" ? (currentProfile.organization_id || "all") : "all"
  );
  const [activeTab, setActiveTab] = useState<"analytics" | "report">("analytics");

  const canFilterAllOrgs = ["super_admin", "csg_officer"].includes(currentProfile.role);

  // Filter documents & events
  const filteredDocs = documents.filter((doc) => {
    if (selectedOrgId !== "all" && doc.organization_id !== selectedOrgId) return false;
    return true;
  });

  const filteredEvents = events.filter((ev) => {
    if (selectedOrgId !== "all" && ev.organization_id !== selectedOrgId) return false;
    return true;
  });

  // Calculate KPIs
  const totalDocs = filteredDocs.length;
  const approvedDocs = filteredDocs.filter((d) => d.status === "approved").length;
  const pendingDocs = filteredDocs.filter((d) => ["submitted", "under_review"].includes(d.status)).length;
  const returnedDocs = filteredDocs.filter((d) => d.status === "returned").length;
  const totalEvents = filteredEvents.length;

  const selectedOrgName =
    selectedOrgId === "all"
      ? "All Recognized Student Organizations (Campus-Wide)"
      : organizations.find((o) => o.id === selectedOrgId)?.name || "Selected Organization";

  // CSV Export
  const handleExportCSV = () => {
    const headers = [
      "Document ID",
      "Title",
      "Type",
      "Organization",
      "Status",
      "Submitted By",
      "Submission Date",
    ];

    const rows = filteredDocs.map((d) => [
      `"${d.id}"`,
      `"${d.title.replace(/"/g, '""')}"`,
      `"${d.type}"`,
      `"${d.organization?.acronym || ""}"`,
      `"${d.status}"`,
      `"${d.author?.full_name || ""}"`,
      `"${d.created_at}"`,
    ]);

    const csvContent = "data:text/csv;charset=utf-8," + [headers.join(","), ...rows.map((e) => e.join(","))].join("\n");
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", `CvSU_CSG_Report_${selectedAY.replace("-", "_")}_${selectedSemester.replace(" ", "_")}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  };

  const handlePrint = () => {
    window.print();
  };

  return (
    <div className="space-y-6">
      {/* Control / Scope Selection Bar (Hidden in Print) */}
      <div className="print:hidden bg-card p-4 rounded-xl border border-border/70 shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div className="flex flex-wrap items-center gap-3">
          {/* Academic Year */}
          <div>
            <label className="block text-[10px] font-bold uppercase text-muted-foreground mb-1">
              Academic Year
            </label>
            <select
              value={selectedAY}
              onChange={(e) => setSelectedAY(e.target.value)}
              className="text-xs bg-background border border-input rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-emerald-700"
            >
              <option value="2026-2027">AY 2026–2027</option>
              <option value="2025-2026">AY 2025–2026</option>
            </select>
          </div>

          {/* Semester */}
          <div>
            <label className="block text-[10px] font-bold uppercase text-muted-foreground mb-1">
              Semester Term
            </label>
            <select
              value={selectedSemester}
              onChange={(e) => setSelectedSemester(e.target.value)}
              className="text-xs bg-background border border-input rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-emerald-700"
            >
              <option value="1st Semester">1st Semester</option>
              <option value="2nd Semester">2nd Semester</option>
              <option value="Mid-Year">Mid-Year Term</option>
            </select>
          </div>

          {/* Org Filter (if CSG / Super Admin) */}
          {canFilterAllOrgs && (
            <div>
              <label className="block text-[10px] font-bold uppercase text-muted-foreground mb-1">
                Organization Scope
              </label>
              <select
                value={selectedOrgId}
                onChange={(e) => setSelectedOrgId(e.target.value)}
                className="text-xs bg-background border border-input rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-emerald-700"
              >
                <option value="all">Campus-Wide (All Organizations)</option>
                {organizations.map((org) => (
                  <option key={org.id} value={org.id}>
                    {org.acronym} — {org.name}
                  </option>
                ))}
              </select>
            </div>
          )}
        </div>

        {/* View Toggles & Export Buttons */}
        <div className="flex items-center gap-2 self-end md:self-center">
          <div className="inline-flex rounded-lg border border-border p-0.5 bg-muted/40">
            <button
              onClick={() => setActiveTab("analytics")}
              className={`px-3 py-1 text-xs font-semibold rounded-md transition-colors ${
                activeTab === "analytics"
                  ? "bg-card text-foreground shadow-sm"
                  : "text-muted-foreground hover:text-foreground"
              }`}
            >
              Analytics
            </button>
            <button
              onClick={() => setActiveTab("report")}
              className={`px-3 py-1 text-xs font-semibold rounded-md transition-colors ${
                activeTab === "report"
                  ? "bg-card text-foreground shadow-sm"
                  : "text-muted-foreground hover:text-foreground"
              }`}
            >
              Formal Report
            </button>
          </div>

          <button
            onClick={handleExportCSV}
            title="Export CSV"
            className="flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold bg-secondary hover:bg-secondary/80 text-secondary-foreground rounded-lg border border-border transition-colors shadow-sm"
          >
            <Download className="w-3.5 h-3.5" />
            CSV
          </button>

          <button
            onClick={handlePrint}
            title="Print or Save PDF"
            className="flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold bg-emerald-800 hover:bg-emerald-900 text-white rounded-lg transition-colors shadow-sm"
          >
            <Printer className="w-3.5 h-3.5" />
            Print / PDF
          </button>
        </div>
      </div>

      {/* KPI Cards Summary */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div className="bg-card p-4 rounded-xl border border-border/70 shadow-sm">
          <div className="flex items-center justify-between">
            <span className="text-xs text-muted-foreground font-medium">Total Proposals</span>
            <FileText className="w-4 h-4 text-emerald-800 dark:text-emerald-400" />
          </div>
          <div className="text-2xl font-bold text-foreground mt-2">{totalDocs}</div>
          <span className="text-[11px] text-muted-foreground">Document submissions</span>
        </div>

        <div className="bg-card p-4 rounded-xl border border-border/70 shadow-sm">
          <div className="flex items-center justify-between">
            <span className="text-xs text-muted-foreground font-medium">Approved Proposals</span>
            <CheckCircle2 className="w-4 h-4 text-emerald-600" />
          </div>
          <div className="text-2xl font-bold text-emerald-700 dark:text-emerald-400 mt-2">
            {approvedDocs}
          </div>
          <span className="text-[11px] text-muted-foreground">
            {totalDocs > 0 ? Math.round((approvedDocs / totalDocs) * 100) : 0}% clearance rate
          </span>
        </div>

        <div className="bg-card p-4 rounded-xl border border-border/70 shadow-sm">
          <div className="flex items-center justify-between">
            <span className="text-xs text-muted-foreground font-medium">Under Review</span>
            <Clock className="w-4 h-4 text-amber-600" />
          </div>
          <div className="text-2xl font-bold text-amber-700 dark:text-amber-400 mt-2">
            {pendingDocs}
          </div>
          <span className="text-[11px] text-muted-foreground">Awaiting evaluation</span>
        </div>

        <div className="bg-card p-4 rounded-xl border border-border/70 shadow-sm">
          <div className="flex items-center justify-between">
            <span className="text-xs text-muted-foreground font-medium">Scheduled Activities</span>
            <Calendar className="w-4 h-4 text-emerald-700 dark:text-emerald-400" />
          </div>
          <div className="text-2xl font-bold text-foreground mt-2">{totalEvents}</div>
          <span className="text-[11px] text-muted-foreground">Events in term calendar</span>
        </div>
      </div>

      {/* Tab 1: Visual Analytics */}
      {activeTab === "analytics" && (
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6 print:hidden">
          {/* Document Status Breakdown */}
          <div className="bg-card p-5 rounded-2xl border border-border/70 shadow-sm space-y-4">
            <div className="flex items-center justify-between">
              <h3 className="font-bold text-sm text-foreground flex items-center gap-2">
                <BarChart3 className="w-4 h-4 text-emerald-700" />
                Document Approval Status Breakdown
              </h3>
            </div>

            <div className="space-y-3 pt-2">
              <div>
                <div className="flex justify-between text-xs font-semibold mb-1">
                  <span className="text-emerald-800 dark:text-emerald-300">Approved</span>
                  <span>{approvedDocs} ({totalDocs > 0 ? Math.round((approvedDocs / totalDocs) * 100) : 0}%)</span>
                </div>
                <div className="w-full h-2 bg-muted rounded-full overflow-hidden">
                  <div
                    className="h-full bg-emerald-700 rounded-full"
                    style={{ width: `${totalDocs > 0 ? (approvedDocs / totalDocs) * 100 : 0}%` }}
                  />
                </div>
              </div>

              <div>
                <div className="flex justify-between text-xs font-semibold mb-1">
                  <span className="text-amber-800 dark:text-amber-300">Pending Review</span>
                  <span>{pendingDocs} ({totalDocs > 0 ? Math.round((pendingDocs / totalDocs) * 100) : 0}%)</span>
                </div>
                <div className="w-full h-2 bg-muted rounded-full overflow-hidden">
                  <div
                    className="h-full bg-amber-500 rounded-full"
                    style={{ width: `${totalDocs > 0 ? (pendingDocs / totalDocs) * 100 : 0}%` }}
                  />
                </div>
              </div>

              <div>
                <div className="flex justify-between text-xs font-semibold mb-1">
                  <span className="text-rose-800 dark:text-rose-300">Returned for Revision</span>
                  <span>{returnedDocs} ({totalDocs > 0 ? Math.round((returnedDocs / totalDocs) * 100) : 0}%)</span>
                </div>
                <div className="w-full h-2 bg-muted rounded-full overflow-hidden">
                  <div
                    className="h-full bg-rose-600 rounded-full"
                    style={{ width: `${totalDocs > 0 ? (returnedDocs / totalDocs) * 100 : 0}%` }}
                  />
                </div>
              </div>
            </div>
          </div>

          {/* Organization Activity Matrix */}
          <div className="bg-card p-5 rounded-2xl border border-border/70 shadow-sm space-y-4">
            <h3 className="font-bold text-sm text-foreground flex items-center gap-2">
              <Building2 className="w-4 h-4 text-emerald-700" />
              Organization Submission Activity
            </h3>

            <div className="divide-y divide-border/60 text-xs">
              {organizations.slice(0, 5).map((org) => {
                const orgDocs = documents.filter((d) => d.organization_id === org.id);
                const orgEvents = events.filter((e) => e.organization_id === org.id);

                return (
                  <div key={org.id} className="py-2.5 flex items-center justify-between">
                    <div>
                      <span className="font-bold text-foreground block">{org.name}</span>
                      <span className="text-[11px] text-muted-foreground">{org.acronym}</span>
                    </div>
                    <div className="flex items-center gap-3 text-right">
                      <span className="px-2 py-0.5 rounded bg-emerald-50 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-300 font-semibold">
                        {orgDocs.length} docs
                      </span>
                      <span className="px-2 py-0.5 rounded bg-blue-50 dark:bg-blue-950/40 text-blue-800 dark:text-blue-300 font-semibold">
                        {orgEvents.length} events
                      </span>
                    </div>
                  </div>
                );
              })}
            </div>
          </div>
        </div>
      )}

      {/* Official Institutional Report (Printable Document Format) */}
      <div className={`bg-card p-8 rounded-2xl border border-border/70 shadow-sm print:p-0 print:border-none print:shadow-none ${activeTab === "report" ? "block" : "hidden print:block"}`}>
        {/* Official CvSU Letterhead Header */}
        <div className="text-center border-b-2 border-emerald-800 pb-4 mb-6">
          <p className="text-xs uppercase tracking-widest font-semibold text-muted-foreground">
            Republic of the Philippines
          </p>
          <h2 className="text-lg font-serif font-bold text-emerald-950 dark:text-emerald-400">
            CAVITE STATE UNIVERSITY
          </h2>
          <p className="text-xs font-semibold text-emerald-900 dark:text-emerald-300">
            CCAT Campus / Imus Campus • Cavite, Philippines
          </p>
          <div className="mt-2 text-xs font-bold text-foreground tracking-wide uppercase bg-muted/30 py-1 inline-block px-4 rounded">
            Central Student Government • Official Performance & Activity Report
          </div>
        </div>

        {/* Report Metadata */}
        <div className="grid grid-cols-2 text-xs mb-6 gap-2">
          <div>
            <span className="text-muted-foreground block">Reporting Period:</span>
            <strong className="text-foreground">{selectedAY} — {selectedSemester}</strong>
          </div>
          <div className="text-right">
            <span className="text-muted-foreground block">Scope:</span>
            <strong className="text-foreground">{selectedOrgName}</strong>
          </div>
          <div>
            <span className="text-muted-foreground block">Generated Date:</span>
            <strong className="text-foreground">{format(new Date(), "MMMM d, yyyy 'at' h:mm a")}</strong>
          </div>
          <div className="text-right">
            <span className="text-muted-foreground block">Generated By:</span>
            <strong className="text-foreground">{currentProfile.full_name} ({currentProfile.role})</strong>
          </div>
        </div>

        {/* Document Records Table */}
        <div className="overflow-x-auto mb-8">
          <table className="w-full text-left text-xs border border-border">
            <thead className="bg-muted/60 text-muted-foreground uppercase text-[10px] font-bold">
              <tr>
                <th className="p-2.5 border-b border-r border-border">Document Title</th>
                <th className="p-2.5 border-b border-r border-border">Type</th>
                <th className="p-2.5 border-b border-r border-border">Organization</th>
                <th className="p-2.5 border-b border-r border-border">Status</th>
                <th className="p-2.5 border-b border-border">Date Submitted</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-border">
              {filteredDocs.length === 0 ? (
                <tr>
                  <td colSpan={5} className="p-4 text-center text-muted-foreground">
                    No document records found for the selected parameters.
                  </td>
                </tr>
              ) : (
                filteredDocs.map((doc) => (
                  <tr key={doc.id} className="hover:bg-muted/20">
                    <td className="p-2.5 border-r border-border font-semibold text-foreground">
                      {doc.title}
                    </td>
                    <td className="p-2.5 border-r border-border capitalize">
                      {doc.type.replace("_", " ")}
                    </td>
                    <td className="p-2.5 border-r border-border">
                      {doc.organization?.acronym || "CSG"}
                    </td>
                    <td className="p-2.5 border-r border-border font-bold uppercase text-[10px]">
                      {doc.status}
                    </td>
                    <td className="p-2.5">
                      {format(parseISO(doc.created_at), "MMM d, yyyy")}
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>

        {/* Formal Institutional Signatory Block */}
        <div className="pt-8 border-t border-border grid grid-cols-3 gap-6 text-center text-xs mt-12">
          <div>
            <div className="h-10"></div>
            <strong className="block border-t border-foreground pt-1">{currentProfile.full_name}</strong>
            <span className="text-[11px] text-muted-foreground">Prepared By (Reporting Officer)</span>
          </div>

          <div>
            <div className="h-10"></div>
            <strong className="block border-t border-foreground pt-1">CSG President</strong>
            <span className="text-[11px] text-muted-foreground">Attested By</span>
          </div>

          <div>
            <div className="h-10"></div>
            <strong className="block border-t border-foreground pt-1">CSG Adviser</strong>
            <span className="text-[11px] text-muted-foreground">Noted & Endorsed By</span>
          </div>
        </div>
      </div>
    </div>
  );
}
