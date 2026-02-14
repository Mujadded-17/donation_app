import { useState } from "react";
import axios from "axios";
import { Link, useNavigate } from "react-router-dom";

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
    <div style={{ maxWidth: 420 }}>
      <h1>Login</h1>

      {msg && <p style={{ color: "green" }}>{msg}</p>}
      {error && <p style={{ color: "red" }}>{error}</p>}

      <form onSubmit={submit} style={{ display: "grid", gap: 10 }}>
        <input value={email} onChange={(e) => setEmail(e.target.value)} placeholder="Email" required />
        <input
          value={password}
          onChange={(e) => setPassword(e.target.value)}
          type="password"
          placeholder="Password"
          required
        />
        <button type="submit">Login</button>
      </form>

      <p style={{ marginTop: 12 }}>
        No account? <Link to="/register">Register</Link>
      </p>
    </div>
  );
}
