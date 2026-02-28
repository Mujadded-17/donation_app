-- Add new categories for the Explore page
INSERT INTO `category` (`category_id`, `name`, `icon`) VALUES
(5, 'Stationery', 'stationery.png'),
(6, 'Gadgets', 'gadgets.png'),
(7, 'Grains', 'grains.png'),
(8, 'Makeup', 'makeup.png'),
(9, 'Accessories', 'accessories.png');

-- Update AUTO_INCREMENT
ALTER TABLE `category` MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;
