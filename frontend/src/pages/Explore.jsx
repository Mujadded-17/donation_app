import { useState, useEffect } from "react";
import { useNavigate } from "react-router-dom";
import axios from "axios";
import "../styles/explore.css";

const API = import.meta.env.VITE_API_BASE_URL || "http://localhost/donation_backend";

// Category images mapping
const categoryImages = {
  Clothes: "https://images.unsplash.com/photo-1489987707025-afc232f7ea0f?w=400&h=300&fit=crop",
  Furniture: "https://images.unsplash.com/photo-1555041469-a586c61ea9bc?w=400&h=300&fit=crop",
  Stationery: "https://images.unsplash.com/photo-1584463489416-d0e0dc2a0fc6?w=400&h=300&fit=crop",
  Gadgets: "https://images.unsplash.com/photo-1498049794561-7780e7231661?w=400&h=300&fit=crop",
  Grains: "https://images.unsplash.com/photo-1586201375761-83865001e31c?w=400&h=300&fit=crop",
  Makeup: "https://images.unsplash.com/photo-1596462502278-27bfdc403348?w=400&h=300&fit=crop",
  Accessories: "https://images.unsplash.com/photo-1535632066927-ab7c9ab60908?w=400&h=300&fit=crop",
  Electronics: "https://images.unsplash.com/photo-1498049794561-7780e7231661?w=400&h=300&fit=crop",
  Books: "https://images.unsplash.com/photo-1495446815901-a7297e633e8d?w=400&h=300&fit=crop",
};

export default function Explore() {
  const navigate = useNavigate();
  const user = JSON.parse(localStorage.getItem("user") || "null");
  
  const [categories, setCategories] = useState([]);
  const [items, setItems] = useState([]);
  const [selectedCategory, setSelectedCategory] = useState(null);
  const [searchQuery, setSearchQuery] = useState("");
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  useEffect(() => {
    fetchCategories();
    fetchItems();
  }, []);

  const fetchCategories = async () => {
    try {
      const res = await axios.get(`${API}/categories_list.php`);
      if (res.data?.success) {
        setCategories(res.data.data || []);
      }
    } catch (err) {
      console.error("Failed to load categories:", err);
    }
  };

  const fetchItems = async (categoryId = null) => {
    setLoading(true);
    setError("");
    
    try {
      const userId = user?.user_id || 0;
      let url = `${API}/explore_items.php?user_id=${userId}`;
      if (categoryId) {
        url += `&category_id=${categoryId}`;
      }
      
      const res = await axios.get(url);
      if (res.data?.success) {
        setItems(res.data.data || []);
      } else {
        setError(res.data?.message || "Failed to load items");
      }
    } catch (err) {
      console.error("Failed to load items:", err);
      setError("Failed to load items");
    } finally {
      setLoading(false);
    }
  };

  const handleCategoryClick = (category) => {
    setSelectedCategory(category.category_id);
    fetchItems(category.category_id);
  };

  const handleAllCategories = () => {
    setSelectedCategory(null);
    fetchItems();
  };

  const getItemsByCategory = (categoryId) => {
    return items.filter((item) => item.category_id === categoryId);
  };

  const filteredCategories = categories.filter((cat) =>
    cat.name.toLowerCase().includes(searchQuery.toLowerCase())
  );

  return (
    <div className="explore-container">
      <div className="explore-header">
        <h1>Explore Categories</h1>
        
        <div className="explore-filters">
          <input
            type="text"
            className="explore-search"
            placeholder="Search category..."
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
          />
          
          <select
            className="explore-dropdown"
            value={selectedCategory || "all"}
            onChange={(e) => {
              const val = e.target.value;
              if (val === "all") {
                handleAllCategories();
              } else {
                const cat = categories.find((c) => c.category_id === parseInt(val));
                if (cat) handleCategoryClick(cat);
              }
            }}
          >
            <option value="all">All Categories</option>
            {categories.map((cat) => (
              <option key={cat.category_id} value={cat.category_id}>
                {cat.name}
              </option>
            ))}
          </select>
        </div>
      </div>

      {error && <div className="explore-error">{error}</div>}

      <div className="categories-grid">
        {filteredCategories.map((category) => {
          const categoryItems = getItemsByCategory(category.category_id);
          const itemCount = categoryItems.length;
          
          return (
            <div
              key={category.category_id}
              className="category-card"
              onClick={() => handleCategoryClick(category)}
              style={{
                backgroundImage: `linear-gradient(rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.5)), url(${categoryImages[category.name] || categoryImages.Clothes})`,
              }}
            >
              <div className="category-content">
                <h3>{category.name}</h3>
                <p className={itemCount > 0 ? "has-items" : "no-items"}>
                  {itemCount > 0 ? `${itemCount} items` : "No items"}
                </p>
              </div>
            </div>
          );
        })}
      </div>

      {selectedCategory && (
        <div className="items-section">
          <div className="items-header">
            <h2>
              {categories.find((c) => c.category_id === selectedCategory)?.name || "Items"}
            </h2>
            <button className="btn-back" onClick={handleAllCategories}>
              ← Back to Categories
            </button>
          </div>

          {loading ? (
            <div className="loading">Loading items...</div>
          ) : items.length === 0 ? (
            <div className="no-items-message">No items available in this category</div>
          ) : (
            <div className="items-grid">
              {items.map((item) => (
                <div key={item.item_id} className="item-card">
                  <div className="item-image">
                    {item.images ? (
                      <img
                        src={`${API}/${item.images}`}
                        alt={item.title}
                        onError={(e) => {
                          e.target.src = "https://via.placeholder.com/300x200?text=No+Image";
                        }}
                      />
                    ) : (
                      <div className="no-image">No Image</div>
                    )}
                  </div>
                  <div className="item-details">
                    <h3>{item.title}</h3>
                    <p className="item-description">{item.description}</p>
                    <div className="item-meta">
                      <span className="item-donor">By: {item.donor_name || "Anonymous"}</span>
                      <span className="item-location">📍 {item.pickup_location}</span>
                    </div>
                    {item.delivery_available === "1" && (
                      <span className="delivery-badge">🚚 Delivery Available</span>
                    )}
                    <button className="btn-request">Request Item</button>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      )}
    </div>
  );
}
