import { useState, useEffect } from "react";
import axios from "axios";
import "../../styles/donorDashboard.css";

const API = import.meta.env.VITE_API_BASE_URL || "http://localhost/donation_backend";

export default function AdminDashboard() {
  const user = JSON.parse(localStorage.getItem("user") || "null");
  const token = localStorage.getItem("token") || "";

  const [activeMenu, setActiveMenu] = useState("dashboard");
  const [activeTab, setActiveTab] = useState("pending");
  const [pendingItems, setPendingItems] = useState([]);
  const [stats, setStats] = useState({
    totalDonations: 0,
    pendingApproval: 0,
    approved: 0,
    declined: 0,
  });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [actionLoadingId, setActionLoadingId] = useState(0);

  const displayName = user?.name || user?.email?.split("@")?.[0] || "Admin";
  const roleLabel = "ADMIN";

  useEffect(() => {
    fetchPendingItems();
    fetchStats();
  }, []);

  const fetchPendingItems = async () => {
    try {
      const res = await axios.get(`${API}/admin_pending_items.php`, {
        headers: { Authorization: `Bearer ${token}` },
      });
      if (res.data?.success) {
        setPendingItems(res.data.data || []);
      }
    } catch (err) {
      console.error("Failed to load pending items:", err);
      setError("Failed to load pending items");
    }
  };

  const fetchStats = async () => {
    try {
      // Get all items to calculate stats
      const res = await axios.get(`${API}/admin_pending_items.php`, {
        headers: { Authorization: `Bearer ${token}` },
      });
      if (res.data?.success) {
        const allItems = res.data.data || [];
        const stats = {
          totalDonations: allItems.length,
          pendingApproval: allItems.filter((i) => i.status === "pending").length,
          approved: allItems.filter((i) => i.status === "available").length,
          declined: allItems.filter((i) => i.status === "declined").length,
        };
        setStats(stats);
      }
    } catch (err) {
      console.error("Failed to load stats:", err);
    } finally {
      setLoading(false);
    }
  };

  const reviewItem = async (itemId, action) => {
    setActionLoadingId(itemId);
    try {
      const res = await axios.post(
        `${API}/admin_update_item_status.php`,
        { item_id: itemId, action },
        {
          headers: {
            "Content-Type": "application/json",
            Authorization: `Bearer ${token}`,
          },
        }
      );

      if (res.data?.success) {
        await fetchPendingItems();
        await fetchStats();
      } else {
        setError(res.data?.message || "Failed to update item");
      }
    } catch (err) {
      setError("Failed to update item");
    } finally {
      setActionLoadingId(0);
    }
  };

  return (
    <div className="dd">
      {/* LEFT SIDEBAR */}
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
            className={`dd-navItem ${activeMenu === "reviews" ? "isActive" : ""}`}
            onClick={() => setActiveMenu("reviews")}
          >
            <span className="dd-ico">✓</span>
            Reviews
          </button>

          <button
            className={`dd-navItem ${activeMenu === "users" ? "isActive" : ""}`}
            onClick={() => setActiveMenu("users")}
          >
            <span className="dd-ico">👥</span>
            Users
          </button>

          <button
            className={`dd-navItem ${activeMenu === "analytics" ? "isActive" : ""}`}
            onClick={() => setActiveMenu("analytics")}
          >
            <span className="dd-ico">📊</span>
            Analytics
          </button>
        </nav>

        <div className="dd-sidebarBottom">
          <div className="dd-help">
            <span className="dd-helpIco">?</span> Admin Guide
          </div>
        </div>
      </aside>

      {/* MAIN */}
      <main className="dd-main">
        {/* TOP BAR */}
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

            <button className="dd-iconBtn" aria-label="Notifications">
              🔔
            </button>
            <button className="dd-iconBtn" aria-label="Settings">
              ⚙️
            </button>
            <div className="dd-miniAvatar" title={displayName}>
              <span />
            </div>
          </div>
        </header>

        {/* GREETING */}
        <section className="dd-greet">
          <h1>
            Welcome back, <span className="dd-nameAccent">{displayName}!</span>
          </h1>
          <p>
            Monitor and approve donations to keep <span className="dd-accent">warmConnect</span> community safe.
          </p>
        </section>

        {/* STATS */}
        <section className="dd-stats">
          <StatCard
            label="TOTAL DONATIONS"
            value={`${stats.totalDonations}`}
            sub="All submissions"
            icon="📦"
          />
          <StatCard
            label="PENDING APPROVAL"
            value={`${stats.pendingApproval}`}
            sub="Awaiting review"
            icon="⏳"
          />
          <StatCard
            label="APPROVED"
            value={`${stats.approved}`}
            sub="Live donations"
            icon="✅"
          />
          <StatCard
            label="DECLINED"
            value={`${stats.declined}`}
            sub="Rejected"
            icon="❌"
          />
        </section>

        {/* CONTENT CARD */}
        <section className="dd-panel">
          {/* TABS */}
          <div className="dd-tabs">
            <button
              className={`dd-tab ${activeTab === "pending" ? "isActive" : ""}`}
              onClick={() => setActiveTab("pending")}
            >
              Pending Review
            </button>
            <button
              className={`dd-tab ${activeTab === "approved" ? "isActive" : ""}`}
              onClick={() => setActiveTab("approved")}
            >
              Approved
            </button>
            <button
              className={`dd-tab ${activeTab === "declined" ? "isActive" : ""}`}
              onClick={() => setActiveTab("declined")}
            >
              Declined
            </button>

            <div className="dd-tabsRight">
              <button className="dd-sortBtn">
                <span className="dd-sortIco">≡</span> Recent first
              </button>
            </div>
          </div>

          {error && <div style={{ color: "red", padding: "10px" }}>{error}</div>}

          {/* LISTINGS GRID */}
          <div className="dd-listGrid">
            {loading ? (
              <EmptyState title="Loading..." text="Fetching pending items..." />
            ) : activeTab === "pending" ? (
              pendingItems.filter((x) => x.status === "pending").length > 0 ? (
                pendingItems
                  .filter((x) => x.status === "pending")
                  .map((x) => (
                    <AdminItemCard
                      key={x.item_id}
                      item={x}
                      onApprove={() => reviewItem(x.item_id, "approve")}
                      onDecline={() => reviewItem(x.item_id, "decline")}
                      isLoading={actionLoadingId === x.item_id}
                    />
                  ))
              ) : (
                <EmptyState title="No pending items" text="All donations have been reviewed!" />
              )
            ) : activeTab === "approved" ? (
              pendingItems.filter((x) => x.status === "available").length > 0 ? (
                pendingItems
                  .filter((x) => x.status === "available")
                  .map((x) => <ApprovedItemCard key={x.item_id} item={x} />)
              ) : (
                <EmptyState title="No approved items" text="No approved donations yet." />
              )
            ) : (
              pendingItems.filter((x) => x.status === "declined").length > 0 ? (
                pendingItems
                  .filter((x) => x.status === "declined")
                  .map((x) => <DeclinedItemCard key={x.item_id} item={x} />)
              ) : (
                <EmptyState title="No declined items" text="No declined donations yet." />
              )
            )}
          </div>
        </section>
      </main>
    </div>
  );
}

/* ---------- STAT CARD ---------- */
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

/* ---------- ADMIN ITEM CARD ---------- */
function AdminItemCard({ item, onApprove, onDecline, isLoading }) {
  return (
    <div className="dd-listCard">
      <div className="dd-listMedia">
        {item.images ? (
          <img
            src={`${API}/${item.images}`}
            alt={item.title}
            onError={(e) => {
              e.target.src = "https://via.placeholder.com/300x200?text=No+Image";
            }}
          />
        ) : (
          <div style={{ background: "#e5e7eb", height: "100%", display: "flex", alignItems: "center", justifyContent: "center" }}>
            No Image
          </div>
        )}
        <div className="dd-chip" style={{ background: "#fbbf24" }}>
          PENDING
        </div>
      </div>

      <div className="dd-listBody">
        <div className="dd-listTitleRow">
          <div className="dd-listTitle">{item.title}</div>
          <div className="dd-listTime">Submitted recently</div>
        </div>

        <div className="dd-listDesc">{item.description}</div>

        <div className="dd-listFooter">
          <div className="dd-metric">
            <span className="dd-metricIco">👤</span> {item.donor_name || "Anonymous"}
          </div>
          <div className="dd-metric">
            <span className="dd-metricIco">📍</span> {item.pickup_location}
          </div>

          <div className="dd-actions" style={{ gap: "8px" }}>
            <button
              style={{
                background: "#10b981",
                color: "white",
                padding: "6px 12px",
                border: "none",
                borderRadius: "6px",
                cursor: isLoading ? "not-allowed" : "pointer",
                opacity: isLoading ? 0.6 : 1,
              }}
              onClick={onApprove}
              disabled={isLoading}
            >
              {isLoading ? "..." : "Approve"}
            </button>
            <button
              style={{
                background: "#ef4444",
                color: "white",
                padding: "6px 12px",
                border: "none",
                borderRadius: "6px",
                cursor: isLoading ? "not-allowed" : "pointer",
                opacity: isLoading ? 0.6 : 1,
              }}
              onClick={onDecline}
              disabled={isLoading}
            >
              {isLoading ? "..." : "Decline"}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}

/* ---------- APPROVED ITEM CARD ---------- */
function ApprovedItemCard({ item }) {
  return (
    <div className="dd-listCard">
      <div className="dd-listMedia">
        {item.images ? (
          <img
            src={`${API}/${item.images}`}
            alt={item.title}
            onError={(e) => {
              e.target.src = "https://via.placeholder.com/300x200?text=No+Image";
            }}
          />
        ) : (
          <div style={{ background: "#e5e7eb", height: "100%", display: "flex", alignItems: "center", justifyContent: "center" }}>
            No Image
          </div>
        )}
        <div className="dd-chip" style={{ background: "#10b981" }}>
          APPROVED
        </div>
      </div>

      <div className="dd-listBody">
        <div className="dd-listTitleRow">
          <div className="dd-listTitle">{item.title}</div>
          <div className="dd-listTime">Approved</div>
        </div>

        <div className="dd-listDesc">{item.description}</div>

        <div className="dd-listFooter">
          <div className="dd-metric">
            <span className="dd-metricIco">👤</span> {item.donor_name || "Anonymous"}
          </div>
          <div className="dd-metric">
            <span className="dd-metricIco">📍</span> {item.pickup_location}
          </div>
        </div>
      </div>
    </div>
  );
}

/* ---------- DECLINED ITEM CARD ---------- */
function DeclinedItemCard({ item }) {
  return (
    <div className="dd-listCard">
      <div className="dd-listMedia">
        {item.images ? (
          <img
            src={`${API}/${item.images}`}
            alt={item.title}
            onError={(e) => {
              e.target.src = "https://via.placeholder.com/300x200?text=No+Image";
            }}
          />
        ) : (
          <div style={{ background: "#e5e7eb", height: "100%", display: "flex", alignItems: "center", justifyContent: "center" }}>
            No Image
          </div>
        )}
        <div className="dd-chip" style={{ background: "#ef4444" }}>
          DECLINED
        </div>
      </div>

      <div className="dd-listBody">
        <div className="dd-listTitleRow">
          <div className="dd-listTitle">{item.title}</div>
          <div className="dd-listTime">Declined</div>
        </div>

        <div className="dd-listDesc">{item.description}</div>

        <div className="dd-listFooter">
          <div className="dd-metric">
            <span className="dd-metricIco">👤</span> {item.donor_name || "Anonymous"}
          </div>
          <div className="dd-metric">
            <span className="dd-metricIco">📍</span> {item.pickup_location}
          </div>
        </div>
      </div>
    </div>
  );
}

/* ---------- EMPTY STATE ---------- */
function EmptyState({ title, text }) {
  return (
    <div className="dd-empty">
      <div className="dd-emptyTitle">{title}</div>
      <div className="dd-emptyText">{text}</div>
    </div>
  );
}
