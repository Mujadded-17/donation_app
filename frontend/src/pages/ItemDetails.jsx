import { useEffect, useState } from "react";
import { useParams, useNavigate } from "react-router-dom";
import axios from "axios";
import "../styles/itemDetails.css";

const API = import.meta.env.VITE_API_BASE_URL || "http://localhost/donation_backend";

export default function ItemDetails() {
  const { id } = useParams();
  const navigate = useNavigate();

  const user = JSON.parse(localStorage.getItem("user") || "null");

  const [item, setItem] = useState(null);
  const [loading, setLoading] = useState(true);
  const [err, setErr] = useState("");
  const [requesting, setRequesting] = useState(false);
  const [successMsg, setSuccessMsg] = useState("");

  useEffect(() => {
    const load = async () => {
      setLoading(true);
      setErr("");

      try {
        const res = await axios.get(`${API}/items_list.php?limit=100`);
        if (!res.data?.success) {
          throw new Error(res.data?.message || "Failed to load item");
        }

        const found = (res.data.data || []).find(
          (x) => String(x.item_id) === String(id)
        );

        if (!found) {
          setErr("Item not found");
        } else {
          setItem(found);
        }
      } catch (e) {
        setErr(e.message || "Failed to load item");
      } finally {
        setLoading(false);
      }
    };

    load();
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

    try {
      setRequesting(true);
      setErr("");

      const res = await axios.post(
        `${API}/receiver_request_item.php`,
        {
          item_id: item.item_id,
          receiver_id: user.user_id,
        },
        { headers: { "Content-Type": "application/json" } }
      );

      if (!res.data?.success) {
        throw new Error(res.data?.message || "Request failed");
      }

      setSuccessMsg("Request sent successfully!");
      setItem({ ...item, status: "claimed" });

    } catch (e) {
      setErr(e.message || "Request failed");
    } finally {
      setRequesting(false);
    }
  };

  if (loading) return <div className="item-loading">Loading...</div>;

  if (err)
    return (
      <div className="item-error">
        <p>{err}</p>
        <button onClick={() => navigate(-1)}>Go Back</button>
      </div>
    );

  return (
    <div className="item-container">
      <div className="item-card">
        <div className="item-image-section">
          {item.image_url ? (
            <img src={item.image_url} alt={item.title} />
          ) : (
            <div className="no-image">No Image</div>
          )}
        </div>

        <div className="item-info-section">
          <h2>{item.title}</h2>
          <p className="item-description">{item.description}</p>

          <div className="item-meta">
            <p><strong>Category:</strong> {item.category_name}</p>
            <p><strong>Location:</strong> {item.pickup_location}</p>
            <p><strong>Status:</strong> {item.status}</p>
          </div>

          {successMsg && <div className="success-msg">{successMsg}</div>}

          {item.status === "available" && user?.user_type === "receiver" && (
            <button
              className="btn-request"
              onClick={handleRequest}
              disabled={requesting}
            >
              {requesting ? "Requesting..." : "Request Item"}
            </button>
          )}

          {item.status !== "available" && (
            <div className="claimed-badge">This item is already claimed</div>
          )}
        </div>
      </div>
    </div>
  );
}