import { z } from "zod";

export const BudgetItemSchema = z.object({
  id: z.string().default(() => Math.random().toString(36).substring(2, 9)),
  description: z.string().min(1, "Item description is required"),
  quantity: z.number().int().min(1, "Quantity must be at least 1"),
  unit_cost: z.number().min(0, "Unit cost must be positive"),
  total_cost: z.number().min(0),
  category: z.enum(['materials', 'food', 'honorarium', 'transportation', 'equipment', 'other']),
});

export const ActivityProposalSchema = z.object({
  title: z.string().min(5, "Title must be at least 5 characters").max(255),
  type: z.enum([
    'activity_proposal',
    'liquidation',
    'accomplishment_report',
    'resolution',
    'memorandum',
    'other',
  ]).default('activity_proposal'),
  theme: z.string().optional(),
  objectives: z.array(z.string().min(1, "Objective cannot be empty")).min(1, "At least one objective is required"),
  target_participants: z.string().optional(),
  expected_count: z.number().int().min(0).optional(),
  venue: z.string().min(2, "Venue is required"),
  start_date: z.string().min(1, "Start date is required"),
  end_date: z.string().min(1, "End date is required"),
  budget_items: z.array(BudgetItemSchema).default([]),
  funding_source: z.string().optional(),
  adviser_name: z.string().optional(),
  attachment_urls: z.array(z.string().url()).default([]),
  action: z.enum(['draft', 'submit']).default('submit'),
});

export const ReviewActionSchema = z.object({
  document_id: z.string().uuid("Invalid document ID"),
  action: z.enum(['approve', 'return', 'under_review']),
  reviewer_feedback: z.string().optional(),
}).refine((data) => {
  if (data.action === 'return' && (!data.reviewer_feedback || data.reviewer_feedback.trim().length < 5)) {
    return false;
  }
  return true;
}, {
  message: "Feedback is required when returning a document for revision (minimum 5 characters).",
  path: ["reviewer_feedback"],
});

export const ResubmitDocumentSchema = ActivityProposalSchema.extend({
  document_id: z.string().uuid(),
  change_summary: z.string().min(5, "Summary of changes is required for resubmission (minimum 5 characters)"),
});
