<?php
require_once __DIR__ . '/db.php';

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    die("Invalid Prescription ID");
}

$stmt = $conn->prepare("SELECT * FROM prescriptions WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$prescription = $res->fetch_assoc();
$stmt->close();

if (!$prescription) {
    die("Prescription not found.");
}

function h($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

$dateFormatted = date('M d, Y', strtotime($prescription['created_at']));
$timeFormatted = date('h:i:s A', strtotime($prescription['created_at']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Prescription</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap');
        
        body {
            font-family: 'Inter', sans-serif;
            background: #e2e8f0;
            margin: 0;
            padding: 40px;
            display: flex;
            justify-content: center;
        }

        .paper {
            background: white;
            width: 210mm;
            min-height: 297mm;
            padding: 20mm;
            box-sizing: border-box;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            position: relative;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h1 {
            margin: 0;
            font-size: 18px;
            font-weight: 800;
        }

        .header p {
            margin: 2px 0;
            font-size: 12px;
            color: #333;
        }

        .prescribed-on {
            text-align: right;
            font-size: 11px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #333;
        }

        .patient-details {
            margin-bottom: 30px;
        }

        .detail-row {
            display: flex;
            align-items: flex-end;
            margin-bottom: 12px;
            font-size: 12px;
            font-weight: 700;
        }

        .detail-label {
            width: 70px;
        }

        .detail-value {
            flex: 1;
            border-bottom: 1px solid #000;
            padding-left: 10px;
            font-weight: 400;
        }

        .rx-symbol {
            font-size: 64px;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 20px;
            letter-spacing: -2px;
        }

        .medicines-list {
            min-height: 200px;
        }

        .medicine-item {
            margin-bottom: 20px;
            font-size: 13px;
        }

        .medicine-name {
            font-weight: 700;
            margin-bottom: 4px;
        }

        .medicine-sig {
            font-style: italic;
        }

        .no-meds {
            font-size: 12px;
            margin-bottom: 30px;
        }

        .notes-section {
            margin-top: 40px;
            font-size: 12px;
            font-weight: 700;
        }
        
        .notes-content {
            margin-top: 10px;
            font-weight: 400;
            line-height: 1.8;
            border-bottom: 1px dashed #000;
            min-height: 80px;
            white-space: pre-wrap;
        }

        .footer {
            position: absolute;
            bottom: 30mm;
            right: 20mm;
            text-align: right;
            font-size: 11px;
        }

        .footer p {
            margin: 2px 0;
        }

        @media print {
            body {
                background: none;
                padding: 0;
            }
            .paper {
                box-shadow: none;
                width: 100%;
                height: 100%;
                padding: 10mm;
            }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="paper">
        <!-- Border container matching the image -->
        <div style="border: 1px solid #000; height: 100%; padding: 20mm 15mm; box-sizing: border-box; position: relative;">
            
            <div class="header">
                <h1>Val O. Acosta, MD</h1>
                <p>General Practice</p>
                <p>Northern Bukidnon State College</p>
                <p>Health Services Office</p>
                <p>Kihare, Tankulan Manolo Fortich Bukidnon</p>
            </div>

            <div class="prescribed-on">
                Prescribed on: <?php echo $dateFormatted; ?> at <?php echo $timeFormatted; ?> PHT
            </div>

            <div class="patient-details">
                <div class="detail-row">
                    <div class="detail-label">Patient:</div>
                    <div class="detail-value"><?php echo h($prescription['full_name']); ?></div>
                </div>
                <div class="detail-row" style="width: 50%;">
                    <div class="detail-label">Age:</div>
                    <div class="detail-value"><?php echo h($prescription['age']); ?></div>
                </div>
                <div class="detail-row" style="width: 50%;">
                    <div class="detail-label">Gender:</div>
                    <div class="detail-value"><?php echo h($prescription['gender']); ?></div>
                </div>
            </div>

            <div class="rx-symbol">Rx</div>

            <div class="medicines-list">
                <?php 
                $hasMeds = false;
                for ($i=1; $i<=3; $i++) {
                    $med = $prescription['medicine_'.$i];
                    $ins = $prescription['instruction_'.$i];
                    if (!empty($med)) {
                        $hasMeds = true;
                        echo '<div class="medicine-item">';
                        echo '<div class="medicine-name">' . $i . '. ' . h($med) . '</div>';
                        if (!empty($ins)) {
                            echo '<div class="medicine-sig">' . h($ins) . '</div>';
                        }
                        echo '</div>';
                    }
                }
                
                if (!$hasMeds) {
                    echo '<div class="no-meds">No medicines entered<br><br>......................................................................................<br><br>......................................................................................</div>';
                }
                ?>
            </div>

            <div class="notes-section">
                Note:
                <div class="notes-content"><?php echo h($prescription['note']); ?></div>
            </div>

            <div class="footer">
                <div style="border-bottom: 1px solid #000; width: 200px; margin-bottom: 5px; margin-left: auto;"></div>
                <p>Physician's Signature</p>
                <p>PRC No.: 0154636</p>
                <p>PTR No.: 6540309</p>
            </div>

        </div>
    </div>

</body>
</html>
