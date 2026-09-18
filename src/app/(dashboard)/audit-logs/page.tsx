import React from "react";
import Link from "next/link";
import { createClient } from "@/lib/supabase/server";
import { createAdminClient } from "@/lib/supabase/admin";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import type { AuditLog, AuditAction } from "@/lib/supabase/types";
import {
  Shield,
  Activity,
  History,
  CheckCircle2,
  AlertTriangle,
  Lock,
  Unlock,
  FileCheck2,
  RotateCcw,
  UserPlus,
  KeyRound,
} from "lucide-react";

interface AuditLogsPageProps {
  searchParams: Promise<{
    action?: string;
  }>;
}

const actionConfig: Record<
  AuditAction,
  { label: string; variant: "default" | "secondary" | "emerald" | "gold" | "destructive" | "outline"; icon: React.ComponentType<{ className?: string }> }
> = {
  login_success: { label: "Login Success", variant: "emerald", icon: CheckCircle2 },
  login_failed: { label: "Login Failed", variant: "destructive", icon: AlertTriangle },
  account_locked: { label: "Account Locked", variant: "destructive", icon: Lock },
  account_unlocked: { label: "Account Unlocked", variant: "gold", icon: Unlock },
  password_reset_request: { label: "Reset Requested", variant: "outline", icon: KeyRound },
  password_reset_success: { label: "Password Reset", variant: "emerald", icon: CheckCircle2 },
  account_activated: { label: "Account Activated", variant: "emerald", icon: CheckCircle2 },
  document_submitted: { label: "Proposal Submitted", variant: "default", icon: FileCheck2 },
  document_approved: { label: "Document Approved", variant: "emerald", icon: FileCheck2 },
  document_returned: { label: "Document Returned", variant: "destructive", icon: RotateCcw },
  role_changed: { label: "Role Modified", variant: "gold", icon: Shield },
  user_created: { label: "Account Provisioned", variant: "gold", icon: UserPlus },
  organization_created: { label: "Organization Created", variant: "emerald", icon: CheckCircle2 },
};

export default async function AuditLogsPage({ searchParams }: AuditLogsPageProps) {
  const { action: filterAction } = await searchParams;

  const supabase = await createClient();
  const { data: { user } } = await supabase.auth.getUser();

  const admin = createAdminClient();

  // Verify super_admin
  if (user) {
    const { data: profile } = await admin
      .from("profiles")
      .select("role")
      .eq("id", user.id)
      .maybeSingle();

    if (!profile || (profile as { role: string }).role !== "super_admin") {
      return (
        <div className="p-8 text-center space-y-3">
          <Shield className="w-12 h-12 text-destructive mx-auto" />
          <h2 className="text-xl font-bold">Access Restricted</h2>
          <p className="text-sm text-muted-foreground">
            The security audit trail is restricted to Super Administrators and CSG Advisers.
          </p>
        </div>
      );
    }
  }

  // Fetch audit logs with actor profile
  let query = admin
    .from("audit_logs")
    .select(`
      id,
      actor_id,
      action,
      target_entity,
      target_id,
      details,
      ip_address,
      created_at,
      profiles:actor_id ( full_name, email, role )
    `)
    .order("created_at", { ascending: false })
    .limit(100);

  if (filterAction && filterAction !== "all") {
    query = query.eq("action", filterAction);
  }

  const { data: rawLogs } = await query;
  const logs = (rawLogs || []) as unknown as Array<{
    id: string;
    actor_id: string | null;
    action: AuditAction;
    target_entity: string;
    target_id: string | null;
    details: Record<string, any>;
    ip_address: string | null;
    created_at: string;
    profiles: { full_name: string; email: string; role: string } | null;
  }>;

  const actionFilters = [
    { label: "All Audit Actions", value: "all" },
    { label: "Logins & Security", value: "login_success" },
    { label: "Failed Logins", value: "login_failed" },
    { label: "Lockouts", value: "account_locked" },
    { label: "Document Approvals", value: "document_approved" },
    { label: "Document Returns", value: "document_returned" },
    { label: "Role Changes", value: "role_changed" },
    { label: "User Provisioning", value: "user_created" },
  ];

  return (
    <div className="space-y-6 max-w-6xl mx-auto">
      <div>
        <h1 className="text-2xl font-bold tracking-tight text-foreground flex items-center gap-2">
          <History className="w-6 h-6 text-emerald-700" />
          Security Audit Trail
        </h1>
        <p className="text-sm text-muted-foreground">
          Immutable, append-only record of system authentication, lockouts, document state transitions, and administrative operations
        </p>
      </div>

      {/* Filter Tabs */}
      <div className="flex items-center gap-2 overflow-x-auto pb-2 border-b">
        {actionFilters.map((af) => {
          const isActive = (!filterAction && af.value === "all") || filterAction === af.value;
          return (
            <Link
              key={af.value}
              href={`/dashboard/audit-logs?action=${af.value}`}
              className={`px-3 py-1.5 text-xs font-semibold rounded-lg transition-colors whitespace-nowrap ${
                isActive
                  ? "bg-emerald-800 text-white shadow-xs"
                  : "text-muted-foreground hover:text-foreground hover:bg-muted"
              }`}
            >
              {af.label}
            </Link>
          );
        })}
      </div>

      <Card className="shadow-sm">
        <CardHeader className="pb-3">
          <CardTitle className="text-base flex items-center gap-2">
            <Activity className="w-4 h-4 text-emerald-700" />
            Audit Records ({logs.length})
          </CardTitle>
          <CardDescription>
            Showing recent operations recorded by the system
          </CardDescription>
        </CardHeader>
        <CardContent className="p-0">
          <div className="overflow-x-auto">
            <table className="w-full text-sm text-left">
              <thead className="text-xs uppercase bg-muted/50 border-y text-muted-foreground">
                <tr>
                  <th className="px-4 py-3">Timestamp</th>
                  <th className="px-4 py-3">Action</th>
                  <th className="px-4 py-3">Actor</th>
                  <th className="px-4 py-3">Target Entity</th>
                  <th className="px-4 py-3">Event Details</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-border/50">
                {logs.length === 0 ? (
                  <tr>
                    <td colSpan={5} className="px-4 py-8 text-center text-muted-foreground text-sm">
                      No audit log entries found.
                    </td>
                  </tr>
                ) : (
                  logs.map((log) => {
                    const config = actionConfig[log.action] || {
                      label: log.action,
                      variant: "outline",
                      icon: Activity,
                    };
                    const Icon = config.icon;

                    return (
                      <tr key={log.id} className="hover:bg-muted/20 transition-colors">
                        <td className="px-4 py-3 text-xs text-muted-foreground whitespace-nowrap">
                          {new Date(log.created_at).toLocaleString()}
                        </td>
                        <td className="px-4 py-3 whitespace-nowrap">
                          <Badge variant={config.variant} className="text-[10px] px-2 py-0.5">
                            <Icon className="w-3 h-3 mr-1" />
                            {config.label}
                          </Badge>
                        </td>
                        <td className="px-4 py-3">
                          <div className="font-medium text-xs text-foreground">
                            {log.profiles?.full_name || "System"}
                          </div>
                          {log.profiles?.email && (
                            <div className="text-[10px] text-muted-foreground">{log.profiles.email}</div>
                          )}
                        </td>
                        <td className="px-4 py-3 text-xs">
                          <span className="font-semibold text-foreground capitalize">{log.target_entity}</span>
                          {log.target_id && (
                            <span className="text-[10px] text-muted-foreground block font-mono">
                              ID: {log.target_id.substring(0, 8)}...
                            </span>
                          )}
                        </td>
                        <td className="px-4 py-3 text-xs">
                          <pre className="text-[10px] bg-muted/50 p-1.5 rounded max-w-xs overflow-x-auto font-mono">
                            {JSON.stringify(log.details, null, 2)}
                          </pre>
                        </td>
                      </tr>
                    );
                  })
                )}
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
