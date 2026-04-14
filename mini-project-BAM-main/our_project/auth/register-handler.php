<?php
session_start();
require_once("config.php");
require 'mailer.php'; // This file contains your send_verification_email() function

function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    return $data;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // =================STUDENT===================

    if ($_POST['role'] == 'student') {

        // ====DATA====
        $first_name = sanitize_input($_POST['first_name']);
        $last_name = sanitize_input($_POST['last_name']);
        $email = sanitize_input($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $student_id = (int)sanitize_input($_POST['student_id']);
        $major = $_POST['major'] ?? '';
        $current_year = sanitize_input($_POST['current_year']);
        $about_me = sanitize_input($_POST['about_me']);
        $linkedin_url = $_POST['linkedin_url'] ?? '';
        $github_url = $_POST['github_url'] ?? '';
        $other_url = $_POST['other_url'] ?? '';

        // ====VALIDATING====
        $errors = [];

        if (empty($first_name)) {
            $errors[] = "First name is required";
        } else if (!preg_match("/^[a-zA-Z-' ]*$/",$first_name)) {
            $errors[] = "Only letters and white space allowed in First name";
        }
        if (empty($last_name)) {
            $errors[] = "Last name is required";
        } else if (!preg_match("/^[a-zA-Z-' ]*$/",$last_name)) {
            $errors[] = "Only letters and white space allowed in Last name";
        }
        if (empty($email)) {
            $errors[] = "Email is required";
        } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format";
        } else if (!str_ends_with($email, '@uca.ac.ma')) {
            $errors[] = "Must use UCA Academique email (@uca.ac.ma)";
        }
        if (empty($password)) {
            $errors[] = "Password is required";
        } else if (strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters";
        }
        if (empty($confirm_password)) {
            $errors[] = "Please confirm your password.";
        } else if ($confirm_password != $password) {
            $errors[] = "Passwords don't match. Please retry!";
        }
        if (empty($student_id)) $errors[] = "Student ID is required";
        if (empty($major)) {
            if (in_array($current_year, ['CI1', 'CI2', 'CI3'])) {
                $errors[] = "Please select a major";
            } else {
                $major = 'N/A';
            }
        }
        if (empty($current_year)) $errors[] = "Current year is required";
        if (empty($about_me)) {
            $errors[] = "An introductory bio is required";
        } else if (strlen($about_me) > 500) {
            $errors[] = "Max characters is 500";
        }
        if (!empty($linkedin_url) &&
            !filter_var($linkedin_url, FILTER_VALIDATE_URL)) {
            $errors[] = "Invalid Linkedin URL";
        }
        if (!empty($github_url) &&
            !filter_var($github_url, FILTER_VALIDATE_URL)) {
            $errors[] = "Invalid GitHub URL";
        }
        if (!empty($other_url) &&
            !filter_var($other_url, FILTER_VALIDATE_URL)) {
            $errors[] = "Invalid URL";
        }

        // ====HANDLING FILE====
        $profile_photo_path = NULL;
        if (isset($_FILES['profile_photo']) &&
            $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
                $allowed_mime = [
                'image/webp',
                'image/avif',
                'image/jpeg',
                'image/png',
                ];
                $file_tmp  = $_FILES['profile_photo']['tmp_name'];
                $file_size = $_FILES['profile_photo']['size'];
                $file_type = mime_content_type($file_tmp);
                $max_size = 2 * 1024 * 1024; //2MB;

                if (!in_array($file_type, $allowed_mime)) {
                    $errors[] = "Invalid format. <i>Profile must be JPG, JPEG, WEBP, AVIF or PNG<i/>";
                } else if($file_size > $max_size) {
                    $errors[] = "Photo must be less than 2MB";
                } else {
                    $ext = pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION);
                    $new_filename = 'student_'. time() . '_' . uniqid() . '.' . $ext;
                    $upload_path = '../uploads/profiles/' . $new_filename;

                    if (move_uploaded_file($file_tmp, $upload_path)) {
                        $profile_photo_path = 'uploads/profiles/' . $new_filename;
                    } else {
                        $errors[] = "Failed to upload profile photo";
                    }
                }
            }

        // ====CHECKING ERR====
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['user_input'] = $_POST;
            header("Location: ../register.php");
            exit;
        }

        // ====CHECK IF EMAIL ALREADY EXISTS IN DB====
        $sql = "SELECT id FROM users WHERE email = ?";
        $stmt = mysqli_prepare($connector, $sql);
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) > 0) {
            $_SESSION['errors'] = ["Email already registered"];
            header("Location: ../register.php");
            exit;
        }

        // ====HASH PASSWORD====
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // ====INSERT INFO INTO USERS TABLE====
        $sql = "INSERT INTO users (email, password, role, first_name, last_name, profile_photo, status, verified, created_at)
                VALUES (?, ?, 'student', ?, ?, ?, 'pending', '0', NOW())";
        $stmt = mysqli_prepare($connector, $sql);
        mysqli_stmt_bind_param($stmt, 'sssss', $email, $hashed_password, $first_name, $last_name, $profile_photo_path);
        
        if (!mysqli_stmt_execute($stmt)) {
            $_SESSION['errors'] = ["Registration failed. Please try again."];
            header("Location: ../register.php");
            exit;
        }

        // ====INSERT INTO STUDENTS TABLE====
        $user_id = mysqli_insert_id($connector);
        $sql = "INSERT INTO student_profiles(user_id, student_id, major, current_year, bio, linkedin_url, github_url, other_url)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($connector, $sql);
        mysqli_stmt_bind_param($stmt, 'isssssss',$user_id, $student_id, $major, $current_year, $about_me, $linkedin_url, $github_url, $other_url);
        
        if (!mysqli_stmt_execute($stmt)) {
            $_SESSION['errors'] = ["Registration failed. Please try again."];
            header("Location: ../register.php");
            exit;
        }

        $_SESSION['success'] = "Registration successful! Please check your email for the verification link";
        $token = bin2hex(random_bytes(32));

        $sql = "UPDATE users SET verification_token = ? WHERE id = ?";
        $stmt = mysqli_prepare($connector, $sql);
        mysqli_stmt_bind_param($stmt, 'si', $token, $user_id);
        mysqli_stmt_execute($stmt);

        $verification_link = "http://localhost/alumni-project/our_project/auth/verify-email-handler.php?token=" . $token;
        send_verification_email($email, $first_name, $verification_link);
        $_SESSION['email_to_verify'] = $email;
        $_SESSION['token'] = $token;
        $_SESSION['verification_link'] = $verification_link;
        header("Location: ../register.php");
        exit;
    }
    
    // ===================ALUMNI===================
    
    else if($_POST['role'] == 'alumni') {
        // ====DATA====
        $first_name = sanitize_input($_POST['first_name']);
        $last_name = sanitize_input($_POST['last_name']);
        $email = sanitize_input($_POST['email']);
        $password = $_POST['password'];
        $industry = $_POST['industry'];
        $current_company = $_POST['company'];
        $current_job = $_POST['job'];
        $willing_to_mentor = $_POST['willing_to_mentor'];
        $major = $_POST['major'];
        $grad_year = $_POST['grad_year'];
        $about_me = sanitize_input($_POST['about_me']);
        $linkedin_url = $_POST['linkedin_url'];
        $github_url = $_POST['github_url'] ?? '';
        $other_url = $_POST['other_url'] ?? '';

        // ====VALIDATING====
        $errors = [];

        if (empty($first_name)) {
            $errors[] = "First name is required";
        } else if (!preg_match("/^[a-zA-Z-' ]*$/",$first_name)) {
            $errors[] = "Only letters and white space allowed in First name";
        }
        if (empty($last_name)) {
            $errors[] = "Last name is required";
        } else if (!preg_match("/^[a-zA-Z-' ]*$/",$last_name)) {
            $errors[] = "Only letters and white space allowed in Last name";
        }
        if (empty($email)) {
            $errors[] = "Email is required";
        } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format";
        }
        if (empty($password)) {
            $errors[] = "Password is required";
        } else if (strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters";
        }
        if (empty($industry)) $errors[] = "Industry field is required";
        if (!empty($current_company)) {
            if (!preg_match("/^[a-zA-Z-' ]*$/",$current_company)) {
                $errors[] = "Only letters and white space allowed in the company name";
            }
        }
        if (empty($major)) {
            $errors[] = "Please select a major";
        }
        if (empty($grad_year)) {
            $errors[] = "Graduation year is required";
        } else if (!filter_var($grad_year, FILTER_VALIDATE_INT) ||
                    $grad_year > date('Y') ||
                    $grad_year < 2008) {
            $errors[] = "Please enter a valid graduation year";
        }
        if (empty($about_me)) {
            $errors[] = "An introductory bio is required";
        } else if (strlen($about_me) > 500) {
            $errors[] = "Max characters is 500";
        }
        if (!empty($linkedin_url) &&
            !filter_var($linkedin_url, FILTER_VALIDATE_URL)) {
            $errors[] = "Invalid Linkedin URL";
        }
        if (!empty($github_url) &&
            !filter_var($github_url, FILTER_VALIDATE_URL)) {
            $errors[] = "Invalid GitHub URL";
        }
        if (!empty($other_url) &&
            !filter_var($other_url, FILTER_VALIDATE_URL)) {
            $errors[] = "Invalid URL";
        }

        // ====HANDLING FILE====
        $profile_photo_path = NULL;
        if (isset($_FILES['profile_photo']) &&
            $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
                $allowed_mime = [
                'image/webp',
                'image/avif',
                'image/jpeg',
                'image/png',
                ];
                $file_tmp  = $_FILES['profile_photo']['tmp_name'];
                $file_size = $_FILES['profile_photo']['size'];
                $file_type = mime_content_type($file_tmp);
                $max_size = 2 * 1024 * 1024; //2MB;

                if (!in_array($file_type, $allowed_mime)) {
                    $errors[] = "Invalid format. <i>Profile must be JPG, JPEG, WEBP, AVIF or PNG<i/>";
                } else if($file_size > $max_size) {
                    $errors[] = "Photo must be less than 2MB";
                } else {
                    $ext = pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION);
                    $new_filename = 'alumni_'. time() . '_' . uniqid() . '.' . $ext;
                    $upload_path = '../uploads/profiles/' . $new_filename;

                    if (move_uploaded_file($file_tmp, $upload_path)) {
                        $profile_photo_path = 'uploads/profiles/' . $new_filename;
                    } else {
                        $errors[] = "Failed to upload profile photo";
                    }
                }
            }

        // ====CHECKING ERR====
        if (!empty($errors)) {
            $_SESSION['errors'] = $errors;
            $_SESSION['user_input'] = $_POST;
            header("Location: ../register.php");
            exit;
        }

        // ====CHECK IF EMAIL ALREADY EXISTS IN DB====
        $sql = "SELECT id FROM users WHERE email = ?";
        $stmt = mysqli_prepare($connector, $sql);
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) > 0) {
            $_SESSION['errors'] = ["Email already registered"];
            header("Location: ../register.php");
            exit;
        }

        // ====HASH PASSWORD====
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // ====INSERT INFO INTO USERS TABLE====
        $sql = "INSERT INTO users (email, password, role, first_name, last_name, profile_photo, status, verified, created_at)
                VALUES (?, ?, 'alumni', ?, ?, ?, 'pending', '1', NOW())";
        $stmt = mysqli_prepare($connector, $sql);
        mysqli_stmt_bind_param($stmt, 'sssss', $email, $hashed_password, $first_name, $last_name, $profile_photo_path);
        
        if (!mysqli_stmt_execute($stmt)) {
            $_SESSION['errors'] = ["Registration failed. Please try again."];
            header("Location: ../register.php");
            exit;
        }

        // ====INSERT INTO ALUMNI TABLE====
        $user_id = mysqli_insert_id($connector);
        $sql = "INSERT INTO alumni_profiles(user_id, graduation_year, major, current_company, current_position, industry, willing_to_mentor, bio, linkedin_url, github_url, other_url)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($connector, $sql);
        mysqli_stmt_bind_param($stmt, 'iisssssssss',$user_id, $grad_year, $major, $current_company, $current_job, $industry, $willing_to_mentor, $about_me, $linkedin_url, $github_url, $other_url);
        
        if (!mysqli_stmt_execute($stmt)) {
            mysqli_query($connector, "DELETE FROM users WHERE id = $user_id");
            $_SESSION['errors'] = ["Registration failed. Please try again."];
            header("Location: ../register.php");
            exit;
        }

        $_SESSION['success'] = "Registration successful! Your account is now pending admin approval. You'll receive an email once approved";
        header("Location: ../register.php");
        exit;
    }
}