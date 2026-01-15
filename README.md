📝 DESCRIPTION OF VALIDATOR

This script serves as a pre-flight safety check. It scans the students_import/ directory and validates all .csv files before they are processed by the main import script.

Its primary purpose is to identify structural errors, formatting issues, or invalid data types that could cause the main import process to fail or corrupt the database.

Key Validation Checks:

Filename Format: Enforces the strict naming convention: students_city_program.csv (e.g., students_athens_eppaik.csv).

CSV Structure: Verifies that the file uses the correct delimiter (;) and contains exactly 6 columns per row.

Email Validation: Checks if the email address follows a valid format (e.g., user@domain.com).

Username Integrity: Performs a strict check on usernames. It flags any username containing forbidden characters (such as Greek characters, spaces, or symbols). Only Latin letters, numbers, dots (.), dashes (-), and underscores (_) are allowed.

Missing Data: Detects empty mandatory fields (First Name, Last Name, Password, etc.).

🚀 How to Use
Preparation: Ensure your CSV files are uploaded to the target directory: /var/www/html/students_import/.

Execution: Open your terminal and run the following command:

Bash

php /var/www/html/csv_validator.php
Interpreting Results: The script will output the status of each file:

[OK] (Green): The file is clean, structurally correct, and ready for import.

[FAIL] (Red): The file contains errors. The script will report the specific row number and the exact error (e.g., "Row 5: Username contains invalid characters" or "Row 10: Invalid column count").

Action: If a file returns [FAIL], correct the specific lines in the CSV file (using a text editor or sed commands) and re-run the validator until all files show [OK].

📝 DESCRIPTION OF IMPORTER

This is the core engine of the toolkit. After the files have been validated by Script 1, this script performs the actual mass insertion of student data into the Open eClass MySQL database.

It is designed to be "intelligent"—it does not require you to manually specify the Department ID for every file. Instead, it reads the filename, extracts the City and Program, and automatically routes the students to the correct Department ID based on a pre-defined internal map.

Key Features:

Smart Routing: Parses filenames (e.g., students_patra_eppaik.csv) to determine the target Department (e.g., ID 17).

Duplicate Prevention: Checks the database in real-time to ensure no username or email is registered twice.

Security: Passwords are encrypted using PHP's standard password_hash (bcrypt) before storage.

Department Linking: Automatically links the new user to their specific department in the user_department table.

Detailed Logging: Generates a timestamped log file for every execution, recording every success and failure.

🗺️ The Logic (Hierarchy Map)
The script uses the following mapping logic to assign students to departments. Ensure your filenames match the City and Program keys below:

City (Filename Key),Program,Target Department ID
athens,eppaik,8
argos,eppaik,9
volos,eppaik,10
heraklion,eppaik,11
thessaloniki,eppaik,12
ioannina,eppaik,13
kozani,eppaik,14
livadeia,eppaik,15
mytilene,eppaik,16
patra,eppaik,17
rodos,eppaik,19
sapes,eppaik,20
serres,eppaik,23
---,---,---
athens,pesyp,24
patra,pesyp,32
(...and so on for all PESYP cities),pesyp,24-35

🚀 How to Use
Prerequisites:

Ensure files are in /var/www/html/students_import/.

Highly Recommended: Run the csv_validator.php first to clean the data.

Execution: Run the script via the command line:
php /var/www/html/eclass_importer_git_v2.php

Process: The script will iterate through every CSV file found in the folder. For each student:

Checks if they already exist.

Hashes their password.

Inserts them into the user table (Status: 5 - Student).

Links them to the correct Department.

Review Logs: Once finished, the script will tell you where the log file is saved (usually in logs/). To view the log:

cat /var/www/html/logs/import_log_YYYY-MM-DD_...txt

⚠️ Important Notes
User Status: All users are imported with Status 5 (Student).

Expiration: Accounts are set to expire automatically on 01/01/2070.

Cleaning Up: To run a fresh import, you must delete existing users first (refer to the cleanup SQL commands in the documentation)
