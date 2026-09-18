"use client";

import React, { useState, useTransition } from "react";
import { format, isPast, parseISO } from "date-fns";
import type { EventRecord, Organization, Profile } from "@/lib/supabase/types";
import { createEvent, updateEventStatus, deleteEvent, checkVenueConflicts, type ConflictCheckResult } from "@/actions/events";
import {
  Calendar as CalendarIcon,
  MapPin,
  Clock,
  Plus,
  AlertTriangle,
  Building2,
  CheckCircle2,
  XCircle,
  Trash2,
  Search,
  Filter,
} from "lucide-react";

interface EventsViewProps {
  initialEvents: EventRecord[];
  organizations: Organization[];
  currentProfile: Profile;
}

export function EventsView({ initialEvents, organizations, currentProfile }: EventsViewProps) {
  const [events, setEvents] = useState<EventRecord[]>(initialEvents);
  const [searchQuery, setSearchQuery] = useState("");
  const [selectedOrgId, setSelectedOrgId] = useState<string>("all");
  const [filterView, setFilterView] = useState<"upcoming" | "all">("upcoming");
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [isPending, startTransition] = useTransition();

  // Form state
  const [title, setTitle] = useState("");
  const [description, setDescription] = useState("");
  const [location, setLocation] = useState("");
  const [startAt, setStartAt] = useState("");
  const [endAt, setEndAt] = useState("");
  const [targetOrgId, setTargetOrgId] = useState(
    currentProfile.role === "sub_org_officer" ? (currentProfile.organization_id || "") : ""
  );
  const [conflictResult, setConflictResult] = useState<ConflictCheckResult | null>(null);
  const [acknowledgeConflict, setAcknowledgeConflict] = useState(false);
  const [formError, setFormError] = useState("");

  const canCreate = ["super_admin", "csg_officer", "sub_org_officer"].includes(currentProfile.role);

  // Filter events
  const filteredEvents = events.filter((ev) => {
    const matchesSearch =
      ev.title.toLowerCase().includes(searchQuery.toLowerCase()) ||
      ev.location.toLowerCase().includes(searchQuery.toLowerCase()) ||
      (ev.organization?.name || "").toLowerCase().includes(searchQuery.toLowerCase()) ||
      (ev.organization?.acronym || "").toLowerCase().includes(searchQuery.toLowerCase());

    const matchesOrg = selectedOrgId === "all" || ev.organization_id === selectedOrgId;

    const startDate = new Date(ev.start_at);
    const matchesTiming = filterView === "all" || startDate >= new Date(Date.now() - 24 * 60 * 60 * 1000);

    return matchesSearch && matchesOrg && matchesTiming;
  });

  const handleLocationOrTimeBlur = async () => {
    if (location.trim() && startAt && endAt) {
      if (new Date(endAt) > new Date(startAt)) {
        const result = await checkVenueConflicts(location, startAt, endAt);
        setConflictResult(result);
        if (!result.hasConflict) {
          setAcknowledgeConflict(false);
        }
      }
    }
  };

  const handleCreateEvent = async (e: React.FormEvent) => {
    e.preventDefault();
    setFormError("");

    const formData = new FormData();
    formData.append("title", title);
    formData.append("description", description);
    formData.append("location", location);
    formData.append("start_at", startAt);
    formData.append("end_at", endAt);
    formData.append("organization_id", targetOrgId);
    formData.append("conflict_acknowledged", acknowledgeConflict ? "true" : "false");

    startTransition(async () => {
      const res = await createEvent(formData);
      if (res.success) {
        setIsModalOpen(false);
        setTitle("");
        setDescription("");
        setLocation("");
        setStartAt("");
        setEndAt("");
        setConflictResult(null);
        setAcknowledgeConflict(false);
        window.location.reload();
      } else {
        if (res.hasConflict) {
          setConflictResult({ hasConflict: true, conflicts: res.conflicts || [] });
        }
        setFormError(res.error || "Failed to schedule event.");
      }
    });
  };

  const handleStatusChange = async (eventId: string, status: "scheduled" | "completed" | "cancelled") => {
    startTransition(async () => {
      const res = await updateEventStatus(eventId, status);
      if (res.success) {
        setEvents((prev) =>
          prev.map((ev) => (ev.id === eventId ? { ...ev, status } : ev))
        );
      }
    });
  };

  const handleDelete = async (eventId: string) => {
    if (!confirm("Are you sure you want to remove this event from the calendar?")) return;
    startTransition(async () => {
      const res = await deleteEvent(eventId);
      if (res.success) {
        setEvents((prev) => prev.filter((ev) => ev.id !== eventId));
      }
    });
  };

  return (
    <div className="space-y-6">
      {/* Controls and Header Bar */}
      <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-card p-4 rounded-xl border border-border/60 shadow-sm">
        <div className="flex flex-wrap items-center gap-3 w-full sm:w-auto">
          <div className="relative flex-1 sm:w-64">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-muted-foreground" />
            <input
              type="text"
              placeholder="Search events, venues..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="w-full pl-9 pr-4 py-1.5 text-sm bg-background border border-input rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-700"
            />
          </div>

          <div className="flex items-center gap-2">
            <Filter className="w-4 h-4 text-muted-foreground" />
            <select
              value={selectedOrgId}
              onChange={(e) => setSelectedOrgId(e.target.value)}
              className="text-sm bg-background border border-input rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-emerald-700"
            >
              <option value="all">All Organizations</option>
              {organizations.map((org) => (
                <option key={org.id} value={org.id}>
                  {org.acronym} - {org.name}
                </option>
              ))}
            </select>
          </div>

          <div className="inline-flex rounded-lg border border-border p-0.5 bg-muted/40">
            <button
              onClick={() => setFilterView("upcoming")}
              className={`px-3 py-1 text-xs font-medium rounded-md transition-colors ${
                filterView === "upcoming"
                  ? "bg-card text-foreground shadow-sm"
                  : "text-muted-foreground hover:text-foreground"
              }`}
            >
              Upcoming
            </button>
            <button
              onClick={() => setFilterView("all")}
              className={`px-3 py-1 text-xs font-medium rounded-md transition-colors ${
                filterView === "all"
                  ? "bg-card text-foreground shadow-sm"
                  : "text-muted-foreground hover:text-foreground"
              }`}
            >
              All Schedule
            </button>
          </div>
        </div>

        {canCreate && (
          <button
            onClick={() => setIsModalOpen(true)}
            className="flex items-center gap-2 px-4 py-2 bg-emerald-800 hover:bg-emerald-900 text-white rounded-lg text-sm font-semibold shadow-sm transition-colors whitespace-nowrap"
          >
            <Plus className="w-4 h-4" />
            Schedule Event
          </button>
        )}
      </div>

      {/* Events Grid */}
      {filteredEvents.length === 0 ? (
        <div className="text-center py-16 bg-card rounded-xl border border-dashed border-border/80">
          <CalendarIcon className="w-12 h-12 text-muted-foreground/40 mx-auto mb-3" />
          <h3 className="text-base font-semibold text-foreground">No events found</h3>
          <p className="text-xs text-muted-foreground mt-1 max-w-sm mx-auto">
            There are currently no scheduled campus activities matching your filter criteria.
          </p>
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
          {filteredEvents.map((ev) => {
            const startDate = parseISO(ev.start_at);
            const endDate = parseISO(ev.end_at);
            const isCompleted = ev.status === "completed";
            const isCancelled = ev.status === "cancelled";

            const canManage =
              currentProfile.role === "super_admin" ||
              currentProfile.role === "csg_officer" ||
              (currentProfile.role === "sub_org_officer" && currentProfile.organization_id === ev.organization_id);

            return (
              <div
                key={ev.id}
                className={`bg-card rounded-xl border transition-shadow hover:shadow-md flex flex-col justify-between overflow-hidden ${
                  isCancelled
                    ? "border-border/40 opacity-70"
                    : ev.conflict_acknowledged
                    ? "border-amber-400/50 shadow-sm"
                    : "border-border/70"
                }`}
              >
                <div className="p-5">
                  {/* Top Bar: Org Tag & Status */}
                  <div className="flex items-center justify-between gap-2 mb-3">
                    <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 dark:bg-emerald-950/70 text-emerald-800 dark:text-emerald-300">
                      <Building2 className="w-3 h-3" />
                      {ev.organization?.acronym || "CSG"}
                    </span>

                    <span
                      className={`text-[11px] font-semibold px-2 py-0.5 rounded-full ${
                        isCompleted
                          ? "bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300"
                          : isCancelled
                          ? "bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300"
                          : "bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300"
                      }`}
                    >
                      {ev.status.toUpperCase()}
                    </span>
                  </div>

                  {/* Title & Description */}
                  <h3 className="font-bold text-base text-foreground leading-snug mb-2">
                    {ev.title}
                  </h3>
                  {ev.description && (
                    <p className="text-xs text-muted-foreground line-clamp-2 mb-4">
                      {ev.description}
                    </p>
                  )}

                  {/* Event Details */}
                  <div className="space-y-2 pt-2 border-t border-border/40 text-xs text-muted-foreground">
                    <div className="flex items-center gap-2">
                      <CalendarIcon className="w-3.5 h-3.5 text-emerald-700 dark:text-emerald-400 shrink-0" />
                      <span>{format(startDate, "EEEE, MMMM d, yyyy")}</span>
                    </div>
                    <div className="flex items-center gap-2">
                      <Clock className="w-3.5 h-3.5 text-emerald-700 dark:text-emerald-400 shrink-0" />
                      <span>
                        {format(startDate, "h:mm a")} – {format(endDate, "h:mm a")}
                      </span>
                    </div>
                    <div className="flex items-center gap-2">
                      <MapPin className="w-3.5 h-3.5 text-emerald-700 dark:text-emerald-400 shrink-0" />
                      <span className="font-medium text-foreground">{ev.location}</span>
                    </div>
                  </div>

                  {/* Soft Conflict Ribbon */}
                  {ev.conflict_acknowledged && (
                    <div className="mt-3 flex items-center gap-1.5 p-2 rounded-lg bg-amber-50 dark:bg-amber-950/40 border border-amber-300 dark:border-amber-800 text-[11px] text-amber-800 dark:text-amber-300">
                      <AlertTriangle className="w-3.5 h-3.5 shrink-0" />
                      <span>Co-utilization / Partitioned Venue Approved</span>
                    </div>
                  )}
                </div>

                {/* Card Actions Footer */}
                {canManage && (
                  <div className="px-5 py-3 bg-muted/30 border-t border-border/50 flex items-center justify-between text-xs">
                    <div className="flex items-center gap-1">
                      {ev.status === "scheduled" && (
                        <>
                          <button
                            onClick={() => handleStatusChange(ev.id, "completed")}
                            title="Mark as completed"
                            className="p-1.5 text-muted-foreground hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-950/40 rounded transition-colors"
                          >
                            <CheckCircle2 className="w-4 h-4" />
                          </button>
                          <button
                            onClick={() => handleStatusChange(ev.id, "cancelled")}
                            title="Cancel event"
                            className="p-1.5 text-muted-foreground hover:text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-950/40 rounded transition-colors"
                          >
                            <XCircle className="w-4 h-4" />
                          </button>
                        </>
                      )}
                      {ev.status !== "scheduled" && (
                        <button
                          onClick={() => handleStatusChange(ev.id, "scheduled")}
                          className="text-[11px] font-medium text-emerald-700 hover:underline"
                        >
                          Reopen
                        </button>
                      )}
                    </div>

                    <button
                      onClick={() => handleDelete(ev.id)}
                      title="Delete event"
                      className="p-1.5 text-muted-foreground hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-950/40 rounded transition-colors"
                    >
                      <Trash2 className="w-4 h-4" />
                    </button>
                  </div>
                )}
              </div>
            );
          })}
        </div>
      )}

      {/* Schedule Event Modal */}
      {isModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-in fade-in duration-200">
          <div className="bg-card rounded-2xl border border-border shadow-2xl max-w-lg w-full overflow-hidden">
            <div className="px-6 py-4 border-b border-border flex items-center justify-between bg-muted/40">
              <div className="flex items-center gap-2">
                <CalendarIcon className="w-5 h-5 text-emerald-800 dark:text-emerald-400" />
                <h3 className="font-bold text-base text-foreground">Schedule Activity / Event</h3>
              </div>
              <button
                onClick={() => setIsModalOpen(false)}
                className="text-muted-foreground hover:text-foreground text-sm font-semibold"
              >
                ✕
              </button>
            </div>

            <form onSubmit={handleCreateEvent} className="p-6 space-y-4">
              {formError && (
                <div className="p-3 bg-red-50 dark:bg-red-950/50 border border-red-300 dark:border-red-800 text-red-700 dark:text-red-300 rounded-lg text-xs">
                  {formError}
                </div>
              )}

              {/* Organization Selection */}
              {currentProfile.role !== "sub_org_officer" && (
                <div>
                  <label className="block text-xs font-semibold text-foreground mb-1">
                    Host Organization *
                  </label>
                  <select
                    value={targetOrgId}
                    onChange={(e) => setTargetOrgId(e.target.value)}
                    required
                    className="w-full px-3 py-2 text-sm bg-background border border-input rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-700"
                  >
                    <option value="">Select Organization</option>
                    {organizations.map((org) => (
                      <option key={org.id} value={org.id}>
                        {org.acronym} — {org.name}
                      </option>
                    ))}
                  </select>
                </div>
              )}

              {/* Title */}
              <div>
                <label className="block text-xs font-semibold text-foreground mb-1">
                  Activity Title *
                </label>
                <input
                  type="text"
                  placeholder="e.g. IT Skills Fair 2026 / CSG General Assembly"
                  value={title}
                  onChange={(e) => setTitle(e.target.value)}
                  required
                  className="w-full px-3 py-2 text-sm bg-background border border-input rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-700"
                />
              </div>

              {/* Location */}
              <div>
                <label className="block text-xs font-semibold text-foreground mb-1">
                  Campus Venue / Location *
                </label>
                <input
                  type="text"
                  placeholder="e.g. Campus Gymnasium, Audio-Visual Room (AVR), Room 304"
                  value={location}
                  onChange={(e) => setLocation(e.target.value)}
                  onBlur={handleLocationOrTimeBlur}
                  required
                  className="w-full px-3 py-2 text-sm bg-background border border-input rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-700"
                />
              </div>

              {/* Dates */}
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                  <label className="block text-xs font-semibold text-foreground mb-1">
                    Start Date & Time *
                  </label>
                  <input
                    type="datetime-local"
                    value={startAt}
                    onChange={(e) => setStartAt(e.target.value)}
                    onBlur={handleLocationOrTimeBlur}
                    required
                    className="w-full px-3 py-2 text-sm bg-background border border-input rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-700"
                  />
                </div>
                <div>
                  <label className="block text-xs font-semibold text-foreground mb-1">
                    End Date & Time *
                  </label>
                  <input
                    type="datetime-local"
                    value={endAt}
                    onChange={(e) => setEndAt(e.target.value)}
                    onBlur={handleLocationOrTimeBlur}
                    required
                    className="w-full px-3 py-2 text-sm bg-background border border-input rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-700"
                  />
                </div>
              </div>

              {/* Conflict Detection Alert */}
              {conflictResult?.hasConflict && (
                <div className="p-3 bg-amber-50 dark:bg-amber-950/50 border border-amber-400 dark:border-amber-800 rounded-xl space-y-2 text-xs text-amber-900 dark:text-amber-200">
                  <div className="flex items-start gap-2 font-semibold">
                    <AlertTriangle className="w-4 h-4 text-amber-600 shrink-0 mt-0.5" />
                    <span>Soft Conflict Warning: Venue Overlap Detected</span>
                  </div>
                  <p className="text-[11px] text-amber-800/90 dark:text-amber-300/90">
                    The requested venue is already reserved by {conflictResult.conflicts.length} activity:
                  </p>
                  <ul className="list-disc list-inside space-y-1 pl-1 text-[11px]">
                    {conflictResult.conflicts.map((c) => (
                      <li key={c.id}>
                        <strong className="font-semibold">{c.title}</strong> ({c.organization?.acronym || "Org"}):{" "}
                        {format(parseISO(c.start_at), "MMM d, h:mm a")} – {format(parseISO(c.end_at), "h:mm a")}
                      </li>
                    ))}
                  </ul>

                  <label className="flex items-start gap-2 mt-2 pt-2 border-t border-amber-300 dark:border-amber-800/60 cursor-pointer">
                    <input
                      type="checkbox"
                      checked={acknowledgeConflict}
                      onChange={(e) => setAcknowledgeConflict(e.target.checked)}
                      className="mt-0.5 rounded border-amber-400 text-emerald-700 focus:ring-emerald-700"
                    />
                    <span className="text-[11px] font-medium text-amber-950 dark:text-amber-100">
                      Acknowledge venue co-utilization / space partition and proceed anyway.
                    </span>
                  </label>
                </div>
              )}

              {/* Description */}
              <div>
                <label className="block text-xs font-semibold text-foreground mb-1">
                  Brief Description (Optional)
                </label>
                <textarea
                  rows={2}
                  placeholder="Outline the nature and target participants of this activity..."
                  value={description}
                  onChange={(e) => setDescription(e.target.value)}
                  className="w-full px-3 py-2 text-sm bg-background border border-input rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-700"
                />
              </div>

              {/* Modal Actions */}
              <div className="flex items-center justify-end gap-3 pt-3 border-t border-border">
                <button
                  type="button"
                  onClick={() => setIsModalOpen(false)}
                  className="px-4 py-2 text-xs font-semibold text-muted-foreground hover:text-foreground"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  disabled={isPending || (Boolean(conflictResult?.hasConflict) && !acknowledgeConflict)}
                  className="px-4 py-2 bg-emerald-800 hover:bg-emerald-900 disabled:opacity-50 text-white rounded-lg text-xs font-bold shadow-sm transition-colors"
                >
                  {isPending ? "Scheduling..." : "Confirm Schedule"}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}
