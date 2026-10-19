function ProductError({ refetch, isLoading, error, retryCount, maxRetries }) {
  const isNotFound = error?.type === "not-found";
  const isConnectionError = error?.type === "connection";
  const reachedLimit = retryCount >= maxRetries;

  const getMessage = () => {
    if (isNotFound) {
      return "Endpoint produk tidak ditemukan. Silakan hubungi pengelola.";
    }

    if (isConnectionError) {
      return "Server belum dapat dihubungi. Tunggu beberapa saat lagi.";
    }

    if (error?.type === "timeout") {
      return "Server membutuhkan waktu terlalu lama untuk merespons. Silakan coba lagi.";
    }

    return "Layanan produk sedang bermasalah. Silakan coba kembali.";
  };

  return (
    <>
      <div className="w-full flex flex-col items-center gap-3 py-8 px-4 bg-[var(--background-white)] rounded-2xl">
        <p className="font-bold text-sm text-[var(--color-primary)] text-center">
          {getMessage()}
        </p>

        {error?.status && (
          <p className="text-xs text-gray-500">
            Kode respons: HTTP {error.status}
          </p>
        )}

        <button
          type="button"
          onClick={() => {
            refetch();
          }}
          disabled={isLoading || isNotFound}
          className="bg-white px-4 py-2 rounded-2xl shadow-[0_2px_0px_0px_rgba(210,210,210,0.8)]
          text-sm font-semibold text-[var(--color-primary)]
          disabled:opacity-50 disabled:cursor-not-allowed
          cursor-pointer hover:text-[var(--color-secondary)]
          hover:bg-[var(--color-primary)] transition-colors duration-300"
        >
          {isLoading ? "Menghubungkan ke server..." : "Coba Lagi"}
        </button>

        {!isLoading && retryCount > 0 && (
          <p className="font-regular text-sm text-[var(--color-primary)] text-center">
            {reachedLimit
              ? " Layanan masih belum tersedia, coba beberapa saat lagi."
              : `Percobaan ulang ${retryCount} dari ${maxRetries} gagal.`}
          </p>
        )}

        {isLoading && (
          <div className="flex items-center gap-2 text-sm text-gray-500">
            <span className="animate-spin h-4 w-4 rounded-full border-2 border-gray-300 border-t-[var(--color-primary)]" />
            Sedang mengambil data produk...
          </div>
        )}
      </div>
    </>
  );
}

export default ProductError;
