import React from "react";
import { createClient } from "@/lib/supabase/server";
import { createAdminClient } from "@/lib/supabase/admin";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { createCourse, toggleCourseStatus, createSection } from "@/actions/admin";
import type { Course, Section } from "@/lib/supabase/types";
import { GraduationCap, Plus, CheckCircle2, XCircle, Shield } from "lucide-react";

export default async function AcademicsManagementPage() {
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
            Only Super Administrators can configure campus academic courses and sections.
          </p>
        </div>
      );
    }
  }

  // Fetch courses and sections
  const { data: rawCourses } = await admin.from("courses").select("*").order("code");
  const { data: rawSections } = await admin
    .from("sections")
    .select(`
      id,
      year_level,
      section_name,
      course_id,
      courses ( code )
    `)
    .order("year_level");

  const courses = (rawCourses || []) as Course[];
  const sections = (rawSections || []) as unknown as Array<{
    id: string;
    year_level: number;
    section_name: string;
    course_id: string;
    courses: { code: string } | null;
  }>;

  return (
    <div className="space-y-8 max-w-6xl mx-auto">
      <div>
        <h1 className="text-2xl font-bold tracking-tight text-foreground">
          Academic Structure Management
        </h1>
        <p className="text-sm text-muted-foreground">
          Maintain CvSU Imus degree programs and student sections populating user registration profiles
        </p>
      </div>

      <div className="grid gap-8 lg:grid-cols-2">
        {/* Degree Programs (Courses) Management */}
        <div className="space-y-6">
          <Card className="shadow-sm border-emerald-800/20">
            <CardHeader>
              <CardTitle className="text-base flex items-center gap-2">
                <GraduationCap className="w-4 h-4 text-emerald-700" />
                Add Degree Program (Course)
              </CardTitle>
            </CardHeader>
            <CardContent>
              <form
                action={async (formData: FormData) => {
                  "use server";
                  await createCourse(formData);
                }}
                className="space-y-3"
              >
                <div className="grid grid-cols-3 gap-3">
                  <div className="col-span-1 space-y-1">
                    <Label htmlFor="code" className="text-xs">Code *</Label>
                    <Input id="code" name="code" placeholder="BSIT" required className="h-8 text-sm uppercase" />
                  </div>
                  <div className="col-span-2 space-y-1">
                    <Label htmlFor="name" className="text-xs">Full Degree Title *</Label>
                    <Input id="name" name="name" placeholder="BS in Information Technology" required className="h-8 text-sm" />
                  </div>
                </div>
                <Button type="submit" size="sm" className="w-full bg-emerald-800 hover:bg-emerald-900 text-white text-xs h-8">
                  <Plus className="w-3.5 h-3.5 mr-1" />
                  Save Degree Program
                </Button>
              </form>
            </CardContent>
          </Card>

          <Card className="shadow-sm">
            <CardHeader className="pb-3">
              <CardTitle className="text-base">Degree Programs ({courses.length})</CardTitle>
            </CardHeader>
            <CardContent className="p-0">
              <div className="overflow-x-auto">
                <table className="w-full text-sm text-left">
                  <thead className="text-xs uppercase bg-muted/50 border-y text-muted-foreground">
                    <tr>
                      <th className="px-4 py-2.5">Code</th>
                      <th className="px-4 py-2.5">Program Name</th>
                      <th className="px-4 py-2.5 text-center">Status</th>
                      <th className="px-4 py-2.5 text-right">Action</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-border/50">
                    {courses.map((c) => (
                      <tr key={c.id} className="hover:bg-muted/20">
                        <td className="px-4 py-2.5 font-bold text-emerald-800 dark:text-emerald-400">
                          {c.code}
                        </td>
                        <td className="px-4 py-2.5 font-medium">{c.name}</td>
                        <td className="px-4 py-2.5 text-center">
                          {c.is_active ? (
                            <Badge variant="emerald" className="text-[10px]">Active</Badge>
                          ) : (
                            <Badge variant="secondary" className="text-[10px]">Inactive</Badge>
                          )}
                        </td>
                        <td className="px-4 py-2.5 text-right">
                          <form
                            action={async () => {
                              "use server";
                              await toggleCourseStatus(c.id, c.is_active);
                            }}
                            className="inline-block"
                          >
                            <Button variant="ghost" size="sm" className="h-7 text-xs">
                              {c.is_active ? "Deactivate" : "Activate"}
                            </Button>
                          </form>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Sections Management */}
        <div className="space-y-6">
          <Card className="shadow-sm border-emerald-800/20">
            <CardHeader>
              <CardTitle className="text-base flex items-center gap-2">
                <Plus className="w-4 h-4 text-emerald-700" />
                Add Academic Section
              </CardTitle>
            </CardHeader>
            <CardContent>
              <form
                action={async (formData: FormData) => {
                  "use server";
                  await createSection(formData);
                }}
                className="space-y-3"
              >
                <div className="space-y-1">
                  <Label htmlFor="courseId" className="text-xs">Program *</Label>
                  <select
                    id="courseId"
                    name="courseId"
                    required
                    className="w-full h-8 rounded-md border border-input bg-background px-2 text-xs focus:outline-none focus:ring-1 focus:ring-ring"
                  >
                    <option value="">Select Degree Program</option>
                    {courses.filter((c) => c.is_active).map((c) => (
                      <option key={c.id} value={c.id}>
                        {c.code} - {c.name}
                      </option>
                    ))}
                  </select>
                </div>

                <div className="grid grid-cols-2 gap-3">
                  <div className="space-y-1">
                    <Label htmlFor="yearLevel" className="text-xs">Year Level (1–5) *</Label>
                    <Input id="yearLevel" name="yearLevel" type="number" min="1" max="5" defaultValue="1" required className="h-8 text-sm" />
                  </div>
                  <div className="space-y-1">
                    <Label htmlFor="sectionName" className="text-xs">Section Name *</Label>
                    <Input id="sectionName" name="sectionName" placeholder="e.g. BSIT 1-1" required className="h-8 text-sm" />
                  </div>
                </div>

                <Button type="submit" size="sm" className="w-full bg-emerald-800 hover:bg-emerald-900 text-white text-xs h-8">
                  <Plus className="w-3.5 h-3.5 mr-1" />
                  Add Section
                </Button>
              </form>
            </CardContent>
          </Card>

          <Card className="shadow-sm">
            <CardHeader className="pb-3">
              <CardTitle className="text-base">Registered Sections ({sections.length})</CardTitle>
            </CardHeader>
            <CardContent className="p-0">
              <div className="overflow-x-auto">
                <table className="w-full text-sm text-left">
                  <thead className="text-xs uppercase bg-muted/50 border-y text-muted-foreground">
                    <tr>
                      <th className="px-4 py-2.5">Program</th>
                      <th className="px-4 py-2.5 text-center">Year</th>
                      <th className="px-4 py-2.5 font-semibold">Section Name</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-border/50">
                    {sections.map((s) => (
                      <tr key={s.id} className="hover:bg-muted/20">
                        <td className="px-4 py-2.5 font-semibold text-emerald-800 dark:text-emerald-400">
                          {s.courses?.code || "N/A"}
                        </td>
                        <td className="px-4 py-2.5 text-center">Year {s.year_level}</td>
                        <td className="px-4 py-2.5 font-medium">{s.section_name}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  );
}
