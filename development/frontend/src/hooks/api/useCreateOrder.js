import { useCallback, useState } from "react";
import { logger } from "../../utils/logger";

const API_BASE_URL = import.meta.env.VITE_API_URL;

function useCreateOrder() {
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState(null);
  const [order, setOrder] = useState(null);

  const createOrder = useCallback(
    async ({ customer_name, customer_phone, customer_address, items }) => {
      try {
        setIsLoading(true);
        setError(null);
        setOrder(null);

        const payload = {
          customer_name,
          customer_phone,
          customer_address,
          items,
        };

        const response = await fetch(`${API_BASE_URL}/orders`, {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
          },
          body: JSON.stringify(payload),
        });

        const result = await response.json();

        if (!response.ok) {
          const message = result.errors
            ? Object.values(result.errors).flat().join(" ")
            : result.message;

          throw new Error(
            message || `Terjadi kesalahan (HTTP ${response.status}).`,
          );
        }

        if (!result.data || typeof result.data !== "object") {
          throw new Error("Format respons API tidak valid.");
        }

        setOrder(result.data);
        logger.log("Berhasil membuat pesanan:", result.data);
        return result.data;
      } catch (err) {
        logger.error("Gagal membuat pesanan:", err);
        setError(err.message || "Terjadi kesalahan saat membuat pesanan.");

        return null;
      } finally {
        setIsLoading(false);
      }
    },
    [],
  );

  return { createOrder, order, isLoading, error };
}

export default useCreateOrder;
