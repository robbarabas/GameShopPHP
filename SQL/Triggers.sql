DELIMITER //

CREATE TRIGGER prevent_duplicate_orders
BEFORE INSERT ON Orders
FOR EACH ROW
BEGIN
    DECLARE existing_count INT;

    -- Count existing orders by same user for the same game
    SELECT COUNT(*) INTO existing_count
    FROM Orders
    WHERE user_id = NEW.user_id AND game_id = NEW.game_id;

    -- If such an order exists, prevent the insert
    IF existing_count > 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'User has already purchased this game.';
    END IF;
    
END;

//DELIMITER ;
-- This trigger logs all new reviews
DROP TRIGGER log_review_insert;


DELIMITER //
CREATE TRIGGER log_review_insert
AFTER INSERT ON reviews
FOR EACH ROW
BEGIN
    INSERT INTO review_log (user_id, game_id, comment, action_time)
    VALUES (NEW.user_id, NEW.game_id, NEW.comment, NOW());
END;
//

DELIMITER ;

DELIMITER //

CREATE TRIGGER prevent_delete_ordered_games
BEFORE DELETE ON games
FOR EACH ROW
BEGIN
    DECLARE order_count INT;

    -- Check if the game exists in the orders table
    SELECT COUNT(*) INTO order_count
    FROM Orders
    WHERE game_id = OLD.game_id;

    IF order_count > 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Cannot delete a game that has been purchased by users.';
    END IF;
END;
//

DELIMITER ;



