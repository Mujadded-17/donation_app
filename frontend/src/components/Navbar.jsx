import { NavLink } from "react-router-dom";

export default function Navbar() {
  const user = (() => {
    try {
      return JSON.parse(localStorage.getItem("user") || "null");
    } catch {
      return null;
    }
  })();

  const linkStyle = ({ isActive }) => ({
    textDecoration: "none",
    padding: "8px 12px",
    borderRadius: 10,
    color: isActive ? "#fff" : "#222",
    background: isActive ? "#111" : "transparent",
    fontWeight: 600,
  });

  const logout = () => {
    localStorage.removeItem("user");
    localStorage.removeItem("token");
    window.location.href = "/login";
  };

  return (
    <div style={{ borderBottom: "1px solid #eee" }}>
      <div
        style={{
          maxWidth: 1100,
          margin: "0 auto",
          padding: "14px 16px",
          display: "flex",
          alignItems: "center",
          justifyContent: "space-between",
          gap: 12,
        }}
      >
        <div style={{ fontWeight: 800 }}>WarmConnect</div>

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
              <span style={{ fontSize: 13, color: "#444" }}>
                {user.name} ({user.user_type})
              </span>
              <button onClick={logout} style={{ padding: "8px 12px", borderRadius: 10 }}>
                Logout
              </button>
            </>
          )}
        </div>
      </div>
    </div>
  );
}
