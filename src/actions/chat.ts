"use server";

import { createServerClient } from "@/lib/supabase/server";
import { revalidatePath } from "next/cache";
import type { ConversationRecord, MessageRecord } from "@/lib/supabase/types";

export async function getPermittedConversations(): Promise<{
  success: boolean;
  conversations?: ConversationRecord[];
  error?: string;
}> {
  const supabase = await createServerClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    return { success: false, error: "Authentication required" };
  }

  const { data: profile } = await supabase
    .from("profiles")
    .select("id, role, organization_id")
    .eq("id", user.id)
    .single();

  if (!profile) {
    return { success: false, error: "Profile not found" };
  }

  let query = supabase
    .from("conversations")
    .select("*, organization:organizations(name, acronym, logo_url)");

  // Role filtering
  if (profile.role === "member") {
    if (!profile.organization_id) {
      return { success: true, conversations: [] };
    }
    query = query
      .eq("type", "organization")
      .eq("organization_id", profile.organization_id);
  } else if (profile.role === "sub_org_officer") {
    // Council + own org
    if (profile.organization_id) {
      query = query.or(
        `type.eq.council,and(type.eq.organization,organization_id.eq.${profile.organization_id})`
      );
    } else {
      query = query.eq("type", "council");
    }
  }
  // super_admin and csg_officer have access to all (council and all org channels)

  const { data: convos, error } = await query.order("updated_at", { ascending: false });

  if (error) {
    return { success: false, error: error.message };
  }

  return { success: true, conversations: (convos as any) || [] };
}

export async function getConversationMessages(conversationId: string): Promise<{
  success: boolean;
  messages?: MessageRecord[];
  error?: string;
}> {
  const supabase = await createServerClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    return { success: false, error: "Authentication required" };
  }

  // Update participant's last_read_at
  await supabase
    .from("conversation_participants")
    .upsert(
      {
        conversation_id: conversationId,
        profile_id: user.id,
        last_read_at: new Date().toISOString(),
      },
      { onConflict: "conversation_id,profile_id" }
    );

  const { data, error } = await supabase
    .from("messages")
    .select("id, conversation_id, sender_id, content, created_at, edited_at, sender:profiles(id, full_name, role, organization_id)")
    .eq("conversation_id", conversationId)
    .order("created_at", { ascending: true })
    .limit(100);

  if (error) {
    return { success: false, error: error.message };
  }

  return { success: true, messages: (data as any) || [] };
}

export async function sendMessage(
  conversationId: string,
  content: string
): Promise<{ success: boolean; message?: MessageRecord; error?: string }> {
  const supabase = await createServerClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    return { success: false, error: "Authentication required" };
  }

  const trimmed = content?.trim();
  if (!trimmed) {
    return { success: false, error: "Message content cannot be empty" };
  }

  // Verify conversation access
  const { data: convo } = await supabase
    .from("conversations")
    .select("id, type, organization_id")
    .eq("id", conversationId)
    .single();

  if (!convo) {
    return { success: false, error: "Conversation not found" };
  }

  const { data: profile } = await supabase
    .from("profiles")
    .select("id, role, organization_id, full_name")
    .eq("id", user.id)
    .single();

  if (!profile) {
    return { success: false, error: "Profile not found" };
  }

  // Enforce boundary
  if (profile.role === "member") {
    if (convo.type !== "organization" || convo.organization_id !== profile.organization_id) {
      return { success: false, error: "Regular members can only post in their assigned organization channel." };
    }
  } else if (profile.role === "sub_org_officer") {
    if (convo.type === "organization" && convo.organization_id !== profile.organization_id) {
      return { success: false, error: "Unauthorized: You cannot post in another organization's channel." };
    }
  }

  const { data: insertedMsg, error: insertError } = await supabase
    .from("messages")
    .insert({
      conversation_id: conversationId,
      sender_id: user.id,
      content: trimmed,
    })
    .select("id, conversation_id, sender_id, content, created_at, edited_at, sender:profiles(id, full_name, role, organization_id)")
    .single();

  if (insertError) {
    return { success: false, error: insertError.message };
  }

  // Touch conversation updated_at
  await supabase
    .from("conversations")
    .update({ updated_at: new Date().toISOString() })
    .eq("id", conversationId);

  // Update sender's participant last_read_at
  await supabase
    .from("conversation_participants")
    .upsert({
      conversation_id: conversationId,
      profile_id: user.id,
      last_read_at: new Date().toISOString(),
    });

  return { success: true, message: insertedMsg as any };
}
