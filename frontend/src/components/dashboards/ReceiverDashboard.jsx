import { useState, useEffect } from "react";
import { useNavigate } from "react-router-dom";
import axios from "axios";
import "../../styles/donorDashboard.css";
import InboxChatPanel from "../chat/InboxChatPanel";

const API = import.meta.env.VITE_API_BASE_URL || "http://localhost/donation_backend";

export default function ReceiverDashboard() {
  const navigate = useNavigate();
  const user = JSON.parse(localStorage.getItem("user") || "null");
  const token = localStorage.getItem("token") || "";

  const [activeMenu, setActiveMenu] = useState("dashboard");
  const [activeTab, setActiveTab] = useState("requests");
  const [requests, setRequests] = useState([]);
  const [receivedItems, setReceivedItems] = useState([]);
  const [stats, setStats] = useState({
    requestsMade: 0,
    itemsReceived: 0,
    completed: 0,
    pending: 0,
  });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  const displayName = user?.name || user?.email?.split("@")?.[0] || "Receiver";
  const roleLabel = "RECEIVER";

  useEffect(() => {
    fetchReceiverData();
  }, []);

  const fetchReceiverData = async () => {
    try {
      const res = await axios.get(`${API}/receiver_dashboard.php?user_id=${user?.user_id || 0}`, {
        headers: { Authorization: `Bearer ${token}` },
      });

      if (res.data?.success) {
        const rows = res.data.data || [];
        const completedRows = rows.filter((i) => i.donation_status === "completed");
        const pendingRows = rows.filter((i) => i.donation_status === "requested");

        setRequests(rows);
        setReceivedItems(completedRows);

        setStats({
          requestsMade: rows.length,
          itemsReceived: completedRows.length,
          completed: completedRows.length,
          pending: pendingRows.length,
        });
      }
    } catch (err) {
      console.error("Failed to load receiver data:", err);
      setError("Failed to load your data");
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="dd">
      <aside className="dd-sidebar">
        <div className="dd-brand">
          <span className="dd-brandMark" />
          <span className="dd-brandText">warmConnect</span>
        </div>

        <div className="dd-profile">
          <div className="dd-avatar">
            <span className="dd-avatarInner" />
          </div>
          <div className="dd-profileMeta">
            <div className="dd-profileName">{displayName}</div>
            <div className="dd-profileRole">{roleLabel}</div>
          </div>
        </div>

        <nav className="dd-nav">
          <button
            className={`dd-navItem ${activeMenu === "dashboard" ? "isActive" : ""}`}
            onClick={() => setActiveMenu("dashboard")}
          >
            <span className="dd-ico">▦</span>
            Dashboard
          </button>

          <button
            className={`dd-navItem ${activeMenu === "requests" ? "isActive" : ""}`}
            onClick={() => setActiveMenu("requests")}
          >
            <span className="dd-ico">📝</span>
            My Requests
          </button>

          <button
            className={`dd-navItem ${activeMenu === "received" ? "isActive" : ""}`}
            onClick={() => setActiveMenu("received")}
          >
            <span className="dd-ico">📦</span>
            Received Items
          </button>

          <button
            className={`dd-navItem ${activeMenu === "community" ? "isActive" : ""}`}
            onClick={() => setActiveMenu("community")}
          >
            <span className="dd-ico">🌍</span>
            Community
          </button>

          <button
            className={`dd-navItem ${activeMenu === "inbox" ? "isActive" : ""}`}
            onClick={() => setActiveMenu("inbox")}
          >
            <span className="dd-ico">✉</span>
            Inbox
          </button>
        </nav>

        <div className="dd-sidebarBottom">
          <div className="dd-help">
            <span className="dd-helpIco">?</span> Getting Started
          </div>
        </div>
      </aside>

      <main className="dd-main">
        <header className="dd-topbar">
          <div className="dd-topLinks">
            <span className="dd-topLink">Home</span>
            <span className="dd-topLink">Explore</span>
            <span className="dd-topLink">Stories</span>
          </div>

          <div className="dd-topRight">
            <div className="dd-search">
              <span className="dd-searchIco">🔎</span>
              <input placeholder="Search donations..." />
            </div>

            <button className="dd-iconBtn" aria-label="Notifications">🔔</button>
            <button className="dd-iconBtn" aria-label="Settings">⚙️</button>
            <div className="dd-miniAvatar" title={displayName}>
              <span />
            </div>
          </div>
        </header>

        <section className="dd-greet">
          <h1>
            Welcome back, <span className="dd-nameAccent">{displayName}!</span>
          </h1>
          <p>Browse and request donations that help you and your community.</p>
        </section>

        <section className="dd-stats">
          <StatCard label="REQUESTS MADE" value={`${stats.requestsMade}`} sub="Total requests" icon="📝" />
          <StatCard label="ITEMS RECEIVED" value={`${stats.itemsReceived}`} sub="Successfully received" icon="✅" />
          <StatCard label="IN PROGRESS" value={`${stats.pending}`} sub="Awaiting pickup" icon="⏳" />
          <StatCard label="COMPLETED" value={`${stats.completed}`} sub="Community impact" icon="🎉" />
        </section>

        {activeMenu !== "inbox" && (
        <section className="dd-panel">
          <div className="dd-tabs">
            <button className={`dd-tab ${activeTab === "requests" ? "isActive" : ""}`} onClick={() => setActiveTab("requests")}>My Requests</button>
            <button className={`dd-tab ${activeTab === "received" ? "isActive" : ""}`} onClick={() => setActiveTab("received")}>Received Items</button>
            <button className={`dd-tab ${activeTab === "impact" ? "isActive" : ""}`} onClick={() => setActiveTab("impact")}>Impact</button>
            <div className="dd-tabsRight">
              <button className="dd-sortBtn"><span className="dd-sortIco">≡</span> Recent first</button>
            </div>
          </div>

          {error && <div style={{ color: "red", padding: "10px" }}>{error}</div>}

          <div className="dd-listGrid">
            {loading ? (
              <EmptyState title="Loading..." text="Fetching your data..." />
            ) : activeTab === "requests" ? (
              requests.length > 0 ? requests.map((x) => <ItemCard key={x.donation_id} item={x} label={(x.donation_status || "requested").toUpperCase()} chipColor={x.donation_status === "completed" ? "#10b981" : "#60a5fa"} onOpenChat={() => navigate(`/chat/${x.donation_id}`)} />) : <EmptyState title="No requests yet" text="Browse donations and request items." />
            ) : activeTab === "received" ? (
              receivedItems.length > 0 ? receivedItems.map((x) => <ItemCard key={x.donation_id} item={x} label="RECEIVED" chipColor="#10b981" onOpenChat={() => navigate(`/chat/${x.donation_id}`)} />) : <EmptyState title="No received items" text="Request donations to get started." />
            ) : (
              <div style={{ gridColumn: "1 / -1", padding: "40px", textAlign: "center" }}>
                <h3>Community Impact</h3>
                <p>You've requested {stats.requestsMade} items and received {stats.itemsReceived} donations.</p>
              </div>
            )}
          </div>
        </section>
        )}

        {activeMenu === "inbox" && (
          <section className="dd-box">
            <div className="dd-boxHead">
              <div>
                <div className="dd-boxTitle">Inbox</div>
                <div className="dd-boxSub">Your donor conversations and replies</div>
              </div>
            </div>

            <InboxChatPanel
              apiBase={API}
              token={token}
              emptyTitle="No requests yet"
            />
          </section>
        )}
      </main>
    </div>
  );
}

function StatCard({ label, value, sub, icon }) {
  return (
    <div className="dd-statCard">
      <div className="dd-statTop">
        <div className="dd-statLabel">{label}</div>
        <div className="dd-statIcon">{icon}</div>
      </div>
      <div className="dd-statValue">{value}</div>
      <div className="dd-statSub">{sub}</div>
    </div>
  );
}

function ItemCard({ item, label, chipColor, onOpenChat }) {
  return (
    <div className="dd-listCard">
      <div className="dd-listMedia">
        {item.images ? (
          <img
            src={`${API}/${item.images}`}
            alt={item.title}
            onError={(e) => {
              e.target.src = "https://via.placeholder.com/300x200?text=Item";
            }}
          />
        ) : (
          <div style={{ background: "#e5e7eb", height: "100%", display: "flex", alignItems: "center", justifyContent: "center" }}>
            No Image
          </div>
        )}
        <div className="dd-chip" style={{ background: chipColor }}>{label}</div>
      </div>

      <div className="dd-listBody">
        <div className="dd-listTitleRow">
          <div className="dd-listTitle">{item.title}</div>
          <div className="dd-listTime">Recent</div>
        </div>
        <div className="dd-listDesc">{item.description}</div>
        <div className="dd-listFooter">
          <div className="dd-metric"><span className="dd-metricIco">👤</span> {item.donor_name || "Anonymous"}</div>
          <div className="dd-metric"><span className="dd-metricIco">📍</span> {item.pickup_location}</div>

          {item.donation_id && (
            <div className="dd-actions">
              <button className="dd-ctaBtn" onClick={onOpenChat}>Open Chat</button>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}

function EmptyState({ title, text }) {
  return (
    <div className="dd-empty">
      <div className="dd-emptyTitle">{title}</div>
      <div className="dd-emptyText">{text}</div>
    </div>
  );
}
