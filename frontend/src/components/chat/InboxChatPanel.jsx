import { useEffect, useMemo, useRef, useState } from "react";
import axios from "axios";

export default function InboxChatPanel({ apiBase, token, emptyTitle = "Inbox" }) {
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [conversations, setConversations] = useState([]);
  const [selectedDonationId, setSelectedDonationId] = useState(0);
  const [messages, setMessages] = useState([]);
  const [chatSending, setChatSending] = useState(false);
  const [chatText, setChatText] = useState("");
  const [currentUserId, setCurrentUserId] = useState(0);
  const [search, setSearch] = useState("");

  const listRef = useRef(null);

  const selectedConversation = useMemo(
    () => conversations.find((x) => Number(x.donation_id) === Number(selectedDonationId)) || null,
    [conversations, selectedDonationId]
  );

  const filteredConversations = useMemo(() => {
    const q = search.trim().toLowerCase();
    if (!q) return conversations;

    return conversations.filter((conv) => {
      const hay = `${conv.peer_name || ""} ${conv.item_title || ""} ${conv.last_message || ""}`.toLowerCase();
      return hay.includes(q);
    });
  }, [conversations, search]);

  const authHeaders = useMemo(
    () => ({
      headers: {
        Authorization: `Bearer ${token}`,
      },
    }),
    [token]
  );

  const fetchConversations = async (showLoading = false) => {
    if (!token) return;
    if (showLoading) setLoading(true);

    try {
      const res = await axios.get(`${apiBase}/donation_conversations.php`, authHeaders);
      if (!res.data?.success) {
        throw new Error(res.data?.message || "Failed to load conversations");
      }

      const rows = res.data.data || [];
      setConversations(rows);

      if (!selectedDonationId && rows.length > 0) {
        setSelectedDonationId(Number(rows[0].donation_id));
      }
    } catch (err) {
      const backend = err?.response?.data;
      setError(backend?.message || err.message || "Failed to load conversations");
    } finally {
      if (showLoading) setLoading(false);
    }
  };

  const fetchMessages = async (donationId) => {
    if (!token || !donationId) return;

    try {
      const res = await axios.get(
        `${apiBase}/donation_chat.php?donation_id=${donationId}`,
        authHeaders
      );

      if (!res.data?.success) {
        throw new Error(res.data?.message || "Failed to load messages");
      }

      setMessages(res.data.messages || []);
      setCurrentUserId(Number(res.data.current_user_id || 0));
      setError("");
    } catch (err) {
      const backend = err?.response?.data;
      setError(backend?.message || err.message || "Failed to load messages");
    }
  };

  useEffect(() => {
    fetchConversations(true);
  }, [token]);

  useEffect(() => {
    if (!selectedDonationId) {
      setMessages([]);
      return;
    }

    fetchMessages(selectedDonationId);
    const timer = setInterval(() => {
      fetchMessages(selectedDonationId);
    }, 3000);

    return () => clearInterval(timer);
  }, [selectedDonationId, token]);

  useEffect(() => {
    const timer = setInterval(() => {
      fetchConversations(false);
    }, 5000);

    return () => clearInterval(timer);
  }, [token, selectedDonationId]);

  useEffect(() => {
    if (listRef.current) {
      listRef.current.scrollTop = listRef.current.scrollHeight;
    }
  }, [messages]);

  const sendMessage = async () => {
    const payload = chatText.trim();
    if (!payload || !selectedDonationId || chatSending) return;

    try {
      setChatSending(true);
      setError("");

      const res = await axios.post(
        `${apiBase}/donation_chat_send.php`,
        {
          donation_id: Number(selectedDonationId),
          message: payload,
        },
        {
          headers: {
            "Content-Type": "application/json",
            Authorization: `Bearer ${token}`,
          },
        }
      );

      if (!res.data?.success) {
        throw new Error(res.data?.message || "Failed to send message");
      }

      setChatText("");
      await fetchMessages(selectedDonationId);
      await fetchConversations(false);
    } catch (err) {
      const backend = err?.response?.data;
      setError(backend?.message || err.message || "Failed to send message");
    } finally {
      setChatSending(false);
    }
  };

  if (loading) {
    return <div className="dd-loading">Loading inbox...</div>;
  }

  return (
    <div className="dd-chatWrap">
      <div className="dd-chatLeft">
        <div className="dd-chatLeftHead">
          <div className="dd-chatLeftTitle">Chats</div>
          <div className="dd-chatLeftSub">Messenger style inbox</div>
          <input
            className="dd-chatSearch"
            placeholder="Search chats"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
          />
        </div>

        {filteredConversations.length === 0 ? (
          <div className="dd-empty">
            <div className="dd-emptyTitle">{emptyTitle}</div>
            <div className="dd-emptyText">No chat conversations yet.</div>
          </div>
        ) : (
          <div className="dd-chatList">
            {filteredConversations.map((conv) => (
              <button
                key={conv.donation_id}
                className={`dd-chatListItem ${Number(conv.donation_id) === Number(selectedDonationId) ? "isActive" : ""}`}
                onClick={() => setSelectedDonationId(Number(conv.donation_id))}
              >
                <div className="dd-chatListTop">
                  <div className="dd-chatPeer">{conv.peer_name || "User"}</div>
                  <div className="dd-chatTime">
                    {conv.last_message_at
                      ? new Date(conv.last_message_at).toLocaleTimeString([], {
                          hour: "2-digit",
                          minute: "2-digit",
                        })
                      : ""}
                  </div>
                </div>
                <div className="dd-chatItemTitle">{conv.item_title || "Donation item"}</div>
                <div className="dd-chatListBottom">
                  <div className="dd-chatPreview">{conv.last_message || "No messages"}</div>
                  {Number(conv.unread_count || 0) > 0 && (
                    <span className="dd-chatUnread">{conv.unread_count}</span>
                  )}
                </div>
              </button>
            ))}
          </div>
        )}
      </div>

      <div className="dd-chatRight">
        {!selectedConversation ? (
          <div className="dd-empty">
            <div className="dd-emptyTitle">Select a conversation</div>
            <div className="dd-emptyText">Choose a chat from the left to start messaging.</div>
          </div>
        ) : (
          <>
            <div className="dd-chatHead">
              <div>
                <div className="dd-chatHeadMain">{selectedConversation.peer_name || "Conversation"}</div>
                <div className="dd-chatHeadSub">{selectedConversation.item_title || "Item"}</div>
              </div>
              <div className="dd-chatPresence">online</div>
            </div>

            {error && <div className="dd-chatError">{error}</div>}

            <div className="dd-chatMessages" ref={listRef}>
              {messages.length === 0 ? (
                <div className="dd-chatNoMsg">No messages yet.</div>
              ) : (
                messages.map((msg) => {
                  const mine = Number(msg.sender_id) === Number(currentUserId);
                  return (
                    <div
                      key={msg.message_id}
                      className={`dd-chatRow ${mine ? "mine" : "other"}`}
                    >
                      <div className={`dd-chatBubble ${mine ? "mine" : "other"}`}>
                        <div className="dd-chatText">{msg.message}</div>
                        <div className="dd-chatMeta">
                          {msg.created_at
                            ? new Date(msg.created_at).toLocaleTimeString([], {
                                hour: "2-digit",
                                minute: "2-digit",
                              })
                            : ""}
                          {mine && (
                            <span className="dd-chatSeenBadge">
                              {msg.seen_by_peer ? "Seen" : "Sent"}
                            </span>
                          )}
                        </div>
                      </div>
                    </div>
                  );
                })
              )}
            </div>

            <div className="dd-chatComposer">
              <input
                type="text"
                placeholder="Type a message..."
                value={chatText}
                onChange={(e) => setChatText(e.target.value)}
                onKeyDown={(e) => {
                  if (e.key === "Enter" && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage();
                  }
                }}
              />
              <button onClick={sendMessage} disabled={chatSending || !chatText.trim()}>
                {chatSending ? "Sending..." : "Send"}
              </button>
            </div>
          </>
        )}
      </div>
    </div>
  );
}
