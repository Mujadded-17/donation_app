import DonorDashboard from "../components/dashboards/DonorDashboard";
import ReceiverDashboard from "../components/dashboards/ReceiverDashboard";
import AdminDashboard from "../components/dashboards/AdminDashboard";

export default function Dashboard() {
  const user = JSON.parse(localStorage.getItem("user") || "null");
  if (!user) return <div>Please login first.</div>;

  const role = String(user.user_type || "").trim().toLowerCase();

  if (role === "donor") return <DonorDashboard />;
  if (role === "receiver") return <ReceiverDashboard />;
  if (role === "admin") return <AdminDashboard />;

  return <div>Invalid user role: {role || "missing"}</div>;
}