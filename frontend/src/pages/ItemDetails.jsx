import { useEffect, useState } from "react";
import { useParams, useNavigate } from "react-router-dom";
import axios from "axios";
import "../styles/itemDetails.css";

const API = import.meta.env.VITE_API_BASE_URL || "http://localhost/donation_backend";

export default function ItemDetails() {
  const { id } = useParams();
  const navigate = useNavigate();

  const user = JSON.parse(localStorage.getItem("user") || "null");
  const token = localStorage.getItem("token") || "";

  const [item, setItem] = useState(null);
  const [loading, setLoading] = useState(true);
  const [err, setErr] = useState("");
  const [requesting, setRequesting] = useState(false);
  const [successMsg, setSuccessMsg] = useState("");

  useEffect(() => {
    const loadItem = async () => {
      setLoading(true);
      setErr("");
      setSuccessMsg("");

      try {
        const res = await axios.get(`${API}/item_detail.php?id=${id}`);

        if (!res.data?.success) {
          throw new Error(res.data?.message || "Failed to load item");
        }

        setItem(res.data.item);
      } catch (e) {
        setErr(e.message || "Failed to load item");
      } finally {
        setLoading(false);
      }
    };

    loadItem();
  }, [id]);

  const handleRequest = async () => {
    if (!user) {
      navigate("/login");
      return;
    }

    if (user.user_type !== "receiver") {
      setErr("Only receivers can request items.");
      return;
    }

    if (!token) {
      setErr("You must be logged in to request an item.");
      return;
    }

    try {
      setRequesting(true);
      setErr("");
      setSuccessMsg("");

      const res = await axios.post(
        `${API}/request_item.php`,
        { item_id: item.item_id },
        {
          headers: {
            "Content-Type": "application/json",
            Authorization: `Bearer ${token}`,
          },
        }
      );

      if (!res.data?.success) {
        throw new Error(res.data?.message || "Request failed");
      }

      setSuccessMsg("Request sent successfully!");
    } catch (e) {
      const backendMessage = e?.response?.data?.message;
      setErr(backendMessage || e.message || "Request failed");
    } finally {
      setRequesting(false);
    }
  };

  if (loading) {
    return <div className="item-loading">Loading...</div>;
  }

  if (err && !item) {
    return (
      <div className="item-error">
        <p>{err}</p>
        <button onClick={() => navigate(-1)}>Go Back</button>
      </div>
    );
  }

  const imageUrl = item?.images ? `${API}/${item.images}` : "";

  return (
    <div className="item-container">
      <div className="item-card">
        <div className="item-image-section">
          {item?.images ? (
            <img
              src={imageUrl}
              alt={item.title}
              onError={(e) => {
                e.target.src = "https://via.placeholder.com/500x350?text=No+Image";
              }}
            />
          ) : (
            <div className="no-image">No Image</div>
          )}
        </div>

        <div className="item-info-section">
          <h2>{item?.title}</h2>
          <p className="item-description">{item?.description}</p>

          <div className="item-meta">
            <p><strong>Category:</strong> {item?.category_name || "N/A"}</p>
            <p><strong>Donor:</strong> {item?.donor_name || "Anonymous"}</p>
            <p><strong>Location:</strong> {item?.pickup_location || "N/A"}</p>
            <p><strong>Status:</strong> {item?.status}</p>
            <p><strong>Delivery:</strong> {String(item?.delivery_available) === "1" ? "Available" : "Not Available"}</p>
          </div>

          {successMsg && <div className="success-msg">{successMsg}</div>}
          {err && item && <div className="item-error"><p>{err}</p></div>}

          {item?.status === "available" && user?.user_type === "receiver" && (
            <button
              className="btn-request"
              onClick={handleRequest}
              disabled={requesting}
            >
              {requesting ? "Requesting..." : "Request Item"}
            </button>
          )}

          {!user && (
            <button
              className="btn-request"
              onClick={() => navigate("/login")}
            >
              Login to Request
            </button>
          )}

          {item?.status !== "available" && (
            <div className="claimed-badge">This item is not available now</div>
          )}

          <button
            className="btn-back"
            onClick={() => navigate(-1)}
            style={{ marginTop: "12px" }}
          >
            ← Back
          </button>
        </div>
      </div>
    </div>
  );
}