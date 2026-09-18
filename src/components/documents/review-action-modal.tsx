"use client";

import React, { useState, useTransition } from "react";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { approveDocument, returnDocument, setUnderReview } from "@/actions/documents";
import { CheckCircle2, RotateCcw, Clock, AlertCircle } from "lucide-react";
import type { DocumentStatus } from "@/lib/supabase/types";

interface ReviewControlsProps {
  documentId: string;
  currentStatus: DocumentStatus;
}

export function ReviewControls({ documentId, currentStatus }: ReviewControlsProps) {
  const [isPending, startTransition] = useTransition();
  const [returnMode, setReturnMode] = useState(false);
  const [feedback, setFeedback] = useState("");
  const [errorMsg, setErrorMsg] = useState<string | null>(null);

  const handleUnderReview = () => {
    setErrorMsg(null);
    startTransition(async () => {
      const res = await setUnderReview(documentId);
      if (!res.success) {
        setErrorMsg(res.error || "Failed to place under review");
      }
    });
  };

  const handleApprove = () => {
    setErrorMsg(null);
    startTransition(async () => {
      const res = await approveDocument(documentId);
      if (!res.success) {
        setErrorMsg(res.error || "Failed to approve document");
      }
    });
  };

  const handleReturn = (e: React.FormEvent) => {
    e.preventDefault();
    setErrorMsg(null);
    if (feedback.trim().length < 5) {
      setErrorMsg("Feedback must be at least 5 characters explaining required revisions.");
      return;
    }

    const formData = new FormData();
    formData.append("document_id", documentId);
    formData.append("reviewer_feedback", feedback);

    startTransition(async () => {
      const res = await returnDocument(formData);
      if (!res.success) {
        setErrorMsg(res.error || "Failed to return document");
      } else {
        setReturnMode(false);
      }
    });
  };

  if (currentStatus === "approved") {
    return (
      <div className="flex items-center gap-2 text-xs text-emerald-700 dark:text-emerald-400 font-medium">
        <CheckCircle2 className="w-4 h-4" />
        <span>Document is approved and official.</span>
      </div>
    );
  }

  return (
    <div className="space-y-4">
      {errorMsg && (
        <Alert variant="destructive">
          <AlertCircle className="w-4 h-4" />
          <AlertDescription>{errorMsg}</AlertDescription>
        </Alert>
      )}

      {!returnMode ? (
        <div className="flex flex-wrap items-center gap-3">
          {currentStatus === "submitted" && (
            <Button
              type="button"
              variant="outline"
              size="sm"
              disabled={isPending}
              onClick={handleUnderReview}
              className="text-amber-800 border-amber-300 hover:bg-amber-50 dark:text-amber-300 dark:border-amber-700"
            >
              <Clock className="w-4 h-4 mr-1.5" />
              Place Under Review
            </Button>
          )}

          <Button
            type="button"
            size="sm"
            disabled={isPending}
            onClick={handleApprove}
            className="bg-emerald-800 hover:bg-emerald-900 text-white shadow-sm"
          >
            <CheckCircle2 className="w-4 h-4 mr-1.5" />
            Approve Document
          </Button>

          <Button
            type="button"
            variant="outline"
            size="sm"
            disabled={isPending}
            onClick={() => setReturnMode(true)}
            className="text-destructive border-destructive/30 hover:bg-destructive/10"
          >
            <RotateCcw className="w-4 h-4 mr-1.5" />
            Return for Revision
          </Button>
        </div>
      ) : (
        <form onSubmit={handleReturn} className="space-y-3 p-4 border rounded-xl bg-destructive/5 border-destructive/20">
          <div className="space-y-1">
            <Label htmlFor="reviewer_feedback" className="text-destructive font-semibold text-xs">
              Mandatory Revision Notes for Organization Author
            </Label>
            <p className="text-xs text-muted-foreground">
              Provide clear feedback detailing what needs to be changed (e.g., budget adjustments, venue conflicts, schedule revisions).
            </p>
          </div>

          <textarea
            id="reviewer_feedback"
            rows={3}
            value={feedback}
            onChange={(e) => setFeedback(e.target.value)}
            placeholder="Specify reason for return and requested corrections..."
            className="w-full rounded-md border border-input bg-background p-2.5 text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-ring"
            required
          />

          <div className="flex items-center gap-2 justify-end">
            <Button
              type="button"
              variant="ghost"
              size="sm"
              disabled={isPending}
              onClick={() => setReturnMode(false)}
            >
              Cancel
            </Button>
            <Button
              type="submit"
              variant="destructive"
              size="sm"
              disabled={isPending}
            >
              {isPending ? "Returning..." : "Confirm Return"}
            </Button>
          </div>
        </form>
      )}
    </div>
  );
}
