import React from "react";
import { Badge } from "@/components/ui/badge";
import type { DocumentStatus } from "@/lib/supabase/types";
import { FileEdit, Send, Clock, CheckCircle2, RotateCcw } from "lucide-react";

interface StatusBadgeProps {
  status: DocumentStatus;
  className?: string;
}

const statusConfig: Record<
  DocumentStatus,
  { label: string; icon: React.ComponentType<{ className?: string }>; variant: "default" | "secondary" | "gold" | "emerald" | "destructive" | "outline" }
> = {
  draft: {
    label: "Draft",
    icon: FileEdit,
    variant: "secondary",
  },
  submitted: {
    label: "Submitted",
    icon: Send,
    variant: "default",
  },
  under_review: {
    label: "Under Review",
    icon: Clock,
    variant: "gold",
  },
  approved: {
    label: "Approved",
    icon: CheckCircle2,
    variant: "emerald",
  },
  returned: {
    label: "Returned for Revision",
    icon: RotateCcw,
    variant: "destructive",
  },
};

export function DocumentStatusBadge({ status, className }: StatusBadgeProps) {
  const config = statusConfig[status] || {
    label: status,
    icon: FileEdit,
    variant: "outline",
  };
  const Icon = config.icon;

  return (
    <Badge variant={config.variant} className={className}>
      <Icon className="w-3 h-3 mr-1" />
      <span>{config.label}</span>
    </Badge>
  );
}
