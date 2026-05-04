[![Review Assignment Due Date](https://classroom.github.com/assets/deadline-readme-button-22041afd0340ce965d47ae6ef1cefeee28c7c493a6346c4f15d667ab976d596c.svg)](https://classroom.github.com/a/OFWe9D1G)

# Clothing Store Web Application (PHP & MySQL)

## Overview

This is a **full-stack PHP web application** for a Clothing Store.
It allows users to register, log in, browse products, add items to a cart, and complete a simulated payment process.
An admin panel is also included to manage users and view orders.

---

## Features

###  User Features

* Register with:

  * Full Name
  * Username
  * Email
  * Password (hashed)
* Login using **Username OR Email**
* Browse products
* Add items to cart
* Checkout with **address validation**
* Simulated payment system
* Contact form

---

### 🛒 Cart & Orders

* Add/remove items from cart
* Session-based cart storage
* Orders saved to database after payment

---

### 💳 Payment System

* Requires:

  * Delivery Address (mandatory)
  * Card details (simulated)
* Prevents checkout if address is missing

---

###  Security

* Password hashing using `password_hash()`
* Login verification using `password_verify()`
* Prepared statements to prevent SQL injection
* Basic input validation

---

###  Admin Features

* Admin login
* Dashboard with:

  * Total users
  * Total products
  * Total orders
* View all users
* View all orders

---

###  Additional Pages

* Forgot Password (UI only)
* Contact Us form (styled + validated)

---

##  Database Setup

### 1. Create Database

Go to **phpMyAdmin** → Create database:

```
ClothingStore
```

---

### 2. Create Tables

#### `tblUser`

```sql
CREATE TABLE tblUser (
    userID INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(100),
    username VARCHAR(50) UNIQUE,
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255),
    status VARCHAR(20)
);
```

---

#### `tblAdmin`

```sql
CREATE TABLE tblAdmin (
    adminID INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100),
    password VARCHAR(255)
);
```

---

#### `tblClothes`

```sql
CREATE TABLE tblClothes (
    clothID INT AUTO_INCREMENT PRIMARY KEY,
    clothName VARCHAR(100),
    price DECIMAL(10,2)
);
```

---

#### `tblAorder`

```sql
CREATE TABLE tblAorder (
    orderID INT AUTO_INCREMENT PRIMARY KEY,
    userID INT,
    clothID INT,
    quantity INT
);
```

---

##  Installation Steps

1. Install **XAMPP / WAMP**
2. Place project folder in:

   ```
   htdocs/
   ```
3. Start:

   * Apache
   * MySQL
4. Open:

   ```
   http://localhost/phpmyadmin
   ```
5. Create database and tables (above)
6. Configure database connection in:

   ```
   DBConn.php
   ```

---

##  Default Usage

### ➤ Add a User

* Use registration page
  OR
* Insert manually in phpMyAdmin (use hashed password)

---

### ➤ Add Products

Insert into `tblClothes`:

```sql
INSERT INTO tblClothes (clothName, price)
VALUES ('Hoodie', 499.99);
```

---

### ➤ Run Application

Open:

```
http://localhost/your-folder/login.php
```

---

##  UI Design

* Dark theme
* Sidebar navigation
* Responsive layout
* Styled forms and product grid
* Hover effects and modern cards

---

##  Notes

* Payment system is **simulated (no real transactions)**
* Forgot password is **UI only (not functional)**
* Images must be stored in `/images` folder

---

##  Future Improvements

* Real payment integration (Stripe/PayPal)
* Email verification system
* Password reset functionality
* Product image uploads
* Search & filtering
* Order history page

---

##  Author

Developed as a **PHP & MySQL Web Application Project**

---

##  License

This project is for educational purposes.

