"use client";

import React, { useState } from "react";
import Link from "next/link";
import { createDocument } from "@/actions/documents";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { BudgetBuilder } from "@/components/documents/budget-builder";
import { ArrowLeft, Plus, Trash2, Send, Save, Building2, ShieldCheck } from "lucide-react";

export default function NewDocumentPage() {
  const [objectives, setObjectives] = useState<string[]>([
    "Provide hands-on academic training for student members",
  ]);

  const addObjective = () => {
    setObjectives([...objectives, ""]);
  };

  const updateObjective = (index: number, val: string) => {
    const updated = [...objectives];
    updated[index] = val;
    setObjectives(updated);
  };

  const removeObjective = (index: number) => {
    if (objectives.length <= 1) return;
    setObjectives(objectives.filter((_, i) => i !== index));
  };

  return (
    <div className="max-w-4xl mx-auto space-y-6">
      <div className="flex items-center gap-3">
        <Button asChild variant="ghost" size="sm">
          <Link href="/dashboard/documents">
            <ArrowLeft className="w-4 h-4 mr-1" />
            Back to Documents
          </Link>
        </Button>
      </div>

      {/* Official Header Template Preview */}
      <div className="border rounded-xl bg-card p-6 shadow-sm border-emerald-800/20 text-center space-y-1">
        <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-100 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-300 text-xs font-semibold">
          <ShieldCheck className="w-4 h-4" />
          Cavite State University – Imus Campus
        </div>
        <h2 className="font-bold text-lg text-emerald-950 dark:text-emerald-100 uppercase tracking-tight">
          Central Student Government & Recognized Student Organizations
        </h2>
        <p className="text-xs text-muted-foreground">
          Official Student Activity Proposal & Budget Requisition Form
        </p>
      </div>

      <form
        action={async (formData: FormData) => {
          await createDocument(formData);
        }}
        className="space-y-8"
      >
        {/* Section 1: General Details */}
        <Card className="shadow-sm">
          <CardHeader>
            <CardTitle className="text-base">1. Activity Overview</CardTitle>
            <CardDescription>
              Basic information regarding the proposed campus project or event
            </CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="title">Activity / Project Title *</Label>
              <Input
                id="title"
                name="title"
                placeholder="e.g. IT Skills Development Workshop & Hackathon 2026"
                required
              />
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
              <div className="space-y-2">
                <Label htmlFor="type">Document Category</Label>
                <select
                  id="type"
                  name="type"
                  defaultValue="activity_proposal"
                  className="w-full h-9 rounded-md border border-input bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-ring"
                >
                  <option value="activity_proposal">Activity Proposal</option>
                  <option value="liquidation">Liquidation Report</option>
                  <option value="accomplishment_report">Accomplishment Report</option>
                  <option value="resolution">Board Resolution</option>
                  <option value="memorandum">Office Memorandum</option>
                  <option value="other">Other Official Document</option>
                </select>
              </div>

              <div className="space-y-2">
                <Label htmlFor="theme">Theme / Slogan</Label>
                <Input
                  id="theme"
                  name="theme"
                  placeholder="e.g. Innovating Beyond Limits"
                />
              </div>
            </div>

            {/* Objectives */}
            <div className="space-y-2 pt-2">
              <div className="flex items-center justify-between">
                <Label>Specific Objectives *</Label>
                <Button
                  type="button"
                  variant="ghost"
                  size="sm"
                  onClick={addObjective}
                  className="text-xs h-7 text-emerald-700 dark:text-emerald-400"
                >
                  <Plus className="w-3.5 h-3.5 mr-1" />
                  Add Objective
                </Button>
              </div>
              <div className="space-y-2">
                {objectives.map((obj, index) => (
                  <div key={index} className="flex items-center gap-2">
                    <Input
                      name="objectives"
                      value={obj}
                      onChange={(e) => updateObjective(index, e.target.value)}
                      placeholder={`Objective #${index + 1}`}
                      required
                      className="text-sm"
                    />
                    {objectives.length > 1 && (
                      <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        onClick={() => removeObjective(index)}
                        className="h-9 w-9 text-muted-foreground hover:text-destructive shrink-0"
                      >
                        <Trash2 className="w-4 h-4" />
                      </Button>
                    )}
                  </div>
                ))}
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Section 2: Venue & Schedule */}
        <Card className="shadow-sm">
          <CardHeader>
            <CardTitle className="text-base">2. Logistics & Schedule</CardTitle>
            <CardDescription>
              Target location, estimated attendance, and implementation timeline
            </CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="grid gap-4 sm:grid-cols-2">
              <div className="space-y-2">
                <Label htmlFor="venue">Proposed Venue *</Label>
                <Input
                  id="venue"
                  name="venue"
                  placeholder="e.g. Campus Gymnasium / IT Laboratory 3"
                  required
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="expected_count">Estimated Participants</Label>
                <Input
                  id="expected_count"
                  name="expected_count"
                  type="number"
                  placeholder="e.g. 150"
                />
              </div>
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
              <div className="space-y-2">
                <Label htmlFor="start_date">Start Date & Time *</Label>
                <Input
                  id="start_date"
                  name="start_date"
                  type="datetime-local"
                  required
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="end_date">End Date & Time *</Label>
                <Input
                  id="end_date"
                  name="end_date"
                  type="datetime-local"
                  required
                />
              </div>
            </div>

            <div className="grid gap-4 sm:grid-cols-2 pt-2">
              <div className="space-y-2">
                <Label htmlFor="funding_source">Funding Source</Label>
                <Input
                  id="funding_source"
                  name="funding_source"
                  placeholder="e.g. Student Organization Fund / Sponsorship"
                />
              </div>

              <div className="space-y-2">
                <Label htmlFor="adviser_name">Organization Faculty Adviser</Label>
                <Input
                  id="adviser_name"
                  name="adviser_name"
                  placeholder="e.g. Prof. Juan Dela Cruz, MIT"
                />
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Section 3: Itemized Budget Table */}
        <Card className="shadow-sm">
          <CardHeader>
            <CardTitle className="text-base">3. Itemized Budget Allocation</CardTitle>
            <CardDescription>
              Specify line items. Quantities and unit costs calculate live subtotals and grand total.
            </CardDescription>
          </CardHeader>
          <CardContent>
            <BudgetBuilder />
          </CardContent>
        </Card>

        {/* Action Controls */}
        <div className="flex items-center justify-end gap-3 pt-4 border-t">
          <Button
            type="submit"
            name="action"
            value="draft"
            variant="outline"
            className="shadow-sm"
          >
            <Save className="w-4 h-4 mr-2" />
            Save as Draft
          </Button>

          <Button
            type="submit"
            name="action"
            value="submit"
            className="bg-emerald-800 hover:bg-emerald-900 text-white shadow-md"
          >
            <Send className="w-4 h-4 mr-2" />
            Submit Proposal for Review
          </Button>
        </div>
      </form>
    </div>
  );
}
