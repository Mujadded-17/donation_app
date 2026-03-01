import { useState } from "react";

const API_BASE = import.meta.env.VITE_API_BASE_URL;

export default function ForgotPassword() {
  const [email, setEmail] = useState("");
  const [msg, setMsg] = useState("");
  const [loading, setLoading] = useState(false);

  const submit = async (e) => {
    e.preventDefault();
    setMsg("");
    setLoading(true);

    try {
      const res = await fetch(`${API_BASE}/forgot_password.php`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ email }),
      });

      const data = await res.json();
      setMsg(data.message || "If the email exists, a reset link has been sent.");
    } catch (err) {
      setMsg("Failed to send reset email. Try again.");
    } finally {
      setLoading(false);
    }
  };

  return (
    <div style={{ padding: 30, maxWidth: 420, margin: "0 auto" }}>
      <h2>Forgot Password</h2>
      <p style={{ opacity: 0.8 }}>Enter your email and we’ll send a reset link.</p>

      <form onSubmit={submit} style={{ marginTop: 16, display: "grid", gap: 10 }}>
        <input
          value={email}
          onChange={(e) => setEmail(e.target.value)}
          placeholder="Email address"
          type="email"
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
          {loading ? "Sending..." : "Send Reset Link"}
        </button>

        {msg && <div style={{ marginTop: 6, fontWeight: 700 }}>{msg}</div>}
      </form>
    </div>
  );
}