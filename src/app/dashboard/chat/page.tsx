import { createServerClient } from "@/lib/supabase/server";
import { redirect } from "next/navigation";
import { getPermittedConversations } from "@/actions/chat";
import { ChatRoom } from "@/components/chat/chat-room";
import type { Profile } from "@/lib/supabase/types";

export const metadata = {
  title: "Live Chat & Communications | CvSU Imus CSG",
  description: "Official real-time messaging channel for CSG officers and student organizations.",
};

export default async function ChatPage() {
  const supabase = await createServerClient();

  const {
    data: { user },
  } = await supabase.auth.getUser();

  if (!user) {
    redirect("/login");
  }

  const { data: profile } = await supabase
    .from("profiles")
    .select("*")
    .eq("id", user.id)
    .single();

  if (!profile) {
    redirect("/login");
  }

  // Get permitted conversations
  let { conversations = [] } = await getPermittedConversations();

  // If no conversations found, bootstrap the CSG Executive Council thread
  if (conversations.length === 0) {
    const { data: csgOrg } = await supabase
      .from("organizations")
      .select("id")
      .eq("acronym", "CSG")
      .single();

    if (csgOrg) {
      await supabase.from("conversations").insert({
        title: "CSG Executive & Presidents Council",
        type: "council",
        organization_id: csgOrg.id,
      });

      const res = await getPermittedConversations();
      conversations = res.conversations || [];
    }
  }

  return (
    <div className="space-y-6">
      {/* Page Header */}
      <div>
        <h1 className="text-2xl font-bold tracking-tight text-foreground">
          Live Chat & Communications
        </h1>
        <p className="text-sm text-muted-foreground mt-1">
          Secure, official real-time communications channel between CSG leadership, student organizations, and executive committees.
        </p>
      </div>

      <ChatRoom
        initialConversations={conversations}
        currentProfile={profile as Profile}
      />
    </div>
  );
}
