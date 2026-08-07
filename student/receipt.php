<?php
require_once '../includes/db_connect.php';
require_once '../includes/auth.php';


check_access('student');

$user_id = $_SESSION['user_id'];

try {

    $stmt = $pdo->prepare("
        SELECT s.*, c.course_name, c.department, c.semester 
        FROM students s 
        LEFT JOIN courses c ON s.course_id = c.course_id 
        WHERE s.user_id = :user_id
    ");
    $stmt->execute(['user_id' => $user_id]);
    $student = $stmt->fetch();


    if (!$student || $student['status'] !== 'Approved') {
        die("Unauthorized access: Your application is not approved yet.");
    }
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}





require_once '../libs/fpdf.php';

class AdmissionPDF extends FPDF
{

    function Header()
    {

        $this->SetFont('Arial', 'B', 18);
        $this->SetTextColor(15, 76, 129);
        $this->Cell(0, 10, 'STATE COLLEGE OF TECHNOLOGY', 0, 1, 'C');

        $this->SetFont('Arial', '', 10);
        $this->SetTextColor(108, 117, 125);
        $this->Cell(0, 5, 'Affiliated to State Technical University | Estd. 1998', 0, 1, 'C');
        $this->Cell(0, 5, 'Website: www.statecollege.edu.in | Email: admissions@statecollege.edu.in', 0, 1, 'C');


        $this->SetDrawColor(220, 224, 230);
        $this->Line(10, 36, 200, 36);
        $this->Ln(8);
    }


    function Footer()
    {

        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(108, 117, 125);


        $this->Cell(0, 10, 'Page ' . $this->PageNo() . ' | Generated Online by Admissions Portal - ' . date('d-M-Y H:i'), 0, 0, 'C');
    }
}


$pdf = new AdmissionPDF('P', 'mm', 'A4');
$pdf->SetMargins(15, 15, 15);
$pdf->AddPage();


$pdf->SetFont('Arial', 'B', 14);
$pdf->SetTextColor(33, 37, 41);
$pdf->Cell(0, 10, 'ADMISSION CONFIRMATION RECEIPT', 0, 1, 'C');
$pdf->Ln(2);


$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(95, 8, 'Admission No: ' . $student['admission_no'], 0, 0, 'L');
$pdf->Cell(75, 8, 'Date: ' . date('d-M-Y', strtotime($student['created_at'])), 0, 1, 'R');

$pdf->SetDrawColor(15, 76, 129);
$pdf->SetLineWidth(0.5);
$pdf->Line(15, 62, 195, 62);
$pdf->Ln(5);


$pdf->SetFont('Arial', 'B', 11);
$pdf->SetTextColor(15, 76, 129);
$pdf->Cell(0, 8, '1. Candidate Personal Details', 0, 1, 'L');
$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(33, 37, 41);


$pdf->Cell(45, 8, 'Student Full Name:', 0, 0, 'L');
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(135, 8, $student['full_name'], 0, 1, 'L');

$pdf->SetFont('Arial', '', 10);
$pdf->Cell(45, 8, "Father's Name:", 0, 0, 'L');
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(50, 8, $student['father_name'], 0, 0, 'L');
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(45, 8, "Mother's Name:", 0, 0, 'L');
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(50, 8, $student['mother_name'], 0, 1, 'L');

$pdf->SetFont('Arial', '', 10);
$pdf->Cell(45, 8, 'Gender:', 0, 0, 'L');
$pdf->Cell(50, 8, $student['gender'], 0, 0, 'L');
$pdf->Cell(45, 8, 'Date of Birth:', 0, 0, 'L');
$pdf->Cell(50, 8, date('d-M-Y', strtotime($student['dob'])), 0, 1, 'L');

$pdf->Cell(45, 8, 'Category:', 0, 0, 'L');
$pdf->Cell(50, 8, $student['category'], 0, 0, 'L');
$pdf->Cell(45, 8, 'Mobile No:', 0, 0, 'L');
$pdf->Cell(50, 8, $student['mobile'], 0, 1, 'L');

$pdf->Cell(45, 8, 'Email Address:', 0, 0, 'L');
$pdf->Cell(50, 8, $student['email'], 0, 0, 'L');
$pdf->Cell(45, 8, 'Pincode:', 0, 0, 'L');
$pdf->Cell(50, 8, $student['pincode'], 0, 1, 'L');

$pdf->Cell(45, 8, 'Full Address:', 0, 0, 'L');
$pdf->Cell(145, 8, $student['address'] . ", " . $student['city'] . ", " . $student['state'], 0, 1, 'L');
$pdf->Ln(4);


$pdf->SetFont('Arial', 'B', 11);
$pdf->SetTextColor(15, 76, 129);
$pdf->Cell(0, 8, '2. Academic Qualifications', 0, 1, 'L');
$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(33, 37, 41);

$pdf->Cell(45, 8, '10th Percentage:', 0, 0, 'L');
$pdf->Cell(50, 8, $student['tenth_percentage'] . '%', 0, 0, 'L');
$pdf->Cell(45, 8, '12th Percentage:', 0, 0, 'L');
$pdf->Cell(50, 8, $student['twelfth_percentage'] . '%', 0, 1, 'L');

$pdf->Cell(45, 8, 'School Board Name:', 0, 0, 'L');
$pdf->Cell(50, 8, $student['school_name'], 0, 0, 'L');
$pdf->Cell(45, 8, 'Passing Year:', 0, 0, 'L');
$pdf->Cell(50, 8, $student['passing_year'], 0, 1, 'L');
$pdf->Ln(4);


$pdf->SetFont('Arial', 'B', 11);
$pdf->SetTextColor(15, 76, 129);
$pdf->Cell(0, 8, '3. Allocated Course Information', 0, 1, 'L');
$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(33, 37, 41);

$pdf->Cell(45, 8, 'Program Allocated:', 0, 0, 'L');
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(145, 8, $student['course_name'], 0, 1, 'L');
$pdf->SetFont('Arial', '', 10);

$pdf->Cell(45, 8, 'Department:', 0, 0, 'L');
$pdf->Cell(50, 8, $student['department'], 0, 1, 'L');
$pdf->Cell(45, 8, 'Semester:', 0, 0, 'L');
$pdf->Cell(50, 8, $student['semester'], 0, 0, 'L');
$pdf->Ln(15);


$pdf->Ln(10);
$sig_y = $pdf->GetY();

$pdf->SetFont('Arial', 'B', 12);
$pdf->SetTextColor(40, 167, 69);
$pdf->Cell(90, 15, '[ STATUS: CONFIRMED / APPROVED ]', 0, 0, 'L');


$pdf->Line(135, $sig_y + 10, 195, $sig_y + 10);

$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(33, 37, 41);

$pdf->SetXY(135, $sig_y + 12);
$pdf->Cell(60, 8, 'Authorized Registrar Signatory', 0, 1, 'C');

$pdf->Ln(5);
$pdf->SetY($sig_y + 22);

$pdf->SetFont('Arial', 'I', 8);
$pdf->SetTextColor(108, 117, 125);
$pdf->Cell(0, 5, 'Note: This is a computer-generated confirmation slip. No manual signature is required.', 0, 1, 'C');


$pdf->Output('I', 'Admission_Receipt_' . $student['admission_no'] . '.pdf');
