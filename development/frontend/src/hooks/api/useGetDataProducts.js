import { useCallback, useEffect, useState } from "react";
import { logger } from "../../utils/logger";

const API_BASE_URL = import.meta.env.VITE_API_URL;
const MAX_RETRIES = 3;
const REQUEST_TIMEOUT = 10000;
const MIN_LOADING_TIME = 1000;

const wait = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

function useGetDataProducts() {
  const [products, setProducts] = useState([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(null);
  const [requestKey, setRequestKey] = useState(0);
  const [retryCount, setRetryCount] = useState(0);

  const refetch = useCallback(() => {
    if (retryCount >= MAX_RETRIES) {
      setRetryCount(0);
    } else {
      setRetryCount((prev) => prev + 1);
    }

    setRequestKey((prev) => prev + 1);
  }, [retryCount]);

  useEffect(() => {
    const controller = new AbortController();
    let cancelled = false;
    let timedOut = false;

    async function fetchProducts() {
      const startTime = Date.now();

      const timeoutId = setTimeout(() => {
        timedOut = true;
        controller.abort();
      }, REQUEST_TIMEOUT);

      try {
        setIsLoading(true);

        if (!API_BASE_URL) {
          const err = new Error("Konfigurasi URL API belum tersedia.");
          err.type = "configuration";
          throw err;
        }

        const response = await fetch(`${API_BASE_URL}/products`, {
          signal: controller.signal,
        });

        if (!response.ok) {
          const err = new Error(
            `Server memberikan respons HTTP ${response.status}.`,
          );
          err.status = response.status;
          throw err;
        }

        const result = await response.json();
        logger.log("Berhasil mengambil produk dari API:", result);

        if (!Array.isArray(result.data)) {
          throw new Error("Format respons API tidak valid.");
        }

        const normalizedProducts = result.data.map((product) => {
          const soldValue = Number(product.sold);

          return {
            ...product,
            imageKey: product.image_key,
            sold:
              product.sold !== null &&
              product.sold !== undefined &&
              product.sold !== "" &&
              Number.isFinite(soldValue) &&
              soldValue >= 0
                ? soldValue
                : 0,
          };
        });

        if (cancelled) return;

        setProducts(normalizedProducts);
        setError(null);
        setRetryCount(0);
      } catch (err) {
        if (cancelled) return;

        if (timedOut) {
          setError({
            message:
              "Server tidak merespons dalam 10 detik. Silakan coba lagi.",
            type: "timeout",
            status: null,
          });
          return;
        }

        if (err.name === "AbortError") return;

        logger.error("Gagal mengambil produk dari API:", err);

        let type = "server";

        if (err.type === "configuration") {
          type = "configuration";
        } else if (err instanceof TypeError) {
          type = "connection";
        } else if (err.status === 404) {
          type = "not-found";
        } else if (err.status >= 500) {
          type = "server";
        } else if (err.status >= 400) {
          type = "request";
        }

        setError({
          message: err.message || "Terjadi kesalahan.",
          type,
          status: err.status ?? null,
        });
      } finally {
        clearTimeout(timeoutId);

        const elapsed = Date.now() - startTime;
        const remaining = MIN_LOADING_TIME - elapsed;

        if (remaining > 0) {
          await wait(remaining);
        }
        if (!cancelled) {
          setIsLoading(false);
        }
      }
    }

    fetchProducts();

    return () => {
      cancelled = true;
      clearTimeoutForCleanup();
    };

    function clearTimeoutForCleanup() {
      controller.abort();
    }
  }, [requestKey]);

  return {
    products,
    isLoading,
    error,
    refetch,
    retryCount,
    maxRetries: MAX_RETRIES,
  };
}

export default useGetDataProducts;
