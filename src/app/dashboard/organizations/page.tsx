import React from "react";
import Link from "next/link";
import { createClient } from "@/lib/supabase/server";
import { createAdminClient } from "@/lib/supabase/admin";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import type { Organization, Profile, UserRole } from "@/lib/supabase/types";
import { Building2, Users, ArrowRight, ShieldCheck, CheckCircle2, Clock } from "lucide-react";

interface OrgPageProps {
  searchParams: Promise<{
    type?: string;
  }>;
}

export default async function OrganizationsDirectoryPage({ searchParams }: OrgPageProps) {
  const { type: filterType } = await searchParams;

  const supabase = await createClient();
  const { data: { user } } = await supabase.auth.getUser();

  const admin = createAdminClient();

  let query = admin
    .from("organizations")
    .select(`
      id,
      name,
      acronym,
      type,
      description,
      is_active,
      created_at
    `)
    .order("acronym");

  if (filterType && filterType !== "all") {
    query = query.eq("type", filterType);
  }

  const { data: rawOrgs } = await query;
  const orgs = (rawOrgs || []) as Organization[];

  const types = [
    { label: "All Organizations", value: "all" },
    { label: "Academic", value: "academic" },
    { label: "Governing", value: "governing" },
    { label: "Special Interest", value: "special_interest" },
  ];

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold tracking-tight text-foreground flex items-center gap-2">
            <Building2 className="w-6 h-6 text-emerald-700" />
            Recognized Student Organizations Directory
          </h1>
          <p className="text-sm text-muted-foreground">
            Official directory of CSG and recognized student bodies at CvSU – Imus Campus
          </p>
        </div>
      </div>

      {/* Filter Tabs */}
      <div className="flex items-center gap-2 overflow-x-auto pb-2 border-b">
        {types.map((t) => {
          const isActive = (!filterType && t.value === "all") || filterType === t.value;
          return (
            <Link
              key={t.value}
              href={`/dashboard/organizations?type=${t.value}`}
              className={`px-3 py-1.5 text-xs font-semibold rounded-lg transition-colors whitespace-nowrap ${
                isActive
                  ? "bg-emerald-800 text-white shadow-xs"
                  : "text-muted-foreground hover:text-foreground hover:bg-muted"
              }`}
            >
              {t.label}
            </Link>
          );
        })}
      </div>

      {/* Organizations Grid */}
      <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        {orgs.map((org) => (
          <Link key={org.id} href={`/dashboard/organizations/${org.id}`} className="group block">
            <Card className="h-full border hover:border-emerald-800/40 hover:shadow-md transition-all flex flex-col justify-between">
              <CardHeader className="p-5 pb-3">
                <div className="flex items-start justify-between gap-2 mb-2">
                  <span className="text-lg font-extrabold tracking-tight text-emerald-800 dark:text-emerald-400 group-hover:underline">
                    {org.acronym}
                  </span>
                  {org.is_active ? (
                    <Badge variant="emerald" className="text-[10px]">
                      <CheckCircle2 className="w-3 h-3 mr-1" />
                      Active
                    </Badge>
                  ) : (
                    <Badge variant="secondary" className="text-[10px]">
                      <Clock className="w-3 h-3 mr-1" />
                      Pending / Inactive
                    </Badge>
                  )}
                </div>
                <CardTitle className="text-base font-semibold line-clamp-1">
                  {org.name}
                </CardTitle>
                <CardDescription className="capitalize text-xs font-medium text-muted-foreground">
                  {org.type} Organization
                </CardDescription>
              </CardHeader>

              <CardContent className="p-5 pt-0 space-y-4">
                <p className="text-xs text-muted-foreground line-clamp-3 leading-relaxed">
                  {org.description || "Official recognized student organization of Cavite State University – Imus Campus."}
                </p>

                <div className="flex items-center justify-between pt-3 border-t text-xs font-medium text-emerald-800 dark:text-emerald-400">
                  <span>View Organization Profile</span>
                  <ArrowRight className="w-4 h-4 group-hover:translate-x-1 transition-transform" />
                </div>
              </CardContent>
            </Card>
          </Link>
        ))}
      </div>
    </div>
  );
}
