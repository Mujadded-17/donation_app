import { useState } from "react";
import axios from "axios";
import { Link, useNavigate } from "react-router-dom";
import "../styles/Login.css";

const API = import.meta.env.VITE_API_BASE_URL || "http://localhost/donation_backend";

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

      if (res.data?.success) {
        localStorage.setItem("user", JSON.stringify(res.data.user));
        setMsg("✅ Login success!");
        setTimeout(() => nav("/"), 600);
      } else {
        setError(res.data?.message || "Login failed");
      }
    } catch (err) {
      console.log(err);
      setError("API error. Check XAMPP + backend URL.");
    }
  };

  return (
    <div className="auth-container">
      <div className="auth-card">
        <div className="auth-header">
          <h1>Welcome Back</h1>
          <p className="auth-subtitle">Sign in to your account</p>
        </div>

        {msg && <div className="message success-message">{msg}</div>}
        {error && <div className="message error-message">{error}</div>}

        <form onSubmit={submit} className="auth-form">
          <div className="form-group">
            <label htmlFor="email">Email Address</label>
            <input
              id="email"
              type="email"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              placeholder="you@example.com"
              required
            />
          </div>

          <div className="form-group">
            <label htmlFor="password">Password</label>
            <input
              id="password"
              type="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              placeholder="Enter your password"
              required
            />
          </div>

          <button type="submit" className="btn-primary">
            Sign In
          </button>
        </form>

        <div className="auth-footer">
          <p>
            Don't have an account? <Link to="/register">Create one</Link>
          </p>
        </div>
      </div>
    </div>
  );
}