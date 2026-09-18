"use client";

import React from "react";
import Link from "next/link";
import { usePathname } from "next/navigation";
import { cn } from "@/lib/utils";
import type { UserRole } from "@/lib/supabase/types";
import {
  LayoutDashboard,
  Users,
  Building2,
  GraduationCap,
  FileText,
  FileCheck2,
  Calendar,
  MessageSquare,
  BarChart3,
  History,
  Settings,
  Megaphone,
  ShieldCheck,
} from "lucide-react";

interface NavItem {
  title: string;
  href: string;
  icon: React.ComponentType<{ className?: string }>;
  roles: UserRole[];
}

const navItems: NavItem[] = [
  {
    title: "Overview",
    href: "/dashboard",
    icon: LayoutDashboard,
    roles: ["super_admin", "csg_officer", "sub_org_officer", "member"],
  },
  {
    title: "User Management",
    href: "/dashboard/users",
    icon: Users,
    roles: ["super_admin"],
  },
  {
    title: "Document Review",
    href: "/dashboard/documents/review",
    icon: FileCheck2,
    roles: ["super_admin", "csg_officer"],
  },
  {
    title: "My Documents",
    href: "/dashboard/documents",
    icon: FileText,
    roles: ["sub_org_officer"],
  },
  {
    title: "Public Documents",
    href: "/dashboard/documents/public",
    icon: FileText,
    roles: ["member"],
  },
  {
    title: "Organizations",
    href: "/dashboard/organizations",
    icon: Building2,
    roles: ["super_admin", "csg_officer", "member"],
  },
  {
    title: "My Organization",
    href: "/dashboard/my-organization",
    icon: Building2,
    roles: ["sub_org_officer"],
  },
  {
    title: "Courses & Sections",
    href: "/dashboard/academics",
    icon: GraduationCap,
    roles: ["super_admin"],
  },
  {
    title: "Announcements",
    href: "/dashboard/announcements",
    icon: Megaphone,
    roles: ["super_admin", "csg_officer", "sub_org_officer", "member"],
  },
  {
    title: "Calendar & Events",
    href: "/dashboard/events",
    icon: Calendar,
    roles: ["super_admin", "csg_officer", "sub_org_officer", "member"],
  },
  {
    title: "Live Chat",
    href: "/dashboard/chat",
    icon: MessageSquare,
    roles: ["super_admin", "csg_officer", "sub_org_officer"],
  },
  {
    title: "Reports & Analytics",
    href: "/dashboard/reports",
    icon: BarChart3,
    roles: ["super_admin", "csg_officer"],
  },
  {
    title: "Audit Logs",
    href: "/dashboard/audit-logs",
    icon: History,
    roles: ["super_admin"],
  },
  {
    title: "System Settings",
    href: "/dashboard/settings",
    icon: Settings,
    roles: ["super_admin"],
  },
];

interface SidebarProps {
  role: UserRole;
  className?: string;
}

export function Sidebar({ role, className }: SidebarProps) {
  const pathname = usePathname();
  const visibleNav = navItems.filter((item) => item.roles.includes(role));

  return (
    <aside
      className={cn(
        "w-64 border-r bg-card flex flex-col justify-between py-6 px-4 h-full",
        className
      )}
    >
      <div className="space-y-6">
        <Link href="/dashboard" className="flex items-center gap-3 px-2">
          <div className="w-9 h-9 rounded-lg bg-emerald-800 text-white flex items-center justify-center font-bold shadow-md">
            <ShieldCheck className="w-5 h-5 text-amber-400" />
          </div>
          <div>
            <span className="font-bold text-base tracking-tight block text-foreground">
              CvSU Imus CSG
            </span>
            <span className="text-[10px] text-muted-foreground block uppercase font-medium">
              Management Portal
            </span>
          </div>
        </Link>

        <nav className="space-y-1">
          <div className="px-3 pb-2 text-[11px] font-semibold text-muted-foreground uppercase tracking-wider">
            Navigation
          </div>
          {visibleNav.map((item) => {
            const Icon = item.icon;
            const isActive =
              pathname === item.href ||
              (item.href !== "/dashboard" && pathname.startsWith(item.href));

            return (
              <Link
                key={item.href}
                href={item.href}
                className={cn(
                  "flex items-center gap-3 px-3 py-2 text-sm font-medium rounded-lg transition-colors",
                  isActive
                    ? "bg-emerald-800 text-white shadow-sm font-semibold"
                    : "text-muted-foreground hover:text-foreground hover:bg-emerald-50 dark:hover:bg-emerald-950/30"
                )}
              >
                <Icon className={cn("w-4 h-4 shrink-0", isActive ? "text-amber-400" : "")} />
                <span>{item.title}</span>
              </Link>
            );
          })}
        </nav>
      </div>

      <div className="p-3 bg-muted/50 rounded-lg border border-border/50 text-xs">
        <span className="text-muted-foreground block">Campus Focus</span>
        <span className="font-semibold text-emerald-800 dark:text-emerald-400 block mt-0.5">
          CvSU – Imus Campus
        </span>
      </div>
    </aside>
  );
}
