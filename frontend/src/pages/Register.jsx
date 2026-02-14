import { useState } from "react";
import axios from "axios";
import { Link, useNavigate } from "react-router-dom";

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
    <div style={{ maxWidth: 420 }}>
      <h1>Register</h1>

      {msg && <p style={{ color: "green" }}>{msg}</p>}
      {error && <p style={{ color: "red" }}>{error}</p>}

      <form onSubmit={submit} style={{ display: "grid", gap: 10 }}>
        <input name="name" placeholder="Full name" value={form.name} onChange={onChange} required />
        <input name="email" placeholder="Email" value={form.email} onChange={onChange} required />
        <input
          name="password"
          type="password"
          placeholder="Password"
          value={form.password}
          onChange={onChange}
          required
        />
        <input name="phone" placeholder="Phone (optional)" value={form.phone} onChange={onChange} />
        <input
          name="address"
          placeholder="Address (optional)"
          value={form.address}
          onChange={onChange}
        />

        <select name="user_type" value={form.user_type} onChange={onChange}>
          <option value="receiver">Receiver</option>
          <option value="donor">Donor</option>
          <option value="admin">Admin</option>
        </select>

        <button type="submit">Create account</button>
      </form>

      <p style={{ marginTop: 12 }}>
        Already have an account? <Link to="/login">Login</Link>
      </p>
    </div>
  );
}
