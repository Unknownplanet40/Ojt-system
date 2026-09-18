"use client";

import React, { useState, useEffect, useRef, useTransition } from "react";
import { format, parseISO } from "date-fns";
import type { ConversationRecord, MessageRecord, Profile } from "@/lib/supabase/types";
import { sendMessage, getConversationMessages } from "@/actions/chat";
import { createClient } from "@/lib/supabase/client";
import {
  MessageSquare,
  Send,
  Users,
  Building2,
  Shield,
  Search,
  CheckCheck,
  Circle,
  Hash,
} from "lucide-react";

interface ChatRoomProps {
  initialConversations: ConversationRecord[];
  currentProfile: Profile;
}

export function ChatRoom({ initialConversations, currentProfile }: ChatRoomProps) {
  const [conversations, setConversations] = useState<ConversationRecord[]>(initialConversations);
  const [activeConversationId, setActiveConversationId] = useState<string>(
    initialConversations[0]?.id || ""
  );
  const [messages, setMessages] = useState<MessageRecord[]>([]);
  const [inputText, setInputText] = useState("");
  const [searchQuery, setSearchQuery] = useState("");
  const [loadingMessages, setLoadingMessages] = useState(false);
  const [isSending, setIsSending] = useState(false);
  const [presenceCount, setPresenceCount] = useState(1);

  const messagesEndRef = useRef<HTMLDivElement>(null);
  const supabase = React.useMemo(() => createClient(), []);

  const activeConversation = conversations.find((c) => c.id === activeConversationId);

  // Auto-scroll to bottom
  const scrollToBottom = () => {
    messagesEndRef.current?.scrollIntoView({ behavior: "smooth" });
  };

  // Load messages when active conversation changes
  useEffect(() => {
    if (!activeConversationId) return;

    let isMounted = true;
    setLoadingMessages(true);

    getConversationMessages(activeConversationId).then((res) => {
      if (isMounted && res.success && res.messages) {
        setMessages(res.messages);
        setLoadingMessages(false);
        setTimeout(scrollToBottom, 100);
      }
    });

    // Realtime channel subscription
    const channel = supabase
      .channel(`chat:${activeConversationId}`)
      .on(
        "postgres_changes",
        {
          event: "INSERT",
          schema: "public",
          table: "messages",
          filter: `conversation_id=eq.${activeConversationId}`,
        },
        async (payload) => {
          const newMsg = payload.new as MessageRecord;
          // Avoid duplicate if sent by current client
          setMessages((prev) => {
            if (prev.some((m) => m.id === newMsg.id)) return prev;
            // Fetch sender profile details if needed
            return [...prev, newMsg];
          });
          setTimeout(scrollToBottom, 100);
        }
      )
      .subscribe();

    // Fallback polling interval (every 6 seconds) to ensure resiliency
    const interval = setInterval(() => {
      getConversationMessages(activeConversationId).then((res) => {
        if (isMounted && res.success && res.messages) {
          setMessages((prev) => {
            if (res.messages && res.messages.length !== prev.length) {
              setTimeout(scrollToBottom, 100);
              return res.messages;
            }
            return prev;
          });
        }
      });
    }, 6000);

    return () => {
      isMounted = false;
      clearInterval(interval);
      supabase.removeChannel(channel);
    };
  }, [activeConversationId, supabase]);

  // Handle Send Message
  const handleSendMessage = async (e: React.FormEvent) => {
    e.preventDefault();
    const trimmed = inputText.trim();
    if (!trimmed || !activeConversationId || isSending) return;

    setIsSending(true);
    setInputText("");

    // Optimistic local update
    const tempId = "temp-" + Date.now();
    const optimisticMsg: MessageRecord = {
      id: tempId,
      conversation_id: activeConversationId,
      sender_id: currentProfile.id,
      content: trimmed,
      created_at: new Date().toISOString(),
      edited_at: null,
      sender: currentProfile,
    };
    setMessages((prev) => [...prev, optimisticMsg]);
    setTimeout(scrollToBottom, 50);

    const res = await sendMessage(activeConversationId, trimmed);
    setIsSending(false);

    if (res.success && res.message) {
      // Replace optimistic message with actual persisted record
      setMessages((prev) =>
        prev.map((m) => (m.id === tempId ? res.message! : m))
      );
    } else {
      // Rollback on error
      setMessages((prev) => prev.filter((m) => m.id !== tempId));
      alert(res.error || "Failed to send message.");
    }
  };

  const filteredConversations = conversations.filter((c) =>
    c.title.toLowerCase().includes(searchQuery.toLowerCase())
  );

  return (
    <div className="flex flex-col md:flex-row h-[calc(100vh-14rem)] min-h-[500px] bg-card rounded-2xl border border-border/80 shadow-sm overflow-hidden">
      {/* Channels Sidebar */}
      <div className="w-full md:w-72 border-r border-border/70 flex flex-col bg-muted/20">
        {/* Search Header */}
        <div className="p-3 border-b border-border/60">
          <div className="relative">
            <Search className="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-muted-foreground" />
            <input
              type="text"
              placeholder="Filter channels..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="w-full pl-8 pr-3 py-1 text-xs bg-background border border-input rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-700"
            />
          </div>
        </div>

        {/* Channels List */}
        <div className="flex-1 overflow-y-auto p-2 space-y-1">
          <div className="px-2 py-1 text-[10px] font-bold uppercase tracking-wider text-muted-foreground">
            Available Channels
          </div>

          {filteredConversations.length === 0 ? (
            <div className="p-4 text-center text-xs text-muted-foreground">
              No chat channels available for your role.
            </div>
          ) : (
            filteredConversations.map((c) => {
              const isActive = c.id === activeConversationId;
              const isCouncil = c.type === "council";

              return (
                <button
                  key={c.id}
                  onClick={() => setActiveConversationId(c.id)}
                  className={`w-full flex items-start gap-2.5 px-3 py-2.5 rounded-xl text-left transition-colors ${
                    isActive
                      ? "bg-emerald-800 text-white shadow-sm font-semibold"
                      : "hover:bg-muted text-foreground"
                  }`}
                >
                  <div
                    className={`w-7 h-7 rounded-lg flex items-center justify-center shrink-0 mt-0.5 ${
                      isActive
                        ? "bg-white/20 text-amber-300"
                        : isCouncil
                        ? "bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300"
                        : "bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300"
                    }`}
                  >
                    {isCouncil ? <Shield className="w-3.5 h-3.5" /> : <Hash className="w-3.5 h-3.5" />}
                  </div>

                  <div className="flex-1 min-w-0">
                    <div className="flex items-center justify-between gap-1">
                      <span className="text-xs font-semibold truncate block">
                        {c.title}
                      </span>
                    </div>
                    <span
                      className={`text-[10px] block truncate mt-0.5 ${
                        isActive ? "text-emerald-100" : "text-muted-foreground"
                      }`}
                    >
                      {isCouncil ? "CSG Council Room" : `${c.organization?.acronym || "Org"} Channel`}
                    </span>
                  </div>
                </button>
              );
            })
          )}
        </div>

        {/* User Scope Banner */}
        <div className="p-3 bg-muted/40 border-t border-border/60 text-[11px] text-muted-foreground flex items-center gap-2">
          <Circle className="w-2 h-2 fill-emerald-500 text-emerald-500 shrink-0" />
          <span className="truncate">
            Online as <strong className="text-foreground">{currentProfile.full_name}</strong>
          </span>
        </div>
      </div>

      {/* Active Conversation Area */}
      <div className="flex-1 flex flex-col bg-background">
        {activeConversation ? (
          <>
            {/* Chat Room Header */}
            <div className="px-5 py-3 border-b border-border/70 flex items-center justify-between bg-card">
              <div className="flex items-center gap-3">
                <div className="w-9 h-9 rounded-xl bg-emerald-100 dark:bg-emerald-950/70 text-emerald-800 dark:text-emerald-300 flex items-center justify-center font-bold">
                  {activeConversation.type === "council" ? (
                    <Shield className="w-4 h-4 text-amber-600" />
                  ) : (
                    <Building2 className="w-4 h-4" />
                  )}
                </div>
                <div>
                  <h2 className="text-sm font-bold text-foreground leading-tight flex items-center gap-2">
                    {activeConversation.title}
                  </h2>
                  <span className="text-[11px] text-muted-foreground flex items-center gap-1.5 mt-0.5">
                    <span className="w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block" />
                    {activeConversation.type === "council"
                      ? "Restricted Council (CSG Officers & Sub-Org Presidents)"
                      : "Official Recognized Organization Channel"}
                  </span>
                </div>
              </div>

              <div className="flex items-center gap-2 text-xs text-muted-foreground">
                <Users className="w-3.5 h-3.5" />
                <span className="text-[11px]">Realtime Active</span>
              </div>
            </div>

            {/* Messages Feed */}
            <div className="flex-1 overflow-y-auto p-5 space-y-4">
              {loadingMessages ? (
                <div className="flex items-center justify-center h-full text-xs text-muted-foreground">
                  Loading message stream...
                </div>
              ) : messages.length === 0 ? (
                <div className="flex flex-col items-center justify-center h-full text-center p-6 text-muted-foreground">
                  <MessageSquare className="w-10 h-10 stroke-1 text-muted-foreground/40 mb-2" />
                  <p className="text-xs font-semibold text-foreground">No messages yet</p>
                  <p className="text-[11px] max-w-xs mt-0.5">
                    Send the first message to initiate official communication in this channel.
                  </p>
                </div>
              ) : (
                messages.map((msg) => {
                  const isCurrentUser = msg.sender_id === currentProfile.id;
                  const senderName = msg.sender?.full_name || (isCurrentUser ? currentProfile.full_name : "Officer");
                  const senderRole = msg.sender?.role || (isCurrentUser ? currentProfile.role : "member");

                  let roleBadge = "Member";
                  if (senderRole === "super_admin") roleBadge = "Super Admin";
                  else if (senderRole === "csg_officer") roleBadge = "CSG Officer";
                  else if (senderRole === "sub_org_officer") roleBadge = "Sub-Org Officer";

                  return (
                    <div
                      key={msg.id}
                      className={`flex flex-col ${isCurrentUser ? "items-end" : "items-start"}`}
                    >
                      {/* Sender Details */}
                      {!isCurrentUser && (
                        <div className="flex items-center gap-1.5 mb-1 px-1">
                          <span className="text-xs font-bold text-foreground">
                            {senderName}
                          </span>
                          <span className="text-[9px] px-1.5 py-0.2 rounded-full font-semibold bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300">
                            {roleBadge}
                          </span>
                        </div>
                      )}

                      {/* Bubble */}
                      <div
                        className={`max-w-md md:max-w-lg px-4 py-2.5 rounded-2xl text-xs leading-relaxed break-words shadow-sm ${
                          isCurrentUser
                            ? "bg-emerald-800 text-white rounded-br-none"
                            : "bg-muted/70 text-foreground border border-border/60 rounded-bl-none"
                        }`}
                      >
                        <p className="whitespace-pre-wrap">{msg.content}</p>
                      </div>

                      {/* Timestamp */}
                      <span className="text-[9px] text-muted-foreground px-1 mt-1">
                        {format(parseISO(msg.created_at), "h:mm a")}
                      </span>
                    </div>
                  );
                })
              )}
              <div ref={messagesEndRef} />
            </div>

            {/* Message Composer Form */}
            <form
              onSubmit={handleSendMessage}
              className="p-3 bg-card border-t border-border/70 flex items-center gap-2"
            >
              <input
                type="text"
                placeholder={`Message #${activeConversation.title}...`}
                value={inputText}
                onChange={(e) => setInputText(e.target.value)}
                disabled={isSending}
                className="flex-1 px-4 py-2 text-xs bg-background border border-input rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-700"
              />
              <button
                type="submit"
                disabled={!inputText.trim() || isSending}
                className="p-2.5 bg-emerald-800 hover:bg-emerald-900 disabled:opacity-50 text-white rounded-xl transition-colors shadow-sm"
              >
                <Send className="w-4 h-4" />
              </button>
            </form>
          </>
        ) : (
          <div className="flex items-center justify-center h-full text-xs text-muted-foreground">
            Select a channel from the left sidebar to start messaging.
          </div>
        )}
      </div>
    </div>
  );
}
