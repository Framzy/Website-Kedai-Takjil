CREATE OR ALTER PROCEDURE dbo.CreateOrder
    @CustomerName NVARCHAR(255),
    @CustomerPhone NVARCHAR(20),
    @CustomerAddress NVARCHAR(MAX),
    @Items NVARCHAR(MAX)
AS
BEGIN
    SET NOCOUNT ON;
    SET XACT_ABORT ON;

    BEGIN TRY
        BEGIN TRANSACTION;

        DECLARE @OrderItems TABLE (
            product_id BIGINT NOT NULL,
            quantity INT NOT NULL,
            unit_price DECIMAL(10,2) NOT NULL
        );

        INSERT INTO @OrderItems (product_id, quantity, unit_price)
        SELECT
            p.id,
            j.quantity,
            p.price
        FROM OPENJSON(@Items)
        WITH (
            product_id BIGINT '$.product_id',
            quantity INT '$.quantity'
        ) AS j
        INNER JOIN dbo.products AS p
            ON p.id = j.product_id
        WHERE p.is_available = 1
          AND j.quantity >= 1;

        IF (
            SELECT COUNT(*) FROM @OrderItems
        ) <> (
            SELECT COUNT(*)
            FROM OPENJSON(@Items)
        )
        BEGIN
            THROW 50001, 'Satu atau lebih produk tidak tersedia atau jumlah tidak valid.', 1;
        END;

        DECLARE @TotalAmount DECIMAL(12,2);

        SELECT @TotalAmount = SUM(quantity * unit_price)
        FROM @OrderItems;

        INSERT INTO dbo.orders (
            customer_name,
            customer_phone,
            customer_address,
            total_amount,
            status,
            created_at,
            updated_at
        )
        VALUES (
            @CustomerName,
            @CustomerPhone,
            @CustomerAddress,
            @TotalAmount,
            'pending',
            GETDATE(),
            GETDATE()
        );

        DECLARE @OrderId BIGINT = SCOPE_IDENTITY();

        INSERT INTO dbo.order_items (
            order_id,
            product_id,
            quantity,
            unit_price,
            subtotal,
            created_at,
            updated_at
        )
        SELECT
            @OrderId,
            product_id,
            quantity,
            unit_price,
            quantity * unit_price,
            GETDATE(),
            GETDATE()
        FROM @OrderItems;

        COMMIT TRANSACTION;

        SELECT @OrderId AS order_id;
    END TRY
    BEGIN CATCH
        IF @@TRANCOUNT > 0
            ROLLBACK TRANSACTION;

        THROW;
    END CATCH;
END;
GO
