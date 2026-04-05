import { useState, useEffect } from "react";
import { useNavigate } from "react-router-dom";
import axios from "axios";
import "../styles/explore.css";

const API = import.meta.env.VITE_API_BASE_URL || "http://localhost/donation_backend";
const ADMIN_EMAIL = "silviaadmin@gmail.com";

const categoryImages = {
  Clothes: "https://images.unsplash.com/photo-1489987707025-afc232f7ea0f?w=400&h=300&fit=crop",
  Furniture: "https://images.unsplash.com/photo-1555041469-a586c61ea9bc?w=400&h=300&fit=crop",
  Stationery: "https://images.unsplash.com/photo-1584463489416-d0e0dc2a0fc6?w=400&h=300&fit=crop",
  Gadgets: "https://images.unsplash.com/photo-1498049794561-7780e7231661?w=400&h=300&fit=crop",
  Grains: "https://images.unsplash.com/photo-1586201375761-83865001e31c?w=400&h=300&fit=crop",
  Makeup: "https://images.unsplash.com/photo-1596462502278-27bfdc403348?w=400&h=300&fit=crop",
  Accessories: "https://images.unsplash.com/photo-1535632066927-ab7c9ab60908?w=400&h=300&fit=crop",
  Electronics: "https://images.unsplash.com/photo-1498049794561-7780e7231661?w=400&h=300&fit=crop",
  Books: "https://images.unsplash.com/photo-1495446815901-a7297e633e8d?w=400&h=300&fit=crop",
};

export default function Explore() {
  const navigate = useNavigate();

  const user = JSON.parse(localStorage.getItem("user") || "null");
  const token = localStorage.getItem("token") || "";
  const userRole = String(user?.user_type || user?.role || "").trim().toLowerCase();
  const isAdmin = (user?.email || "").toLowerCase() === ADMIN_EMAIL;

  const [categories, setCategories] = useState([]);
  const [items, setItems] = useState([]);
  const [selectedCategory, setSelectedCategory] = useState(null);
  const [searchQuery, setSearchQuery] = useState("");
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [actionLoadingId, setActionLoadingId] = useState(0);
  const [requestLoadingId, setRequestLoadingId] = useState(0);
  const [reviewTab, setReviewTab] = useState("pending");

  useEffect(() => {
    fetchCategories();
    fetchItems();
  }, []);

  const fetchCategories = async () => {
    try {
      const res = await axios.get(`${API}/categories_list.php`);
      if (res.data?.success) {
        setCategories(res.data.data || []);
      }
    } catch (err) {
      console.error("Failed to load categories:", err);
    }
  };

  const fetchItems = async (categoryId = null, tab = reviewTab) => {
    setLoading(true);
    setError("");

    try {
      let url;
      let config = {};

      if (isAdmin) {
        const endpoint = tab === "pending" ? "admin_pending_items.php" : "admin_approved_items.php";
        url = `${API}/${endpoint}`;
        if (categoryId) {
          url += `?category_id=${categoryId}`;
        }
        config = {
          headers: {
            Authorization: `Bearer ${token}`,
          },
        };
      } else {
        const userId = user?.user_id || 0;
        url = `${API}/explore_items.php?user_id=${userId}`;
        if (categoryId) {
          url += `&category_id=${categoryId}`;
        }
      }

      const res = await axios.get(url, config);
      if (res.data?.success) {
        setItems(res.data.data || []);
      } else {
        setError(res.data?.message || "Failed to load items");
      }
    } catch (err) {
      const backend = err?.response?.data;
      if (backend?.message) {
        setError(backend.message);
      } else {
        setError("Failed to load items");
      }
    } finally {
      setLoading(false);
    }
  };

  const reviewItem = async (itemId, action) => {
    if (!isAdmin) return;

    setActionLoadingId(itemId);
    setError("");

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

      if (!res.data?.success) {
        setError(res.data?.message || "Failed to review item");
      } else {
        // If approved, switch to Approved tab to show the item
        if (action === "approve") {
          setReviewTab("approved");
          await fetchItems(selectedCategory, "approved");
        } else {
          // If declined, refresh pending items
          await fetchItems(selectedCategory, "pending");
        }
      }
    } catch (err) {
      const backend = err?.response?.data;
      setError(backend?.message || "Failed to review item");
    } finally {
      setActionLoadingId(0);
    }
  };

  const requestItem = async (itemId) => {
    if (!user) {
      navigate("/login");
      return;
    }

    if (userRole !== "receiver") {
      setError("Only receiver accounts can request items");
      return;
    }

    if (!token) {
      setError("You must be logged in to request an item");
      return;
    }

    setRequestLoadingId(itemId);
    setError("");

    try {
      const res = await axios.post(
        `${API}/request_item.php`,
        { item_id: itemId },
        {
          headers: {
            "Content-Type": "application/json",
            Authorization: `Bearer ${token}`,
          },
        }
      );

      if (!res.data?.success) {
        setError(res.data?.message || "Request failed");
        return;
      }

      // Remove requested item from this list to avoid duplicate request click.
      setItems((prev) => prev.filter((it) => Number(it.item_id) !== Number(itemId)));

      if (res.data?.donation_id) {
        navigate(`/chat/${res.data.donation_id}`);
      }
    } catch (err) {
      const backend = err?.response?.data;
      setError(backend?.message || "Request failed");
    } finally {
      setRequestLoadingId(0);
    }
  };

  const handleCategoryClick = (category) => {
    setSelectedCategory(category.category_id);
    fetchItems(category.category_id);
  };

  const handleAllCategories = () => {
    setSelectedCategory(null);
    fetchItems();
  };

  const getItemsByCategory = (categoryId) => {
    const numericCategoryId = Number(categoryId);
    return items.filter((item) => Number(item.category_id) === numericCategoryId);
  };

  const filteredCategories = categories.filter((cat) =>
    cat.name.toLowerCase().includes(searchQuery.toLowerCase())
  );

  const selectedCategoryName =
    categories.find((c) => c.category_id === selectedCategory)?.name || "All Categories";

  const openItemDetails = (itemId) => {
    navigate(`/item/${itemId}`);
  };

  return (
    <div className="explore-container">
      <div className="explore-header">
        <h1>{isAdmin ? "Review Donations" : "Explore Needs"}</h1>
        
        {isAdmin && (
          <div className="admin-tabs">
            <button
              className={`tab-btn ${reviewTab === "pending" ? "active" : ""}`}
              onClick={() => {
                setReviewTab("pending");
                setSelectedCategory(null);
                fetchItems(null, "pending");
              }}
            >
              ⏳ Pending Review
            </button>
            <button
              className={`tab-btn ${reviewTab === "approved" ? "active" : ""}`}
              onClick={() => {
                setReviewTab("approved");
                setSelectedCategory(null);
                fetchItems(null, "approved");
              }}
            >
              ✅ Approved Items
            </button>
          </div>
        )}

        <div className="explore-filters">
          <input
            type="text"
            className="explore-search"
            placeholder="Search category..."
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
          />
        </div>
      </div>

      {error && <div className="explore-error">{error}</div>}

      <div className="categories-strip">
        <button
          type="button"
          className={`category-pill ${selectedCategory === null ? "is-active" : ""}`}
          onClick={handleAllCategories}
        >
          <span className="category-pill-icon all-pill">All</span>
          <span className="category-pill-name">All</span>
          <span className="category-pill-count">{items.length} items</span>
        </button>

        {filteredCategories.map((category) => {
          const categoryItems = getItemsByCategory(category.category_id);
          const itemCount = categoryItems.length;

          return (
            <button
              type="button"
              key={category.category_id}
              className={`category-pill ${selectedCategory === category.category_id ? "is-active" : ""}`}
              onClick={() => handleCategoryClick(category)}
            >
              <span
                className="category-pill-icon"
                style={{
                  backgroundImage: `url(${categoryImages[category.name] || categoryImages.Clothes})`,
                }}
              />
              <span className="category-pill-name">{category.name}</span>
              <span className="category-pill-count">{itemCount} items</span>
            </button>
          );
        })}
      </div>

      <div className="items-section">
        <div className="items-header">
          <h2>{selectedCategoryName}</h2>
          {selectedCategory && (
            <button className="btn-back" onClick={handleAllCategories}>
              ← Back to Categories
            </button>
          )}
        </div>

        {loading ? (
          <div className="loading">Loading items...</div>
        ) : items.length === 0 ? (
          <div className="no-items-message">
            {isAdmin ? (reviewTab === "pending" ? "No pending items in this category" : "No approved items in this category") : "No items available in this category"}
          </div>
        ) : (
          <div className="items-grid">
            {items.map((item) => (
              <div
                key={item.item_id}
                className="item-card"
                onClick={() => openItemDetails(item.item_id)}
                style={{ cursor: "pointer" }}
              >
                <div className="item-image">
                  {item.images ? (
                    <img
                      src={`${API}/${item.images}`}
                      alt={item.title}
                      onError={(e) => {
                        e.target.src = "https://via.placeholder.com/300x200?text=No+Image";
                      }}
                    />
                  ) : (
                    <div className="no-image">No Image</div>
                  )}
                </div>

                <div className="item-details">
                  <h3>{item.title}</h3>
                  <p className="item-description">{item.description}</p>

                  <div className="item-meta">
                    <span className="item-donor">By: {item.donor_name || "Anonymous"}</span>
                    <span className="item-location">📍 {item.pickup_location}</span>
                  </div>

                  {String(item.delivery_available) === "1" && (
                    <span className="delivery-badge">🚚 Delivery Available</span>
                  )}

                  {isAdmin ? (
                    <div
                      className="admin-actions"
                      onClick={(e) => e.stopPropagation()}
                    >
                      <span className={reviewTab === "pending" ? "pending-badge" : "approved-badge"}>
                        {reviewTab === "pending" ? "🔔 Pending Review" : "✅ Approved"}
                      </span>

                      {reviewTab === "pending" && (
                        <>
                          <button
                            className="btn-approve"
                            onClick={() => reviewItem(item.item_id, "approve")}
                            disabled={actionLoadingId === item.item_id}
                          >
                            {actionLoadingId === item.item_id ? "Processing..." : "Approve"}
                          </button>

                          <button
                            className="btn-decline"
                            onClick={() => reviewItem(item.item_id, "decline")}
                            disabled={actionLoadingId === item.item_id}
                          >
                            {actionLoadingId === item.item_id ? "Processing..." : "Decline"}
                          </button>
                        </>
                      )}
                    </div>
                  ) : (
                    <button
                      className="btn-request"
                      onClick={(e) => {
                        e.stopPropagation();
                        requestItem(item.item_id);
                      }}
                      disabled={requestLoadingId === item.item_id}
                    >
                      {requestLoadingId === item.item_id ? "Requesting..." : "Request Item"}
                    </button>
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