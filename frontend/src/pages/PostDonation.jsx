import { useState, useEffect } from "react";
import { useNavigate } from "react-router-dom";
import axios from "axios";
import "../styles/postdonation.css";

const API = import.meta.env.VITE_API_BASE_URL || "http://localhost/donation_backend";

export default function PostDonation() {
  const navigate = useNavigate();
  const user = JSON.parse(localStorage.getItem("user") || "null");

  const [itemName, setItemName] = useState("");
  const [description, setDescription] = useState("");
  const [image, setImage] = useState(null);
  const [imagePreview, setImagePreview] = useState(null);
  const [pickupLocation, setPickupLocation] = useState("");
  const [deliveryAvailable, setDeliveryAvailable] = useState(false);
  const [categoryId, setCategoryId] = useState("");
  const [categories, setCategories] = useState([]);
  
  const [message, setMessage] = useState("");
  const [error, setError] = useState("");
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    fetchCategories();
  }, []);

  const fetchCategories = async () => {
    try {
      const res = await axios.get(`${API}/categories_list.php`);
      if (res.data?.success) {
        setCategories(res.data.data || []);
        if (res.data.data.length > 0) {
          setCategoryId(res.data.data[0].category_id.toString());
        }
      }
    } catch (err) {
      console.error("Failed to load categories:", err);
    }
  };

  // Check if user is logged in
  if (!user) {
    return (
      <div className="wc-post-container">
        <div className="wc-post-card">
          <div className="wc-post-header">
            <h1>Login Required</h1>
            <p className="wc-post-subtitle">Please log in to post a donation</p>
          </div>
          <button
            className="wc-btn-post-primary"
            onClick={() => navigate("/login")}
          >
            Go to Login
          </button>
        </div>
      </div>
    );
  }

  const handleImageChange = (e) => {
    const file = e.target.files[0];
    if (file) {
      setImage(file);
      const reader = new FileReader();
      reader.onloadend = () => {
        setImagePreview(reader.result);
      };
      reader.readAsDataURL(file);
    }
  };

 const handleSubmit = async (e) => {
  e.preventDefault();
  setMessage("");
  setError("");

  if (!itemName.trim() || !description.trim() || !pickupLocation.trim()) {
    setError("Please fill in all required fields");
    return;
  }
  if (!categoryId) {
    setError("Please select a category");
    return;
  }
  if (!image) {
    setError("Please upload an image of the item");
    return;
  }

  setLoading(true);

  try {
    const formData = new FormData();
    formData.append("donor_id", user.user_id);                 // ✅ donor_id (matches DB)
    formData.append("title", itemName);
    formData.append("description", description);
    formData.append("pickup_location", pickupLocation);
    formData.append("delivery_available", deliveryAvailable ? "1" : "0");
    formData.append("category_id", categoryId);
    formData.append("image", image);                           // ✅ key must match $_FILES["image"]

    const res = await axios.post(`${API}/items_create.php`, formData);
    console.log("PostDonation response:", res.data);

    if (res.data?.success) {
      setMessage("✅ Donation submitted! It is pending admin review.");

      setItemName("");
      setDescription("");
      setImage(null);
      setImagePreview(null);
      setPickupLocation("");
      setDeliveryAvailable(false);
      setCategoryId(categories.length > 0 ? categories[0].category_id.toString() : "");

      setTimeout(() => {
        navigate("/explore");
      }, 1500);
    } else {
      const backendErr = res.data?.error ? ` - ${res.data.error}` : "";
      setError((res.data?.message || "Failed to submit donation request") + backendErr);
    }
  } catch (err) {
    console.error("PostDonation error:", err);
    const backend = err?.response?.data;
    if (backend?.message || backend?.error) {
      const backendErr = backend?.error ? ` - ${backend.error}` : "";
      setError((backend?.message || "Backend error") + backendErr);
    } else if (err?.message) {
      setError(`Network error: ${err.message}. Check XAMPP and backend URL (${API})`);
    } else {
      setError("Failed to submit. Please ensure the backend is running.");
    }
  } finally {
    setLoading(false);
  }
};

  return (
    <div className="wc-post-container">
      <div className="wc-post-card">
        <div className="wc-post-header">
          <h1>Post a Donation</h1>
          <p className="wc-post-subtitle">
            Share items you would like to donate with the community
          </p>
        </div>

        {message && <div className="wc-message wc-success-message">{message}</div>}
        {error && <div className="wc-message wc-error-message">{error}</div>}

        <form onSubmit={handleSubmit} className="wc-post-form">
          {/* Image Upload */}
          <div className="wc-form-group">
            <label htmlFor="image" className="wc-form-label">
              Item Image <span className="wc-required">*</span>
            </label>
            <div className="wc-image-upload-container">
              {imagePreview ? (
                <div className="wc-image-preview">
                  <img src={imagePreview} alt="Preview" />
                  <button
                    type="button"
                    className="wc-remove-image"
                    onClick={() => {
                      setImage(null);
                      setImagePreview(null);
                    }}
                  >
                    ✕
                  </button>
                </div>
              ) : (
                <label htmlFor="image" className="wc-image-upload-box">
                  <div className="wc-upload-icon">
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
                        d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"
                      />
                    </svg>
                  </div>
                  <div className="wc-upload-text">
                    <span className="wc-upload-main">Click to upload image</span>
                    <span className="wc-upload-sub">PNG, JPG, GIF up to 10MB</span>
                  </div>
                </label>
              )}
              <input
                type="file"
                id="image"
                accept="image/*"
                onChange={handleImageChange}
                style={{ display: "none" }}
              />
            </div>
          </div>

          {/* Item Name */}
          <div className="wc-form-group">
            <label htmlFor="itemName" className="wc-form-label">
              Item Name <span className="wc-required">*</span>
            </label>
            <input
              id="itemName"
              type="text"
              className="wc-form-input"
              value={itemName}
              onChange={(e) => setItemName(e.target.value)}
              placeholder="e.g., Winter Jacket, Books, etc."
              required
            />
          </div>

          {/* Description */}
          <div className="wc-form-group">
            <label htmlFor="description" className="wc-form-label">
              Description <span className="wc-required">*</span>
            </label>
            <textarea
              id="description"
              className="wc-form-textarea"
              value={description}
              onChange={(e) => setDescription(e.target.value)}
              placeholder="Provide details about the item, its condition, and why you're donating it..."
              rows={5}
              required
            />
          </div>

          {/* Category */}
          <div className="wc-form-group">
            <label htmlFor="category" className="wc-form-label">
              Category <span className="wc-required">*</span>
            </label>
            <select
              id="category"
              className="wc-form-input"
              value={categoryId}
              onChange={(e) => setCategoryId(e.target.value)}
              required
            >
              <option value="">Select a category</option>
              {categories.map((cat) => (
                <option key={cat.category_id} value={cat.category_id}>
                  {cat.name}
                </option>
              ))}
            </select>
          </div>

          {/* Pickup Location */}
          <div className="wc-form-group">
            <label htmlFor="pickupLocation" className="wc-form-label">
              Pickup Location <span className="wc-required">*</span>
            </label>
            <input
              id="pickupLocation"
              type="text"
              className="wc-form-input"
              value={pickupLocation}
              onChange={(e) => setPickupLocation(e.target.value)}
              placeholder="e.g., Brooklyn, NY"
              required
            />
          </div>

          {/* Delivery Available */}
          <div className="wc-form-group wc-checkbox-group">
            <label className="wc-checkbox-label">
              <input
                type="checkbox"
                checked={deliveryAvailable}
                onChange={(e) => setDeliveryAvailable(e.target.checked)}
              />
              <span>Delivery available</span>
            </label>
          </div>

          {/* Info Box */}
          <div className="wc-info-box">
            <div className="wc-info-icon">ℹ️</div>
            <div className="wc-info-text">
              Your donation will be posted immediately and appear on the Explore page 
              for others to see and request. Make sure to select the correct category!
            </div>
          </div>

          {/* Submit Button */}
          <div className="wc-form-actions">
            <button
              type="button"
              className="wc-btn-post-secondary"
              onClick={() => navigate("/")}
              disabled={loading}
            >
              Cancel
            </button>
            <button
              type="submit"
              className="wc-btn-post-primary"
              disabled={loading}
            >
              {loading ? "Submitting..." : "Submit Donation"}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}