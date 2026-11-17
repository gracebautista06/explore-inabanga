EXPLORE INABANGA: Tourism and Heritage Information System
Municipality of Inabanga, Bohol, Philippines
=========================================================

QUICK SETUP (3 steps)
---------------------
1. Copy the "explore-inabanga" folder into XAMPP's htdocs folder
   (for example C:\xampp\htdocs\explore-inabanga).
2. Open the XAMPP Control Panel and start Apache. (MySQL is NOT needed.)
3. Open http://localhost/explore-inabanga in your browser.

The database file and all tables are created automatically on the first
visit, and sample data is added. There is nothing to import.

NO XAMPP? Use PHP's built-in server instead:
   cd explore-inabanga
   php -S localhost:8000
Then open http://localhost:8000

ADMIN PANEL
-----------
URL:      http://localhost/explore-inabanga/admin/login.php
          (also linked as "Admin login" in the site footer)
Username: admin
Password: admin123

IMPORTANT: change the default password right after your first login
(Dashboard > Change password).

REQUIREMENTS
------------
- PHP 7.4 or newer with the pdo_sqlite extension (enabled by default in XAMPP)
- Internet connection for the map (Leaflet and OpenStreetMap load from a CDN)
- The "data" and "uploads" folders must be writable by the web server

WHERE THINGS ARE
----------------
data/inabanga.sqlite   the database (created automatically)
uploads/               photos uploaded through the admin panel
includes/config.php    database connection, auto-setup, sample data, helpers
assets/                CSS and JavaScript

RESET THE DATABASE
------------------
Stop Apache, delete data/inabanga.sqlite, and open the site again.
Everything is recreated with the sample data. (Uploaded photos in the
uploads folder are not deleted automatically.)

ABOUT THE SAMPLE DATA
---------------------
Entries marked "(Sample data - to be verified by LGU)" are generic
placeholders. Map coordinates are approximate. Before presenting the system
as official, replace them in the admin panel with verified information
from the Municipality of Inabanga. Contact details on the About page
(in about.php) are placeholders too.

TROUBLESHOOTING
---------------
- "Database error" message: make sure the "data" folder is writable and the
  pdo_sqlite extension is enabled (php.ini: extension=pdo_sqlite, then
  restart Apache).
- Image upload fails: make sure the "uploads" folder is writable, the image
  is JPG, PNG, or WEBP, and it is under 2MB. If PHP's upload_max_filesize or
  post_max_size is smaller than 2MB, raise it in php.ini.
- Map is blank: check your internet connection (map tiles come from
  OpenStreetMap).
- Links/styles broken: open the site through http://localhost/..., not by
  double-clicking the PHP files.