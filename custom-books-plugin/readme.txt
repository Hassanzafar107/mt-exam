Book Management Plugin
Version: 1.0
Author: Hassan Zafar
Description: A custom WordPress plugin to manage books with custom post types, metadata, and data management capabilities. And Shortcode to show books listing.

Features
Create, edit, and delete books using a custom post type.
Meta fields for books include author, publisher, ISBN, published date, ZIP code, country, and state.
Auto-populate country and state fields using ZIP code via the Zippopotam API.
Manage books from a custom admin menu.
Export book data to CSV from the admin page or frontend.
Custom shortcode to display books in a filterable, exportable table on the frontend.
Requirements
WordPress 5.0 or higher.
PHP 7.0 or higher.
Installation
Download and extract the plugin files.
Upload the book-management folder to your /wp-content/plugins/ directory.
Activate the plugin through the "Plugins" menu in WordPress.
Usage
1. Register Book Post Type
Once activated, a new post type Books will be available in the WordPress dashboard. You can create new books by navigating to Book Management > Add New Book.

2. Custom Fields
For each book, the following custom fields are available:

Author
Publisher
ISBN
Published Date
ZIP Code: Auto-populates country and state.
Country
State
3. Admin Menu
A custom admin menu, Book Management, will appear in the WordPress dashboard. It includes:

All Books: A DataTable with options to view, edit, or delete books. Export to CSV is available.
Add New Book: Redirects to the add new book post type page.
4. Shortcode
Display a list of all books on the frontend using the following shortcode:

php
Copy code
[books_table author="Author Name" publisher="Publisher Name"]
You can filter books by author and publisher with these attributes.

5. DataTables Integration
The plugin uses DataTables and Bootstrap for listing books in a table format.
You can export book lists to CSV from the admin or frontend tables.
6. Auto-Populate Location Fields
When adding or editing a book, entering a ZIP code will auto-populate the Country and State fields using the Zippopotam API.

AJAX Requests
The plugin supports AJAX functionality to delete books directly from the admin table.
Confirmation is required before deleting a book.
Enqueued Scripts & Styles
Bootstrap and DataTables for styling and functionality.
DataTables Buttons for export features (CSV export).

Changelog
1.0
Initial release with core features: book management, custom fields, admin page, shortcode, CSV export, and auto-populate location fields.
