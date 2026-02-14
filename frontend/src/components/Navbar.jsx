import { Link, NavLink, useNavigate } from "react-router-dom";
import { useEffect, useState } from "react";

export default function Navbar() {
  const nav = useNavigate();
  const [user, setUser] = useState(null);

  useEffect(() => {
    const u = localStorage.getItem("user");
    setUser(u ? JSON.parse(u) : null);
  }, []);

  const logout = () => {
    localStorage.removeItem("user");
    setUser(null);
    nav("/login");
  };

  const linkStyle = ({ isActive }) => ({
    textDecoration: "none",
    padding: "8px 12px",
    borderRadius: 8,
    color: isActive ? "white" : "#222",
    background: isActive ? "#111" : "transparent",
  });

  return (
    <div style={{ borderBottom: "1px solid #ddd" }}>
      <div
        style={{
          maxWidth: 900,
          margin: "0 auto",
          padding: 16,
          display: "flex",
          alignItems: "center",
          justifyContent: "space-between",
          gap: 12,
        }}
      >
        <Link to="/" style={{ textDecoration: "none", color: "#111", fontWeight: 700 }}>
          Donation Project
        </Link>

        <div style={{ display: "flex", gap: 8, alignItems: "center" }}>
          <NavLink to="/" style={linkStyle} end>Home</NavLink>
          <NavLink to="/items" style={linkStyle}>Items</NavLink>

          {!user ? (
            <>
              <NavLink to="/login" style={linkStyle}>Login</NavLink>
              <NavLink to="/register" style={linkStyle}>Register</NavLink>
            </>
          ) : (
            <>
              <span style={{ fontSize: 14, color: "#333" }}>
                {user.name} ({user.user_type})
              </span>
              <button onClick={logout}>Logout</button>
            </>
          )}
        </div>
      </div>
    </div>
  );
}
