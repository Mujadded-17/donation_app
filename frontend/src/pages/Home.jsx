import { Link } from "react-router-dom";

export default function Home() {
  return (
    <div>
      <h1 style={{ marginBottom: 8 }}>Welcome 👋</h1>
      <p style={{ marginTop: 0, color: "#444" }}>
        A simple donation platform where donors post items and receivers request them.
      </p>

      <div style={{ marginTop: 20, padding: 16, border: "1px solid #ddd", borderRadius: 12 }}>
        <h2 style={{ marginTop: 0 }}>Quick Links</h2>
        <p>Browse available items and test backend connection.</p>
        <Link to="/items" style={{ display: "inline-block", marginTop: 8 }}>
          Go to Items →
        </Link>
      </div>
    </div>
  );
}
