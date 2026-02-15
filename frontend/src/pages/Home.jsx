import React, { useEffect, useMemo, useRef, useState } from "react";
import { useNavigate } from "react-router-dom";
import "../styles/home.css";

export default function Home() {
  const navigate = useNavigate();
  const sliderRef = useRef(null);

  const slides = useMemo(
    () => [
      {
        tag: "BOOKS",
        img: "/images/card-books.jpg",
        title: "Children's Picture Books",
        meta: "Local Community",
        desc: "A set of 10 classic...",
        cta: "Request Item",
      },
      {
        tag: "FOOD",
        img: "/images/card-produce.jpg",
        title: "Fresh Garden Produce",
        meta: "Nearby",
        desc: "Excess harvest from my backyard garden.",
        cta: "Request Item",
      },
    ],
    []
  );

  const [canScroll, setCanScroll] = useState({ left: false, right: true });

  useEffect(() => {
    const el = sliderRef.current;
    if (!el) return;

    const update = () => {
      const left = el.scrollLeft > 8;
      const right = el.scrollLeft + el.clientWidth < el.scrollWidth - 8;
      setCanScroll({ left, right });
    };

    update();
    el.addEventListener("scroll", update);
    window.addEventListener("resize", update);

    return () => {
      el.removeEventListener("scroll", update);
      window.removeEventListener("resize", update);
    };
  }, []);

  const scrollByCards = (dir) => {
    const el = sliderRef.current;
    if (!el) return;
    const delta = Math.min(420, el.clientWidth * 0.9) * dir;
    el.scrollBy({ left: delta, behavior: "smooth" });
  };

  return (
    <div className="wc">
      {/* Hero Section */}
      <section className="wc-hero">
        <div className="wc-container wc-hero-grid">
          <div className="wc-hero-left">
            <h1 className="wc-hero-title">
              Give with love.
              <br />
              <span>Grow with us.</span>
            </h1>

            <p className="wc-hero-sub">
              WarmConnect is a kindness-driven community platform for local giving.
            </p>

            <div className="wc-hero-ctas">
              <button
                className="wc-btn wc-btn-solid wc-btn-lg"
                onClick={() => navigate("/login")}
              >
                Post a Donation
              </button>

              <button
                className="wc-btn wc-btn-outline wc-btn-lg"
                onClick={() => navigate("/items")}
              >
                Explore Needs
              </button>
            </div>
          </div>
        </div>
      </section>

      {/* Latest Shares */}
      <section className="wc-section">
        <div className="wc-container">
          <div className="wc-row-between">
            <h3 className="wc-h3">Latest Shares</h3>

            <div className="wc-arrows">
              <button
                className="wc-arrow"
                onClick={() => scrollByCards(-1)}
                disabled={!canScroll.left}
              >
                ‹
              </button>
              <button
                className="wc-arrow"
                onClick={() => scrollByCards(1)}
                disabled={!canScroll.right}
              >
                ›
              </button>
            </div>
          </div>

          <div className="wc-slider" ref={sliderRef}>
            {slides.map((s) => (
              <div key={s.title} className="wc-listing">
                <div className="wc-listing-title">{s.title}</div>
                <div className="wc-listing-desc">{s.desc}</div>
                <button className="wc-listing-cta">
                  {s.cta}
                </button>
              </div>
            ))}
          </div>
        </div>
      </section>
    </div>
  );
}
