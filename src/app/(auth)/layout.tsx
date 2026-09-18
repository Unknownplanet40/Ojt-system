import React from "react";
import Link from "next/link";
import { ShieldCheck } from "lucide-react";

export default function AuthLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <div className="min-h-screen flex flex-col justify-between bg-gradient-to-b from-emerald-950/20 via-background to-background">
      <header className="p-6 flex items-center justify-between border-b bg-background/80 backdrop-blur-sm">
        <Link href="/login" className="flex items-center gap-3">
          <div className="w-10 h-10 rounded-lg bg-emerald-800 text-white flex items-center justify-center font-bold shadow-md">
            <ShieldCheck className="w-6 h-6 text-amber-400" />
          </div>
          <div>
            <span className="font-bold text-lg tracking-tight block text-foreground">
              CvSU Imus CSG
            </span>
            <span className="text-xs text-muted-foreground block">
              Central Student Government Management System
            </span>
          </div>
        </Link>
        <span className="text-xs font-semibold px-2.5 py-1 rounded-full bg-emerald-100 dark:bg-emerald-950/50 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
          Cavite State University – Imus Campus
        </span>
      </header>

      <main className="flex-1 flex items-center justify-center p-4 sm:p-6 lg:p-8">
        <div className="w-full max-w-md">{children}</div>
      </main>

      <footer className="p-6 text-center text-xs text-muted-foreground border-t">
        <p>© 2026 Central Student Government - Cavite State University Imus Campus. All rights reserved.</p>
        <p className="mt-1">For student leader onboarding & technical inquiries, contact the CSG IT Committee.</p>
      </footer>
    </div>
  );
}
