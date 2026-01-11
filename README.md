# Rav'n'Tea POS System

A modern, responsive Point of Sale (POS) system designed for cafes and tea shops. Built with PHP, MySQL, and Tailwind CSS with DaisyUI components.


## Features

- 🚀 **Multi-role System**
  - Admin: Full system access
  - Cashier: Order management
  - Barista: Order fulfillment

- 📊 **Dashboard**
  - Real-time sales overview
  - Quick access to key metrics
  - Recent activity feed

- ☕ **Product Management**
  - Add/edit/delete products
  - Categorize items
  - Track inventory levels

- 💳 **Order Processing**
  - Create and manage orders
  - Table management
  - Order status tracking
  - Payment processing

- 📈 **Reporting**
  - Sales reports
  - Inventory reports
  - Export to PDF/Excel

## Tech Stack

- **Backend**: PHP 8.0+
- **Database**: MySQL
- **Frontend**: 
  - HTML5, CSS3
  - JavaScript (ES6+)
  - [Tailwind CSS](https://tailwindcss.com/)
  - [DaisyUI](https://daisyui.com/)
  - [Lucide Icons](https://lucide.dev/)

## Installation

1. **Prerequisites**
   - PHP 8.0 or higher
   - MySQL 5.7 or higher
   - Web server (Apache/Nginx)
   - Composer (for dependencies)

2. **Setup**
   ```bash
   # Clone the repository
    git clone https://github.com/ravvdevv/Rav-n-Tea-POS-System.git
    cd Rav-n-Tea-POS-System
   

   # Create database and import schema
   mysql -u username -p database_name < sql-setup.sql
   
   # Configure environment
   cp .env.example .env
   # Edit .env with your database credentials
   ```

3. **Configure Web Server**
   - Point your web server to the `public` directory
   - Make sure `storage` directory is writable

## Configuration

Edit the `.env` file to configure:

```env
DB_HOST=localhost
DB_NAME=ravntea_pos
DB_USER=your_username
DB_PASS=your_password

APP_NAME="Rav'n'Tea POS"
APP_ENV=production
APP_DEBUG=false
```


## Usage

- Admin: admin / password
- Cashier: cashier / password
- Barista: barista / password



## Security

- Password hashing using PHP's `password_hash()`
- CSRF protection
- XSS prevention
- SQL injection prevention using prepared statements
- Session management

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Credits

- [Tailwind CSS](https://tailwindcss.com/)
- [DaisyUI](https://daisyui.com/)
- [Lucide Icons](https://lucide.dev/)
- [PHP](https://www.php.net/)

---

**Rav'n'Tea POS** © 2025 - All Rights Reserved
