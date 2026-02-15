import { useEffect, useState } from "react";
import axios from "axios";

const API = import.meta.env.VITE_API_BASE_URL || "http://localhost/donation_backend";

export default function Items() {
  const [items, setItems] = useState([]);
  const [error, setError] = useState("");

  useEffect(() => {
    setError("");
    axios
      .get(`${API}/items_list.php`)
      .then((res) => {
        if (res.data?.success) setItems(res.data.data);
        else setError(res.data?.message || "Failed to load items");
      })
      .catch(() => setError("API call failed. Check XAMPP + backend URL."));
  }, []);

  return (
    <div>
      <h1>Items</h1>

      {error && <p style={{ color: "red" }}>{error}</p>}

      {items.length === 0 && !error && <p>Loading...</p>}

      <div style={{ display: "grid", gap: 12 }}>
        {items.map((it) => (
          <div
            key={it.item_id}
            style={{ border: "1px solid #ddd", borderRadius: 12, padding: 12 }}
          >
            <h3 style={{ margin: "0 0 6px" }}>{it.title}</h3>
            <p style={{ margin: "0 0 6px", color: "#444" }}>{it.description}</p>
            <div style={{ fontSize: 14, color: "#333" }}>
              <div><b>Status:</b> {it.status}</div>
              <div><b>Pickup:</b> {it.pickup_location}</div>
              <div><b>Delivery:</b> {it.delivery_available === "1" ? "Yes" : "No"}</div>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}