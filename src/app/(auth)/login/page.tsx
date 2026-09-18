"use client";

import React, { useActionState } from "react";
import Link from "next/link";
import { loginUser, type AuthActionResult } from "@/actions/auth";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from "@/components/ui/card";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";
import { AlertCircle, Lock, User, ArrowRight } from "lucide-react";

const initialState: AuthActionResult = {
  success: false,
};

export default function LoginPage() {
  const [state, formAction, isPending] = useActionState(loginUser, initialState);

  return (
    <Card className="shadow-lg border-emerald-900/10 dark:border-emerald-800/30">
      <CardHeader className="space-y-1 text-center">
        <CardTitle className="text-2xl font-bold tracking-tight text-emerald-950 dark:text-emerald-50">
          Sign In
        </CardTitle>
        <CardDescription>
          Access your CSG officer, student leader, or member portal
        </CardDescription>
      </CardHeader>

      <form action={formAction}>
        <CardContent className="space-y-4">
          {state?.error && (
            <Alert variant="destructive">
              <AlertCircle className="h-4 w-4" />
              <AlertTitle>Authentication Failed</AlertTitle>
              <AlertDescription>{state.error}</AlertDescription>
            </Alert>
          )}

          <div className="space-y-2">
            <Label htmlFor="identifier">Student Number or Institutional Email</Label>
            <div className="relative">
              <User className="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
              <Input
                id="identifier"
                name="identifier"
                placeholder="202310001 or name@cvsu.edu.ph"
                className="pl-9"
                required
                autoComplete="username"
              />
            </div>
            {state?.fieldErrors?.identifier && (
              <p className="text-xs text-destructive">{state.fieldErrors.identifier[0]}</p>
            )}
          </div>

          <div className="space-y-2">
            <div className="flex items-center justify-between">
              <Label htmlFor="password">Password</Label>
              <Link
                href="/reset-password"
                className="text-xs text-emerald-700 hover:text-emerald-800 dark:text-emerald-400 hover:underline"
              >
                Forgot password?
              </Link>
            </div>
            <div className="relative">
              <Lock className="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
              <Input
                id="password"
                name="password"
                type="password"
                placeholder="••••••••"
                className="pl-9"
                required
                autoComplete="current-password"
              />
            </div>
            {state?.fieldErrors?.password && (
              <p className="text-xs text-destructive">{state.fieldErrors.password[0]}</p>
            )}
          </div>
        </CardContent>

        <CardFooter className="flex flex-col space-y-4">
          <Button
            type="submit"
            className="w-full bg-emerald-800 hover:bg-emerald-900 text-white shadow-md transition-all"
            disabled={isPending}
          >
            {isPending ? "Signing in..." : "Sign In"}
            {!isPending && <ArrowRight className="ml-2 h-4 w-4" />}
          </Button>

          <div className="text-center text-xs text-muted-foreground">
            First time logging in with a temporary account?{" "}
            <Link
              href="/activate"
              className="font-semibold text-emerald-700 dark:text-emerald-400 hover:underline"
            >
              Activate Account
            </Link>
          </div>
        </CardFooter>
      </form>
    </Card>
  );
}
