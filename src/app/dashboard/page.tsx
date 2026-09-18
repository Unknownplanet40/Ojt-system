import React from "react";
import Link from "next/link";
import { createClient } from "@/lib/supabase/server";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import type { Profile, UserRole } from "@/lib/supabase/types";
import {
  FileText,
  Users,
  Building2,
  FileCheck2,
  AlertCircle,
  Megaphone,
  Calendar,
  ArrowRight,
  Shield,
  Activity,
  PlusCircle,
} from "lucide-react";

export default async function DashboardPage() {
  let role: UserRole = "super_admin";
  let fullName = "Leader";

  try {
    const supabase = await createClient();
    const {
      data: { user },
    } = await supabase.auth.getUser();

    if (user) {
      const { data } = await supabase
        .from("profiles")
        .select("role, full_name")
        .eq("id", user.id)
        .maybeSingle();

      const profileData = data as { role?: UserRole; full_name?: string } | null;
      if (profileData?.role) {
        role = profileData.role;
      }
      if (profileData?.full_name) {
        fullName = profileData.full_name;
      }
    }
  } catch {
    // Fallback for build / unconfigured environment
  }

  return (
    <div className="space-y-8">
      {/* Welcome Banner */}
      <div className="relative overflow-hidden rounded-2xl bg-gradient-to-r from-emerald-900 via-emerald-800 to-emerald-950 p-6 sm:p-8 text-white shadow-xl">
        <div className="relative z-10 max-w-2xl space-y-2">
          <Badge className="bg-amber-500 hover:bg-amber-600 text-emerald-950 font-bold px-3 py-1">
            CvSU Imus Campus
          </Badge>
          <h1 className="text-2xl sm:text-3xl font-extrabold tracking-tight">
            Welcome, {fullName}!
          </h1>
          <p className="text-emerald-100/90 text-sm sm:text-base leading-relaxed">
            Central Student Government Management System. Streamlining organizational workflows, document governance, and leadership accountability.
          </p>
        </div>
        <div className="absolute right-0 top-0 -mt-8 -mr-8 w-64 h-64 bg-emerald-700/20 rounded-full blur-3xl pointer-events-none" />
      </div>

      {/* Role-Specific Metric Cards */}
      {role === "super_admin" && (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <Card className="shadow-sm border-emerald-800/20">
            <CardHeader className="flex flex-row items-center justify-between pb-2">
              <CardTitle className="text-sm font-medium text-muted-foreground">
                Total Users
              </CardTitle>
              <Users className="w-4 h-4 text-emerald-700 dark:text-emerald-400" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">124</div>
              <p className="text-xs text-muted-foreground mt-1">
                Active students & officers
              </p>
            </CardContent>
          </Card>

          <Card className="shadow-sm border-emerald-800/20">
            <CardHeader className="flex flex-row items-center justify-between pb-2">
              <CardTitle className="text-sm font-medium text-muted-foreground">
                Organizations
              </CardTitle>
              <Building2 className="w-4 h-4 text-emerald-700 dark:text-emerald-400" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">18</div>
              <p className="text-xs text-muted-foreground mt-1">
                Recognized student bodies
              </p>
            </CardContent>
          </Card>

          <Card className="shadow-sm border-emerald-800/20">
            <CardHeader className="flex flex-row items-center justify-between pb-2">
              <CardTitle className="text-sm font-medium text-muted-foreground">
                Audit Trail
              </CardTitle>
              <Activity className="w-4 h-4 text-emerald-700 dark:text-emerald-400" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">1,842</div>
              <p className="text-xs text-muted-foreground mt-1">
                Immutable security logs
              </p>
            </CardContent>
          </Card>

          <Card className="shadow-sm border-emerald-800/20">
            <CardHeader className="flex flex-row items-center justify-between pb-2">
              <CardTitle className="text-sm font-medium text-muted-foreground">
                System Health
              </CardTitle>
              <Shield className="w-4 h-4 text-emerald-700 dark:text-emerald-400" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold text-emerald-700 dark:text-emerald-400">
                Healthy
              </div>
              <p className="text-xs text-muted-foreground mt-1">
                All services operational
              </p>
            </CardContent>
          </Card>
        </div>
      )}

      {(role === "csg_officer" || role === "sub_org_officer") && (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <Card className="shadow-sm border-emerald-800/20">
            <CardHeader className="flex flex-row items-center justify-between pb-2">
              <CardTitle className="text-sm font-medium text-muted-foreground">
                Pending Reviews
              </CardTitle>
              <FileCheck2 className="w-4 h-4 text-amber-600" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">7</div>
              <p className="text-xs text-muted-foreground mt-1">
                Proposals awaiting action
              </p>
            </CardContent>
          </Card>

          <Card className="shadow-sm border-emerald-800/20">
            <CardHeader className="flex flex-row items-center justify-between pb-2">
              <CardTitle className="text-sm font-medium text-muted-foreground">
                Approved Documents
              </CardTitle>
              <FileText className="w-4 h-4 text-emerald-700 dark:text-emerald-400" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">42</div>
              <p className="text-xs text-muted-foreground mt-1">
                Current academic year
              </p>
            </CardContent>
          </Card>

          <Card className="shadow-sm border-emerald-800/20">
            <CardHeader className="flex flex-row items-center justify-between pb-2">
              <CardTitle className="text-sm font-medium text-muted-foreground">
                Upcoming Events
              </CardTitle>
              <Calendar className="w-4 h-4 text-emerald-700 dark:text-emerald-400" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">5</div>
              <p className="text-xs text-muted-foreground mt-1">
                Scheduled this month
              </p>
            </CardContent>
          </Card>

          <Card className="shadow-sm border-emerald-800/20">
            <CardHeader className="flex flex-row items-center justify-between pb-2">
              <CardTitle className="text-sm font-medium text-muted-foreground">
                Announcements
              </CardTitle>
              <Megaphone className="w-4 h-4 text-emerald-700 dark:text-emerald-400" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">12</div>
              <p className="text-xs text-muted-foreground mt-1">
                Campus-wide broadcasts
              </p>
            </CardContent>
          </Card>
        </div>
      )}

      {/* Quick Actions & Recent Updates */}
      <div className="grid gap-6 md:grid-cols-2">
        <Card className="shadow-sm">
          <CardHeader>
            <CardTitle className="text-base">Quick Operations</CardTitle>
            <CardDescription>
              Common actions for your current role
            </CardDescription>
          </CardHeader>
          <CardContent className="space-y-3">
            {role === "super_admin" && (
              <>
                <Button asChild variant="outline" className="w-full justify-between">
                  <Link href="/dashboard/users">
                    <span className="flex items-center gap-2">
                      <Users className="w-4 h-4 text-emerald-700" />
                      Provision New User Account
                    </span>
                    <ArrowRight className="w-4 h-4" />
                  </Link>
                </Button>
                <Button asChild variant="outline" className="w-full justify-between">
                  <Link href="/dashboard/audit-logs">
                    <span className="flex items-center gap-2">
                      <Activity className="w-4 h-4 text-emerald-700" />
                      Inspect Security Audit Trail
                    </span>
                    <ArrowRight className="w-4 h-4" />
                  </Link>
                </Button>
                <Button asChild variant="outline" className="w-full justify-between">
                  <Link href="/dashboard/academics">
                    <span className="flex items-center gap-2">
                      <Building2 className="w-4 h-4 text-emerald-700" />
                      Manage Courses & Sections
                    </span>
                    <ArrowRight className="w-4 h-4" />
                  </Link>
                </Button>
                <Button asChild variant="outline" className="w-full justify-between">
                  <Link href="/dashboard/reports">
                    <span className="flex items-center gap-2">
                      <Activity className="w-4 h-4 text-emerald-700" />
                      Institutional Reports & Analytics
                    </span>
                    <ArrowRight className="w-4 h-4" />
                  </Link>
                </Button>
                <Button asChild variant="outline" className="w-full justify-between">
                  <Link href="/dashboard/chat">
                    <span className="flex items-center gap-2">
                      <Shield className="w-4 h-4 text-emerald-700" />
                      CSG Executive Live Chat
                    </span>
                    <ArrowRight className="w-4 h-4" />
                  </Link>
                </Button>
              </>
            )}

            {(role === "csg_officer" || role === "sub_org_officer") && (
              <>
                <Button asChild className="w-full justify-between bg-emerald-800 hover:bg-emerald-900 text-white">
                  <Link href="/dashboard/documents/new">
                    <span className="flex items-center gap-2">
                      <PlusCircle className="w-4 h-4" />
                      Submit Activity Proposal
                    </span>
                    <ArrowRight className="w-4 h-4" />
                  </Link>
                </Button>
                <Button asChild variant="outline" className="w-full justify-between">
                  <Link href="/dashboard/events">
                    <span className="flex items-center gap-2">
                      <Calendar className="w-4 h-4 text-emerald-700" />
                      Schedule Campus Event
                    </span>
                    <ArrowRight className="w-4 h-4" />
                  </Link>
                </Button>
                <Button asChild variant="outline" className="w-full justify-between">
                  <Link href="/dashboard/chat">
                    <span className="flex items-center gap-2">
                      <Shield className="w-4 h-4 text-emerald-700" />
                      Live Chat Communications
                    </span>
                    <ArrowRight className="w-4 h-4" />
                  </Link>
                </Button>
                <Button asChild variant="outline" className="w-full justify-between">
                  <Link href="/dashboard/reports">
                    <span className="flex items-center gap-2">
                      <Activity className="w-4 h-4 text-emerald-700" />
                      Term Reports & Metrics
                    </span>
                    <ArrowRight className="w-4 h-4" />
                  </Link>
                </Button>
              </>
            )}

            {role === "member" && (
              <>
                <Button asChild variant="outline" className="w-full justify-between">
                  <Link href="/dashboard/organizations">
                    <span className="flex items-center gap-2">
                      <Building2 className="w-4 h-4 text-emerald-700" />
                      Browse Recognized Organizations
                    </span>
                    <ArrowRight className="w-4 h-4" />
                  </Link>
                </Button>
                <Button asChild variant="outline" className="w-full justify-between">
                  <Link href="/dashboard/events">
                    <span className="flex items-center gap-2">
                      <Calendar className="w-4 h-4 text-emerald-700" />
                      View Campus Event Calendar
                    </span>
                    <ArrowRight className="w-4 h-4" />
                  </Link>
                </Button>
                <Button asChild variant="outline" className="w-full justify-between">
                  <Link href="/dashboard/announcements">
                    <span className="flex items-center gap-2">
                      <Megaphone className="w-4 h-4 text-emerald-700" />
                      Campus Announcements Feed
                    </span>
                    <ArrowRight className="w-4 h-4" />
                  </Link>
                </Button>
                <Button asChild variant="outline" className="w-full justify-between">
                  <Link href="/dashboard/chat">
                    <span className="flex items-center gap-2">
                      <Shield className="w-4 h-4 text-emerald-700" />
                      Organization Live Chat
                    </span>
                    <ArrowRight className="w-4 h-4" />
                  </Link>
                </Button>
              </>
            )}
          </CardContent>
        </Card>

        {/* Announcements Preview */}
        <Card className="shadow-sm">
          <CardHeader>
            <CardTitle className="text-base">Recent Campus Broadcasts</CardTitle>
            <CardDescription>
              Official notices from the Central Student Government
            </CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="p-3.5 rounded-lg border bg-muted/30 space-y-1.5">
              <div className="flex items-center justify-between">
                <Badge variant="emerald" className="text-[10px]">Announcement</Badge>
                <span className="text-[11px] text-muted-foreground">Today</span>
              </div>
              <h4 className="font-semibold text-sm">
                Academic Year 2026–2027 Student Organization Recognition Open
              </h4>
              <p className="text-xs text-muted-foreground">
                All student organization officers are requested to submit recognition renewal packets via the document management tab.
              </p>
            </div>

            <div className="p-3.5 rounded-lg border bg-muted/30 space-y-1.5">
              <div className="flex items-center justify-between">
                <Badge variant="gold" className="text-[10px]">Advisory</Badge>
                <span className="text-[11px] text-muted-foreground">Yesterday</span>
              </div>
              <h4 className="font-semibold text-sm">
                General Student Assembly & Leadership Summit
              </h4>
              <p className="text-xs text-muted-foreground">
                Scheduled for next Friday at the Campus Gymnasium. Attendance is mandatory for all recognized organization presidents and secretaries.
              </p>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
