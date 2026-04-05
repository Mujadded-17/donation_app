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
  const userRole = String(user?.user_type || user?.role || "").trim().toLowerCase();

  const [item, setItem] = useState(null);
  const [loading, setLoading] = useState(true);
  const [err, setErr] = useState("");
  const [requesting, setRequesting] = useState(false);
  const [successMsg, setSuccessMsg] = useState("");
  const [requested, setRequested] = useState(false);
  const [requestedDonationId, setRequestedDonationId] = useState(0);

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

        if (token && userRole === "receiver") {
          try {
            const statusRes = await axios.get(
              `${API}/request_status.php?item_id=${res.data.item.item_id}`,
              {
                headers: {
                  Authorization: `Bearer ${token}`,
                },
              }
            );

            if (statusRes.data?.success && statusRes.data?.requested) {
              setRequested(true);
              setRequestedDonationId(Number(statusRes.data?.donation_id || 0));
            } else {
              setRequested(false);
              setRequestedDonationId(0);
            }
          } catch {
            // Ignore request-status failures; item details should still load.
          }
        }
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

    if (userRole !== "receiver") {
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

      setRequested(true);
      setRequestedDonationId(Number(res.data?.donation_id || 0));
      setSuccessMsg("Request sent successfully!");

      // Keep user on details page and show requested state.
    } catch (e) {
      const backendMessage = e?.response?.data?.message;
      if ((backendMessage || "").toLowerCase().includes("already requested")) {
        setRequested(true);
      }
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

          {item?.status === "available" && userRole === "receiver" && !requested && (
            <button
              className="btn-request"
              onClick={handleRequest}
              disabled={requesting}
            >
              {requesting ? "Requesting..." : "Request Item"}
            </button>
          )}

          {item?.status === "available" && userRole === "receiver" && requested && (
            <>
              <div className="claimed-badge">You already requested this item</div>
              {requestedDonationId > 0 && (
                <button
                  className="btn-request"
                  onClick={() => navigate(`/chat/${requestedDonationId}`)}
                >
                  Open Chat
                </button>
              )}
            </>
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