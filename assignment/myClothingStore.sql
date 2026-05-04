-- Create tables for ClothingStore database

CREATE TABLE IF NOT EXISTS tblUser (
    userID INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    fullname VARCHAR(100) NOT NULL,
    status ENUM('verified', 'pending') DEFAULT 'pending',
    role ENUM('user', 'seller', 'admin') DEFAULT 'user'
);

CREATE TABLE IF NOT EXISTS tblClothes (
    clothID INT AUTO_INCREMENT PRIMARY KEY,
    clothName VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255)
);

CREATE TABLE IF NOT EXISTS tblAorder (
    orderID INT AUTO_INCREMENT PRIMARY KEY,
    userID INT NOT NULL,
    clothID INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    FOREIGN KEY (userID) REFERENCES tblUser(userID),
    FOREIGN KEY (clothID) REFERENCES tblClothes(clothID)
);

-- Insert sample data

INSERT INTO tblUser (username, email, password, fullname, status, role) VALUES
('admin', 'admin@example.com', '$2y$10$examplehashedpassword', 'Administrator', 'verified', 'admin'),
('seller1', 'seller@example.com', '$2y$10$examplehashedpassword', 'Seller One', 'verified', 'seller'),
('user1', 'user@example.com', '$2y$10$examplehashedpassword', 'User One', 'verified', 'user');

INSERT INTO tblClothes (clothName, price, image) VALUES
('Baggy Tie', 50.00, 'baggy tie.jpeg'),
('Basenji Blue', 60.00, 'basenji Blue.jpeg'),
('Heavenly', 70.00, 'heavenly.jpeg'),
('Hoodie', 80.00, 'hoodie.jpeg'),
('Night Wing', 90.00, 'night wing.jpeg'),
('Red', 40.00, 'red.jpeg'),
('Running Black', 55.00, 'running black.jpeg'),
('Soweto Towers', 65.00, 'soweto towers.jpeg'),
('Space Rocket', 75.00, 'space rocket.jpeg'),
('T-Shirt', 30.00, 'tshirt.jpeg'),
('White Senji', 45.00, 'whiteSenji.jpeg');