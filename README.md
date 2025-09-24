# Cabinet Inventory System

![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)
![CSS3](https://img.shields.io/badge/CSS3-1572B6?style=for-the-badge&logo=css3&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)
![MySQL](https://img.shields.io/badge/MySQL-005C84?style=for-the-badge&logo=mysql&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-563D7C?style=for-the-badge&logo=bootstrap&logoColor=white)

A comprehensive web-based inventory management system designed for tracking and organizing cabinet contents with QR code integration, real-time search, advanced analytics, and role-based user authentication.

## 🆕 Latest Updates (v2.0)

### 🎯 **Major System Overhaul**
- **📁 File Reorganization**: Complete restructure with `admin/`, `public/`, and `includes/` folders
- **🔍 Real-time Search**: Instant search across all cabinets and items with pagination
- **📊 Advanced Analytics**: Enhanced dashboard with role-based permissions
- **📱 Mobile Optimization**: Improved responsive design with landscape scrolling
- **🎨 UI/UX Enhancements**: Modern loading animations and blur effects

### 🚀 **New Features Added**

#### **🔍 Advanced Search System**
- **Real-time Search**: Type-as-you-search functionality for instant results
- **Global Search**: Search across all cabinets regardless of current page
- **Smart Pagination**: Maintains pagination during search (5 items for admin, 9 for public)
- **Loading Animations**: Beautiful `.webm` animations during search operations
- **No Highlight Search**: Clean, professional search input styling

#### **📊 Enhanced Dashboard**
- **Role-based Interface**: Different views for Admin and Encoder roles
- **Activity Tracking**: Real-time activity monitoring with status updates
- **Export Functionality**: CSV and PDF export with loading animations
- **Cabinet Management**: Advanced cabinet editing with photo uploads
- **User Management**: Complete user account management system

#### **📱 Mobile-First Design**
- **Responsive Tables**: Horizontal scrolling for mobile devices
- **Touch-Friendly**: Optimized for touch interactions
- **Landscape Support**: Proper orientation handling
- **Loading States**: Transparent loading animations for mobile

#### **🎨 UI/UX Improvements**
- **Loading Animations**: 
  - `Trail-Loading.webm` for processing
  - `Success_Check.webm` for success states
  - `Cross.webm` for error states
- **Blur Effects**: Professional modal backgrounds
- **Modern Styling**: Updated color schemes and typography
- **Accessibility**: Better contrast and keyboard navigation

#### **📄 Export & Reporting**
- **PDF Generation**: Browser-based PDF creation with print dialog
- **CSV Export**: Excel-compatible data export
- **QR Code Integration**: QR codes in PDF reports
- **Print Optimization**: A4 landscape formatting

#### **🔐 Security & Authentication**
- **Role-based Access**: Admin and Encoder permission levels
- **Session Management**: Secure user sessions
- **Email Integration**: Automated user account creation emails
- **Dynamic URLs**: Auto-detecting server URLs for email links

## ✨ Core Features

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

1. **Public Access**: Visit the main page for cabinet viewing and QR scanning
2. **Admin Login**: Access admin panel through `/admin/login.php`
3. **Dashboard**: View system overview and recent activities
4. **Cabinet Management**: Create and manage cabinet information
5. **User Management**: Manage user accounts and permissions
6. **Export Data**: Export cabinet data in CSV or PDF format

### Key Pages

#### **Public Interface**
- **`public/index.php`** - Main public interface with real-time search
- **`public/qr-scan.php`** - QR code scanning interface
- **`public/public_api.php`** - Public API endpoints

#### **Admin Interface**
- **`admin/dashboard.php`** - System overview and analytics
- **`admin/cabinet.php`** - Cabinet management interface
- **`admin/users.php`** - User management (admin only)
- **`admin/profile.php`** - User profile management
- **`admin/login.php`** - Admin authentication

#### **Core System**
- **`includes/export.php`** - Data export functionality
- **`includes/email_service.php`** - Email notifications
- **`includes/cabinet_api.php`** - Cabinet API endpoints

### 🔍 **Search Features**

#### **Real-time Search**
- **Instant Results**: Type to search cabinets and items instantly
- **Global Search**: Search across all cabinets regardless of current page
- **Smart Pagination**: Maintains pagination during search
- **Loading Animations**: Beautiful animations during search operations

#### **Search Options**
- **Cabinet Search**: Search by cabinet number or name
- **Item Search**: Search by item name or category
- **Combined Search**: Search both cabinets and items simultaneously

### 📊 **Dashboard Features**

#### **Role-based Interface**
- **Admin Role**: Full access to all features
- **Encoder Role**: Limited access to specific functions
- **Activity Tracking**: Real-time monitoring of system activities
- **Statistics**: Visual representation of system data

#### **Export Capabilities**
- **CSV Export**: Excel-compatible data export
- **PDF Export**: Professional PDF reports with QR codes
- **Bulk Export**: Export all cabinets or individual cabinets
- **Print Optimization**: A4 landscape formatting for reports

### 📱 **Mobile Features**

#### **Responsive Design**
- **Touch-friendly**: Optimized for mobile interactions
- **Landscape Support**: Proper orientation handling
- **Horizontal Scrolling**: Tables scroll horizontally on mobile
- **Loading States**: Transparent animations for mobile

#### **QR Code Integration**
- **Generate QR Codes**: Automatic QR code generation for cabinets
- **Scan QR Codes**: Mobile-friendly QR code scanning
- **Quick Access**: Direct access to cabinet information via QR codes

### API Endpoints

The system provides several API endpoints for integration:

- **`cabinet_api.php`** - Cabinet data CRUD operations
- **`public_api.php`** - Public API for external access
- **`export.php`** - Data export functionality

## 📁 Project Structure

```
cabinet-inventory-system/
├── admin/                 # Admin panel files
│   ├── dashboard.php     # Main admin dashboard
│   ├── login.php         # Admin login page
│   ├── cabinet.php       # Cabinet management
│   ├── users.php         # User management
│   ├── profile.php       # User profile management
│   └── index.php         # Admin redirect
├── public/               # Public-facing files
│   ├── index.php         # Main public interface
│   ├── public_api.php    # Public API endpoints
│   └── qr-scan.php      # QR code scanning
├── includes/             # Core system files
│   ├── auth.php         # Authentication functions
│   ├── config.php       # Database configuration
│   ├── functions.php    # Utility functions
│   ├── sidebar.php      # Navigation sidebar
│   ├── email_service.php # Email functionality
│   ├── cabinet_api.php  # Cabinet API endpoints
│   ├── export.php       # Data export functionality
│   ├── simple_pdf.php   # PDF generation
│   └── pdf_generator.php # Advanced PDF generation
├── assets/               # Static assets
│   ├── css/             # Stylesheets
│   │   ├── cabinet.css
│   │   ├── dashboard.css
│   │   ├── index.css
│   │   ├── navbar.css
│   │   └── mobile-enhancements.css
│   ├── js/              # JavaScript files
│   │   ├── cabinet.js
│   │   └── index.js
│   └── images/          # Image assets and animations
│       ├── Trail-Loading.webm
│       ├── Success_Check.webm
│       ├── Cross.webm
│       └── cabinet-icon.svg
├── qrcodes/             # Generated QR code images
├── uploads/             # File upload directory
├── logs/                # System logs
├── phpmailer/           # Email library
├── cabinet_info_system.sql # Database schema
├── network_config.json  # Network configuration
├── server.php           # Development server
├── index.php            # Root redirect
└── favicon.ico          # Browser icon
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
