import { BrowserRouter, Routes, Route } from "react-router-dom";

import Navbar from "./components/Navbar";
import Home from "./pages/Home";
import Login from "./pages/Login";
import Register from "./pages/Register";
import Items from "./pages/Items";
import PostDonation from "./pages/PostDonation";
import MyDonations from "./pages/MyDonations";
import Explore from "./pages/Explore";

export default function App() {
  return (
    <BrowserRouter>
      <Navbar />
      <Routes>
        <Route path="/" element={<Home />} />
        <Route path="/items" element={<Items />} />
        <Route path="/explore" element={<Explore />} />
        <Route path="/login" element={<Login />} />
        <Route path="/register" element={<Register />} />
        <Route path="/post-donation" element={<PostDonation />} />
        <Route path="/my-donations" element={<MyDonations />} />
      </Routes>
    </BrowserRouter>
  );
}
