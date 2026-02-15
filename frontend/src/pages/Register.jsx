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
      const res = await axios.post(${API}/auth_register.php, form, {
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
      <div className="auth-card">
        <div className="auth-header">
          <h1>Create Account</h1>
          <p className="auth-subtitle">Join our community</p>
        </div>

        {msg && <div className="message success-message">{msg}</div>}
        {error && <div className="message error-message">{error}</div>}

        <form onSubmit={submit} className="auth-form">
          <div className="form-group">
            <label htmlFor="name">Full Name</label>
            <input
              id="name"
              type="text"
              name="name"
              placeholder="John Doe"
              value={form.name}
              onChange={onChange}
              required
            />
          </div>

          <div className="form-group">
            <label htmlFor="email">Email Address</label>
            <input
              id="email"
              type="email"
              name="email"
              placeholder="you@example.com"
              value={form.email}
              onChange={onChange}
              required
            />
          </div>

          <div className="form-group">
            <label htmlFor="password">Password</label>
            <input
              id="password"
              type="password"
              name="password"
              placeholder="Create a strong password"
              value={form.password}
              onChange={onChange}
              required
            />
          </div>

          <div className="form-group">
            <label htmlFor="phone">Phone (Optional)</label>
            <input
              id="phone"
              type="tel"
              name="phone"
              placeholder="Your phone number"
              value={form.phone}
              onChange={onChange}
            />
          </div>

          <div className="form-group">
            <label htmlFor="address">Address (Optional)</label>
            <input
              id="address"
              type="text"
              name="address"
              placeholder="Your address"
              value={form.address}
              onChange={onChange}
            />
          </div>

          <div className="form-group">
            <label htmlFor="user_type">Account Type</label>
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
            Create Account
          </button>
        </form>

        <div className="auth-footer">
          <p>
            Already have an account? <Link to="/login">Sign in</Link>
          </p>
        </div>
      </div>
    </div>
  );
}