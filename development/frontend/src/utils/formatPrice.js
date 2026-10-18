export const formatPrice = (price) => {
  const priceFormated = Math.round(price);
  return priceFormated.toLocaleString("id-ID", {
    style: "currency",
    currency: "IDR",

    minimumFractionDigits: 2,
  });
};
