import { useState } from "react";
import { motion } from "framer-motion";
import { useCart } from "../context/hooks/useCart";
import PopupCheckout from "../components/cart/PopupCheckout";
import ProductList from "../components/product/ProductList";

const Products = () => {
  const [showCheckout, setShowCheckout] = useState(false);
  const { clearCart } = useCart();

  const handleCheckout = () => {
    clearCart();
  };

  return (
    <div className="min-h-screen bg-[var(--background-white)] overflow-hidden px-8 pt-20 pb-12 md:p-10 md:pt-24">
      <div className="w-full flex justify-center items-center pb-10">
        <motion.h1
          initial={{ opacity: 0, y: -50 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.4, ease: "easeInOut", delay: 0.2 }}
          className="w-fit font-bold text-center text-2xl sm:text-4xl text-[var(--color-primary)] 
        px-15 py-2 shadow-[0_10px_8px_-10px_rgba(0,0,0,0.3)]"
        >
          Semua Produk
        </motion.h1>
      </div>

      <motion.div
        initial={{ opacity: 0, y: 50 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ duration: 0.4, ease: "easeInOut", delay: 0.2 }}
        className="w-full flex flex-col justify-center items-center bg-[white] rounded-3xl shadow-lg"
      >
        <div className="w-full p-12">
          <ProductList productListOptions="all" />
        </div>
      </motion.div>

      <PopupCheckout
        show={showCheckout}
        onClose={() => setShowCheckout(false)}
        onCheckout={handleCheckout}
      />
    </div>
  );
};

export default Products;
