<?php
/*
Copyright 2026 @b3RpZ2Fz@

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS”, WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/
/**
 * ==============================================================================
 * OPEN ECLASS - MASS IMPORT SCRIPT (v2.7)
 * ==============================================================================
 * Περιγραφή: Μαζική εισαγωγή φοιτητών με αυστηρό Validation & Logging.
 * Λειτουργίες:
 * - Αυτόματη ανίχνευση Πόλης/Προγράμματος από το όνομα αρχείου.
 * - Καταγραφή συμβάντων σε Log file με Timestamp.
 * - Έλεγχος Duplicates (Username/Email).
 * - Έλεγχος μορφής Email & Username.
 * * Prompt engineering : @b3RpZ2Fz@
 * ==============================================================================
 */
// ------------------------------------------------------------------------------
// 1. SETUP & CONFIGURATION
// ------------------------------------------------------------------------------
define('ECLASS_PATH', 'your path');
// Φόρτωση eClass Core
if (!file_exists('your path' . 'config/config.php')) {
    die("[CRITICAL] Δεν βρέθηκε το config.php. Τερματισμός.\n");
}
require_once 'your path' . 'include/init.php';
// Ρυθμίσεις Φακέλων
$import_dir = 'your path' . 'students_import/';
$log_dir = 'your path' . 'logs/';
// Δημιουργία φακέλου logs αν δεν υπάρχει
if (!is_dir($log_dir)) { mkdir($log_dir, 0755, true); }
// Όνομα αρχείου Log με Timestamp (π.χ. import_log_2025-05-20_14-30-10.txt)
$log_file = $log_dir . 'import_log_' . date('Y-m-d_H-i-s') . '.txt';
// ------------------------------------------------------------------------------
// 2. HELPER FUNCTION: LOGGER
// ------------------------------------------------------------------------------
function logger($message, $type = 'INFO') {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    // Μορφή: [TIMESTAMP] [TYPE] Μήνυμα
    $formatted_msg = "[$timestamp] [$type] $message" . PHP_EOL;
  
    // 1. Εμφάνιση στην οθόνη
    echo $formatted_msg;
  
    // 2. Εγγραφή στο αρχείο (Append mode)
    file_put_contents($log_file, $formatted_msg, FILE_APPEND);
}
// ------------------------------------------------------------------------------
// 3. HIERARCHY MAP (Χαρτογράφηση IDs)
// ------------------------------------------------------------------------------
$hierarchy_map = [
    'eppaik' => [
        'athens' => 8, 'argos' => 9, 'volos' => 10, 'heraklion' => 11,
        'thessaloniki' => 12, 'ioannina' => 13, 'kozani' => 14, 'livadeia' => 15,
        'mytilene' => 16, 'patra' => 17, 'rodos' => 19, 'sapes' => 20, 'serres' => 23
    ],
    'pesyp' => [
        'athens' => 24, 'argos' => 25, 'volos' => 26, 'heraklion' => 27,
        'thessaloniki' => 28, 'ioannina' => 29, 'kozani' => 30, 'livadeia' => 31,
        'patra' => 32, 'rodos' => 33, 'sapes' => 34, 'serres' => 35
    ]
];
// ------------------------------------------------------------------------------
// 4. MAIN EXECUTION
// ------------------------------------------------------------------------------
logger("--- ΕΚΚΙΝΗΣΗ ΔΙΑΔΙΚΑΣΙΑΣ IMPORT (v2.7 Final Corrected) ---");
logger("Log file: $log_file");
if (!is_dir($import_dir)) {
    logger("Ο φάκελος $import_dir δεν υπάρχει. Τερματισμός.", 'CRITICAL');
    exit;
}
$files = glob($import_dir . 'students_*.csv');
if (empty($files)) {
    logger("Δεν βρέθηκαν αρχεία CSV στο $import_dir.", 'WARNING');
    exit;
}
foreach ($files as $filepath) {
    $filename = basename($filepath);
    logger("----------------------------------------");
    logger("Επεξεργασία αρχείου: $filename");
    // --> Parsing Ονόματος Αρχείου
    if (!preg_match('/students_([a-z]+)_([a-z]+)\.csv/i', $filename, $matches)) {
        logger("SKIP: Λάθος μορφή ονόματος αρχείου ($filename). Απαιτείται: students_city_program.csv", 'ERROR');
        continue;
    }
    $city_key = strtolower($matches[1]);
    $prog_key = strtolower($matches[2]);
    // Validation Χαρτογράφησης
    if (!isset($hierarchy_map[$prog_key])) {
        logger("SKIP: Άγνωστο πρόγραμμα '$prog_key'.", 'ERROR');
        continue;
    }
    if (!isset($hierarchy_map[$prog_key][$city_key])) {
        logger("SKIP: Η πόλη '$city_key' δεν υπάρχει στο πρόγραμμα '$prog_key'.", 'ERROR');
        continue;
    }
    $target_dept_id = $hierarchy_map[$prog_key][$city_key];
    logger("Στόχος: " . strtoupper($prog_key) . " - " . strtoupper($city_key) . " (ID: $target_dept_id)");
    // --> Άνοιγμα CSV
    if (($handle = fopen($filepath, "r")) === FALSE) {
        logger("Δεν μπορώ να ανοίξω το αρχείο $filename", 'ERROR');
        continue;
    }
    // Skip Headers
    fgetcsv($handle, 2000, ";");
    $row_num = 1; // Ξεκινάμε από 1 (αφού παραλείψαμε header)
    $stats = ['success' => 0, 'failed' => 0];
    while (($data = fgetcsv($handle, 2000, ";")) !== FALSE) {
        // Skip empty lines
        if (count($data) < 6) { continue; }
        // Data Mapping
        $givenname = trim($data[0]); // First Name
        $surname = trim($data[1]); // Last Name
        $email = trim($data[2]);
        $am = trim($data[3]);
        $uname = trim($data[4]);
        $pass = trim($data[5]);
        $validation_errors = [];
        // --- VALIDATION RULES ---
        if (empty($uname) || empty($email) || empty($pass)) {
            $validation_errors[] = "Λείπουν υποχρεωτικά πεδία";
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $validation_errors[] = "Άκυρο Email";
        }
      
        // DB Duplicates Check
        $exists_u = Database::get()->querySingle("SELECT id FROM user WHERE username = ?s", $uname);
        if ($exists_u) $validation_errors[] = "Username υπάρχει ήδη";
        $exists_e = Database::get()->querySingle("SELECT id FROM user WHERE email = ?s", $email);
        if ($exists_e) $validation_errors[] = "Email υπάρχει ήδη";
        // --- ACTION ---
        if (!empty($validation_errors)) {
            $err_msg = implode(", ", $validation_errors);
            logger("Γραμμή $row_num: ΑΠΟΤΥΧΙΑ για χρήστη '$uname' ($email) -> $err_msg", 'FAIL');
            $stats['failed']++;
        } else {
            // INSERT USER (Fixed: Added expires_at)
            $password_encrypted = password_hash($pass, PASSWORD_DEFAULT);
          
            // Expires at 2070
            $sql = "INSERT INTO user
                    (givenname, surname, username, password, email, am, status, registered_at, expires_at)
                    VALUES (?s, ?s, ?s, ?s, ?s, ?s, 5, NOW(), '2070-01-01 00:00:00')";
          
            Database::get()->query($sql, $givenname, $surname, $uname, $password_encrypted, $email, $am);
          
            // MANUAL ID RETRIEVAL
            $user_obj = Database::get()->querySingle("SELECT id FROM user WHERE username = ?s", $uname);
          
            if ($user_obj && isset($user_obj->id)) {
                $new_id = $user_obj->id;
              
                // Link with Department
                // FIXED: Table 'user_department' with columns 'department' and 'user'
                Database::get()->query("INSERT IGNORE INTO user_department (department, user) VALUES (?d, ?d)", $target_dept_id, $new_id);
                logger("Γραμμή $row_num: ΕΠΙΤΥΧΙΑ για '$uname' (ID: $new_id)", 'SUCCESS');
                $stats['success']++;
            } else {
                logger("Γραμμή $row_num: DB ERROR - Δεν βρέθηκε το ID μετά την εισαγωγή '$uname'", 'ERROR');
                $stats['failed']++;
            }
        }
        $row_num++;
    }
    fclose($handle);
  
    logger("Τέλος αρχείου $filename. Αποτελέσματα: Επιτυχίες: {$stats['success']} | Αποτυχίες: {$stats['failed']}");
}
logger("--- ΤΕΛΟΣ ΔΙΑΔΙΚΑΣΙΑΣ ---");
echo "\n[INFO] Μπορείτε να δείτε το αναλυτικό log στο: $log_file\n";