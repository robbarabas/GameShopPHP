-- Procedure 1: Print all game titles using a cursor
DELIMITER //
CREATE PROCEDURE PrintAllGameTitles()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE g_title VARCHAR(255);
    DECLARE cur CURSOR FOR SELECT title FROM games;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

    OPEN cur;
    read_loop: LOOP
        FETCH cur INTO g_title;
        IF done THEN
            LEAVE read_loop;
        END IF;
        SELECT g_title AS Title;
    END LOOP;
    CLOSE cur;
END;
//
DELIMITER ;

-- Procedure 2: Increase price of all orders by X%
DELIMITER //
CREATE PROCEDURE IncreaseOrderPrices(IN percent DECIMAL(5,2))
BEGIN
    UPDATE Orders SET total_price = total_price * (1 + percent / 100);
END;
//
DELIMITER ;

-- Procedure 3: Add for a user the whole library good  (loop-based insert)

DELIMITER //
CREATE PROCEDURE InsertFullLibrary(IN TARGET_USER INT)
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE g_id INT;

    DECLARE cur CURSOR FOR SELECT game_id FROM games;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

    OPEN cur;

    read_loop: LOOP
        FETCH cur INTO g_id;
        IF done THEN
            LEAVE read_loop;
        END IF;

        INSERT INTO Orders (user_id, game_id, total_price, order_date)
        VALUES (TARGET_USER, g_id, 0.00, NOW());
    END LOOP;

    CLOSE cur;
END;
//
DELIMITER ;
-- Procedure 4: Archive old orders
DELIMITER //
CREATE PROCEDURE DeleteOldOrders(IN cutoffDate DATETIME)
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE orderId INT;
    DECLARE cur CURSOR FOR SELECT order_id FROM Orders WHERE order_date < cutoffDate;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

    OPEN cur;

    read_loop: LOOP
        FETCH cur INTO orderId;
        IF done THEN
            LEAVE read_loop;
        END IF;

        DELETE FROM Orders WHERE order_id = orderId;
    END LOOP;

    CLOSE cur;
END//
DELIMITER ;
DELIMITER //

CREATE PROCEDURE GetAverageRating(IN target_game_id INT, OUT avg_rating DECIMAL(4,2))
BEGIN
    SELECT AVG(rating)
    INTO avg_rating
    FROM reviews
    WHERE game_id = target_game_id;
END //
//
DELIMITER ;

//
DELIMITER ;
-- Function 1: Count number of orders for a user
DELIMITER //
CREATE FUNCTION GetOrderCountByUser(uid INT) RETURNS INT
BEGIN
    DECLARE count INT;
    SELECT COUNT(*) INTO count FROM Orders WHERE user_id = uid;
    RETURN count;
END;
//
DELIMITER ;

-- Function 2: Calculate total revenue
DELIMITER //
CREATE FUNCTION GetTotalRevenue() RETURNS DECIMAL(10,2)
BEGIN
    DECLARE total DECIMAL(10,2);
    SELECT SUM(total_price) INTO total FROM Orders;
    RETURN IFNULL(total, 0);
END;
//
DELIMITER ;

-- Function 3: Check if a user has more than N orders
DELIMITER //
CREATE FUNCTION HasManyOrders(uid INT, min_orders INT) RETURNS BOOLEAN
BEGIN
    DECLARE cnt INT;
    SELECT COUNT(*) INTO cnt FROM Orders WHERE user_id = uid;
    RETURN cnt > min_orders;
END;
//
DELIMITER ;
-- Procedures
CALL PrintAllGameTitles();
CALL IncreaseOrderPrices(10);
CALL InsertFullLibrary(1);
CALL DeleteOldOrders('2025-06-15 00:00:00');

-- Functions (in SELECT)
SELECT GetOrderCountByUser(1);
SELECT GetTotalRevenue();
SELECT HasManyOrders(1, 3);


