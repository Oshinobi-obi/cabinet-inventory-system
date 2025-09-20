# Cabinet Inventory System

![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=for-the-badge&logo=css3&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)
![MySQL](https://img.shields.io/badge/MySQL-005C84?style=for-the-badge&logo=mysql&logoColor=white)

A comprehensive web-based inventory management system designed for tracking and organizing cabinet contents with QR code integration and user authentication.

## ✨ Features

- 🔐 **User Authentication System** - Secure login/logout functionality
- 📱 **QR Code Integration** - Generate and scan QR codes for quick cabinet access
- 📊 **Dashboard Analytics** - Real-time overview of inventory status and recent activities
- 🗄️ **Cabinet Management** - Create, update, and organize cabinet information
- 📦 **Item Categorization** - Organize items by categories for better inventory control
- 🔍 **Advanced Search** - Search by cabinet number, name, or item details
- 📄 **Export Functionality** - Export inventory data for reporting
- 🔗 **Public API** - RESTful API endpoints for external integrations
- 📱 **Responsive Design** - Mobile-friendly interface for on-the-go access

## 🛠️ Technology Stack

| Technology                                                                                               | Usage                                            | Files                     |
| -------------------------------------------------------------------------------------------------------- | ------------------------------------------------ | ------------------------- |
| ![PHP](https://img.shields.io/badge/PHP-777BB4?style=flat&logo=php&logoColor=white)                      | Server-side logic, API endpoints, authentication | `*.php` files             |
| ![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=flat&logo=css3&logoColor=white)                   | Styling and responsive design                    | `assets/css/*.css`        |
| ![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=flat&logo=javascript&logoColor=black) | Client-side interactivity, AJAX requests         | `assets/js/*.js`          |
| ![MySQL](https://img.shields.io/badge/MySQL-005C84?style=flat&logo=mysql&logoColor=white)                | Database management                              | `cabinet_info_system.sql` |

## 🚀 Installation

### Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher (or MariaDB 10.2+)
- Web server (Apache/Nginx)
- Web browser with JavaScript enabled

### Setup Instructions

1. **Clone the repository**

   ```bash
   git clone https://github.com/Oshinobi-obi/cabinet-inventory-system.git
   cd cabinet-inventory-system
   ```

2. **Database Setup**

   - Create a new MySQL database
   - Import the database schema:

   ```bash
   mysql -u username -p database_name < cabinet_info_system.sql
   ```

3. **Configuration**

   - Navigate to `includes/config.php`
   - Update database connection settings:

   ```php
   $host = 'your_host';
   $dbname = 'your_database_name';
   $username = 'your_username';
   $password = 'your_password';
   ```

4. **Web Server Setup**

   - Point your web server document root to the project directory
   - Ensure PHP has write permissions to `uploads/` and `qrcodes/` directories

5. **Access the Application**
   - Open your browser and navigate to your domain/server
   - Complete the initial setup if prompted

## 📖 Usage

### Getting Started

1. **Login**: Access the system through the login page
2. **Dashboard**: View system overview and recent activities
3. **Cabinet Management**: Create and manage cabinet information
4. **QR Codes**: Generate QR codes for cabinets and scan for quick access
5. **Search**: Use the search functionality to find specific cabinets or items

### Key Pages

- **`index.php`** - Main search interface and QR scanning
- **`dashboard.php`** - System overview and analytics
- **`cabinet.php`** - Cabinet management interface
- **`users.php`** - User management (admin)
- **`qr-scan.php`** - QR code scanning interface

### API Endpoints

The system provides several API endpoints for integration:

- **`cabinet_api.php`** - Cabinet data CRUD operations
- **`public_api.php`** - Public API for external access
- **`export.php`** - Data export functionality

## 📁 Project Structure

```
cabinet-inventory-system/
├── assets/                 # Static assets
│   ├── css/               # Stylesheets
│   │   ├── cabinet.css
│   │   ├── dashboard.css
│   │   ├── index.css
│   │   └── navbar.css
│   ├── js/                # JavaScript files
│   │   ├── cabinet.js
│   │   └── index.js
│   └── images/            # Image assets
├── includes/              # PHP includes and utilities
│   ├── auth.php          # Authentication functions
│   ├── config.php        # Database configuration
│   ├── functions.php     # Utility functions
│   └── sidebar.php       # Sidebar component
├── qrcodes/              # Generated QR code images
├── uploads/              # File upload directory
├── cabinet_info_system.sql # Database schema
├── index.php             # Main application entry point
├── dashboard.php         # Dashboard interface
├── cabinet.php           # Cabinet management
├── login.php             # User authentication
├── users.php             # User management
└── Various PHP files     # Additional functionality
```

## 🔧 Configuration

### Database Configuration

Edit `includes/config.php` to configure database connection:

```php
$host = 'localhost';        // Database host
$dbname = 'cabinet_system'; // Database name
$username = 'root';         // Database username
$password = '';             // Database password
```

### QR Code Settings

QR codes are automatically generated and stored in the `qrcodes/` directory. Ensure this directory has write permissions.

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/new-feature`)
3. Commit your changes (`git commit -am 'Add new feature'`)
4. Push to the branch (`git push origin feature/new-feature`)
5. Create a Pull Request

### Development Guidelines

- Follow PSR-12 coding standards for PHP
- Use meaningful variable and function names
- Comment complex logic
- Test changes thoroughly before submitting

## 🐛 Troubleshooting

### Common Issues

1. **Database Connection Errors**

   - Verify database credentials in `includes/config.php`
   - Ensure MySQL service is running
   - Check database exists and schema is imported

2. **Permission Issues**

   - Ensure web server has write permissions to `uploads/` and `qrcodes/`
   - Check file ownership and permissions

3. **QR Code Generation Problems**
   - Verify QR code libraries are properly installed
   - Check `qrcodes/` directory permissions

## 📄 License

This project is open source. Please check the repository for specific license terms.

## 👥 Support

For support, bug reports, or feature requests:

- Open an issue on GitHub
- Review existing documentation
- Check troubleshooting section above

---

**Built with ❤️ using PHP, CSS, and JavaScript**
