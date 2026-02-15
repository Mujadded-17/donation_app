import { useState } from "react";
import axios from "axios";
import { Link, useNavigate } from "react-router-dom";
import "../styles/Login.css";

const API = import.meta.env.VITE_API_BASE_URL || "http://localhost/donation_backend";

export default function Register() {
  const nav = useNavigate();
  const [form, setForm] = useState({
    name: "",
    email: "",
    password: "",
    phone: "",
    address: "",
    user_type: "receiver",
  });

  const [msg, setMsg] = useState("");
  const [error, setError] = useState("");

  const onChange = (e) => setForm((p) => ({ ...p, [e.target.name]: e.target.value }));

  const submit = async (e) => {
    e.preventDefault();
    setMsg("");
    setError("");

    try {
      const res = await axios.post(`${API}/auth_register.php`, form, {
        headers: { "Content-Type": "application/json" },
      });

      if (res.data?.success) {
        setMsg("✅ Registered! Now login.");
        setTimeout(() => nav("/login"), 600);
      } else {
        setError(res.data?.message || "Registration failed");
      }
    } catch (err) {
      console.log(err);
      setError("API error. Check XAMPP + backend URL.");
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
            Create your account to share items and support your neighbors.
          </p>
        </div>

        <div className="auth-panel auth-panel-right">
          <div className="auth-form-wrap">
            <div className="auth-avatar" aria-hidden="true" />
            <div className="auth-header">
              <p className="auth-subtitle">Register below to get started.</p>
            </div>

            {msg && <div className="message success-message">{msg}</div>}
            {error && <div className="message error-message">{error}</div>}

            <form onSubmit={submit} className="auth-form">
              <div className="form-group">
                <input
                  id="name"
                  type="text"
                  name="name"
                  placeholder="Full Name"
                  value={form.name}
                  onChange={onChange}
                  required
                />
              </div>

              <div className="form-group input-with-icon">
                <span className="input-icon" aria-hidden="true">@</span>
                <input
                  id="email"
                  type="email"
                  name="email"
                  placeholder="E-mail Address"
                  value={form.email}
                  onChange={onChange}
                  required
                />
              </div>

              <div className="form-group input-with-icon">
                <span className="input-icon" aria-hidden="true">*</span>
                <input
                  id="password"
                  type="password"
                  name="password"
                  placeholder="Your Password"
                  value={form.password}
                  onChange={onChange}
                  required
                />
              </div>

              <div className="form-group">
                <input
                  id="phone"
                  type="tel"
                  name="phone"
                  placeholder="Phone (Optional)"
                  value={form.phone}
                  onChange={onChange}
                />
              </div>

              <div className="form-group">
                <input
                  id="address"
                  type="text"
                  name="address"
                  placeholder="Address (Optional)"
                  value={form.address}
                  onChange={onChange}
                />
              </div>

              <div className="form-group">
                <select
                  id="user_type"
                  name="user_type"
                  value={form.user_type}
                  onChange={onChange}
                >
                  <option value="receiver">Receiver</option>
                  <option value="donor">Donor</option>
                  <option value="admin">Admin</option>
                </select>
              </div>

              <button type="submit" className="btn-primary">
                Register
              </button>
            </form>

            <div className="auth-footer">
              <p>
                Already have an account? <Link to="/login">Login</Link> here.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}