<?php



// SANITIZE DATA
function clean($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}


// VALIDATION
function emptyField($username, $password, $confirmPassword, $firstName, $lastName, $birthdate, $sex, $address, $civilStatus, $phoneNumber, $email) {
    if(empty($username) || empty($password) || empty($confirmPassword) || empty($firstName) || empty($lastName) || empty($birthdate) 
        || empty($sex) || empty($address) || empty($civilStatus) || empty($phoneNumber) || empty($email)) {
        
        return true;    
    }

    return false;
}


function invalidNamePattern($firstName, $lastName) {
    $namePattern = "/^[a-zA-Z\sñÑ.]+$/";

    if ((!empty($firstName) || !empty($lastName)) && (!preg_match($namePattern, $firstName) || !preg_match($namePattern, $lastName))) {
        return true;
    }

    return false;

}


function invalidSex($sex) {
    $validSex = ['Male', 'Female'];

    if (!empty($sex) && !in_array($sex, $validSex)) {
        return true;
    }

    return false;
}


function invalidCivilStatus($civilStatus) {
    $validCivilStatus = ['Single', 'Married', 'Widowed', 'Separated'];

    if (!empty($civilStatus) && !in_array($civilStatus, $validCivilStatus)) {
        return true;
    }

    return false;
}


function passwordNotMatch($password, $confirmPassword) {
    if ($password !== $confirmPassword) {
        return true;
    }

    return false;
}


function passwordLength($password) {
    if (!empty($password) && strlen($password) < 8) {
        return true;
    }

    return false;
}


function invalidPhoneNo($phoneNumber) {
    if (!empty($phoneNumber) && !preg_match('/^09[0-9]{9}$/', $phoneNumber)) {
        return true;
    }

    return false;
}


function usernameTaken($conn, $username) {
    $stmt = mysqli_prepare($conn, "SELECT username FROM users WHERE LOWER(username) = LOWER(?) LIMIT 1");
    
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        return true;
    }

    return false;
}


function emailRegistered($conn, $email) {
    $stmt = mysqli_prepare($conn, "SELECT emailAddress FROM residents WHERE emailAddress = ? LIMIT 1");
    
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    return mysqli_num_rows($result) > 0;
}


function phoneRegistered($conn, $phoneNumber) {
    $stmt = mysqli_prepare($conn, "SELECT phoneNumber FROM residents WHERE phoneNumber = ? LIMIT 1");
    
    mysqli_stmt_bind_param($stmt, "s", $phoneNumber);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    return mysqli_num_rows($result) > 0;
}

function userColumnExists($conn, $columnName) {
    static $cache = [];

    if (!$conn) {
        return false;
    }

    if (isset($cache[$columnName])) {
        return $cache[$columnName];
    }

    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS column_count FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = ?");
    if (!$stmt) {
        $cache[$columnName] = false;
        return false;
    }

    mysqli_stmt_bind_param($stmt, "s", $columnName);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    $cache[$columnName] = $row && (int) $row['column_count'] > 0;
    return $cache[$columnName];
}

function userAccountLogLabel($conn, $userId) {
    $userId = (int) $userId;
    if (!$conn || $userId <= 0) {
        return 'Unknown account';
    }

    $firstNameSelect = userColumnExists($conn, 'first_name') ? 'u.first_name AS accountFirstName' : 'NULL AS accountFirstName';
    $lastNameSelect = userColumnExists($conn, 'last_name') ? 'u.last_name AS accountLastName' : 'NULL AS accountLastName';

    $sql = "SELECT
            u.username,
            u.role,
            $firstNameSelect,
            $lastNameSelect,
            res.firstName AS residentFirstName,
            res.lastName AS residentLastName
        FROM users u
        LEFT JOIN residents res ON u.residentId = res.residentId
        WHERE u.userId = ?
        LIMIT 1";

    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return 'User account #' . $userId;
    }

    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    if (!$user) {
        return 'User account #' . $userId;
    }

    $residentName = trim((string) ($user['residentFirstName'] ?? '') . ' ' . (string) ($user['residentLastName'] ?? ''));
    $accountName = trim((string) ($user['accountFirstName'] ?? '') . ' ' . (string) ($user['accountLastName'] ?? ''));
    $displayName = $residentName ?: ($accountName ?: ($user['username'] ?? ('User #' . $userId)));
    $username = trim((string) ($user['username'] ?? ''));
    $role = ucfirst((string) ($user['role'] ?? 'user'));

    $details = [];
    if ($username !== '') {
        $details[] = 'username: ' . $username;
    }
    if ($role !== '') {
        $details[] = 'role: ' . $role;
    }

    return $displayName . ($details ? ' (' . implode(', ', $details) . ')' : '');
}

function formatActivityLogDescription($conn, $description) {
    $description = trim((string) $description);
    if ($description === '') {
        return 'System activity';
    }

    if (preg_match('/\buser account #(\d+)\b/i', $description, $matches)) {
        $userId = (int) $matches[1];
        $userLabel = userAccountLogLabel($conn, $userId);

        if (stripos($userLabel, 'User account #') !== 0) {
            return preg_replace('/\buser account #' . preg_quote((string) $userId, '/') . '\b/i', 'user account: ' . $userLabel, $description, 1);
        }
    }

    return $description;
}


function invalidBirthdate($birthdate) {
    if (empty($birthdate)) return true;

    $dateInput = strtotime($birthdate);
    $currentDate = time();
    $minYear = strtotime("1900-01-01");

    if ($dateInput > $currentDate || $dateInput < $minYear) {
        return true;
    }
    
    return false;
}

function buildResidentDocumentData($residentData, $requestData) {
    $fillUpData = $requestData['fill_up_data'] ?? [];
    if (is_string($fillUpData)) {
        $fillUpData = json_decode($fillUpData, true) ?: [];
    }
    if (!is_array($fillUpData)) {
        $fillUpData = [];
    }

    $normalizeFillUpLabel = static function ($label) {
        return strtolower(preg_replace('/[^a-z0-9]+/i', '', (string) $label));
    };
    $getFillUpValue = static function (array $aliases) use ($fillUpData, $normalizeFillUpLabel) {
        $normalizedAliases = array_map($normalizeFillUpLabel, $aliases);
        foreach ($fillUpData as $label => $value) {
            if (in_array($normalizeFillUpLabel($label), $normalizedAliases, true) && is_scalar($value)) {
                return trim((string) $value);
            }
        }
        return '';
    };

    $isOnBehalf = strtolower($getFillUpValue(['Requesting on behalf of someone else'])) === 'yes';
    if ($isOnBehalf) {
        $residentData = [
            'full_name' => $getFillUpValue(['Full Name', 'Name', 'Resident Name']),
            'first_name' => $getFillUpValue(['First Name', 'Given Name']),
            'middle_name' => $getFillUpValue(['Middle Name']),
            'last_name' => $getFillUpValue(['Last Name', 'Surname', 'Family Name']),
            'suffix' => $getFillUpValue(['Suffix']),
            'birthdate' => $getFillUpValue(['Birthday', 'Birthdate', 'Date of Birth']),
            'sex' => $getFillUpValue(['Gender', 'Sex']),
            'civil_status' => $getFillUpValue(['Civil Status']),
            'address' => $getFillUpValue(['Address', 'Complete Address', 'Full Address']),
            'birthplace' => $getFillUpValue(['Place of Birth', 'Birthplace']),
        ];
    }

    $fullName = trim((string) ($residentData['first_name'] ?? '') . ' ' . (string) ($residentData['middle_name'] ?? '') . ' ' . (string) ($residentData['last_name'] ?? '') . ' ' . (string) ($residentData['suffix'] ?? ''));
    $fullName = preg_replace('/\s+/', ' ', $fullName);
    $fullName = trim($fullName);

    $address = trim((string) ($residentData['address'] ?? '') . ' ' . (string) ($residentData['full_address'] ?? ''));
    $address = preg_replace('/\s+/', ' ', $address);

    $birthdate = trim((string) ($residentData['birthdate'] ?? ''));
    $age = '';
    if ($birthdate !== '') {
        try {
            $dob = new DateTime($birthdate);
            $now = new DateTime('today');
            $age = (string) $now->diff($dob)->y;
        } catch (Exception $e) {
            $age = '';
        }
    }

    $issueDate = trim((string) ($requestData['date'] ?? ''));
    if ($issueDate === '') {
        $issueDate = date('F j, Y');
    }
    $residentName = $fullName ?: ($residentData['full_name'] ?? '');
    $residentAddress = $address ?: 'Barangay Krus na Ligas, Quezon City';
    $agePhrase = $age !== '' ? $age . ' years old' : 'of legal age';
    $fillUpData = $requestData['fill_up_data'] ?? [];
    if (is_string($fillUpData)) {
        $fillUpData = json_decode($fillUpData, true) ?: [];
    }
    if (!is_array($fillUpData)) {
        $fillUpData = [];
    }

    $isOnBehalf = false;
    foreach ($fillUpData as $fieldLabel => $fieldValue) {
        $normalizedLabel = strtolower(preg_replace('/[^a-z0-9]+/i', '', (string) $fieldLabel));
        if ($normalizedLabel === 'requestingonbehalfofsomeoneelse' && strtolower(trim((string) $fieldValue)) === 'yes') {
            $isOnBehalf = true;
            break;
        }
    }

    if (!$isOnBehalf) {
        $birthDateAnswer = $birthdate !== '' && strtotime($birthdate) !== false
            ? date('F j, Y', strtotime($birthdate))
            : $birthdate;
        $profileAnswers = [
            'Full Name' => $residentName,
            'Name' => $residentName,
            'First Name' => $residentData['first_name'] ?? '',
            'Middle Name' => $residentData['middle_name'] ?? '',
            'Last Name' => $residentData['last_name'] ?? '',
            'Suffix' => $residentData['suffix'] ?? '',
            'Address' => $residentAddress,
            'Complete Address' => $residentAddress,
            'Full Address' => $residentAddress,
            'Birthday' => $birthDateAnswer,
            'Birthdate' => $birthDateAnswer,
            'Date of Birth' => $birthDateAnswer,
            'Gender' => $residentData['sex'] ?? '',
            'Sex' => $residentData['sex'] ?? '',
            'Civil Status' => $residentData['civil_status'] ?? '',
            'Place of Birth' => $residentData['birthplace'] ?? '',
            'Birthplace' => $residentData['birthplace'] ?? '',
            'Purpose' => $requestData['purpose'] ?? '',
        ];
        $existingAnswerLabels = array_map(static function ($label) {
            return strtolower(preg_replace('/[^a-z0-9]+/i', '', (string) $label));
        }, array_keys($fillUpData));

        foreach ($profileAnswers as $label => $value) {
            $normalizedLabel = strtolower(preg_replace('/[^a-z0-9]+/i', '', $label));
            if (trim((string) $value) !== '' && !in_array($normalizedLabel, $existingAnswerLabels, true)) {
                $fillUpData[$label] = $value;
                $existingAnswerLabels[] = $normalizedLabel;
            }
        }
    }

    return [
        'name' => $residentName,
        'full_name' => $residentName,
        'first_name' => $residentData['first_name'] ?? '',
        'last_name' => $residentData['last_name'] ?? '',
        'middle_name' => $residentData['middle_name'] ?? '',
        'suffix' => $residentData['suffix'] ?? '',
        'sex' => $residentData['sex'] ?? '',
        'civil_status' => $residentData['civil_status'] ?? '',
        'birthplace' => $residentData['birthplace'] ?? '',
        'age' => $age,
        'age_phrase' => $agePhrase,
        'address' => $residentAddress,
        'full_address' => $residentAddress,
        'document_type' => $requestData['document_type'] ?? '',
        'purpose' => $requestData['purpose'] ?? '',
        'request_number' => $requestData['request_number'] ?? '',
        'date' => $issueDate,
        'today' => $issueDate,
        'barangay' => 'Barangay Krus na Ligas',
        'city' => 'Quezon City',
        'office' => 'Office of the Punong Barangay',
        'prepared_by' => 'Barangay Staff',
        'approved_by' => 'Punong Barangay',
        'fill_up_data' => $fillUpData
    ];
}

function applyDocumentPlaceholders($content, $data) {
    $replacements = [];
    foreach ($data as $key => $value) {
        $replacements['{{' . $key . '}}'] = (string) $value;
        $replacements['[[' . $key . ']]'] = (string) $value;
        $replacements['{' . $key . '}'] = (string) $value;
    }

    return strtr((string) $content, $replacements);
}

// FUNCTIONS_PHP_VERSION: 2026-08-25-fix2
function databaseColumnExists($conn, $tableName, $columnName) {
    if (!$conn) {
        return false;
    }

    $tableName = trim((string) $tableName);
    $columnName = trim((string) $columnName);
    if ($tableName === '' || $columnName === '') {
        return false;
    }
    if (!preg_match('/^[A-Za-z0-9_]+$/', $tableName) || !preg_match('/^[A-Za-z0-9_]+$/', $columnName)) {
        return false;
    }

    $stmt = mysqli_prepare(
        $conn,
        "SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1"
    );
    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, 'ss', $tableName, $columnName);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $exists = mysqli_fetch_assoc($result) !== null;
    mysqli_stmt_close($stmt);

    return $exists;
}

function documentTemplateSelectColumns($conn) {
    // The `requirements` column is part of the base schema (see document_templates
    // CREATE TABLE), so it is always selected directly instead of being probed at
    // runtime via databaseColumnExists(). This avoids relying on a prepared
    // SHOW COLUMNS / information_schema lookup on every request.
    return "id, name, category, description, fee, usage_count, file_name, file_path, template_content, requirements, fill_up_fields, created_at, last_modified";
}

function getDocumentTemplates($conn) {
    if (!$conn) {
        return [];
    }

    $templates = [];
    $selectColumns = documentTemplateSelectColumns($conn);
    $sql = "SELECT $selectColumns
            FROM document_templates
            ORDER BY name ASC, id ASC";
    $result = mysqli_query($conn, $sql);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $templates[] = $row;
        }
    }

    return $templates;
}

function getDocumentTemplateById($conn, $templateId) {
    $templateId = (int) $templateId;
    if (!$conn || $templateId <= 0) {
        return null;
    }

    $stmt = mysqli_prepare(
        $conn,
        "SELECT " . documentTemplateSelectColumns($conn) . "
         FROM document_templates
         WHERE id = ?
         LIMIT 1"
    );
    if (!$stmt) {
        return null;
    }

    mysqli_stmt_bind_param($stmt, 'i', $templateId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $template = mysqli_fetch_assoc($result) ?: null;
    mysqli_stmt_close($stmt);

    return $template;
}

function getDocumentTemplateForRequest($conn, $requestData) {
    $templateId = (int) ($requestData['template_id'] ?? 0);
    if ($templateId > 0) {
        $template = getDocumentTemplateById($conn, $templateId);
        if ($template) {
            return $template;
        }
    }

    $documentType = trim((string) ($requestData['document_type'] ?? ''));
    if (!$conn || $documentType === '') {
        return null;
    }

    $stmt = mysqli_prepare(
        $conn,
        "SELECT " . documentTemplateSelectColumns($conn) . "
         FROM document_templates
         WHERE LOWER(TRIM(name)) = LOWER(TRIM(?))
         ORDER BY id DESC
         LIMIT 1"
    );
    if (!$stmt) {
        return null;
    }

    mysqli_stmt_bind_param($stmt, 's', $documentType);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $template = mysqli_fetch_assoc($result) ?: null;
    mysqli_stmt_close($stmt);

    return $template;
}

function parseDocumentTemplateRequirements($requirementsText) {
    $lines = preg_split('/\r\n|\r|\n/', (string) $requirementsText);
    $requirements = [];
    foreach ($lines as $line) {
        $requirement = trim($line);
        if ($requirement !== '') {
            $requirements[] = $requirement;
        }
    }

    return array_values(array_unique($requirements));
}

function parseDocumentTemplateFillUpFields($fieldsText) {
    $lines = preg_split('/\r\n|\r|\n/', (string) $fieldsText);
    $fields = [];
    foreach ($lines as $line) {
        $field = trim($line);
        if ($field !== '') {
            $fields[] = $field;
        }
    }

    return array_values(array_unique($fields));
}

function residentProfileValueForTemplateField($fieldLabel, $profile) {
    $normalizedLabel = strtolower((string) $fieldLabel);
    $normalizedLabel = preg_replace('/[^a-z0-9]+/', '', $normalizedLabel);
    $fullName = trim(implode(' ', array_filter([
        $profile['first_name'] ?? '',
        $profile['middle_name'] ?? '',
        $profile['last_name'] ?? '',
        $profile['suffix'] ?? '',
    ], static function ($part) {
        return trim((string) $part) !== '';
    })));

    $profileValues = [
        'name' => $fullName,
        'fullname' => $fullName,
        'residentname' => $fullName,
        'firstname' => $profile['first_name'] ?? '',
        'middlename' => $profile['middle_name'] ?? '',
        'lastname' => $profile['last_name'] ?? '',
        'suffix' => $profile['suffix'] ?? '',
        'address' => $profile['address'] ?? '',
        'completeaddress' => $profile['address'] ?? '',
        'fulladdress' => $profile['address'] ?? '',
        'birthdate' => $profile['birthdate'] ?? '',
        'birthday' => $profile['birthdate'] ?? '',
        'dateofbirth' => $profile['birthdate'] ?? '',
        'gender' => $profile['sex'] ?? '',
        'sex' => $profile['sex'] ?? '',
        'civilstatus' => $profile['civil_status'] ?? '',
        'birthplace' => $profile['birthplace'] ?? '',
        'placeofbirth' => $profile['birthplace'] ?? '',
    ];

    return trim((string) ($profileValues[$normalizedLabel] ?? ''));
}

function renderTemplateFillUpFieldsEditor($fieldsText) {
    $fields = parseDocumentTemplateFillUpFields($fieldsText);
    if (!$fields) {
        $fields = [''];
    }

    $html = '<div class="template-fill-up-editor" data-template-fill-up-editor><div class="template-fill-up-rows" data-template-fill-up-rows>';
    foreach ($fields as $field) {
        $html .= '<div class="template-fill-up-row">';
        $html .= '<input type="text" name="fill_up_fields[]" value="' . htmlspecialchars($field, ENT_QUOTES, 'UTF-8') . '" placeholder="e.g. Full Name" aria-label="Fill-up field">';
        $html .= '<button type="button" class="template-fill-up-remove" data-remove-fill-up-field aria-label="Remove fill-up field"><i class="bi bi-dash-lg" aria-hidden="true"></i></button>';
        $html .= '</div>';
    }

    return $html . '</div><button type="button" class="template-fill-up-add" data-add-fill-up-field><i class="bi bi-plus-lg" aria-hidden="true"></i><span>Add field</span></button></div>';
}

function sanitizeTemplateHtml($html) {
    $html = trim((string) $html);
    if ($html === '') {
        return '';
    }

    $document = new DOMDocument('1.0', 'UTF-8');
    libxml_use_internal_errors(true);
    $document->loadHTML('<?xml encoding="UTF-8"><div id="template-body">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    $root = $document->getElementById('template-body');
    if (!$root) {
        return '';
    }

    $allowedTags = ['div', 'p', 'br', 'strong', 'b', 'em', 'i', 'u', 'span', 'font'];
    $walk = function (DOMNode $node) use (&$walk, $allowedTags) {
        if ($node instanceof DOMElement) {
            if (!in_array(strtolower($node->tagName), $allowedTags, true)) {
                $text = $node->ownerDocument->createTextNode($node->textContent);
                $node->parentNode->replaceChild($text, $node);
                return;
            }

            $fontSize = $node->getAttribute('size');
            $style = $node->getAttribute('style');
            while ($node->attributes->length > 0) {
                $node->removeAttributeNode($node->attributes->item(0));
            }
            if (strtolower($node->tagName) === 'font' && in_array($fontSize, ['1', '2', '3', '4', '5', '6', '7'], true)) {
                $node->setAttribute('size', $fontSize);
            }
            if ($style !== '') {
                $safeStyle = [];
                foreach (explode(';', strtolower($style)) as $declaration) {
                    [$property, $value] = array_pad(explode(':', $declaration, 2), 2, '');
                    $property = trim($property);
                    $value = trim($value);
                    if (in_array($property, ['text-align', 'font-size', 'font-weight', 'font-style', 'text-decoration'], true)
                        && preg_match('/^[a-z0-9 .%(),-]+$/i', $value)) {
                        $safeStyle[] = $property . ':' . $value;
                    }
                }
                if ($safeStyle) {
                    $node->setAttribute('style', implode(';', $safeStyle));
                }
            }
        }

        foreach (iterator_to_array($node->childNodes) as $child) {
            $walk($child);
        }
    };
    foreach (iterator_to_array($root->childNodes) as $child) {
        $walk($child);
    }

    $result = '';
    foreach ($root->childNodes as $child) {
        $result .= $document->saveHTML($child);
    }
    return trim($result);
}

function renderTemplateBodyEditor($content) {
    $safeContent = sanitizeTemplateHtml($content);
    $escapedContent = htmlspecialchars($safeContent, ENT_QUOTES, 'UTF-8');
    $fontSizeOptions = '<option value="">Choose size</option>';
    for ($size = 8; $size <= 24; $size += 0.5) {
        $label = rtrim(rtrim(number_format($size, 1, '.', ''), '0'), '.');
        $fontSizeOptions .= '<option value="' . $label . '">' . $label . ' pt</option>';
    }
    foreach ([26, 28, 32, 36, 48, 72] as $size) {
        $fontSizeOptions .= '<option value="' . $size . '">' . $size . ' pt</option>';
    }
    return '<div class="template-rich-editor" data-template-rich-editor>'
        . '<div class="template-rich-toolbar" role="toolbar" aria-label="Template formatting">'
        . '<span class="template-toolbar-label">Format</span>'
        . '<span class="template-toolbar-group">'
        . '<button type="button" data-template-command="bold" aria-label="Bold" title="Bold"><strong>B</strong></button>'
        . '<button type="button" data-template-command="italic" aria-label="Italic" title="Italic"><em>I</em></button>'
        . '<button type="button" data-template-command="underline" aria-label="Underline" title="Underline"><u>U</u></button>'
        . '</span>'
        . '<span class="template-toolbar-group template-toolbar-alignment">'
        . '<button type="button" data-template-command="justifyLeft" aria-label="Align left" title="Align left"><i class="bi bi-text-left" aria-hidden="true"></i></button>'
        . '<button type="button" data-template-command="justifyCenter" aria-label="Align center" title="Align center"><i class="bi bi-text-center" aria-hidden="true"></i></button>'
        . '<button type="button" data-template-command="justifyRight" aria-label="Align right" title="Align right"><i class="bi bi-text-right" aria-hidden="true"></i></button>'
        . '<button type="button" data-template-command="justifyFull" aria-label="Justify" title="Justify"><i class="bi bi-justify" aria-hidden="true"></i></button>'
        . '</span>'
        . '<label class="template-font-size-control"><span>Size</span><select data-template-font-size aria-label="Font size in points">' . $fontSizeOptions . '</select></label>'
        . '</div>'
        . '<div class="template-rich-content" contenteditable="true" data-template-editor-content aria-label="Template body">' . $safeContent . '</div>'
        . '<textarea name="template_content" data-template-editor-value hidden>' . $escapedContent . '</textarea>'
        . '<div class="template-editor-hint"><i class="bi bi-info-circle" aria-hidden="true"></i><span>Write the complete document body. Use placeholders such as <code>{{name}}</code>, <code>{{address}}</code>, or <code>/Gender</code>.</span></div>'
        . '</div>';
}

function renderDocumentRequestFillUpData($fillUpDataJson) {
    $answers = json_decode((string) $fillUpDataJson, true);
    if (!is_array($answers) || !$answers) {
        return '';
    }

    $html = '<div class="details-grid request-fill-up-data"><div class="wide"><span>Resident-provided details</span></div>';
    foreach ($answers as $label => $value) {
        if (!is_scalar($value) || trim((string) $value) === '') {
            continue;
        }

        $html .= '<div class="wide"><span>' . htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8') . '</span><strong>' . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') . '</strong></div>';
    }

    return $html . '</div>';
}

function normalizeBarangayDocumentType($documentType) {
    $type = strtolower(trim((string) $documentType));
    if (strpos($type, 'indigency') !== false || strpos($type, 'indigent') !== false) {
        return 'indigency';
    }
    if (strpos($type, 'residency') !== false || strpos($type, 'resident') !== false) {
        return 'residency';
    }
    if (strpos($type, 'clearance') !== false) {
        return 'clearance';
    }
    return 'certificate';
}

function getDefaultBarangayDocumentTemplates() {
    $now = date('Y-m-d H:i:s');
    return [
        [
            'id' => -1,
            'name' => 'Barangay Clearance',
            'category' => 'Certificates',
            'description' => 'Formal clearance certifying residency, good moral character, and no derogatory barangay record.',
            'fee' => 0,
            'usage_count' => 0,
            'file_name' => null,
            'file_path' => null,
            'template_content' => '',
            'fill_up_fields' => "Full Name\nComplete Address\nDate of Birth\nPlace of Birth",
            'created_at' => $now,
            'last_modified' => $now,
            'is_default' => true,
        ],
        [
            'id' => -2,
            'name' => 'Certificate of Residency',
            'category' => 'Certificates',
            'description' => 'Standard barangay certification that the requester is a bona fide resident of the barangay.',
            'fee' => 0,
            'usage_count' => 0,
            'file_name' => null,
            'file_path' => null,
            'template_content' => '',
            'fill_up_fields' => "Full Name\nComplete Address\nDate of Birth\nPlace of Birth",
            'created_at' => $now,
            'last_modified' => $now,
            'is_default' => true,
        ],
        [
            'id' => -3,
            'name' => 'Certificate of Indigency',
            'category' => 'Certificates',
            'description' => 'Formal certification for residents requesting assistance, scholarship, medical, or other support.',
            'fee' => 0,
            'usage_count' => 0,
            'file_name' => null,
            'file_path' => null,
            'template_content' => '',
            'fill_up_fields' => "Full Name\nComplete Address\nDate of Birth\nPlace of Birth\nNumber of Household Members\nEstimated Monthly Household Income",
            'created_at' => $now,
            'last_modified' => $now,
            'is_default' => true,
        ],
    ];
}

function mergeDefaultBarangayDocumentTemplates($templates) {
    $existingNames = [];
    foreach ($templates as $template) {
        $existingNames[strtolower(trim((string) ($template['name'] ?? '')))] = true;
    }

    foreach (getDefaultBarangayDocumentTemplates() as $defaultTemplate) {
        $defaultName = strtolower(trim((string) ($defaultTemplate['name'] ?? '')));
        if (!isset($existingNames[$defaultName])) {
            $templates[] = $defaultTemplate;
        }
    }

    return $templates;
}

function getBarangayDocumentTemplateContent($documentType = '') {
    // Single, generic fallback body — used only as a starting point/placeholder
    // (e.g. when seeding the built-in default templates, or if a template ends up
    // with no content at all). It is the SAME for every document type; nothing
    // certificate-specific (indigency wording, clearance wording, residency
    // wording, or wording for any new type an admin invents) is hardcoded here.
    // All of that now lives purely in whatever the admin/staff types into the
    // "Template Body" field when they create or edit a template.
    return "has requested a barangay document from this Office.";
}

function buildBarangayDocumentCertificate($data) {
    $documentType = trim((string) ($data['document_type'] ?? 'Barangay Certificate'));

    return buildBarangayDocumentCertificateFromTemplate(getBarangayDocumentTemplateContent($documentType), $data);
}

function buildBarangayDocumentCertificateFromTemplate($templateContent, $data) {
    $name = trim((string) ($data['name'] ?? 'Resident'));
    $agePhrase = trim((string) ($data['age_phrase'] ?? 'of legal age'));
    $address = trim((string) ($data['address'] ?? 'Barangay Krus na Ligas, Quezon City'));
    $purpose = trim((string) ($data['purpose'] ?? 'official purposes'));
    if ($purpose === '') {
        $purpose = 'official purposes';
    }
    $issueDate = trim((string) ($data['date'] ?? date('F j, Y')));
    $documentType = trim((string) ($data['document_type'] ?? 'Barangay Certificate'));

    // Resolve slash-prefixed fill-up labels such as /Gender, regardless of case.
    $rawBody = sanitizeTemplateHtml($templateContent);
    foreach ($data as $placeholderKey => $placeholderValue) {
        if (is_scalar($placeholderValue)) {
            $rawBody = str_replace(
                ['{{' . $placeholderKey . '}}', '[[' . $placeholderKey . ']]', '{' . $placeholderKey . '}'],
                (string) $placeholderValue,
                $rawBody
            );
        }
    }
    foreach (($data['fill_up_data'] ?? []) as $fieldLabel => $fieldValue) {
        $fieldLabel = trim((string) $fieldLabel);
        if ($fieldLabel === '' || !is_scalar($fieldValue)) {
            continue;
        }

        $placeholderLabel = rtrim($fieldLabel, " \t\n\r\0\x0B:");
        if ($placeholderLabel === '') {
            continue;
        }

        $normalizedPlaceholderLabel = strtolower(preg_replace('/[^a-z0-9]+/i', '', $placeholderLabel));
        $replacementValue = (string) $fieldValue;
        if (in_array($normalizedPlaceholderLabel, ['gender', 'sex'], true)) {
            $genderAnswer = strtolower(trim((string) $fieldValue));
            if (in_array($genderAnswer, ['female', 'woman', 'girl', 'f'], true)) {
                $replacementValue = 'she';
            } elseif (in_array($genderAnswer, ['male', 'man', 'boy', 'm'], true)) {
                $replacementValue = 'he';
            }
        }

        $placeholderPattern = '/\/\s*' . preg_quote($placeholderLabel, '/') . '(?![a-zA-Z0-9_])/i';
        $rawBody = preg_replace_callback($placeholderPattern, static function () use ($replacementValue) {
            return $replacementValue;
        }, $rawBody);
    }

    $genderValue = trim((string) ($data['sex'] ?? ''));
    foreach (($data['fill_up_data'] ?? []) as $fieldLabel => $fieldValue) {
        $normalizedLabel = strtolower(preg_replace('/[^a-z0-9]+/i', '', (string) $fieldLabel));
        if (in_array($normalizedLabel, ['gender', 'sex'], true) && is_scalar($fieldValue) && trim((string) $fieldValue) !== '') {
            $genderValue = trim((string) $fieldValue);
            break;
        }
    }

    $genderKey = strtolower($genderValue);
    $pronouns = [];
    if (in_array($genderKey, ['female', 'woman', 'girl', 'she', 'f'], true)) {
        $pronouns = [
            'he' => 'she',
            'she' => 'she',
            'him' => 'her',
            'her' => 'her',
            'his' => 'her',
            'hers' => 'hers',
            'himself' => 'herself',
            'herself' => 'herself',
        ];
    } elseif (in_array($genderKey, ['male', 'man', 'boy', 'he', 'm'], true)) {
        $pronouns = [
            'he' => 'he',
            'she' => 'he',
            'him' => 'him',
            'her' => 'him',
            'his' => 'his',
            'hers' => 'his',
            'himself' => 'himself',
            'herself' => 'himself',
        ];
    }

    if ($pronouns) {
        $rawBody = preg_replace_callback('/\b(himself|herself|he|she|him|her|his|hers)\b/i', static function ($matches) use ($pronouns) {
            $word = $matches[0];
            $replacement = $pronouns[strtolower($word)] ?? $word;
            if ($word === strtoupper($word)) {
                return strtoupper($replacement);
            }
            if ($word === ucfirst(strtolower($word))) {
                return ucfirst($replacement);
            }
            return $replacement;
        }, $rawBody);
    }

    $bodyText = preg_replace('/<\/(p|div)>/i', "\n", $rawBody);
    $bodyText = preg_replace('/<br\s*\/?>/i', "\n", $bodyText);
    $bodyText = strip_tags($bodyText);
    $bodyLines = preg_split('/\r\n|\r|\n/', $bodyText);
    $bodyParagraphs = array_values(array_filter(array_map('trim', $bodyLines), static function ($line) {
        return $line !== '';
    }));

    return [
        'title' => strtoupper($documentType),
        'control_number' => trim((string) ($data['request_number'] ?? '')),
        'date' => $issueDate,
        'body_html' => $rawBody,
        'paragraphs' => $bodyParagraphs,
        'prepared_by' => trim((string) ($data['prepared_by'] ?? 'Barangay Staff')),
        'approved_by' => trim((string) ($data['approved_by'] ?? 'Punong Barangay')),
    ];
}

function buildBarangayDocumentPlainText($certificate) {
    $lines = [
        'Republic of the Philippines',
        'Quezon City',
        'Barangay Krus na Ligas',
        'Office of the Punong Barangay',
        '',
        trim((string) ($certificate['title'] ?? 'BARANGAY CERTIFICATE')),
        'Control No. ' . trim((string) ($certificate['control_number'] ?? '')),
        '',
        'TO WHOM IT MAY CONCERN:',
        '',
    ];

    foreach (($certificate['paragraphs'] ?? []) as $paragraph) {
        $paragraph = trim((string) $paragraph);
        if ($paragraph !== '') {
            $lines[] = $paragraph;
            $lines[] = '';
        }
    }

    $lines[] = 'RENIEL JOHN C. PERDIDO';
    $lines[] = 'Punong Barangay';

    return implode("\n", $lines);
}

function generateDocumentForRequest($conn, $requestId, $outputDir) {
    $requestId = (int) $requestId;
    if (!$conn || $requestId <= 0) {
        return '';
    }

    $stmt = mysqli_prepare(
        $conn,
        "SELECT
            r.request_id,
            r.request_number,
            r.document_type,
            r.purpose,
            r.fill_up_data,
            r.template_id,
            r.reviewed_at,
            r.date_updated,
            res.firstName,
            res.middleName,
            res.lastName,
            res.suffix,
            res.dateOfBirth,
            res.sex,
            res.civilStatus,
            res.fullAddress
         FROM document_requests r
         LEFT JOIN users u ON r.user_id = u.userId
         LEFT JOIN residents res ON u.residentId = res.residentId
         WHERE r.request_id = ?
         LIMIT 1"
    );
    if (!$stmt) {
        return '';
    }

    mysqli_stmt_bind_param($stmt, 'i', $requestId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $request = mysqli_fetch_assoc($result) ?: null;
    mysqli_stmt_close($stmt);

    if (!$request) {
        return '';
    }

    $issueSourceDate = $request['reviewed_at'] ?: ($request['date_updated'] ?? '');
    $issueDate = $issueSourceDate ? date('F j, Y', strtotime($issueSourceDate)) : date('F j, Y');
    $documentData = buildResidentDocumentData([
        'first_name' => $request['firstName'] ?? '',
        'middle_name' => $request['middleName'] ?? '',
        'last_name' => $request['lastName'] ?? '',
        'suffix' => $request['suffix'] ?? '',
        'birthdate' => $request['dateOfBirth'] ?? '',
        'sex' => $request['sex'] ?? '',
        'civil_status' => $request['civilStatus'] ?? '',
        'address' => $request['fullAddress'] ?? '',
    ], [
        'document_type' => $request['document_type'] ?? 'Barangay Certificate',
        'purpose' => $request['purpose'] ?? 'official purposes',
        'fill_up_data' => $request['fill_up_data'] ?? '',
        'request_number' => $request['request_number'] ?? '',
        'date' => $issueDate,
    ]);

    $template = getDocumentTemplateForRequest($conn, $request);
    $templateContent = $template['template_content'] ?? getBarangayDocumentTemplateContent($request['document_type'] ?? 'Barangay Certificate');
    $certificate = buildBarangayDocumentCertificateFromTemplate($templateContent, $documentData);
    $generatedPath = generateBarangayCertificatePdf($certificate, $outputDir, $request['request_number'] ?? ('request-' . $requestId));

    if ($generatedPath !== '') {
        $updateStmt = mysqli_prepare($conn, "UPDATE document_requests SET generated_file_path = ? WHERE request_id = ?");
        if ($updateStmt) {
            mysqli_stmt_bind_param($updateStmt, 'si', $generatedPath, $requestId);
            mysqli_stmt_execute($updateStmt);
            mysqli_stmt_close($updateStmt);
        }
    }

    return $generatedPath;
}

function regenerateGeneratedDocumentsForTemplate($conn, $templateId, $outputDir) {
    $templateId = (int) $templateId;
    if (!$conn || $templateId <= 0) {
        return 0;
    }

    $stmt = mysqli_prepare(
        $conn,
        "SELECT request_id
         FROM document_requests
         WHERE template_id = ?
           AND status IN ('approved', 'printed', 'released')
         ORDER BY request_id ASC"
    );
    if (!$stmt) {
        return 0;
    }

    mysqli_stmt_bind_param($stmt, 'i', $templateId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $updatedCount = 0;
    while ($row = mysqli_fetch_assoc($result)) {
        $generatedPath = generateDocumentForRequest($conn, (int) ($row['request_id'] ?? 0), $outputDir);
        if ($generatedPath !== '') {
            $updatedCount++;
        }
    }
    mysqli_stmt_close($stmt);

    return $updatedCount;
}

function escapePdfText($text) {
    return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], (string) $text);
}

/**
 * Converts display text to a built-in PDF font encoding without changing the
 * certificate data. This keeps the generated PDF self-contained.
 */
function barangayPdfText($text) {
    $text = (string) $text;
    if (function_exists('iconv')) {
        $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT', $text);
        if ($converted !== false) {
            $text = $converted;
        }
    }

    return escapePdfText($text);
}

function barangayPdfTextCommand($font, $size, $x, $y, $text, $color = '0 0 0') {
    return "BT\n" . $color . " rg\n/" . $font . ' ' . $size . " Tf\n" . round($x, 2) . ' ' . round($y, 2) . " Td\n(" . barangayPdfText($text) . ") Tj\nET\n";
}

function barangayPdfCenteredTextCommand($font, $size, $centerX, $y, $text, $color = '0 0 0') {
    // Built-in PDF fonts do not expose font metrics here. This conservative
    // estimate is sufficient for the fixed certificate headings and signature.
    $encoded = barangayPdfText($text);
    $x = $centerX - ((strlen($encoded) * $size * 0.52) / 2);

    return barangayPdfTextCommand($font, $size, $x, $y, $text, $color);
}

function barangayPdfJpegImage($filePath) {
    if (!is_file($filePath)) {
        return null;
    }

    $imageSize = @getimagesize($filePath);
    $imageData = @file_get_contents($filePath);
    if (!$imageSize || $imageData === false || empty($imageSize[0]) || empty($imageSize[1])) {
        return null;
    }

    return [
        'width' => (int) $imageSize[0],
        'height' => (int) $imageSize[1],
        'object' => "<< /Type /XObject /Subtype /Image /Width " . (int) $imageSize[0]
            . " /Height " . (int) $imageSize[1]
            . " /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length " . strlen($imageData)
            . " >>\nstream\n" . $imageData . "\nendstream",
    ];
}

function templateBodyPdfLines($bodyHtml, $defaultFontSize) {
        $document = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8"><div id="body">' . (string) $bodyHtml . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        $root = $document->getElementById('body');
        if (!$root) {
            return [];
        }

        $lines = [[]];
        $appendText = function ($text, $size, $bold) use (&$lines) {
            $text = preg_replace('/\s+/', ' ', (string) $text);
            if (trim($text) === '') {
                return;
            }
            $lines[count($lines) - 1][] = [
                'text' => $text,
                'size' => $size,
                'font' => $bold ? 'F2' : 'F1',
            ];
        };
        $walk = function (DOMNode $node, $size, $bold) use (&$walk, &$lines, $appendText, $defaultFontSize) {
            if ($node instanceof DOMText) {
                $appendText($node->nodeValue, $size, $bold);
                return;
            }
            if (!$node instanceof DOMElement) {
                return;
            }

            $nextSize = $size;
            $nextBold = $bold || in_array(strtolower($node->tagName), ['b', 'strong'], true);
            $style = strtolower($node->getAttribute('style'));
            if (preg_match('/font-size\s*:\s*([0-9]+(?:\.[0-9]+)?)pt/i', $style, $match)) {
                $nextSize = max(6, min(72, (float) $match[1]));
            }
            foreach ($node->childNodes as $child) {
                $walk($child, $nextSize, $nextBold);
            }
            if (in_array(strtolower($node->tagName), ['br', 'p', 'div'], true)) {
                $lines[] = [];
            }
        };
        foreach ($root->childNodes as $child) {
            $walk($child, (float) $defaultFontSize, false);
        }

        while ($lines && !$lines[count($lines) - 1]) {
            array_pop($lines);
        }
        return $lines;
    }

/**
 * Creates the approved-document PDF using the same fixed visual identity as
 * the admin/staff certificate preview: letterhead, red bars/footer, and the
 * single Punong Barangay signature anchored above the footer.
 */
function generateBarangayCertificatePdf($certificate, $outputDir, $requestNumber) {
    if (!is_dir($outputDir)) {
        mkdir($outputDir, 0777, true);
    }

    $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', (string) ($requestNumber ?: 'document'));
    $fileName = $safeName . '_' . time() . '.pdf';
    $filePath = rtrim($outputDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $fileName;

    $projectRoot = dirname(__DIR__);
    $knlLogo = barangayPdfJpegImage($projectRoot . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'template-logos' . DIRECTORY_SEPARATOR . 'knl-logo.jpg');
    $qcLogo = barangayPdfJpegImage($projectRoot . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'template-logos' . DIRECTORY_SEPARATOR . 'lungsod.jpg');

    $plainBody = trim(strip_tags((string) ($certificate['body_html'] ?? '')));
    $estimatedLineCount = max(1, count(preg_split('/\r\n|\r|\n/', wordwrap($plainBody, 92, "\n", true))));

    // Keep every paragraph on one page while reserving the lower-right area
    // for the signature and the fixed footer.
    $lineCount = $estimatedLineCount;
    $availableBodyHeight = 430;
    $lineHeight = max(7, min(15, $availableBodyHeight / $lineCount));
    $bodyFontSize = max(6, min(11, $lineHeight * 0.72));
    $bodyLines = templateBodyPdfLines((string) ($certificate['body_html'] ?? ''), $bodyFontSize);
    if (!$bodyLines) {
        $bodyLines = [[['text' => '', 'size' => $bodyFontSize, 'font' => 'F1']]];
    }

    $contentStream = "q\n0.608 0.255 0.267 rg\n24 806 547 18 re f\n24 24 547 54 re f\nQ\n";
    if ($knlLogo) {
        $contentStream .= "q\n52 0 0 52 42 718 cm\n/Im1 Do\nQ\n";
    }
    if ($qcLogo) {
        $contentStream .= "q\n52 0 0 52 501 718 cm\n/Im2 Do\nQ\n";
    }

    $contentStream .= barangayPdfCenteredTextCommand('F2', 10, 297.5, 780, 'REPUBLIC OF THE PHILIPPINES');
    $contentStream .= barangayPdfCenteredTextCommand('F2', 10, 297.5, 767, 'QUEZON CITY, METRO MANILA');
    $contentStream .= barangayPdfCenteredTextCommand('F2', 24, 297.5, 738, 'BARANGAY KRUS NA LIGAS', '0.651 0.255 0.275');
    $contentStream .= barangayPdfCenteredTextCommand('F1', 18, 297.5, 676, strtoupper((string) ($certificate['title'] ?? 'BARANGAY CERTIFICATE')));
    $contentStream .= "0 0 0 RG\n1 w\n170 671 m\n425 671 l\nS\n";
    $contentStream .= barangayPdfTextCommand('F2', 10.5, 70, 626, 'TO WHOM IT MAY CONCERN:');

    $bodyY = 594;
    foreach ($bodyLines as $lineRuns) {
        $bodyX = 70;
        foreach ($lineRuns as $run) {
            $text = trim((string) ($run['text'] ?? ''));
            if ($text === '') {
                continue;
            }
            $runSize = (float) ($run['size'] ?? $bodyFontSize);
            $runFont = $run['font'] ?? 'F1';
            $maxCharacters = max(18, (int) floor(92 * ($bodyFontSize / max($runSize, 1))));
            $chunks = explode("\n", wordwrap($text, $maxCharacters, "\n", true));
            foreach ($chunks as $chunkIndex => $chunk) {
                if ($chunkIndex > 0) {
                    $bodyY -= $lineHeight;
                    $bodyX = 70;
                }
                $contentStream .= barangayPdfTextCommand($runFont, $runSize, $bodyX, $bodyY, trim($chunk));
                $bodyX += max(20, strlen($chunk) * $runSize * 0.48);
            }
        }
        $bodyY -= $lineHeight;
    }

    $contentStream .= barangayPdfCenteredTextCommand('F2', 10, 445, 128, 'RENIEL JOHN C. PERDIDO');
    $contentStream .= barangayPdfCenteredTextCommand('F4', 9.5, 445, 114, 'Punong Barangay');
    $contentStream .= barangayPdfTextCommand('F3', 5.8, 38, 47, '0962-3962951 / 0287110367', '1 1 1');
    $contentStream .= barangayPdfTextCommand('F3', 5.8, 160, 47, 'brgy.krusnaligas@quezoncity.gov.ph', '1 1 1');
    $contentStream .= barangayPdfTextCommand('F3', 5.8, 335, 47, '20 Plaza Sta. Ines Street', '1 1 1');

    $streamObject = "<< /Length " . strlen($contentStream) . " >>\nstream\n" . $contentStream . "endstream";
    $resources = '<< /Font << /F1 5 0 R /F2 6 0 R /F3 7 0 R /F4 8 0 R >>';
    // F4 occupies object 8; image objects begin at object 9.
    $imageResources = '';
    $imageObjects = [];
    if ($knlLogo) {
        $imageResources .= ' /Im1 9 0 R';
        $imageObjects[] = $knlLogo['object'];
    }
    if ($qcLogo) {
        $imageResources .= ' /Im2 ' . (9 + count($imageObjects)) . ' 0 R';
        $imageObjects[] = $qcLogo['object'];
    }
    if ($imageResources !== '') {
        $resources .= ' /XObject <<' . $imageResources . ' >>';
    }
    $resources .= ' >>';

    $objects = [
        '<< /Type /Catalog /Pages 2 0 R >>',
        '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
        '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources ' . $resources . ' >>',
        $streamObject,
        '<< /Type /Font /Subtype /Type1 /BaseFont /Times-Roman >>',
        '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>',
        '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Oblique >>',
    ];
    foreach ($imageObjects as $imageObject) {
        $objects[] = $imageObject;
    }

    $pdf = "%PDF-1.4\n";
    $offsets = [];
    foreach ($objects as $index => $object) {
        $offsets[$index] = strlen($pdf);
        $pdf .= ($index + 1) . " 0 obj\n" . $object . "\nendobj\n";
    }

    $xrefOffset = strlen($pdf);
    $pdf .= 'xref' . "\n0 " . (count($objects) + 1) . "\n";
    $pdf .= "0000000000 65535 f \n";
    foreach ($offsets as $offset) {
        $pdf .= sprintf("%010d 00000 n \n", $offset);
    }
    $pdf .= 'trailer' . "\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n" . $xrefOffset . "\n%%EOF\n";

    file_put_contents($filePath, $pdf);

    return 'uploads/generated_documents/' . basename($filePath);
}

function generateFilledDocumentPdf($templateContent, $outputDir, $data, $requestNumber) {
    if (!is_dir($outputDir)) {
        mkdir($outputDir, 0777, true);
    }

    $filledText = applyDocumentPlaceholders((string) $templateContent, $data);
    if (trim($filledText) === '') {
        $filledText = applyDocumentPlaceholders(getBarangayDocumentTemplateContent($data['document_type'] ?? 'Barangay Certificate'), $data);
    }

    $lines = preg_split('/\r\n|\r|\n/', $filledText);
    $lines = array_values(array_filter($lines, static function ($line) {
        return trim($line) !== '';
    }));
    if ($lines === []) {
        $lines = ['Barangay Document'];
    }

    $wrappedLines = [];
    foreach ($lines as $line) {
        $wrapped = wordwrap($line, 90, "\n", true);
        $wrappedLines = array_merge($wrappedLines, explode("\n", $wrapped));
    }

    $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', (string) ($requestNumber ?: 'document'));
    $fileName = $safeName . '_' . time() . '.pdf';
    $filePath = rtrim($outputDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $fileName;

    $contentStream = "BT\n/F1 12 Tf\n72 720 Td\n";
    foreach ($wrappedLines as $index => $line) {
        $contentStream .= '(' . escapePdfText($line) . ") Tj\n";
        if ($index < count($wrappedLines) - 1) {
            $contentStream .= "0 -14 Td\n";
        }
    }
    $contentStream .= "ET\n";

    $streamObject = "<< /Length " . strlen($contentStream) . " >>\nstream\n" . $contentStream . "\nendstream";

    $objects = [
        '<< /Type /Catalog /Pages 2 0 R >>',
        '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
        '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
        $streamObject,
        '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>'
    ];

    $pdf = "%PDF-1.4\n";
    $offsets = [];
    foreach ($objects as $index => $object) {
        $offsets[$index] = strlen($pdf);
        $pdf .= ($index + 1) . " 0 obj\n" . $object . "\nendobj\n";
    }

    $xrefOffset = strlen($pdf);
    $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
    $pdf .= "0000000000 65535 f \n";
    foreach ($offsets as $offset) {
        $pdf .= sprintf("%010d 00000 n \n", $offset);
    }
    $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n" . $xrefOffset . "\n%%EOF\n";

    file_put_contents($filePath, $pdf);

    return 'uploads/generated_documents/' . basename($filePath);
}

function logActivity($conn, $userId, $username, $role, $action, $description) {
    if (!$conn) {
        return false;
    }

    $userId = (int) $userId;
    $username = trim((string) $username);
    $role = strtolower(trim((string) $role));
    $action = strtolower(preg_replace('/[^a-z0-9_]/', '', (string) $action));
    $description = trim((string) $description);

    if ($description === '') {
        $description = ucfirst(str_replace('_', ' ', $action));
    }

    $stmt = mysqli_prepare($conn, "INSERT INTO activity_logs (user_id, username, role, action, description, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param($stmt, "issss", $userId, $username, $role, $action, $description);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return $result;
}
