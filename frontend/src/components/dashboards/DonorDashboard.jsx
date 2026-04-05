import { useEffect, useMemo, useState } from "react";
import { useNavigate } from "react-router-dom";
import "../../styles/donorDashboard.css";

const API =
  import.meta.env.VITE_API_BASE_URL || "http://localhost/donation_backend";

const EMPTY_DATA = {
  donor: null,
  summary: {
    total_items: 0,
    available_items: 0,
    pending_items: 0,
    claimed_items: 0,
    declined_items: 0,
    completed_donations: 0,
    total_requests_received: 0,
    my_requests_count: 0,
    kindness_spark: 0,
    favorite_category: null,
    latest_post_date: null,
  },
  items: {
    available: [],
    pending: [],
    claimed: [],
    declined: [],
    completed: [],
  },
  requests_received: [],
  my_requests: [],
  notifications: [],
};

export default function DonorDashboard() {
  const navigate = useNavigate();

  const user = useMemo(() => {
    try {
      return JSON.parse(localStorage.getItem("user") || "null");
    } catch {
      return null;
    }
  }, []);

  const [activeMenu, setActiveMenu] = useState("dashboard");
  const [activeTab, setActiveTab] = useState("donations");
  const [donationSubTab, setDonationSubTab] = useState("available");
  const [search, setSearch] = useState("");

  const [data, setData] = useState(EMPTY_DATA);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  const displayName =
    data?.donor?.name || user?.name || user?.email?.split("@")?.[0] || "User";

  const roleLabel = useMemo(() => {
    const t = String(data?.donor?.user_type || user?.user_type || "donor")
      .trim()
      .toLowerCase();

    if (t === "donor") return "KINDNESS ADVOCATE";
    if (t === "receiver") return "COMMUNITY MEMBER";
    return "ADMIN";
  }, [data?.donor?.user_type, user?.user_type]);

  useEffect(() => {
    if (!user?.user_id) {
      setError("Please login first.");
      setLoading(false);
      return;
    }

    fetchDashboard();
  }, [user?.user_id]);

  async function fetchDashboard() {
    try {
      setLoading(true);
      setError("");

      const res = await fetch(`${API}/donor_dashboard.php?user_id=${user.user_id}`);
      const json = await res.json();

      if (!json?.success) {
        throw new Error(json?.message || "Failed to load donor dashboard");
      }

      setData({
        ...EMPTY_DATA,
        ...json,
      });
    } catch (err) {
      console.error("Donor dashboard error:", err);
      setError(err.message || "Failed to load dashboard");
    } finally {
      setLoading(false);
    }
  }

  const notificationCount = data.notifications.length;

  const filteredDonationItems = useMemo(() => {
    let list = [];

    if (donationSubTab === "available") list = data.items.available || [];
    if (donationSubTab === "pending") list = data.items.pending || [];
    if (donationSubTab === "claimed") list = data.items.claimed || [];
    if (donationSubTab === "declined") list = data.items.declined || [];
    if (donationSubTab === "completed") list = data.items.completed || [];

    return filterBySearch(list, search, [
      "title",
      "description",
      "pickup_location",
      "category_name",
      "receiver_name",
      "latest_receiver_name",
    ]);
  }, [data.items, donationSubTab, search]);

  const filteredMyRequests = useMemo(() => {
    return filterBySearch(data.my_requests || [], search, [
      "title",
      "description",
      "pickup_location",
      "category_name",
      "donor_name",
      "status",
    ]);
  }, [data.my_requests, search]);

  const filteredRequestsReceived = useMemo(() => {
    return filterBySearch(data.requests_received || [], search, [
      "title",
      "description",
      "pickup_location",
      "category_name",
      "receiver_name",
      "status",
    ]);
  }, [data.requests_received, search]);

  const filteredNotifications = useMemo(() => {
    return filterBySearch(data.notifications || [], search, ["message", "type"]);
  }, [data.notifications, search]);

  if (loading) {
    return (
      <div className="dd">
        <main className="dd-main">
          <div className="dd-loading">Loading donor dashboard...</div>
        </main>
      </div>
    );
  }

  if (error) {
    return (
      <div className="dd">
        <main className="dd-main">
          <div className="dd-errorBox">
            <h2>Dashboard unavailable</h2>
            <p>{error}</p>
            <button className="dd-ctaBtn" onClick={fetchDashboard}>
              Try Again
            </button>
          </div>
        </main>
      </div>
    );
  }

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
            className="dd-navItem"
            onClick={() => navigate("/post-donation")}
          >
            <span className="dd-ico">✦</span>
            Offer a Gift
          </button>

          <button
            className={`dd-navItem ${activeMenu === "impact" ? "isActive" : ""}`}
            onClick={() => setActiveMenu("impact")}
          >
            <span className="dd-ico">♡</span>
            My Impact
          </button>

          <button
            className={`dd-navItem ${activeMenu === "inbox" ? "isActive" : ""}`}
            onClick={() => setActiveMenu("inbox")}
          >
            <span className="dd-ico">✉</span>
            Inbox
            <span className="dd-badge">{notificationCount}</span>
          </button>
        </nav>

        <div className="dd-sidebarBottom">
          <button
            className="dd-createBtn"
            onClick={() => navigate("/post-donation")}
          >
            <span className="dd-plus">＋</span> Create New
          </button>
          <div className="dd-help">
            <span className="dd-helpIco">?</span> Built from live database data
          </div>
        </div>
      </aside>

      <main className="dd-main">
        <header className="dd-topbar">
          <div className="dd-topLinks">
            <button className="dd-linkBtn" onClick={() => navigate("/")}>
              Home
            </button>
            <button className="dd-linkBtn" onClick={() => navigate("/explore")}>
              Explore
            </button>
            <button className="dd-linkBtn" onClick={fetchDashboard}>
              Refresh
            </button>
          </div>

          <div className="dd-topRight">
            <div className="dd-search">
              <span className="dd-searchIco">🔎</span>
              <input
                placeholder="Search donations, requests, notifications..."
                value={search}
                onChange={(e) => setSearch(e.target.value)}
              />
            </div>

            <button
              className="dd-iconBtn"
              aria-label="Notifications"
              onClick={() => setActiveMenu("inbox")}
            >
              🔔
            </button>

            <div className="dd-miniAvatar" title={displayName}>
              <span />
            </div>
          </div>
        </header>

        <section className="dd-greet">
          <h1>
            Hello <span className="dd-nameAccent">{displayName}!</span>
          </h1>
          <p>
            You currently have <span className="dd-accent">{data.summary.total_items}</span>{" "}
            posted items and{" "}
            <span className="dd-accent">{data.summary.completed_donations}</span>{" "}
            completed donations.
          </p>
        </section>

        <section className="dd-stats">
          <StatCard
            label="WARMTH SHARED"
            value={`${data.summary.total_items} Items`}
            sub={`${data.summary.available_items} active listings`}
            icon="💡"
          />
          <StatCard
            label="NEIGHBORS HELPED"
            value={`${data.summary.completed_donations}`}
            sub={`${data.summary.total_requests_received} total requests received`}
            icon="👥"
          />
          <StatCard
            label="KINDNESS SPARK"
            value={`${data.summary.kindness_spark}`}
            sub={data.summary.favorite_category || "No favorite category yet"}
            icon="✨"
          />
        </section>

        {activeMenu === "dashboard" && (
          <>
            <section className="dd-panel">
              <div className="dd-tabs">
                <button
                  className={`dd-tab ${activeTab === "donations" ? "isActive" : ""}`}
                  onClick={() => setActiveTab("donations")}
                >
                  My Donations
                </button>
                <button
                  className={`dd-tab ${activeTab === "requests" ? "isActive" : ""}`}
                  onClick={() => setActiveTab("requests")}
                >
                  My Requests
                </button>
                <button
                  className={`dd-tab ${activeTab === "community" ? "isActive" : ""}`}
                  onClick={() => setActiveTab("community")}
                >
                  My Community
                </button>

                <div className="dd-tabsRight">
                  <div className="dd-sortBtn">Live data</div>
                </div>
              </div>

              {activeTab === "donations" && (
                <>
                  <div className="dd-subtabs">
                    <button
                      className={`dd-subtab ${donationSubTab === "available" ? "isActive" : ""}`}
                      onClick={() => setDonationSubTab("available")}
                    >
                      Active Listings
                    </button>
                    <button
                      className={`dd-subtab ${donationSubTab === "pending" ? "isActive" : ""}`}
                      onClick={() => setDonationSubTab("pending")}
                    >
                      Pending Review
                    </button>
                    <button
                      className={`dd-subtab ${donationSubTab === "claimed" ? "isActive" : ""}`}
                      onClick={() => setDonationSubTab("claimed")}
                    >
                      Claimed
                    </button>
                    <button
                      className={`dd-subtab ${donationSubTab === "completed" ? "isActive" : ""}`}
                      onClick={() => setDonationSubTab("completed")}
                    >
                      Completed
                    </button>
                    <button
                      className={`dd-subtab ${donationSubTab === "declined" ? "isActive" : ""}`}
                      onClick={() => setDonationSubTab("declined")}
                    >
                      Declined
                    </button>
                  </div>

                  <div className="dd-listGrid">
                    {filteredDonationItems.length === 0 ? (
                      <EmptyState
                        title="No records found"
                        text="There is no matching donation data for this section."
                      />
                    ) : (
                      filteredDonationItems.map((item) => (
                        <DonationItemCard
                          key={`${donationSubTab}-${item.item_id}-${item.donation_id || ""}`}
                          item={item}
                          apiBase={API}
                          onOpen={() => navigate(`/item/${item.item_id}`)}
                        />
                      ))
                    )}
                  </div>
                </>
              )}

              {activeTab === "requests" && (
                <div className="dd-listGrid dd-listGridSingle">
                  {filteredMyRequests.length === 0 ? (
                    <EmptyState
                      title="No requests found"
                      text="This user has not made any item requests yet."
                    />
                  ) : (
                    filteredMyRequests.map((req) => (
                      <RequestCard
                        key={req.donation_id}
                        item={req}
                        apiBase={API}
                        variant="outgoing"
                      />
                    ))
                  )}
                </div>
              )}

              {activeTab === "community" && (
                <div className="dd-communityWrap">
                  <div className="dd-box">
                    <div className="dd-boxHead">
                      <div>
                        <div className="dd-boxTitle">Incoming Requests</div>
                        <div className="dd-boxSub">
                          Live requests made on your posted items
                        </div>
                      </div>
                    </div>

                    <div className="dd-listGrid dd-listGridSingle">
                      {filteredRequestsReceived.length === 0 ? (
                        <EmptyState
                          title="No incoming requests"
                          text="No one has requested your items yet."
                        />
                      ) : (
                        filteredRequestsReceived.slice(0, 6).map((req) => (
                          <RequestCard
                            key={req.donation_id}
                            item={req}
                            apiBase={API}
                            variant="incoming"
                          />
                        ))
                      )}
                    </div>
                  </div>

                  <div className="dd-box">
                    <div className="dd-boxHead">
                      <div>
                        <div className="dd-boxTitle">Recent Notifications</div>
                        <div className="dd-boxSub">
                          Latest updates from your donation activity
                        </div>
                      </div>
                    </div>

                    <div className="dd-noticeList">
                      {filteredNotifications.length === 0 ? (
                        <EmptyState
                          title="No notifications"
                          text="There are no notifications for this account yet."
                        />
                      ) : (
                        filteredNotifications.slice(0, 8).map((note) => (
                          <NotificationRow key={note.notify_id} note={note} />
                        ))
                      )}
                    </div>
                  </div>
                </div>
              )}
            </section>

            <section className="dd-bottomGrid">
              <div className="dd-box">
                <div className="dd-boxHead">
                  <div>
                    <div className="dd-boxTitle">Impact Snapshot</div>
                    <div className="dd-boxSub">
                      Summary built from item, donation, and notification tables
                    </div>
                  </div>
                </div>

                <div className="dd-kpiGrid">
                  <Kpi label="Available Items" value={data.summary.available_items} />
                  <Kpi label="Pending Review" value={data.summary.pending_items} />
                  <Kpi label="Claimed Items" value={data.summary.claimed_items} />
                  <Kpi label="Declined Items" value={data.summary.declined_items} />
                  <Kpi label="Requests Made" value={data.summary.my_requests_count} />
                  <Kpi
                    label="Favorite Category"
                    value={data.summary.favorite_category || "N/A"}
                  />
                </div>
              </div>

              <div className="dd-box">
                <div className="dd-boxHead">
                  <div>
                    <div className="dd-boxTitle">Recent Notifications</div>
                    <div className="dd-boxSub">
                      {data.notifications.length} notification(s) found
                    </div>
                  </div>
                </div>

                <div className="dd-noticeList">
                  {data.notifications.length === 0 ? (
                    <EmptyState
                      title="No notifications yet"
                      text="This section will fill as donation activity grows."
                    />
                  ) : (
                    data.notifications.slice(0, 5).map((note) => (
                      <NotificationRow key={note.notify_id} note={note} />
                    ))
                  )}
                </div>
              </div>
            </section>
          </>
        )}

        {activeMenu === "impact" && (
          <section className="dd-box">
            <div className="dd-boxHead">
              <div>
                <div className="dd-boxTitle">My Impact</div>
                <div className="dd-boxSub">
                  Everything below is calculated from your current database records
                </div>
              </div>
            </div>

            <div className="dd-kpiGrid">
              <Kpi label="Total Posted Items" value={data.summary.total_items} />
              <Kpi label="Completed Donations" value={data.summary.completed_donations} />
              <Kpi label="Requests Received" value={data.summary.total_requests_received} />
              <Kpi label="Kindness Spark" value={data.summary.kindness_spark} />
              <Kpi
                label="Favorite Category"
                value={data.summary.favorite_category || "N/A"}
              />
              <Kpi
                label="Latest Post Date"
                value={formatDate(data.summary.latest_post_date)}
              />
            </div>

            <div className="dd-tableWrap">
              <table className="dd-table">
                <thead>
                  <tr>
                    <th>Metric</th>
                    <th>Value</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>Available items</td>
                    <td>{data.summary.available_items}</td>
                  </tr>
                  <tr>
                    <td>Pending items</td>
                    <td>{data.summary.pending_items}</td>
                  </tr>
                  <tr>
                    <td>Claimed items</td>
                    <td>{data.summary.claimed_items}</td>
                  </tr>
                  <tr>
                    <td>Declined items</td>
                    <td>{data.summary.declined_items}</td>
                  </tr>
                  <tr>
                    <td>Requests made by this user</td>
                    <td>{data.summary.my_requests_count}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </section>
        )}

        {activeMenu === "inbox" && (
          <section className="dd-box">
            <div className="dd-boxHead">
              <div>
                <div className="dd-boxTitle">Inbox</div>
                <div className="dd-boxSub">
                  Notification stream from the notification table
                </div>
              </div>
            </div>

            <div className="dd-noticeList">
              {filteredNotifications.length === 0 ? (
                <EmptyState
                  title="Inbox is empty"
                  text="No notification records were found for this user."
                />
              ) : (
                filteredNotifications.map((note) => (
                  <NotificationRow key={note.notify_id} note={note} />
                ))
              )}
            </div>
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

function Kpi({ label, value }) {
  return (
    <div className="dd-kpi">
      <div className="dd-kpiLabel">{label}</div>
      <div className="dd-kpiValue">{value}</div>
    </div>
  );
}

function DonationItemCard({ item, apiBase, onOpen }) {
  const status = item.donation_status || item.item_status || item.status;

  return (
    <div className="dd-listCard">
      <div className="dd-listMedia">
        {buildImageUrl(apiBase, item.image_path || item.images) ? (
          <img
            src={buildImageUrl(apiBase, item.image_path || item.images)}
            alt={item.title}
          />
        ) : (
          <div className="dd-mediaFallback">No image</div>
        )}
        <span className={`dd-chip dd-status-${normalizeStatusClass(status)}`}>
          {status || "unknown"}
        </span>
      </div>

      <div className="dd-listBody">
        <div className="dd-listTitleRow">
          <div className="dd-listTitle">{item.title}</div>
          <div className="dd-listTime">
            {formatDate(item.request_date || item.post_date)}
          </div>
        </div>

        <div className="dd-inlineMeta">
          {item.category_name && <span className="dd-pill">{item.category_name}</span>}
          {item.pickup_location && <span className="dd-pill">{item.pickup_location}</span>}
          {Number(item.delivery_available) === 1 && (
            <span className="dd-pill">Delivery available</span>
          )}
        </div>

        <div className="dd-listDesc">{item.description || "No description provided."}</div>

        <div className="dd-listFooter">
          {"request_count" in item && (
            <div className="dd-metric">📩 {item.request_count} request(s)</div>
          )}

          {item.receiver_name && (
            <div className="dd-note">👤 Receiver: {item.receiver_name}</div>
          )}

          {item.latest_receiver_name && (
            <div className="dd-note">👤 Latest requester: {item.latest_receiver_name}</div>
          )}

          <div className="dd-actions">
            <button className="dd-ctaBtn" onClick={onOpen}>
              View Item
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}

function RequestCard({ item, apiBase, variant }) {
  const isIncoming = variant === "incoming";

  return (
    <div className="dd-listCard">
      <div className="dd-listMedia">
        {buildImageUrl(apiBase, item.image_path || item.images) ? (
          <img
            src={buildImageUrl(apiBase, item.image_path || item.images)}
            alt={item.title}
          />
        ) : (
          <div className="dd-mediaFallback">No image</div>
        )}
        <span className={`dd-chip dd-status-${normalizeStatusClass(item.status)}`}>
          {item.status}
        </span>
      </div>

      <div className="dd-listBody">
        <div className="dd-listTitleRow">
          <div className="dd-listTitle">{item.title}</div>
          <div className="dd-listTime">{formatDate(item.request_date)}</div>
        </div>

        <div className="dd-inlineMeta">
          {item.category_name && <span className="dd-pill">{item.category_name}</span>}
          {item.pickup_location && <span className="dd-pill">{item.pickup_location}</span>}
        </div>

        <div className="dd-listDesc">{item.description || "No description provided."}</div>

        <div className="dd-listFooter">
          {isIncoming ? (
            <>
              <div className="dd-note">👤 Requested by: {item.receiver_name || "Unknown"}</div>
              {item.receiver_email && <div className="dd-metric">✉ {item.receiver_email}</div>}
            </>
          ) : (
            <>
              <div className="dd-note">🤝 Donor: {item.donor_name || "Unknown"}</div>
              {item.donor_email && <div className="dd-metric">✉ {item.donor_email}</div>}
            </>
          )}
        </div>
      </div>
    </div>
  );
}

function NotificationRow({ note }) {
  return (
    <div className="dd-noticeRow">
      <div className="dd-noticeType">{note.type}</div>
      <div className="dd-noticeMsg">{note.message}</div>
      <div className="dd-noticeTime">{formatDate(note.create_time, true)}</div>
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

function buildImageUrl(apiBase, rawPath) {
  if (!rawPath) return null;
  if (/^https?:\/\//i.test(rawPath)) return rawPath;
  if (rawPath.startsWith("uploads/")) return `${apiBase}/${rawPath}`;
  return `${apiBase}/uploads/${rawPath}`;
}

function normalizeStatusClass(value) {
  return String(value || "unknown").trim().toLowerCase().replace(/\s+/g, "-");
}

function formatDate(value, withTime = false) {
  if (!value) return "N/A";
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return value;

  return withTime
    ? date.toLocaleString()
    : date.toLocaleDateString(undefined, {
        year: "numeric",
        month: "short",
        day: "numeric",
      });
}

function filterBySearch(list, keyword, fields) {
  const q = String(keyword || "").trim().toLowerCase();
  if (!q) return list;

  return list.filter((item) =>
    fields.some((field) =>
      String(item?.[field] ?? "")
        .toLowerCase()
        .includes(q)
    )
  );
}