INSERT INTO Games (title, price, release_date, image_url)
VALUES
('The Witcher 3: Wild Hunt', 39.99, '2015-05-19', 'images/witcher3.jpg'),
('Cyberpunk 2077', 59.99, '2020-12-10', 'images/cyberpunk2077.jpg'),
('Minecraft', 26.95,  '2011-11-18', 'images/minecraft.jpg'),
('FIFA 24', 69.99,  '2024-09-27', 'images/fifa24.jpg'),
('Age of Empires IV', 49.99,  '2021-10-28', 'images/aoe4.jpg'),
('Call of Duty: Modern Warfare II', 59.99, '2022-10-28', 'images/codmw2.jpg'),
('Stardew Valley', 14.99,  '2016-02-26', 'images/stardew.jpg'),
('Elden Ring', 59.99,  '2022-02-25', 'images/eldenring.jpg'),
('Hades', 24.99,  '2020-09-17', 'images/hades.jpg'),
('League of Legends', 0.00,  '2009-10-27', 'images/lol.jpg');
-- batch 2
INSERT INTO Games (title, price, release_date, image_url)
VALUES
('Hollow Knight', 15.00,  '2017-02-24', 'images/hollowknight.jpg'),
('Among Us', 4.99,  '2018-06-15', 'images/amongus.jpg'),
('Valorant', 0.00,  '2020-06-02', 'images/valorant.jpg'),
('The Legend of Zelda: Breath of the Wild', 59.99,  '2017-03-03', 'images/zelda_botw.jpg'),
('Overwatch 2', 39.99, '2022-10-04', 'images/overwatch2.jpg'),
('Dark Souls III', 49.99,  '2016-03-24', 'images/darksouls3.jpg'),
('Fall Guys', 19.99,  '2020-08-04', 'images/fallguys.jpg'),
('Animal Crossing: New Horizons', 59.99,  '2020-03-20', 'images/animalcrossing.jpg'),
('Rocket League', 19.99,  '2015-07-07', 'images/rocketleague.jpg'),
('DOOM Eternal', 59.99,  '2020-03-20', 'images/doometernal.jpg');

INSERT INTO Genres (genre_name) VALUES
('RPG'),
('Action'),
('Adventure'),
('Shooter'),
('Strategy'),
('Sports'),
('Simulation'),
('Multiplayer');



INSERT INTO GameGenres (game_id, genre_id) VALUES
-- Witcher 3
(1, 1), (1, 2), (1, 3),

-- Cyberpunk
(2, 1), (2, 2), (2, 4),

-- Minecraft
(3, 3), (3, 7), (3, 8),

-- FIFA
(4, 6), (4, 8),

-- AOE IV
(5, 5), (5, 1),

-- COD MW2
(6, 4), (6, 2), (6, 8),

-- Stardew Valley
(7, 1), (7, 7), (7, 3),

-- Elden Ring
(8, 1), (8, 2), (8, 3),

-- Hades
(9, 1), (9, 2),

-- League of Legends
(10, 2), (10, 8);
-- batch 2
INSERT INTO gamegenres (game_id, genre_id) VALUES
-- Hollow Knight
(11, 1), (11, 3),

-- Among Us
(12, 4), (12, 8),

-- Valorant
(13, 4), (13, 6), (13, 8),

-- Zelda: BOTW
(14, 1), (14, 3), (14, 2),

-- Overwatch 2
(15, 2), (15, 4), (15, 8),

-- Dark Souls III
(16, 1), (16, 2),

-- Fall Guys
(17, 4), (17, 3),

-- Animal Crossing: New Horizons
(18, 1), (18, 7),

-- Rocket League
(19, 4), (19, 8),

-- DOOM Eternal
(20, 2), (20, 4);

INSERT IGNORE INTO GameGenres (game_id, genre_id) VALUES
(3, 1),
(4, 2),
(5, 8),
(6, 1),
(9, 3),
(10, 5),
(11, 2),
(16, 3),
(17, 6),
(18, 8),
(19, 6);



