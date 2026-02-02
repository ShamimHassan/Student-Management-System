# 🎓 Student Management System

A web-based **Student Management System (SMS)** built using **HTML, CSS, Bootstrap, JavaScript, jQuery, PHP, and MySQL**.  
This application helps educational institutions manage student records, courses, results, and attendance efficiently.

---

## 📌 Features

### 🔐 Authentication
- Secure admin login & logout
- Session-based authentication

### 👨‍🎓 Student Management
- Add, edit, delete students
- Unique student ID
- Active / inactive status
- Search & filter (jQuery)

### 📘 Course Management
- Add and manage courses
- Assign courses to students

### 📊 Result Management
- Enter and update marks
- Automatic grade calculation
- Student-wise result reports

### 🕒 Attendance Management
- Date-wise attendance tracking
- Present / Absent status
- Attendance percentage calculation

### Payment Management
- Monthly Course Fee in Tk (Bangladeshi Taka)
- Complete / Due status
- Payment tracking and reporting

### 📱 Responsive Design
- Fully responsive UI using Bootstrap

---

## 🧱 System Architecture


---

## 🛠️ Tech Stack

### Frontend
- HTML5
- CSS3
- Bootstrap
- JavaScript
- jQuery

### Backend
- PHP

### Database
- MySQL

---

## 🗄️ Database Schema

### Tables
- `admins` – Admin authentication
- `students` – Student information
- `courses` – Course details
- `student_courses` – Student-course mapping
- `results` – Marks and grades
- `attendance` – Attendance records

### Relationships
- One student → many courses
- One course → many students
- Results and attendance linked with students

---

## 📁 Project Structure

The project follows a modular and organized folder structure to ensure scalability, maintainability, and clean separation of concerns.

student-management-system/
├── assets/
│ ├── css/
│ │ └── style.css
│ ├── js/
│ │ └── script.js
│ └── images/
│ └── logo.png
│
├── admin/
│ ├── dashboard.php # Admin dashboard
│ ├── students.php # Student management (CRUD)
│ ├── courses.php # Course management
│ ├── results.php # Result management
│ └── attendance.php # Attendance management
│
├── includes/
│ ├── config.php # Database connection
│ ├── auth.php # Authentication & session check
│ ├── header.php # Common header
│ └── footer.php # Common footer
│
├── login.php # Admin login page
├── logout.php # Logout handler
├── index.php # Landing page
│
├── database.sql # Database schema
└── README.md # Project documentation


---

## ⚙️ Installation & Setup

### Prerequisites
- PHP 8+
- MySQL 8+
- Apache Server (XAMPP / WAMP)
- Web Browser

### Step-by-Step Installation

1. **Clone or Download the Project**
   ```bash
   git clone <repository-url>
   # OR
   # Download and extract the project files to your web server directory
   ```

2. **Set up Database**
   - Open phpMyAdmin (http://localhost/phpmyadmin)
   - Create a new database named `student_management_system`
   - Import the `database.sql` file from the project root
   - This will create all necessary tables and insert a default admin user

3. **Configure Database Connection**
   - Open `includes/config.php`
   - Update database credentials if needed (default XAMPP settings are pre-configured):
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'student_management_system');
   ```

4. **Set up Web Server**
   - Place the project folder in your web server directory:
     - XAMPP: `C:\xampp\htdocs\Student-Management-System`
     - WAMP: `C:\wamp64\www\Student-Management-System`
   - Start Apache and MySQL services

5. **Access the Application**
   - Open your browser and navigate to: `http://localhost/Student-Management-System`
   - You should be redirected to the login page

### Default Login Credentials
**Admin Account:**
- Username: `admin`
- Password: `password`
- Role: Admin (Full system access)

**Student Account:**
- Username: `student`
- Password: `password`
- Role: Student (View-only access to personal data)

### Registration Feature
New users can create accounts directly through the registration page:
- **Admin Registration**: Create admin accounts with full system access
- **Student Registration**: Create student accounts with personal data entry
- **Role-Based Forms**: Different registration forms based on selected role
- **Real-time Validation**: Password confirmation and field validation
- **User-Friendly Interface**: Clean, responsive design with visual feedback

### Account Creation Process
1. Visit the registration page at `http://localhost/Student-Management-System/register.php`
2. Select account type (Admin or Student)
3. Fill in required information:
   - **All users**: Username, Password, Confirm Password
   - **Students only**: First Name, Last Name, Email, Phone, Student ID
4. Submit form to create account
5. Login with new credentials

### Security Features
- Password hashing using PHP's password_hash()
- Username uniqueness validation
- Student ID and email uniqueness validation
- Transaction handling for student account creation
- Form validation with real-time feedback

### Post-Installation Steps
1. Change the default admin password after first login
2. Add students, courses, and other data as needed
3. Configure any additional settings in `includes/config.php`

## 🎯 Usage Guide

### Admin Dashboard
- View system statistics and quick actions
- Monitor student enrollment, course offerings, and payment status

### Student Management
- Add, edit, delete students
- Assign unique student IDs
- Set active/inactive status
- Search and filter students

### Course Management
- Create and manage courses
- Set course codes, names, and fees
- Track student enrollment per course

### Result Management
- Enter exam results for students
- Automatic grade calculation (A+, A, B, C, D, F)
- View student performance reports

### Attendance Tracking
- Mark daily attendance for courses
- View attendance percentages
- Generate attendance reports

### Payment Management
- Record student payments
- Track due amounts
- Generate payment reports

## 🔧 Configuration Options

### Customizing the System
- **Base URL**: Modify `BASE_URL` in `includes/config.php` for different deployment environments
- **Database Settings**: Adjust connection parameters in `includes/config.php`
- **Styling**: Customize `assets/css/style.css` for different themes
- **JavaScript**: Modify `assets/js/script.js` for additional functionality

### Security Considerations
- Change default admin credentials immediately
- Use strong passwords for all accounts
- Regular database backups
- Keep PHP and MySQL updated
- Consider SSL for production deployment

## 📱 Responsive Design

The system is fully responsive and works on:
- Desktop computers
- Laptops
- Tablets
- Mobile devices

Built with Bootstrap 5 for consistent cross-device experience.

## 🛠️ Troubleshooting

### Common Issues

**Database Connection Error**
- Check if MySQL service is running
- Verify database credentials in `includes/config.php`
- Ensure database `student_management_system` exists

**404 Page Not Found**
- Check Apache service is running
- Verify project folder location
- Confirm `.htaccess` file exists (if using URL rewriting)

**Login Issues**
- Ensure `session_start()` is working
- Check file permissions
- Verify admin user exists in database

**CSS/JS Not Loading**
- Check file paths in `includes/header.php`
- Verify internet connection (for CDN resources)
- Clear browser cache

### Server Requirements
- PHP 8.0 or higher
- MySQL 5.7 or higher
- Apache 2.4 or higher
- Modern web browser with JavaScript enabled

## 🤝 Support

For issues, suggestions, or contributions:
- Check the troubleshooting section above
- Review the code documentation
- Ensure all prerequisites are met

## 📄 License

This project is open-source and available under the MIT License.

