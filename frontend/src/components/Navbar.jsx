
Navbar.jsx





import React, { useMemo } from "react";
import { Link, useNavigate } from "react-router-dom";
import "../styles/navbar.css";

export default function Navbar() {
  const navigate = useNavigate();

  const user = useMemo(() => {
    try {
      return JSON.parse(localStorage.getItem("user") || "null");
    } catch {
      return null;
    }
  }, []);

  const logout = () => {
    localStorage.removeItem("user");
    localStorage.removeItem("token");
    navigate("/login");
  };

  return (
    <header className="wc-nav">
      <div className="wc-nav-inner wc-nav-container">
        {/* Brand */}
        <Link to="/" className="wc-brand" aria-label="WarmConnect Home">
          <LogoMark />
          <span className="wc-brand-text">WarmConnect</span>
        </Link>

        {/* Links (same as UI) */}
        <nav className="wc-nav-links" aria-label="Primary">
          <a href="#explore">Explore</a>
          <a href="#how">How it Works</a>
          <a href="#stories">Stories</a>
          {user && (
            <Link to="/my-donations" style={{ color: 'var(--wc-orange)' }}>
              My Donations
            </Link>
          )}
        </nav>

        {/* Right */}
        <div className="wc-nav-right">
          <div className="wc-search" role="search">
            <SearchIcon />
            <input
              aria-label="Search"
              placeholder="Search for items near you..."
              autoComplete="off"
            />
          </div>

          {!user ? (
            <>
              <Link to="/login" className="wc-btn wc-btn-ghost">
                Login
              </Link>

              <button
                type="button"
                className="wc-btn wc-btn-solid"
                onClick={() => navigate("/login")}
              >
                Post a Donation
              </button>
            </>
          ) : (
            <>
              <div className="wc-user-chip" title={`${user?.name} (${user?.user_type})`}>
                <div className="wc-user-avatar" aria-hidden="true" />
                <div className="wc-user-text">
                  <div className="wc-user-name">{user?.name || "User"}</div>
                  <div className="wc-user-role">{user?.user_type || "member"}</div>
                </div>
              </div>

              <button type="button" className="wc-btn wc-btn-ghost" onClick={logout}>
                Logout
              </button>

              <button 
                type="button" 
                className="wc-btn wc-btn-solid"
                onClick={() => navigate("/post-donation")}
              >
                Post a Donation
              </button>
            </>
          )}
        </div>
      </div>
    </header>
  );
}

/* Icons */
function LogoMark() {
  return (
    <svg className="wc-logo" viewBox="0 0 24 24" aria-hidden="true">
      <path
        d="M6.2 8.4c2.7-3.5 7.2-4.2 10.3-1.8 1.5 1.2 2.2 2.9 2.2 4.7 0 3.8-3.4 6.9-6.7 8.7-3-1.8-6.7-4.9-6.7-8.7 0-1.1.3-2.1.9-2.9z"
        fill="var(--wc-orange)"
        opacity="0.95"
      />
      <path
        d="M4 13c.8 3.8 4.4 6.7 8 8.8 3.6-2.1 7.2-5 8-8.8"
        fill="none"
        stroke="var(--wc-orange-dark)"
        strokeWidth="1.6"
        strokeLinecap="round"
      />
    </svg>
  );
}

function SearchIcon() {
  return (
    <svg className="wc-search-icon" viewBox="0 0 24 24" aria-hidden="true">
      <path
        d="M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z"
        fill="none"
        stroke="currentColor"
        strokeWidth="2"
      />
      <path
        d="M16.5 16.5 21 21"
        fill="none"
        stroke="currentColor"
        strokeWidth="2"
        strokeLinecap="round"
      />
    </svg>
  );
}