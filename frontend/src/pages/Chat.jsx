import { useEffect, useMemo, useRef, useState } from "react";
import { useNavigate, useParams } from "react-router-dom";
import axios from "axios";
import "../styles/chat.css";

const API = import.meta.env.VITE_API_BASE_URL || "http://localhost/donation_backend";

export default function Chat() {
  const navigate = useNavigate();
  const { donationId } = useParams();

  const token = localStorage.getItem("token") || "";
  const user = useMemo(() => {
    try {
      return JSON.parse(localStorage.getItem("user") || "null");
    } catch {
      return null;
    }
  }, []);

  const [loading, setLoading] = useState(true);
  const [sending, setSending] = useState(false);
  const [error, setError] = useState("");
  const [donation, setDonation] = useState(null);
  const [messages, setMessages] = useState([]);
  const [text, setText] = useState("");
  const [currentUserId, setCurrentUserId] = useState(0);
  const [lastMessageId, setLastMessageId] = useState(0);

  const listRef = useRef(null);

  useEffect(() => {
    if (!token || !user) {
      navigate("/login");
      return;
    }

    fetchChat(true);

    const timer = setInterval(() => {
      fetchChat(false);
    }, 3000);

    return () => clearInterval(timer);
  }, [donationId]);

  useEffect(() => {
    if (!messages.length) return;

    const latest = Number(messages[messages.length - 1]?.message_id || 0);
    if (latest !== lastMessageId) {
      setLastMessageId(latest);
      if (listRef.current) {
        listRef.current.scrollTop = listRef.current.scrollHeight;
      }
    }
  }, [messages]);

  const fetchChat = async (showLoading) => {
    if (showLoading) setLoading(true);

    try {
      const res = await axios.get(`${API}/donation_chat.php?donation_id=${donationId}`, {
        headers: {
          Authorization: `Bearer ${token}`,
        },
      });

      if (!res.data?.success) {
        throw new Error(res.data?.message || "Failed to load chat");
      }

      setDonation(res.data.donation || null);
      setMessages(res.data.messages || []);
      setCurrentUserId(Number(res.data.current_user_id || 0));
      setError("");
    } catch (err) {
      const backendMessage = err?.response?.data?.message;
      setError(backendMessage || err.message || "Failed to load chat");
    } finally {
      if (showLoading) setLoading(false);
    }
  };

  const sendMessage = async () => {
    const payload = text.trim();
    if (!payload || sending) return;

    try {
      setSending(true);
      setError("");

      const res = await axios.post(
        `${API}/donation_chat_send.php`,
        {
          donation_id: Number(donationId),
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

      setText("");
      await fetchChat(false);
    } catch (err) {
      const backendMessage = err?.response?.data?.message;
      setError(backendMessage || err.message || "Failed to send message");
    } finally {
      setSending(false);
    }
  };

  if (loading) {
    return <div className="chat-loading">Loading chat...</div>;
  }

  const donorName = donation?.donor_name || "Donor";
  const receiverName = donation?.receiver_name || "Receiver";

  return (
    <div className="chat-page">
      <div className="chat-card">
        <div className="chat-header">
          <div>
            <h2>{donation?.item_title || "Donation Chat"}</h2>
            <p>
              {donorName} and {receiverName} can chat here privately.
            </p>
          </div>
          <button className="chat-back-btn" onClick={() => navigate(-1)}>
            Back
          </button>
        </div>

        {error && <div className="chat-error">{error}</div>}

        <div className="chat-messages" ref={listRef}>
          {messages.length === 0 ? (
            <div className="chat-empty">No messages yet. Start the conversation.</div>
          ) : (
            messages.map((msg) => {
              const mine = Number(msg.sender_id) === Number(currentUserId);
              return (
                <div
                  key={msg.message_id}
                  className={`chat-bubble-row ${mine ? "mine" : "other"}`}
                >
                  <div className={`chat-bubble ${mine ? "mine" : "other"}`}>
                    <div className="chat-author">{mine ? "You" : msg.sender_name || "User"}</div>
                    <div className="chat-text">{msg.message}</div>
                    <div className="chat-time">
                      {msg.created_at ? new Date(msg.created_at).toLocaleString() : ""}
                    </div>
                  </div>
                </div>
              );
            })
          )}
        </div>

        <div className="chat-input-wrap">
          <input
            type="text"
            value={text}
            placeholder="Type your message..."
            onChange={(e) => setText(e.target.value)}
            onKeyDown={(e) => {
              if (e.key === "Enter" && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
              }
            }}
          />
          <button onClick={sendMessage} disabled={sending || !text.trim()}>
            {sending ? "Sending..." : "Send"}
          </button>
        </div>
      </div>
    </div>
  );
}
