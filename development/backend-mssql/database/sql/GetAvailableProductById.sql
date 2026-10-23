CREATE OR ALTER PROCEDURE dbo.GetAvailableProductById
    @ProductId INT
AS
BEGIN
    SET NOCOUNT ON;

    SELECT
        p.id,
        p.name,
        p.description,
        p.price,
        p.image_key,
        p.is_available,
        p.created_at,
        p.updated_at,
        COALESCE(o.sold, 0) AS sold
    FROM dbo.products AS p
    LEFT JOIN (
        SELECT
            product_id,
            SUM(quantity) AS sold
        FROM dbo.order_items
        GROUP BY product_id
    ) AS o
        ON p.id = o.product_id
    WHERE p.id = @ProductId
        AND p.is_available = 1;
END;
GO
