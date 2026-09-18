"use client";

import React from "react";
import { useTheme } from "next-themes";
import { signOutUser } from "@/actions/auth";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Avatar, AvatarFallback } from "@/components/ui/avatar";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import type { Profile, UserRole } from "@/lib/supabase/types";
import { Moon, Sun, LogOut, User, Shield } from "lucide-react";

interface HeaderProps {
  profile: Profile;
}

const roleLabels: Record<UserRole, { label: string; variant: "default" | "gold" | "emerald" | "secondary" }> = {
  super_admin: { label: "Super Admin", variant: "gold" },
  csg_officer: { label: "CSG Officer", variant: "emerald" },
  sub_org_officer: { label: "Sub-Org Officer", variant: "secondary" },
  member: { label: "Student Member", variant: "default" },
};

export function Header({ profile }: HeaderProps) {
  const { theme, setTheme } = useTheme();
  const roleConfig = roleLabels[profile.role] || { label: profile.role, variant: "default" };

  const initials = profile.full_name
    ? profile.full_name
        .split(" ")
        .map((n) => n[0])
        .slice(0, 2)
        .join("")
        .toUpperCase()
    : "US";

  return (
    <header className="h-16 border-b bg-card/80 backdrop-blur-sm px-6 flex items-center justify-between sticky top-0 z-30">
      <div className="flex items-center gap-3">
        <Badge variant={roleConfig.variant} className="font-semibold px-2.5 py-0.5">
          <Shield className="w-3 h-3 mr-1" />
          {roleConfig.label}
        </Badge>
        {profile.student_number && (
          <span className="text-xs text-muted-foreground hidden sm:inline">
            SN: {profile.student_number}
          </span>
        )}
      </div>

      <div className="flex items-center gap-4">
        {/* Theme Switcher */}
        <Button
          variant="ghost"
          size="icon"
          onClick={() => setTheme(theme === "dark" ? "light" : "dark")}
          className="text-muted-foreground hover:text-foreground"
          title="Toggle theme"
        >
          <Sun className="h-4 w-4 rotate-0 scale-100 transition-all dark:-rotate-90 dark:scale-0" />
          <Moon className="absolute h-4 w-4 rotate-90 scale-0 transition-all dark:rotate-0 dark:scale-100" />
          <span className="sr-only">Toggle theme</span>
        </Button>

        {/* User Dropdown */}
        <DropdownMenu>
          <DropdownMenuTrigger asChild>
            <Button variant="ghost" className="relative h-9 w-9 rounded-full p-0">
              <Avatar className="h-9 w-9 border border-border">
                <AvatarFallback className="bg-emerald-800 text-white font-semibold text-xs">
                  {initials}
                </AvatarFallback>
              </Avatar>
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end" className="w-56">
            <DropdownMenuLabel className="font-normal">
              <div className="flex flex-col space-y-1">
                <p className="text-sm font-semibold leading-none">{profile.full_name}</p>
                <p className="text-xs leading-none text-muted-foreground">{profile.email}</p>
              </div>
            </DropdownMenuLabel>
            <DropdownMenuSeparator />
            <DropdownMenuItem asChild>
              <div className="flex items-center gap-2 cursor-pointer">
                <User className="w-4 h-4 text-muted-foreground" />
                <span>My Profile</span>
              </div>
            </DropdownMenuItem>
            <DropdownMenuSeparator />
            <DropdownMenuItem
              className="text-destructive focus:text-destructive cursor-pointer"
              onClick={() => signOutUser()}
            >
              <LogOut className="w-4 h-4 mr-2" />
              <span>Sign Out</span>
            </DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
      </div>
    </header>
  );
}
