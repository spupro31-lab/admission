<?php
require_once '../includes/db_connect.php';
require_once '../includes/auth.php';


check_access('student');

$user_id = $_SESSION['user_id'];

try {

    $stmt = $pdo->prepare("
        SELECT s.*, c.course_name, c.department 
        FROM students s 
        LEFT JOIN courses c ON s.course_id = c.course_id 
        WHERE s.user_id = :user_id
    ");
    $stmt->execute(['user_id' => $user_id]);
    $student = $stmt->fetch();


    if (!$student || $student['payment_status'] !== 'Paid') {
        die("Unauthorized access: You have not completed the fee payment yet.");
    }
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}




require_once '../libs/fpdf.php';

class PaymentReceiptPDF extends FPDF
{

    function Header()
    {

        $this->SetFont('Arial', 'B', 18);
        $this->SetTextColor(15, 76, 129);
        $this->Cell(0, 10, 'STATE COLLEGE OF TECHNOLOGY', 0, 1, 'C');

        $this->SetFont('Arial', '', 10);
        $this->SetTextColor(108, 117, 125);
        $this->Cell(0, 5, 'Affiliated to State Technical University | Estd. 1998', 0, 1, 'C');
        $this->Cell(0, 5, 'Website: www.statecollege.edu.in | Email: accounts@statecollege.edu.in', 0, 1, 'C');


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

$pdf = new PaymentReceiptPDF('P', 'mm', 'A4');
$pdf->SetMargins(15, 15, 15);
$pdf->AddPage();


$pdf->SetFont('Arial', 'B', 14);
$pdf->SetTextColor(33, 37, 41);
$pdf->Cell(0, 10, 'FEE PAYMENT RECEIPT', 0, 1, 'C');
$pdf->Ln(2);


$pdf->SetFont('Arial', 'B', 10);

$receipt_no = "REC-" . substr(md5($student['transaction_id']), 0, 8);
$pdf->Cell(95, 8, 'Receipt No: ' . strtoupper($receipt_no), 0, 0, 'L');
$pdf->Cell(75, 8, 'Date: ' . date('d-M-Y'), 0, 1, 'R');

$pdf->SetDrawColor(15, 76, 129);
$pdf->SetLineWidth(0.5);
$pdf->Line(15, 62, 195, 62);
$pdf->Ln(5);


$pdf->SetFont('Arial', 'B', 11);
$pdf->SetTextColor(15, 76, 129);
$pdf->Cell(0, 8, '1. Payer Details', 0, 1, 'L');
$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(33, 37, 41);

$pdf->Cell(45, 8, 'Student Name:', 0, 0, 'L');
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(135, 8, $student['full_name'], 0, 1, 'L');

$pdf->Cell(45, 8, 'Admission No / ID:', 0, 0, 'L');
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(135, 8, $student['admission_no'], 0, 1, 'L');

$pdf->SetFont('Arial', '', 10);
$pdf->Cell(45, 8, 'Email Address:', 0, 0, 'L');
$pdf->Cell(50, 8, $student['email'], 0, 0, 'L');
$pdf->Cell(45, 8, 'Mobile No:', 0, 0, 'L');
$pdf->Cell(50, 8, $student['mobile'], 0, 1, 'L');

$pdf->Cell(45, 8, 'Applied Course:', 0, 0, 'L');
$pdf->Cell(135, 8, $student['course_name'] . " (" . $student['department'] . ")", 0, 1, 'L');
$pdf->Ln(4);


$pdf->SetFont('Arial', 'B', 11);
$pdf->SetTextColor(15, 76, 129);
$pdf->Cell(0, 8, '2. Transaction Information', 0, 1, 'L');
$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(33, 37, 41);

$pdf->Cell(45, 8, 'Transaction Status:', 0, 0, 'L');
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetTextColor(40, 167, 69);
$pdf->Cell(135, 8, 'SUCCESS / PAID', 0, 1, 'L');
$pdf->SetTextColor(33, 37, 41);

$pdf->SetFont('Arial', '', 10);
$pdf->Cell(45, 8, 'Payment Reference ID:', 0, 0, 'L');
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(135, 8, $student['transaction_id'], 0, 1, 'L');

$pdf->SetFont('Arial', '', 10);
$pdf->Cell(45, 8, 'Payment Mode:', 0, 0, 'L');
$pdf->Cell(135, 8, 'UPI (Unified Payments Interface)', 0, 1, 'L');
$pdf->Ln(4);


$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(240, 244, 248);
$pdf->Cell(10, 10, 'S.No', 1, 0, 'C', true);
$pdf->Cell(120, 10, 'Fee Description', 1, 0, 'L', true);
$pdf->Cell(50, 10, 'Amount (Rs)', 1, 1, 'R', true);

$pdf->SetFont('Arial', '', 10);
$pdf->Cell(10, 10, '1', 1, 0, 'C');
$pdf->Cell(120, 10, 'Admission Processing Fee (Academic Year 2026-27)', 1, 0, 'L');
$pdf->Cell(50, 10, '500.00', 1, 1, 'R');

$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(130, 10, 'Total Paid Amount', 1, 0, 'R', true);
$pdf->Cell(50, 10, 'Rs. 500.00', 1, 1, 'R', true);
$pdf->Ln(15);


$pdf->Ln(5);
$sig_y = $pdf->GetY();

$pdf->SetFont('Arial', 'B', 11);
$pdf->SetTextColor(40, 167, 69);
$pdf->Cell(90, 15, 'PAYMENT VERIFIED ONLINE', 0, 0, 'L');


$pdf->Line(135, $sig_y + 10, 195, $sig_y + 10);

$pdf->SetFont('Arial', '', 9);
$pdf->SetTextColor(33, 37, 41);
$pdf->SetXY(135, $sig_y + 12);
$pdf->Cell(60, 8, 'Authorized Accounts Officer', 0, 1, 'C');

$pdf->Ln(5);
$pdf->SetY($sig_y + 22);

$pdf->SetFont('Arial', 'I', 8);
$pdf->SetTextColor(108, 117, 125);
$pdf->Cell(0, 5, 'Note: This is an official digital payment receipt generated from SCT Admission portal.', 0, 1, 'C');


$pdf->Output('I', 'Payment_Receipt_' . $student['admission_no'] . '.pdf');
