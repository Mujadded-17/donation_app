import { useMemo, useState } from "react";
import { useLocation, useNavigate } from "react-router-dom";

const API_BASE = import.meta.env.VITE_API_BASE_URL;

export default function ResetPassword() {
  const location = useLocation();
  const navigate = useNavigate();

  const token = useMemo(() => {
    return new URLSearchParams(location.search).get("token") || "";
  }, [location.search]);

  const [newPassword, setNewPassword] = useState("");
  const [msg, setMsg] = useState("");
  const [loading, setLoading] = useState(false);

  const submit = async (e) => {
    e.preventDefault();
    setMsg("");
    setLoading(true);

    try {
      const res = await fetch(`${API_BASE}/reset_password.php`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ token, new_password: newPassword }),
      });

      const data = await res.json();

      if (!res.ok) {
        setMsg(data.message || "Reset failed.");
        return;
      }

      setMsg(data.message || "Password updated. Redirecting to login...");
      setTimeout(() => navigate("/login"), 1200);
    } catch (err) {
      setMsg("Failed to reset password. Try again.");
    } finally {
      setLoading(false);
    }
  };

  if (!token) {
    return (
      <div style={{ padding: 30, maxWidth: 520, margin: "0 auto" }}>
        <h2>Reset Password</h2>
        <p style={{ color: "crimson", fontWeight: 800 }}>
          Invalid reset link (missing token).
        </p>
      </div>
    );
  }

  return (
    <div style={{ padding: 30, maxWidth: 420, margin: "0 auto" }}>
      <h2>Reset Password</h2>
      <p style={{ opacity: 0.8 }}>Enter your new password.</p>

      <form onSubmit={submit} style={{ marginTop: 16, display: "grid", gap: 10 }}>
        <input
          value={newPassword}
          onChange={(e) => setNewPassword(e.target.value)}
          placeholder="New password (min 6 chars)"
          type="password"
          required
          style={{ padding: 12, borderRadius: 10, border: "1px solid #ddd" }}
        />

        <button
          disabled={loading}
          style={{
            padding: 12,
            borderRadius: 10,
            border: "none",
            background: "#2f6cf6",
            color: "white",
            fontWeight: 800,
            cursor: "pointer",
          }}
        >
          {loading ? "Saving..." : "Update Password"}
        </button>

        {msg && <div style={{ marginTop: 6, fontWeight: 700 }}>{msg}</div>}
      </form>
    </div>
  );
}