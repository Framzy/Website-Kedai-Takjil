import { motion } from "framer-motion";
import { formatPrice } from "../../utils/formatPrice";
import { productImages } from "../../assets/images/productImages";
import CartItemQuantityControl from "./CartItemQuantityControl";

const CartItem = ({
  product,
  cart,
  totalPrice,
  onChangeQuantity,
  removeItem,
}) => {
  const imgSrc = productImages[product.imageKey];

  return (
    <motion.div
      initial={{ opacity: 0, y: 50 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ duration: 0.4, ease: "easeInOut", delay: 0.2 }}
      key={cart.product_id}
      className="w-full h-fit even:bg-gray-100 sm:even:bg-white odd:bg-gray-100 rounded-xl"
    >
      <div className="w-full h-full flex flex-col sm:flex-row justify-start items-center p-6 gap-8">
        <div className="w-full h-40 sm:h-auto sm:w-56 aspect-square rounded-xl shadow-lg overflow-hidden">
          <img
            src={imgSrc}
            alt={product.name}
            className="w-full h-full object-cover drop-shadow-lg brightness-90 hover:scale-105 hover:brightness-100 transition-all duration-300"
          />
        </div>
        <div className="w-full sm:w-fit h-full gap-4 flex flex-col justify-start items-left ">
          <div>
            <h2 className="font-bold text-base sm:text-xl text-gray-600">
              {product.name}
            </h2>
            <h2 className="font-bold text-lg sm:text-2xl text-[var(--color-primary)]">
              {formatPrice(totalPrice)}
            </h2>
          </div>
          <CartItemQuantityControl
            onChangeQuantity={onChangeQuantity}
            removeItem={removeItem}
            cart={cart}
          />
        </div>
      </div>
    </motion.div>
  );
};

export default CartItem;
