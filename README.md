markdown

# Restaurant Website

This is a simple Restaurant Management System created using PHP, Js and MySQL.
---

## Tech Stack & Requirments
* **Local Server:** WAMP Server / XaMPP
* **Backend:** PHP (v8.x or higher)
* **Database:** MySQL
* **Frontend:** HTML5, CSS3, JavaScript
---

## Installation & Setup Guide

### 1. Deploying the project to the local server
Copy or clone this project into the `www` directory of the WAMP Server (e.g., `C:\wamp64\www\`).

### 2. Setting up the database
1. Start the WAMP Server and go to `http://localhost/phpmyadmin`.
2. Create a new database (e.g., `my_db_name`).

### 3. Configuring Database Credentials
Open the file containing the project's database connection (`config\connection.php`) and enter your local server details as shown below:
```php
host = 'localhost'
dbName = 'the_name_of_the_database you created'
$username = 'root';
$password = ''; // Since there is no default password for WAMP, leave it blank
