import { useState } from "react";
import { motion } from "framer-motion";
import { useCart } from "../context/hooks/useCart";
import { useNavigate } from "react-router-dom";
import PopupCheckout from "../components/cart/PopupCheckout";
import CartList from "../components/cart/CartList";
import CartEmpty from "../components/cart/CartEmpty";
import useGetDataProducts from "../hooks/api/useGetDataProducts";
import CartPayment from "../components/cart/CartPayment";
import CartSkeleton from "../components/cart/CartSkeleton";
import ProductError from "../components/product/ProductError";

const Cart = () => {
  const [showCheckout, setShowCheckout] = useState(false);
  const { carts, removeItem, changeQuantity, clearCart } = useCart();
  const { products, isLoading, error, refetch, retryCount, maxRetries } =
    useGetDataProducts();
  const navigate = useNavigate();

  const handleCheckout = () => {
    clearCart();
    setShowCheckout(false);
  };

  const isEmptyCart = carts.length === 0;
  const skeletonCount = isEmptyCart ? 0 : carts.length;

  return (
    <>
      <PopupCheckout
        show={showCheckout}
        onClose={() => setShowCheckout(false)}
        onCheckout={handleCheckout}
      />
      <div className="cart min-h-screen bg-[var(--background-white)] overflow-hidden p-6 pt-26 sm:p-12 sm:pt-26 md:p-10 md:pt-28">
        {isEmptyCart ? (
          <CartEmpty navigateTo={navigate} />
        ) : (
          <div className="flex flex-col justify-start items-start bg-white rounded-3xl shadow-lg">
            <motion.div
              initial={{ opacity: 0, y: 50 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ duration: 0.4, ease: "easeInOut", delay: 0.2 }}
              className="w-full flex flex-col justify-center items-start p-6 sm:p-12 rounded-t-3xl"
            >
              {error ? (
                <ProductError
                  refetch={refetch}
                  isLoading={isLoading}
                  error={error}
                  retryCount={retryCount}
                  maxRetries={maxRetries}
                />
              ) : isLoading ? (
                <div className="w-full flex flex-col items-center justify-center gap-6 sm:gap-8">
                  {Array.from({ length: skeletonCount }).map((_, index) => (
                    <CartSkeleton key={index} />
                  ))}
                </div>
              ) : (
                <CartList
                  products={products}
                  carts={carts}
                  removeItem={removeItem}
                  onChangeQuantity={changeQuantity}
                />
              )}
            </motion.div>

            <div className="w-full flex flex-col justify-center items-end rounded-b-3xl border-t-2 border-gray-200 ">
              <CartPayment
                carts={carts}
                products={products}
                setShowCheckout={setShowCheckout}
                navigateTo={navigate}
              />
            </div>
          </div>
        )}
      </div>
    </>
  );
};

export default Cart;
