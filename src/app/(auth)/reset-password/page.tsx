"use client";

import React, { useActionState } from "react";
import Link from "next/link";
import { requestPasswordReset, type AuthActionResult } from "@/actions/auth";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from "@/components/ui/card";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";
import { AlertCircle, CheckCircle2, Mail, ArrowLeft } from "lucide-react";

const initialState: AuthActionResult = {
  success: false,
};

export default function ResetPasswordPage() {
  const [state, formAction, isPending] = useActionState(requestPasswordReset, initialState);

  return (
    <Card className="shadow-lg border-emerald-900/10 dark:border-emerald-800/30">
      <CardHeader className="space-y-1 text-center">
        <CardTitle className="text-2xl font-bold tracking-tight text-emerald-950 dark:text-emerald-50">
          Reset Password
        </CardTitle>
        <CardDescription>
          Enter your registered institutional email to receive a secure password recovery link
        </CardDescription>
      </CardHeader>

      {state?.success ? (
        <CardContent className="space-y-4 text-center py-6">
          <div className="w-12 h-12 bg-emerald-100 dark:bg-emerald-950/40 text-emerald-600 rounded-full flex items-center justify-center mx-auto">
            <CheckCircle2 className="w-6 h-6" />
          </div>
          <h3 className="font-semibold text-lg text-emerald-950 dark:text-emerald-50">
            Recovery Email Sent
          </h3>
          <p className="text-sm text-muted-foreground">
            If an account matches this email, instructions have been sent. The link expires in 15 minutes.
          </p>
          <div className="pt-4">
            <Button asChild variant="outline" className="w-full">
              <Link href="/login">
                <ArrowLeft className="mr-2 h-4 w-4" />
                Return to Sign In
              </Link>
            </Button>
          </div>
        </CardContent>
      ) : (
        <form action={formAction}>
          <CardContent className="space-y-4">
            {state?.error && (
              <Alert variant="destructive">
                <AlertCircle className="h-4 w-4" />
                <AlertTitle>Error</AlertTitle>
                <AlertDescription>{state.error}</AlertDescription>
              </Alert>
            )}

            <div className="space-y-2">
              <Label htmlFor="email">Institutional Email</Label>
              <div className="relative">
                <Mail className="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
                <Input
                  id="email"
                  name="email"
                  type="email"
                  placeholder="student@cvsu.edu.ph"
                  className="pl-9"
                  required
                />
              </div>
              {state?.fieldErrors?.email && (
                <p className="text-xs text-destructive">{state.fieldErrors.email[0]}</p>
              )}
            </div>
          </CardContent>

          <CardFooter className="flex flex-col space-y-4">
            <Button
              type="submit"
              className="w-full bg-emerald-800 hover:bg-emerald-900 text-white shadow-md transition-all"
              disabled={isPending}
            >
              {isPending ? "Sending link..." : "Send Recovery Link"}
            </Button>

            <div className="text-center text-xs text-muted-foreground">
              Remembered your password?{" "}
              <Link
                href="/login"
                className="font-semibold text-emerald-700 dark:text-emerald-400 hover:underline"
              >
                Sign In
              </Link>
            </div>
          </CardFooter>
        </form>
      )}
    </Card>
  );
}
