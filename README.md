# ⚖️ Legalease - Law Management System

**Legalease** is a multi-role legal case management platform built using PHP and Oracle DBMS, designed to streamline interactions between Clients, Lawyers, and Judges. It offers user authentication, role-based dashboards, case tracking, and form submissions — all in a clean, responsive UI powered by Bootstrap.

---

## 📂 Project Structure

legalease/
│
├── index.html # Public home page
├── login.html / login.php # Login form and processor
├── register.html # User registration page
├── logout.php # Logout handler
│
├── client-dashboard.php # Client's dashboard view
├── client_profile.php # Client's profile page
│
├── lawyer-dashboard.php # Lawyer's dashboard view
├── lawyer_profile.php # Lawyer's profile page
│
├── judge-dashboard.php # Judge's dashboard view
├── judge_profile.php # Judge's profile page
├── judge-case-details.php # Case details for judges
│
├── case-details.php # Generic case info page
├── get_lawyers.php # Fetch lawyers by specialization (AJAX)
├── get_specializations.php # Fetch specializations for dropdown
│
├── submit-feedback.php # Submit feedback form handler
├── submit-payment.php # Submit payment form handler
│
├── styles.css # Custom styles
├── script.js # Frontend JavaScript
├── images/ # Static assets like logos or icons


---

## 🛠️ Tech Stack

| Component       | Technology                        |
|----------------|------------------------------------|
| Frontend       | HTML, CSS, JavaScript, Bootstrap   |
| Backend        | PHP                                |
| Database       | Oracle DBMS                        |
| Web Server     | Apache (via XAMPP)                 |

---

## 🚀 How to Run Locally

1. **Place Files**: Move the project folder into `C:\xampp\htdocs\` if using XAMPP.

2. **Start XAMPP**:
   - Start **Apache**.
   - Oracle DB should be up and accessible (you’re not using MySQL).

3. **Oracle DB Connection**:
   - Open your database connection file (e.g. `db.php`, not shown here but assumed).
   - Use Oracle connection like:
     ```php
     $conn = oci_connect('username', 'password', '//localhost/XEPDB1');
     ```

4. **Open in Browser**:  
   Navigate to:  
http://localhost/legalease/index.html

5. **Database Setup**:  
Refer to `legalease_schema.docx` (included separately) to manually create tables in Oracle.

---

## ✨ Key Functionalities

- **Public Access**:
- `index.html`: Displays introductory content and legal awareness
- `login.html` & `register.html`: User authentication

- **Client Features**:
- View lawyer info, submit feedback, track case progress

- **Lawyer Features**:
- Access client cases, manage case status, update availability

- **Judge Features**:
- View detailed case files, manage hearings, finalize case status

- **Dynamic Data Fetching**:
- `get_lawyers.php` and `get_specializations.php` provide AJAX support for dropdowns

---

## 📌 Notes

- PHP must have the **OCI8 extension enabled** for Oracle DB connection:
- Uncomment in `php.ini`:
 ```
 extension=oci8_12c.dll  ; for Oracle 12c Instant Client
 ```
- Oracle database schema is **provided in a Word document** (`legalease_schema.docx`)
- No `.sql` file is included as the DB is set up manually in Oracle

---

## 👩‍💻 Contributors

- **Tanisha Mathur** – Frontend Development, Backend Integration 
- **Prisha Chadha** - Backend Development, Database
- **Soumya Jha** - Backend Development, Database 

---

## 📜 License

This project is for educational/demo purposes only.

