import { AnimatePresence, motion } from "framer-motion";
import { useCart } from "../../context/hooks/useCart";
import { formatPrice } from "../../utils/formatPrice";
import { productImages } from "../../assets/images/productImages";

function ProductItem({ product }) {
  const { carts, addToCart, isInCart } = useCart();
  const imgSrc = productImages[product.imageKey];

  const inCart = isInCart(product.id);

  const cartItem = carts.find((cart) => cart.product_id === product.id);
  const totalCart = cartItem?.quantity ?? 0;

  return (
    <>
      <motion.div
        initial={{ opacity: 0, y: 50 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ duration: 0.4, ease: "easeInOut", delay: 0.2 }}
        key={product.id}
        className="w-60 h-auto gap-2 rounded-xl shadow-lg p-3 flex flex-col justify-between"
        data-id={product.id}
      >
        <div className="w-full h-40 rounded-lg overflow-hidden">
          <img
            src={imgSrc}
            alt={product.name}
            className="w-full h-full object-cover drop-shadow-lg brightness-90
                 hover:scale-105 hover:brightness-100
                 transition-all duration-300"
          />
        </div>
        <div className="w-full h-30 gap-1 flex flex-col justify-center">
          <p className="font-bold text-lg">{product.name}</p>
          <p className="font-extrabold text-lg text-[var(--color-primary)]">
            {formatPrice(product.price)}
          </p>
          <div className="relative">
            <button
              onClick={() => addToCart(product.id)}
              className={`
                        w-full py-2 font-semibold rounded-3xl cursor-pointer transition-colors duration-300
                        shadow-[0_4px_0px_0px_rgba(210,210,210,0.8)]
                        ${
                          inCart
                            ? "bg-[var(--color-primary)]/70 text-white hover:bg-[var(--color-secondary)]"
                            : "bg-[var(--color-primary)] text-white hover:bg-[var(--color-secondary)]"
                        }
                      `}
            >
              {inCart ? "Tambah" : "Pesan"}
            </button>
            {inCart && (
              <AnimatePresence mode="popLayout">
                <motion.div
                  key={totalCart}
                  initial={{ opacity: 0, scale: 0.5 }}
                  animate={{ opacity: 1, scale: 1 }}
                  exit={{ opacity: 1, scale: 0.5 }}
                  transition={{
                    duration: 0.4,
                    delay: 0.5,
                    ease: "easeInOut",
                  }}
                  className={`absolute -top-2 -right-2 min-w-6 h-6 px-1 bg-white border-2 border-[var(--color-primary)] rounded-full flex justify-center items-center 
                            transition-colors duration-300`}
                >
                  <motion.span
                    key={totalCart}
                    initial={{ opacity: 0, scale: 0.5, y: -4 }}
                    animate={{ opacity: 1, scale: 1, y: 0 }}
                    exit={{ opacity: 0, scale: 0.5, y: 4 }}
                    transition={{
                      duration: 0.4,
                      delay: 0.5,
                      ease: "easeInOut",
                    }}
                    className="font-bold text-xs text-[var(--color-primary)] leading-none"
                  >
                    {totalCart}
                  </motion.span>
                </motion.div>
              </AnimatePresence>
            )}
          </div>
        </div>
      </motion.div>
    </>
  );
}

export default ProductItem;
