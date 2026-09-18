import React from "react";
import { createClient } from "@/lib/supabase/server";
import { createAdminClient } from "@/lib/supabase/admin";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";
import { createAnnouncement, togglePinAnnouncement, deleteAnnouncement } from "@/actions/announcements";
import type { AnnouncementRecord, Profile, UserRole, Organization } from "@/lib/supabase/types";
import {
  Megaphone,
  Pin,
  AlertTriangle,
  Send,
  Building2,
  Globe,
  Trash2,
  Calendar,
  User,
} from "lucide-react";

export default async function AnnouncementsPage() {
  const supabase = await createClient();
  const { data: { user } } = await supabase.auth.getUser();

  const admin = createAdminClient();

  let userRole: UserRole = "member";
  let userOrgId: string | null = null;
  let currentUserId = user?.id || null;

  if (user) {
    const { data: profile } = await admin
      .from("profiles")
      .select("role, organization_id")
      .eq("id", user.id)
      .maybeSingle();

    if (profile) {
      userRole = (profile as { role: UserRole; organization_id: string | null }).role;
      userOrgId = (profile as { role: UserRole; organization_id: string | null }).organization_id;
    }
  }

  // Fetch announcements per RLS / scope rules
  let query = admin
    .from("announcements")
    .select(`
      id,
      title,
      content,
      priority,
      visibility,
      organization_id,
      author_id,
      is_pinned,
      created_at,
      organizations ( name, acronym ),
      profiles:author_id ( full_name )
    `)
    .order("is_pinned", { ascending: false })
    .order("created_at", { ascending: false });

  // If member or sub_org_officer, filter visibility
  if (userRole === "member" || userRole === "sub_org_officer") {
    if (userOrgId) {
      query = query.or(`visibility.eq.campus_wide,organization_id.eq.${userOrgId}`);
    } else {
      query = query.eq("visibility", "campus_wide");
    }
  }

  const { data: rawAnnouncements } = await query;
  const announcements = (rawAnnouncements || []) as unknown as Array<{
    id: string;
    title: string;
    content: string;
    priority: "high" | "normal" | "low";
    visibility: "campus_wide" | "organization_only";
    organization_id: string | null;
    author_id: string;
    is_pinned: boolean;
    created_at: string;
    organizations: { name: string; acronym: string } | null;
    profiles: { full_name: string } | null;
  }>;

  // Fetch active organizations for CSG broadcast target selector
  const { data: orgs } = await admin.from("organizations").select("id, name, acronym").eq("is_active", true);

  const canPost = userRole === "super_admin" || userRole === "csg_officer" || userRole === "sub_org_officer";

  // Filter out high-priority urgent banners
  const highPriorityAlerts = announcements.filter((a) => a.priority === "high");

  return (
    <div className="max-w-5xl mx-auto space-y-6">
      {/* Header */}
      <div>
        <h1 className="text-2xl font-bold tracking-tight text-foreground flex items-center gap-2">
          <Megaphone className="w-6 h-6 text-emerald-700" />
          Campus Announcements & Official Broadcasts
        </h1>
        <p className="text-sm text-muted-foreground">
          Official bulletins from the Central Student Government and recognized student bodies
        </p>
      </div>

      {/* Urgent High-Priority Alert Banners */}
      {highPriorityAlerts.length > 0 && (
        <div className="space-y-3">
          {highPriorityAlerts.slice(0, 2).map((alert) => (
            <Alert key={alert.id} variant="gold" className="shadow-sm border-amber-500/40">
              <AlertTriangle className="h-4 w-4 text-amber-600" />
              <div className="flex items-center justify-between">
                <AlertTitle className="font-bold text-amber-950 dark:text-amber-200">
                  {alert.title}
                </AlertTitle>
                <Badge variant="gold" className="text-[10px]">
                  Urgent Notice
                </Badge>
              </div>
              <AlertDescription className="text-xs text-amber-900/90 dark:text-amber-300/90 mt-1">
                {alert.content}
              </AlertDescription>
            </Alert>
          ))}
        </div>
      )}

      <div className="grid gap-6 lg:grid-cols-3">
        {/* Broadcast Creator (for authorized officers) */}
        {canPost && (
          <Card className="lg:col-span-1 shadow-sm border-emerald-800/20 h-fit">
            <CardHeader>
              <CardTitle className="text-base flex items-center gap-2">
                <Send className="w-4 h-4 text-emerald-700" />
                Broadcast Announcement
              </CardTitle>
              <CardDescription>
                {userRole === "sub_org_officer"
                  ? "Publish internal notice to your organization members"
                  : "Publish campus-wide or organization-scoped notices"}
              </CardDescription>
            </CardHeader>
            <CardContent>
              <form
                action={async (formData: FormData) => {
                  "use server";
                  await createAnnouncement(formData);
                }}
                className="space-y-4"
              >
                <div className="space-y-1">
                  <Label htmlFor="title" className="text-xs">Subject / Title *</Label>
                  <Input id="title" name="title" placeholder="e.g. General Assembly Notice" required className="h-8 text-sm" />
                </div>

                <div className="space-y-1">
                  <Label htmlFor="content" className="text-xs">Announcement Body *</Label>
                  <textarea
                    id="content"
                    name="content"
                    rows={4}
                    placeholder="Enter official announcement details..."
                    required
                    className="w-full rounded-md border border-input bg-background p-2.5 text-xs shadow-sm focus:outline-none focus:ring-1 focus:ring-ring"
                  />
                </div>

                <div className="grid grid-cols-2 gap-2">
                  <div className="space-y-1">
                    <Label htmlFor="priority" className="text-xs">Priority</Label>
                    <select
                      id="priority"
                      name="priority"
                      defaultValue="normal"
                      className="w-full h-8 rounded-md border border-input bg-background px-2 text-xs focus:outline-none focus:ring-1 focus:ring-ring"
                    >
                      <option value="normal">Normal</option>
                      <option value="high">High (Urgent)</option>
                      <option value="low">Low (FYI)</option>
                    </select>
                  </div>

                  {userRole !== "sub_org_officer" ? (
                    <div className="space-y-1">
                      <Label htmlFor="visibility" className="text-xs">Target Scope</Label>
                      <select
                        id="visibility"
                        name="visibility"
                        defaultValue="campus_wide"
                        className="w-full h-8 rounded-md border border-input bg-background px-2 text-xs focus:outline-none focus:ring-1 focus:ring-ring"
                      >
                        <option value="campus_wide">Campus-Wide</option>
                        <option value="organization_only">Specific Org</option>
                      </select>
                    </div>
                  ) : (
                    <div className="space-y-1">
                      <Label className="text-xs">Scope</Label>
                      <div className="text-xs font-semibold text-emerald-800 dark:text-emerald-400 py-1.5">
                        Internal Org
                      </div>
                    </div>
                  )}
                </div>

                {userRole !== "sub_org_officer" && (
                  <div className="space-y-1">
                    <Label htmlFor="organizationId" className="text-xs">Target Organization (if specific)</Label>
                    <select
                      id="organizationId"
                      name="organizationId"
                      defaultValue=""
                      className="w-full h-8 rounded-md border border-input bg-background px-2 text-xs focus:outline-none focus:ring-1 focus:ring-ring"
                    >
                      <option value="">All Organizations (Global)</option>
                      {orgs?.map((o: any) => (
                        <option key={o.id} value={o.id}>
                          {o.acronym} - {o.name}
                        </option>
                      ))}
                    </select>
                  </div>
                )}

                <div className="flex items-center gap-2 pt-1">
                  <input
                    type="checkbox"
                    id="isPinned"
                    name="isPinned"
                    value="true"
                    className="rounded border-input text-emerald-800 focus:ring-emerald-800"
                  />
                  <Label htmlFor="isPinned" className="text-xs font-normal cursor-pointer">
                    Pin this announcement to top of feed
                  </Label>
                </div>

                <Button type="submit" size="sm" className="w-full bg-emerald-800 hover:bg-emerald-900 text-white text-xs h-8">
                  <Send className="w-3.5 h-3.5 mr-1" />
                  Publish Broadcast
                </Button>
              </form>
            </CardContent>
          </Card>
        )}

        {/* Announcements Feed */}
        <div className={`${canPost ? "lg:col-span-2" : "lg:col-span-3"} space-y-4`}>
          {announcements.length === 0 ? (
            <Card className="border-dashed p-12 text-center space-y-2">
              <Megaphone className="w-12 h-12 text-muted-foreground mx-auto" />
              <h3 className="font-semibold text-base">No announcements available</h3>
              <p className="text-xs text-muted-foreground">
                There are currently no active announcements or broadcasts matching your account scope.
              </p>
            </Card>
          ) : (
            announcements.map((item) => {
              const isAuthor = currentUserId === item.author_id;
              const isSuperAdminOrCSG = userRole === "super_admin" || userRole === "csg_officer";

              return (
                <Card
                  key={item.id}
                  className={`border shadow-xs transition-all ${
                    item.is_pinned ? "border-emerald-800/40 bg-emerald-50/10 dark:bg-emerald-950/5" : ""
                  }`}
                >
                  <CardHeader className="p-5 pb-3">
                    <div className="flex items-start justify-between gap-3">
                      <div className="flex flex-wrap items-center gap-2">
                        {item.is_pinned && (
                          <Badge variant="emerald" className="text-[10px]">
                            <Pin className="w-3 h-3 mr-1" />
                            Pinned
                          </Badge>
                        )}
                        {item.priority === "high" && (
                          <Badge variant="destructive" className="text-[10px]">
                            Urgent
                          </Badge>
                        )}
                        {item.visibility === "campus_wide" ? (
                          <span className="inline-flex items-center gap-1 text-[11px] text-muted-foreground font-semibold">
                            <Globe className="w-3 h-3" />
                            Campus-Wide
                          </span>
                        ) : (
                          <span className="inline-flex items-center gap-1 text-[11px] text-muted-foreground font-semibold">
                            <Building2 className="w-3 h-3 text-emerald-700" />
                            {item.organizations?.acronym || "Internal Org"}
                          </span>
                        )}
                      </div>

                      {(isAuthor || isSuperAdminOrCSG) && (
                        <div className="flex items-center gap-1">
                          <form
                            action={async () => {
                              "use server";
                              await togglePinAnnouncement(item.id, item.is_pinned);
                            }}
                          >
                            <Button
                              type="submit"
                              variant="ghost"
                              size="icon"
                              className="h-7 w-7 text-muted-foreground"
                              title={item.is_pinned ? "Unpin" : "Pin to top"}
                            >
                              <Pin className="w-3.5 h-3.5" />
                            </Button>
                          </form>

                          <form
                            action={async () => {
                              "use server";
                              await deleteAnnouncement(item.id);
                            }}
                          >
                            <Button
                              type="submit"
                              variant="ghost"
                              size="icon"
                              className="h-7 w-7 text-muted-foreground hover:text-destructive"
                              title="Delete announcement"
                            >
                              <Trash2 className="w-3.5 h-3.5" />
                            </Button>
                          </form>
                        </div>
                      )}
                    </div>

                    <CardTitle className="text-base font-bold text-foreground mt-2">
                      {item.title}
                    </CardTitle>
                    <CardDescription className="flex items-center gap-2 text-xs">
                      <User className="w-3 h-3" />
                      <span>{item.profiles?.full_name || "Official Sender"}</span>
                      <span>•</span>
                      <Calendar className="w-3 h-3" />
                      <span>{new Date(item.created_at).toLocaleString()}</span>
                    </CardDescription>
                  </CardHeader>

                  <CardContent className="p-5 pt-0">
                    <p className="text-sm text-foreground/90 whitespace-pre-line leading-relaxed">
                      {item.content}
                    </p>
                  </CardContent>
                </Card>
              );
            })
          )}
        </div>
      </div>
    </div>
  );
}
