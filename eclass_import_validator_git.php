<?php
/*
Copyright 2026 @b3RpZ2Fz@

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS”, WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/
/**
 * ==============================================================================
 * CSV PRE-FLIGHT VALIDATOR TOOL
 * ==============================================================================
 * Σκοπός: Έλεγχος ακεραιότητας και ποιότητας δεδομένων CSV ΠΡΙΝ την εισαγωγή.
 * Δεν αγγίζει τη Βάση Δεδομένων. Είναι καθαρά File Parser.
 * Prompt engineering: @b3RpZ2Fz@
 * ==============================================================================
 */
// Ρυθμίσεις (Πρέπει να είναι ίδιες με το Import Script)
define('IMPORT_DIR', 'your path');
// Χρώματα για κονσόλα (Linux Terminal Colors)
$RED = "\033[31m";
$GREEN = "\033[32m";
$YELLOW = "\033[33m";
$CYAN = "\033[36m";
$BOLD = "\033[1m";
$RESET = "\033[0m";
echo "\n{$BOLD}=== CSV DATA INTEGRITY VALIDATOR ==={$RESET}\n";
echo "Scanning directory: " . 'your path' . "\n\n";
if (!is_dir('your path')) {
    die("{$RED}[CRITICAL] Ο φάκελος " . 'your path' . " δεν υπάρχει!{$RESET}\n");
}
$files = glob('your path' . 'students_*.csv');
if (empty($files)) {
    die("{$YELLOW}[WARN] Δεν βρέθηκαν αρχεία CSV (students_*.csv).{$RESET}\n");
}
$total_files = 0;
$files_with_errors = 0;
foreach ($files as $filepath) {
    $total_files++;
    $filename = basename($filepath);
    $file_errors = []; // αποθήκευση για τα λάθη του αρχείου
   
    echo "{$CYAN}Checking: $filename...{$RESET}";
    // 1. Έλεγχος Ονόματος Αρχείου
    if (!preg_match('/students_([a-z]+)_([a-z]+)\.csv/i', $filename)) {
        $file_errors[] = "[FILENAME] Λάθος μορφή ονόματος. Πρέπει να είναι: students_city_program.csv";
    }
    // 2. Άνοιγμα και Ανάγνωση
    if (($handle = fopen($filepath, "r")) !== FALSE) {
       
        // Skip Header row
        $header = fgetcsv($handle, 0, ";");
       
        // Έλεγχος αν το αρχείο είναι άδειο ή δεν έχει header
        if (!$header) {
            $file_errors[] = "[EMPTY] Το αρχείο φαίνεται άδειο ή κατεστραμμένο.";
        }
        $row = 1; // Ξεκινάμε από 1 μετά το header
       
        while (($data = fgetcsv($handle, 0, ";")) !== FALSE) {
            // Αγνοούμε κενές γραμμές
            if (count($data) == 1 && empty($data[0])) continue;
            // Έλεγχος Στηλών (Πρέπει να είναι τουλάχιστον 6)
            if (count($data) < 6) {
                $file_errors[] = "Row $row: Λάθος αριθμός στηλών (Βρέθηκαν " . count($data) . ", αναμένονται 6). Ελέγξτε τα ;";
                $row++;
                continue;
            }
            // Mapping
            $nom = trim($data[0]);
            $prenom = trim($data[1]);
            $email = trim($data[2]);
            $am = trim($data[3]);
            $uname = trim($data[4]);
            $pass = trim($data[5]);
            // --- DATA CHECKS ---
            // Check A: Κενά Υποχρεωτικά Πεδία
            if ($nom === '' || $prenom === '') {
                $file_errors[] = "Row $row: Λείπει Όνομα ή Επώνυμο.";
            }
            if ($uname === '') {
                $file_errors[] = "Row $row: Λείπει το Username.";
            }
            if ($pass === '') {
                $file_errors[] = "Row $row: Λείπει το Password.";
            }
            // Check B: Email Format
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $file_errors[] = "Row $row: Μη έγκυρο Email ('$email').";
            }
            // Check C: Username Characters (Αυστηρό: Μόνο λατινικά, αριθμοί, ., -, _)
            // Αυτό προστατεύει από ελληνικά, κενά, τόνους που σπάνε τη βάση
            if (preg_match('/[^a-zA-Z0-9\.\-\_]/', $uname)) {
                $file_errors[] = "Row $row: Το Username '$uname' περιέχει απαγορευμένους χαρακτήρες (π.χ. ελληνικά, κενά).";
            }
            $row++;
        }
        fclose($handle);
    } else {
        $file_errors[] = "[SYSTEM] Δεν μπορώ να ανοίξω το αρχείο.";
    }
    // 3. Εμφάνιση Αποτελεσμάτων
    if (empty($file_errors)) {
        // Όλα καλά
        echo " {$GREEN}[OK]{$RESET}\n";
    } else {
        // Βρέθηκαν λάθη
        $files_with_errors++;
        echo " {$RED}[FAIL]{$RESET}\n";
        foreach ($file_errors as $err) {
            echo " {$RED}-> $err{$RESET}\n";
        }
        echo "\n";
    }
}
echo "--------------------------------------------------\n";
echo "SUMMARY:\n";
echo "Total Files: $total_files\n";
if ($files_with_errors == 0) {
    echo "Status: {$GREEN}ALL CLEAN! READY FOR IMPORT.{$RESET}\n";
} else {
    echo "Status: {$RED}$files_with_errors files have errors. FIX THEM BEFORE IMPORTING.{$RESET}\n";
}
echo "--------------------------------------------------\n";