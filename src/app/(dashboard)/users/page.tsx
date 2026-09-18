import React from "react";
import { createClient } from "@/lib/supabase/server";
import { createAdminClient } from "@/lib/supabase/admin";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { unlockUserAccount, provisionUser, updateUserRoleAndOrg } from "@/actions/admin";
import type { Profile, Organization, Course, Section, UserRole } from "@/lib/supabase/types";
import {
  Users,
  UserPlus,
  Shield,
  Building2,
  Unlock,
  Lock,
  CheckCircle2,
  Clock,
  GraduationCap,
} from "lucide-react";

interface UsersPageProps {
  searchParams: Promise<{
    q?: string;
    role?: string;
  }>;
}

export default async function UsersManagementPage({ searchParams }: UsersPageProps) {
  const { q: searchQuery, role: filterRole } = await searchParams;

  const supabase = await createClient();
  const { data: { user } } = await supabase.auth.getUser();

  const admin = createAdminClient();

  // Guard: Verify super_admin or csg_officer
  if (user) {
    const { data: currentProfile } = await admin
      .from("profiles")
      .select("role")
      .eq("id", user.id)
      .maybeSingle();

    if (!currentProfile || (currentProfile as { role: string }).role !== "super_admin") {
      return (
        <div className="p-8 text-center space-y-3">
          <Shield className="w-12 h-12 text-destructive mx-auto" />
          <h2 className="text-xl font-bold">Access Restricted</h2>
          <p className="text-sm text-muted-foreground">
            Only Super Administrators can manage user accounts and assign administrative roles.
          </p>
        </div>
      );
    }
  }

  // Fetch all profiles with organization and course
  let query = admin
    .from("profiles")
    .select(`
      id,
      student_number,
      full_name,
      email,
      role,
      organization_id,
      course_id,
      section_id,
      is_active,
      failed_login_attempts,
      locked_until,
      created_at,
      organizations ( name, acronym ),
      courses ( code, name )
    `)
    .order("created_at", { ascending: false });

  if (filterRole && filterRole !== "all") {
    query = query.eq("role", filterRole);
  }

  const { data: rawProfiles } = await query;
  const profiles = (rawProfiles || []) as unknown as Array<
    Profile & {
      organizations: { name: string; acronym: string } | null;
      courses: { code: string; name: string } | null;
    }
  >;

  // Fetch dropdown data for the provisioning form
  const { data: orgs } = await admin.from("organizations").select("id, name, acronym").eq("is_active", true);
  const { data: courses } = await admin.from("courses").select("id, code, name").eq("is_active", true);
  const { data: sections } = await admin.from("sections").select("id, course_id, section_name, year_level");

  const filteredProfiles = profiles.filter((p) => {
    if (!searchQuery) return true;
    const term = searchQuery.toLowerCase();
    return (
      p.full_name?.toLowerCase().includes(term) ||
      p.email?.toLowerCase().includes(term) ||
      p.student_number?.toLowerCase().includes(term)
    );
  });

  const roleBadgeConfig: Record<UserRole, { label: string; variant: "default" | "gold" | "emerald" | "secondary" }> = {
    super_admin: { label: "Super Admin", variant: "gold" },
    csg_officer: { label: "CSG Officer", variant: "emerald" },
    sub_org_officer: { label: "Sub-Org Officer", variant: "secondary" },
    member: { label: "Member", variant: "default" },
  };

  return (
    <div className="space-y-8">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold tracking-tight text-foreground">
            User Account Management
          </h1>
          <p className="text-sm text-muted-foreground">
            Provision student leader accounts, manage administrative roles, and release account lockouts
          </p>
        </div>
      </div>

      <div className="grid gap-6 lg:grid-cols-3">
        {/* Provisioning Form Card */}
        <Card className="lg:col-span-1 shadow-sm border-emerald-800/20">
          <CardHeader>
            <CardTitle className="text-base flex items-center gap-2">
              <UserPlus className="w-4 h-4 text-emerald-700" />
              Provision New Account
            </CardTitle>
            <CardDescription>
              Create temporary credentials for incoming officers. Account will require first-time activation.
            </CardDescription>
          </CardHeader>
          <CardContent>
            <form
              action={async (formData: FormData) => {
                "use server";
                await provisionUser(formData);
              }}
              className="space-y-4"
            >
              <div className="space-y-1.5">
                <Label htmlFor="fullName" className="text-xs">Full Name *</Label>
                <Input id="fullName" name="fullName" placeholder="e.g. Maria Santos" required className="h-8 text-sm" />
              </div>

              <div className="space-y-1.5">
                <Label htmlFor="email" className="text-xs">Institutional Email *</Label>
                <Input id="email" name="email" type="email" placeholder="student@cvsu.edu.ph" required className="h-8 text-sm" />
              </div>

              <div className="space-y-1.5">
                <Label htmlFor="studentNumber" className="text-xs">Student Number *</Label>
                <Input id="studentNumber" name="studentNumber" placeholder="e.g. 202310123" required className="h-8 text-sm" />
              </div>

              <div className="space-y-1.5">
                <Label htmlFor="role" className="text-xs">System Role *</Label>
                <select
                  id="role"
                  name="role"
                  defaultValue="member"
                  className="w-full h-8 rounded-md border border-input bg-background px-2 text-xs focus:outline-none focus:ring-1 focus:ring-ring"
                >
                  <option value="member">Member</option>
                  <option value="sub_org_officer">Sub-Org Officer</option>
                  <option value="csg_officer">CSG Officer</option>
                  <option value="super_admin">Super Admin</option>
                </select>
              </div>

              <div className="space-y-1.5">
                <Label htmlFor="organizationId" className="text-xs">Assigned Organization</Label>
                <select
                  id="organizationId"
                  name="organizationId"
                  defaultValue=""
                  className="w-full h-8 rounded-md border border-input bg-background px-2 text-xs focus:outline-none focus:ring-1 focus:ring-ring"
                >
                  <option value="">No Organization / General</option>
                  {orgs?.map((o: any) => (
                    <option key={o.id} value={o.id}>
                      {o.acronym} - {o.name}
                    </option>
                  ))}
                </select>
              </div>

              <div className="grid grid-cols-2 gap-2">
                <div className="space-y-1.5">
                  <Label htmlFor="courseId" className="text-xs">Degree Program</Label>
                  <select
                    id="courseId"
                    name="courseId"
                    defaultValue=""
                    className="w-full h-8 rounded-md border border-input bg-background px-2 text-xs focus:outline-none focus:ring-1 focus:ring-ring"
                  >
                    <option value="">Select Course</option>
                    {courses?.map((c: any) => (
                      <option key={c.id} value={c.id}>
                        {c.code}
                      </option>
                    ))}
                  </select>
                </div>

                <div className="space-y-1.5">
                  <Label htmlFor="sectionId" className="text-xs">Section</Label>
                  <select
                    id="sectionId"
                    name="sectionId"
                    defaultValue=""
                    className="w-full h-8 rounded-md border border-input bg-background px-2 text-xs focus:outline-none focus:ring-1 focus:ring-ring"
                  >
                    <option value="">Select Section</option>
                    {sections?.map((s: any) => (
                      <option key={s.id} value={s.id}>
                        {s.section_name}
                      </option>
                    ))}
                  </select>
                </div>
              </div>

              <Button type="submit" className="w-full bg-emerald-800 hover:bg-emerald-900 text-white text-xs h-8 mt-2">
                <UserPlus className="w-3.5 h-3.5 mr-1" />
                Provision User
              </Button>
            </form>
          </CardContent>
        </Card>

        {/* Users Directory Table */}
        <Card className="lg:col-span-2 shadow-sm">
          <CardHeader className="pb-3">
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
              <div>
                <CardTitle className="text-base flex items-center gap-2">
                  <Users className="w-4 h-4 text-emerald-700" />
                  Registered Accounts ({filteredProfiles.length})
                </CardTitle>
                <CardDescription>
                  Filter by student number, institutional email, or role
                </CardDescription>
              </div>
            </div>
          </CardHeader>
          <CardContent className="p-0">
            <div className="overflow-x-auto">
              <table className="w-full text-sm text-left">
                <thead className="text-xs uppercase bg-muted/50 border-y text-muted-foreground">
                  <tr>
                    <th className="px-4 py-3">User / Email</th>
                    <th className="px-4 py-3">Role & Org</th>
                    <th className="px-4 py-3 text-center">Status</th>
                    <th className="px-4 py-3 text-right">Actions</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-border/50">
                  {filteredProfiles.length === 0 ? (
                    <tr>
                      <td colSpan={4} className="px-4 py-8 text-center text-muted-foreground text-sm">
                        No registered users found matching your search.
                      </td>
                    </tr>
                  ) : (
                    filteredProfiles.map((p) => {
                      const isLocked = p.locked_until && new Date(p.locked_until) > new Date();
                      const roleConfig = roleBadgeConfig[p.role] || { label: p.role, variant: "default" };

                      return (
                        <tr key={p.id} className="hover:bg-muted/20 transition-colors">
                          <td className="px-4 py-3">
                            <div className="font-semibold text-foreground">{p.full_name}</div>
                            <div className="text-xs text-muted-foreground">{p.email}</div>
                            {p.student_number && (
                              <span className="text-[10px] text-muted-foreground font-mono">
                                SN: {p.student_number}
                              </span>
                            )}
                          </td>
                          <td className="px-4 py-3">
                            <Badge variant={roleConfig.variant} className="text-[10px] px-2 py-0.5">
                              {roleConfig.label}
                            </Badge>
                            {p.organizations && (
                              <div className="text-xs text-muted-foreground flex items-center gap-1 mt-1 font-medium">
                                <Building2 className="w-3 h-3 text-emerald-700" />
                                {p.organizations.acronym}
                              </div>
                            )}
                          </td>
                          <td className="px-4 py-3 text-center">
                            {isLocked ? (
                              <Badge variant="destructive" className="text-[10px]">
                                <Lock className="w-3 h-3 mr-1" />
                                Locked ({p.failed_login_attempts})
                              </Badge>
                            ) : p.is_active ? (
                              <Badge variant="emerald" className="text-[10px]">
                                <CheckCircle2 className="w-3 h-3 mr-1" />
                                Active
                              </Badge>
                            ) : (
                              <Badge variant="gold" className="text-[10px]">
                                <Clock className="w-3 h-3 mr-1" />
                                Pending Activation
                              </Badge>
                            )}
                          </td>
                          <td className="px-4 py-3 text-right">
                            {isLocked && (
                              <form
                                action={async () => {
                                  "use server";
                                  await unlockUserAccount(p.id);
                                }}
                                className="inline-block"
                              >
                                <Button
                                  type="submit"
                                  size="sm"
                                  variant="outline"
                                  className="text-xs h-7 text-emerald-700 hover:bg-emerald-50 dark:hover:bg-emerald-950/30"
                                >
                                  <Unlock className="w-3.5 h-3.5 mr-1" />
                                  Unlock
                                </Button>
                              </form>
                            )}
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
    </div>
  );
}
