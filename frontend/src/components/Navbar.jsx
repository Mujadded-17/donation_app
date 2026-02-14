import React from "react";
import "../styles/home.css";
export default function Navbar() {
  return (
    <header className="wc-nav">
      <div className="wc-container wc-nav-inner">
        <div className="wc-brand">
          <LogoMark />
          <span className="wc-brand-text">WarmConnect</span>
        </div>

        <nav className="wc-nav-links">
          <a href="#explore">Explore</a>
          <a href="#how">How it Works</a>
          <a href="#stories">Stories</a>
        </nav>

        <div className="wc-nav-right">
          <div className="wc-search">
            <SearchIcon />
            <input aria-label="Search" placeholder="Search for items near you..." />
          </div>

          <button className="wc-btn wc-btn-ghost">Login</button>
          <button className="wc-btn wc-btn-solid">Post a Donation</button>
        </div>
      </div>
    </header>
  );
}

/* --- Icons used by Navbar --- */

function LogoMark() {
  return (
    <svg width="22" height="22" viewBox="0 0 24 24" aria-hidden="true">
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
    <svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true">
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
