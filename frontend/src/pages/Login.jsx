// src/pages/Login.jsx  (your file with the ONLY required change added)
import { useState } from "react";
import axios from "axios";
import { Link, useNavigate } from "react-router-dom";
import "../styles/Login.css";

const API =
  import.meta.env.VITE_API_BASE_URL || "http://localhost/donation_backend";

export default function Login() {
  const nav = useNavigate();
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");

  const [msg, setMsg] = useState("");
  const [error, setError] = useState("");

  const submit = async (e) => {
    e.preventDefault();
    setMsg("");
    setError("");

    try {
      const res = await axios.post(
        `${API}/auth_login.php`,
        { email, password },
        { headers: { "Content-Type": "application/json" } }
      );

      console.log("Login response:", res.data);

      if (res.data?.success) {
        localStorage.setItem("user", JSON.stringify(res.data.user));
        if (res.data?.token) {
          localStorage.setItem("token", res.data.token);
        }

        // ✅ IMPORTANT: update Navbar immediately in SAME TAB
        window.dispatchEvent(new Event("auth-changed"));

        setMsg("✅ Login success!");
        setTimeout(() => nav("/"), 600);
      } else {
        const backendErr = res.data?.error ? ` - ${res.data.error}` : "";
        setError((res.data?.message || "Login failed") + backendErr);
      }
    } catch (err) {
      console.error("Login error:", err);
      const backend = err?.response?.data;
      if (backend?.message || backend?.error) {
        const backendErr = backend?.error ? ` - ${backend.error}` : "";
        setError((backend?.message || "API error") + backendErr);
      } else if (err?.message) {
        setError(
          `Network error: ${err.message}. Check XAMPP and backend URL (${API})`
        );
      } else {
        setError("API error. Check XAMPP + backend URL.");
      }
    }
  };

  return (
    <div className="auth-container">
      <div className="auth-shell">
        <div className="auth-panel auth-panel-left">
          <div className="auth-orb" aria-hidden="true" />
          <div className="auth-brand">
            <span className="auth-brand-dot" aria-hidden="true" />
            <span>WarmConnect</span>
          </div>
          <h2 className="auth-hero-title">Welcome to the WarmConnect Community!</h2>
          <p className="auth-hero-sub">
            Share more, waste less, and build a kinder neighborhood.
          </p>
        </div>

        <div className="auth-panel auth-panel-right">
          <div className="auth-form-wrap">
            <div className="auth-avatar" aria-hidden="true" />
            <div className="auth-header">
              <p className="auth-subtitle">Login below to get started.</p>
            </div>

            {msg && <div className="message success-message">{msg}</div>}
            {error && <div className="message error-message">{error}</div>}

            <form onSubmit={submit} className="auth-form">
              <div className="form-group input-with-icon">
                <span className="input-icon" aria-hidden="true">@</span>
                <input
                  id="email"
                  type="email"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  placeholder="E-mail Address"
                  required
                />
              </div>

              <div className="form-group input-with-icon">
                <span className="input-icon" aria-hidden="true">*</span>
                <input
                  id="password"
                  type="password"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  placeholder="Your Password"
                  required
                />
              </div>

              <label className="auth-checkbox auth-checkbox-inline">
                <input type="checkbox" />
                <span>Keep me logged in</span>
              </label>

              <button type="submit" className="btn-primary">
                Login
              </button>
            </form>

            <div className="auth-footer">
              <p>
                New user? <Link to="/register">Register</Link> here.
              </p>
              <p>
                <Link to="/forgot-password">Forgot password?</Link>
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}