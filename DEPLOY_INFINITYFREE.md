# Deploying to InfinityFree (free hosting)

## 1. Create the hosting account
1. Sign up at https://www.infinityfree.com and click **Create Account**.
2. Pick a free subdomain (for example `my-students.infinityfreeapp.com`).
3. Open the account's **Control Panel** (cPanel).

## 2. Create the database
1. In the Control Panel, open **MySQL Databases** and create a database.
2. Write down the four values shown there:
   - **MySQL Hostname** (looks like `sql123.infinityfree.com`)
   - **MySQL Database Name** (looks like `if0_12345678_students`)
   - **MySQL Username** (looks like `if0_12345678`)
   - **MySQL Password** (your hosting account password; shown in the client area)
3. Click **phpMyAdmin** next to the new database, open the **Import** tab, and upload
   `database/student_registration_system_hosting.sql`.
   (Do not use the other `.sql` file: it contains `CREATE DATABASE`, which InfinityFree blocks.)

## 3. Add your database settings
Create the file `config/database.local.php` with your values:

```php
<?php
return [
    'host' => 'sql123.infinityfree.com',
    'port' => 3306,
    'dbname' => 'if0_12345678_students',
    'username' => 'if0_12345678',
    'password' => 'YOUR_PASSWORD',
    'charset' => 'utf8mb4',
];
```

Optionally create `config/app.local.php` to hide PHP errors from visitors:

```php
<?php
define('APP_ENV', 'production');
```

## 4. Upload the files
1. In the Control Panel open **Online File Manager** (or use FileZilla with the FTP details from the client area).
2. Open the `htdocs` folder and delete the default `index2.html` file if present.
3. Upload everything from the project (contents of the zip, plus the two config files above) into `htdocs`,
   so that `htdocs/index.php` exists.
4. Make sure the `uploads/students/` folder exists (profile pictures are saved there).

## 5. Test
Open your subdomain. New InfinityFree sites can take up to 72 hours for DNS to work everywhere,
but usually it is a few minutes. Register an account, log in, and add a student.
