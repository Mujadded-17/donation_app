import { useEffect, useState } from "react";
import { useNavigate } from "react-router-dom";
import axios from "axios";
import "../styles/mydonations.css";

const API = import.meta.env.VITE_API_BASE_URL || "http://localhost/donation_backend";

export default function MyDonations() {
  const navigate = useNavigate();
  const user = JSON.parse(localStorage.getItem("user") || "null");

  const [donations, setDonations] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [filter, setFilter] = useState("All"); // All, Pending, Approved, Rejected

  useEffect(() => {
    if (!user) {
      navigate("/login");
      return;
    }

    fetchMyDonations();
  }, [user, navigate]);

  const fetchMyDonations = async () => {
    setLoading(true);
    setError("");

    try {
      const res = await axios.get(`${API}/my_donations.php?user_id=${user.user_id}`);

      if (res.data?.success) {
        setDonations(res.data.data || []);
      } else {
        setError(res.data?.message || "Failed to load donations");
      }
    } catch (err) {
      console.error(err);
      setError("Failed to fetch donations. Please ensure the backend is running.");
    } finally {
      setLoading(false);
    }
  };

  const filteredDonations =
    filter === "All"
      ? donations
      : donations.filter((d) => d.status === filter);

  const getStatusBadgeClass = (status) => {
    switch (status) {
      case "Approved":
        return "wc-status-badge wc-status-approved";
      case "Rejected":
        return "wc-status-badge wc-status-rejected";
      case "Pending":
      default:
        return "wc-status-badge wc-status-pending";
    }
  };

  if (!user) {
    return null;
  }

  return (
    <div className="wc-mydonations-container">
      <div className="wc-mydonations-content">
        {/* Header */}
        <div className="wc-mydonations-header">
          <div>
            <h1>My Donations</h1>
            <p className="wc-mydonations-subtitle">
              Track the status of your donation requests
            </p>
          </div>
          <button
            className="wc-btn-add-donation"
            onClick={() => navigate("/post-donation")}
          >
            + Post New Donation
          </button>
        </div>

        {/* Filter Tabs */}
        <div className="wc-filter-tabs">
          {["All", "Pending", "Approved", "Rejected"].map((tab) => (
            <button
              key={tab}
              className={`wc-filter-tab ${filter === tab ? "wc-filter-active" : ""}`}
              onClick={() => setFilter(tab)}
            >
              {tab}
              {tab !== "All" && (
                <span className="wc-filter-count">
                  {donations.filter((d) => d.status === tab).length}
                </span>
              )}
            </button>
          ))}
        </div>

        {/* Loading State */}
        {loading && (
          <div className="wc-loading">
            <div className="wc-spinner"></div>
            <p>Loading your donations...</p>
          </div>
        )}

        {/* Error State */}
        {error && (
          <div className="wc-error-box">
            <p>{error}</p>
            <button className="wc-btn-retry" onClick={fetchMyDonations}>
              Retry
            </button>
          </div>
        )}

        {/* Empty State */}
        {!loading && !error && filteredDonations.length === 0 && (
          <div className="wc-empty-state">
            <div className="wc-empty-icon">📦</div>
            <h3>No donations found</h3>
            <p>
              {filter === "All"
                ? "You haven't posted any donation requests yet."
                : `You don't have any ${filter.toLowerCase()} donations.`}
            </p>
            {filter === "All" && (
              <button
                className="wc-btn-empty-post"
                onClick={() => navigate("/post-donation")}
              >
                Post Your First Donation
              </button>
            )}
          </div>
        )}

        {/* Donations Grid */}
        {!loading && !error && filteredDonations.length > 0 && (
          <div className="wc-donations-grid">
            {filteredDonations.map((donation) => (
              <div key={donation.item_id} className="wc-donation-card">
                {/* Image */}
                {donation.image_url ? (
                  <div className="wc-donation-image">
                    <img
                      src={`${API}/${donation.image_url}`}
                      alt={donation.title}
                      onError={(e) => {
                        e.target.style.display = "none";
                        e.target.parentElement.innerHTML =
                          '<div class="wc-no-image">No Image</div>';
                      }}
                    />
                    <span className={getStatusBadgeClass(donation.status)}>
                      {donation.status}
                    </span>
                  </div>
                ) : (
                  <div className="wc-donation-image">
                    <div className="wc-no-image">No Image</div>
                    <span className={getStatusBadgeClass(donation.status)}>
                      {donation.status}
                    </span>
                  </div>
                )}

                {/* Content */}
                <div className="wc-donation-content">
                  <h3 className="wc-donation-title">{donation.title}</h3>
                  <p className="wc-donation-description">{donation.description}</p>

                  <div className="wc-donation-meta">
                    <div className="wc-meta-item">
                      <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        strokeWidth={1.5}
                        stroke="currentColor"
                      >
                        <path
                          strokeLinecap="round"
                          strokeLinejoin="round"
                          d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"
                        />
                        <path
                          strokeLinecap="round"
                          strokeLinejoin="round"
                          d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"
                        />
                      </svg>
                      <span>{donation.pickup_location}</span>
                    </div>

                    {donation.delivery_available === "1" && (
                      <div className="wc-meta-item wc-delivery-badge">
                        <svg
                          xmlns="http://www.w3.org/2000/svg"
                          fill="none"
                          viewBox="0 0 24 24"
                          strokeWidth={1.5}
                          stroke="currentColor"
                        >
                          <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"
                          />
                        </svg>
                        <span>Delivery</span>
                      </div>
                    )}
                  </div>

                  {donation.created_at && (
                    <div className="wc-donation-date">
                      Posted on {new Date(donation.created_at).toLocaleDateString()}
                    </div>
                  )}
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}
