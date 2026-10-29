<?php



require_once 'session.php';
require_once '../config/db.php';
require_once '../helpers/functions.php';



if ($_SERVER['REQUEST_METHOD'] === "POST") {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['errors'] = ["Invalid security session."];
        header("Location: ../pages/registerPage.php");
        exit();
    }

    $_SESSION['old'] = $_POST;
    unset(
        $_SESSION['old']['password'],
        $_SESSION['old']['confirm_password'],
        $_SESSION['old']['csrf_token']
    );
    $errors = [];

    $username = clean($_POST['username']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $role = "resident";

    $firstName = clean($_POST['first_name']);
    $lastName = clean($_POST['last_name']);
    $middleName = !empty($_POST['middle_name']) ? clean($_POST['middle_name']) : null;
    $suffix = !empty($_POST['suffix']) ? clean($_POST['suffix']) : null;
    $birthdate = clean($_POST['birthdate']);
    $sex = clean($_POST['sex'] ?? '');
    $civilStatus = clean($_POST['civil_status'] ?? '');
    $address = clean($_POST['address']);
    $phoneNumber = clean($_POST['phone_no']);
    $email = clean($_POST['email']);
    $privacyConsent = $_POST['privacy_consent'] ?? '';

    // VALIDATIONS
    if(emptyField($username, $password, $confirmPassword, $firstName, $lastName, $birthdate, $sex, $address, $civilStatus, $phoneNumber, $email)) {
        $errors[] = "Fill all required fields!";
    }
    if(invalidNamePattern($firstName, $lastName)) {
        $errors[] = "Name contains invalid characters!"; 
    }
    if(passwordNotMatch($password, $confirmPassword)) {
        $errors[] = "Passwords do not match!";
    }
    if(passwordLength($password)) {
        $errors[] = "Password must be at least 8 characters!";
    }
    if(invalidPhoneNo($phoneNumber)) {
        $errors[] = "Invalid phone number format!";
    }
    if(invalidBirthdate($birthdate)) {
        $errors[] = "Please enter a valid birthdate!";
    }
    if(usernameTaken($conn, $username)) {
        $errors[] = "Username is already taken!";
    }
    if ($privacyConsent !== '1') {
        $errors[] = "You must agree to the Data Privacy Act notice before registering.";
    }

    $proofPath = null;
    $storedProofPath = $_SESSION['registration_proof_path'] ?? '';
    $storedProofName = basename(str_replace('\\', '/', (string) $storedProofPath));
    if (
        $storedProofPath === 'uploads/verification_proofs/' . $storedProofName
        && is_file(__DIR__ . '/../uploads/verification_proofs/' . $storedProofName)
    ) {
        $proofPath = $storedProofPath;
    } else {
        unset($_SESSION['registration_proof_path']);
    }

    $proofFile = $_FILES['verification_proof'] ?? null;
    $validProofUpload = false;
    $allowedProofTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'application/pdf' => 'pdf',
    ];

    if (!$proofFile || ($proofFile['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        if (!$proofPath) {
            $errors[] = "Please upload proof of residency or a valid ID for verification.";
        }
    } elseif (($proofFile['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        $errors[] = "Verification proof upload failed. Please try again.";
    } else {
        $maxProofSize = 5 * 1024 * 1024;
        if (($proofFile['size'] ?? 0) > $maxProofSize) {
            $errors[] = "Verification proof must not exceed 5MB.";
        } else {
            $fileInfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeType = $fileInfo->file($proofFile['tmp_name']);
            if (!isset($allowedProofTypes[$mimeType])) {
                $errors[] = "Verification proof must be a JPG, PNG, or PDF file.";
            } else {
                $validProofUpload = true;
            }
        }
    }

    if ($validProofUpload) {
        $uploadDir = __DIR__ . '/../uploads/verification_proofs/';
        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
            $errors[] = "Failed to save verification proof.";
        } else {
            $extension = $allowedProofTypes[$mimeType];
            $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '', pathinfo($proofFile['name'], PATHINFO_FILENAME));
            $fileName = uniqid('proof_', true) . '_' . ($safeName ?: 'verification') . '.' . $extension;
            $targetPath = $uploadDir . $fileName;

            if (!move_uploaded_file($proofFile['tmp_name'], $targetPath)) {
                $errors[] = "Failed to save verification proof.";
            } else {
                $proofPath = 'uploads/verification_proofs/' . $fileName;
                $_SESSION['registration_proof_path'] = $proofPath;
            }
        }
    }

    // SEARCH FOR EXISTING RESIDENT
    $checkResident = "SELECT residentId FROM residents WHERE TRIM(firstName) = TRIM(?) AND TRIM(lastName) = TRIM(?) AND dateOfBirth = ? AND sex = ? LIMIT 1";
    $checkStmt = mysqli_prepare($conn, $checkResident);
    
    mysqli_stmt_bind_param($checkStmt, "ssss", $firstName, $lastName, $birthdate, $sex);
    mysqli_stmt_execute($checkStmt);
    
    $result = mysqli_stmt_get_result($checkStmt);
    $matchedResident = mysqli_fetch_assoc($result);
    
    mysqli_stmt_close($checkStmt);

    if ($matchedResident) {
        $resId = $matchedResident['residentId'];
        
        // CHECK IF THERE IS AN EXISTING ACCOUNT FOR RESIDENT
        $checkAccount = "SELECT userId FROM users WHERE residentId = ? LIMIT 1";
        $accStmt = mysqli_prepare($conn, $checkAccount);
        
        mysqli_stmt_bind_param($accStmt, "i", $resId);
        mysqli_stmt_execute($accStmt);
        
        $accResult = mysqli_stmt_get_result($accStmt);
        
        if (mysqli_fetch_assoc($accResult)) {
            $errors[] = "An account is already linked to this resident profile. Please login or reset your password.";
        }
        mysqli_stmt_close($accStmt);
    } else {
        if(emailRegistered($conn, $email)) {
            $errors[] = "Email address is already registered!";
        }
        if(phoneRegistered($conn, $phoneNumber)) {
            $errors[] = "Phone number is already in use!";
        }
    }

    // ERROR HANDLING
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        header("Location: ../pages/registerPage.php");
        exit();
    }

    // START DATABASE TRANSACTION
    mysqli_begin_transaction($conn);
    try {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $residentId = null;

        if ($matchedResident) {
            $residentId = $matchedResident['residentId'];
            $updateResident = "UPDATE residents SET phoneNumber = ?, emailAddress = ?, civilStatus = ?, fullAddress = ? WHERE residentId = ?";
            $upStmt = mysqli_prepare($conn, $updateResident);
            
            mysqli_stmt_bind_param($upStmt, "ssssi", $phoneNumber, $email, $civilStatus, $address, $residentId);
            mysqli_stmt_execute($upStmt);
            mysqli_stmt_close($upStmt);
        } else {
            $insertResident = "INSERT INTO residents (firstName, lastName, middleName, suffix, dateOfBirth, sex, civilStatus, fullAddress, phoneNumber, emailAddress) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $insResStmt = mysqli_prepare($conn, $insertResident);
            
            mysqli_stmt_bind_param($insResStmt, "ssssssssss", $firstName, $lastName, $middleName, $suffix, $birthdate, $sex, $civilStatus, $address, $phoneNumber, $email);
            mysqli_stmt_execute($insResStmt);
            
            $residentId = mysqli_insert_id($conn);
            
            mysqli_stmt_close($insResStmt);
        }

        // --- CREATE USER ACCOUNT OR LINK USER ACCOUNT TO AN EXISTING RESIDENT RECORD ---
        if ($residentId) {
            $accountStatus = 'pending';
            $insertUser = "INSERT INTO users (username, password, role, residentId, account_status, verification_proof) VALUES (?, ?, ?, ?, ?, ?)";
            $insUserStmt = mysqli_prepare($conn, $insertUser);
            
            mysqli_stmt_bind_param($insUserStmt, "sssiss", $username, $hashedPassword, $role, $residentId, $accountStatus, $proofPath);
            
            if (mysqli_stmt_execute($insUserStmt)) {
                $newUserId = (int) mysqli_insert_id($conn);
                mysqli_commit($conn);

                refreshToken();
                
                mysqli_stmt_close($insUserStmt);
                
                unset($_SESSION['old']);
                unset($_SESSION['registration_proof_path']);

                $_SESSION['success'] = "Registration submitted! You can now login to your account.";
                $registeredUserLabel = userAccountLogLabel($conn, $newUserId);
                logActivity($conn, $newUserId, $username, 'resident', 'registration_submitted', 'Resident registered account: ' . $registeredUserLabel . '. Pending admin verification.');
                
                header("Location: ../pages/loginPage.php"); 
                exit();
            } else {
                throw new Exception("Failed to create user account.");
            }
        }
    } catch (Exception $e) {
        mysqli_rollback($conn);

        $errorMessage = $e->getMessage();
        error_log("Registration failed: " . $errorMessage);
        $_SESSION['errors'] = ["Registration failed: " . $errorMessage];
        
        header("Location: ../pages/registerPage.php");
        exit();
    }
} else {
    header("Location: ../pages/registerPage.php");
    exit();
}
