<?php
include 'db_connect.php';

$date     = isset($_GET['date'])     ? $_GET['date']     : '';
$space_id = isset($_GET['space_id']) ? $_GET['space_id'] : '';

$success  = '';
$error    = '';
$is_taken = false;

$isRoom = false;
$space_price = 0;

if (is_numeric($space_id)) {
    $stmt = $conn->prepare("SELECT price FROM space WHERE space_id = ?");
    $stmt->bind_param("i", $space_id);
    $stmt->execute();
    $spaceResult = $stmt->get_result();
    $spaceRow = $spaceResult->fetch_assoc();
    $stmt->close();

    if ($spaceRow) {
        $space_price = (float) $spaceRow['price'];
    }

    $stmt = $conn->prepare("SELECT * FROM room WHERE space_id = ?");
    $stmt->bind_param("i", $space_id);
    $stmt->execute();
    $roomResult = $stmt->get_result();
    $isRoom = $roomResult->num_rows > 0;
    $stmt->close();
}

$TOTAL_SLOTS = 32;
$slotStatus = [];

if ($isRoom && is_numeric($space_id) && $date !== '') {
    for ($i = 0; $i < $TOTAL_SLOTS; $i++) {
        $slotStatus[$i] = 'available';
    }

    $stmt = $conn->prepare("
        SELECT reservation_time, expected_timeout, reservation_status
        FROM reservation
        WHERE space_id = ?
          AND reservation_date = ?
          AND reservation_status IN ('confirmed', 'pending')
    ");
    $stmt->bind_param("is", $space_id, $date);
    $stmt->execute();
    $bookingsResult = $stmt->get_result();

    while ($row = $bookingsResult->fetch_assoc()) {
        $status = strtolower($row['reservation_status']);
        $startMinutes = (int)substr($row['reservation_time'], 0, 2) * 60 + (int)substr($row['reservation_time'], 3, 2);
        $endMinutes   = (int)substr($row['expected_timeout'], 0, 2) * 60 + (int)substr($row['expected_timeout'], 3, 2);

        for ($i = 0; $i < $TOTAL_SLOTS; $i++) {
            $slotStart = 420 + ($i * 30);
            $slotEnd   = $slotStart + 30;
            if ($startMinutes < $slotEnd && $endMinutes > $slotStart) {
                if ($status === 'confirmed') {
                    $slotStatus[$i] = 'occupied';
                } elseif ($status === 'pending' && $slotStatus[$i] !== 'occupied') {
                    $slotStatus[$i] = 'pending';
                }
            }
        }
    }
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name  = trim($_POST['first_name']  ?? '');
    $middle_name = trim($_POST['middle_name'] ?? '');
    $last_name   = trim($_POST['last_name']   ?? '');
    $suffix      = trim($_POST['suffix']      ?? '');
    $email       = trim($_POST['email']       ?? '');
    $phone       = trim($_POST['phone']       ?? '');
    $sex         = trim($_POST['sex']         ?? '');
    $res_date    = trim($_POST['reservation_date'] ?? '');
    $res_space   = trim($_POST['space_id']    ?? '');

    $payment_mode   = trim($_POST['payment_mode']   ?? '');
    $reference_code = trim($_POST['reference_code'] ?? '');
    $amount_paid    = trim($_POST['amount_paid']    ?? '');

    $split_payment    = $_POST['split_payment']    ?? 'no';
    $payment_mode_2   = trim($_POST['payment_mode_2']   ?? '');
    $reference_code_2 = trim($_POST['reference_code_2'] ?? '');
    $amount_paid_2    = trim($_POST['amount_paid_2']    ?? '');

    // --- Validation ---
    if ($payment_mode === '') {
        $error = "Please select a payment mode.";
    } elseif (($payment_mode === 'Card' || $payment_mode === 'E-wallet') && $reference_code === '') {
        $error = "Reference code is required for Card / E-wallet payments.";
    } elseif ($amount_paid === '' || !is_numeric($amount_paid) || (float)$amount_paid <= 0) {
        $error = "Please enter a valid amount paid.";
    } elseif ($payment_mode === 'Cash' && $split_payment === 'yes') {
        if ($payment_mode_2 === '') {
            $error = "Please select a second payment mode for the split payment.";
        } elseif (($payment_mode_2 === 'Card' || $payment_mode_2 === 'E-wallet') && $reference_code_2 === '') {
            $error = "Reference code is required for the second payment mode.";
        } elseif ($amount_paid_2 === '' || !is_numeric($amount_paid_2) || (float)$amount_paid_2 <= 0) {
            $error = "Please enter a valid amount for the second payment.";
        }
    }

    if ($error === '' && $isRoom) {
        $res_time    = trim($_POST['reservation_time'] ?? '');
        $res_timeout = trim($_POST['expected_timeout']  ?? '');

        if (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $res_time) ||
            !preg_match('/^\d{2}:\d{2}:\d{2}$/', $res_timeout)) {
            $error = "Please select a valid time range.";
        } else {
            $startMinutes = (int)substr($res_time, 0, 2) * 60 + (int)substr($res_time, 3, 2);
            $endMinutes   = (int)substr($res_timeout, 0, 2) * 60 + (int)substr($res_timeout, 3, 2);

            if ($endMinutes <= $startMinutes) {
                $error = "End time must be after start time.";
            } elseif ($endMinutes - $startMinutes < 240) {
                $error = "Booking must be at least 4 hours long.";
            } elseif (($endMinutes - $startMinutes) % 60 !== 0) {
                $error = "Booking duration must be in whole hours (e.g. 4 or 5 hours, not 4.5).";
            } else {
                // Check for overlapping bookings
                $stmt = $conn->prepare("
                    SELECT reservation_time, expected_timeout
                    FROM reservation
                    WHERE space_id = ?
                      AND reservation_date = ?
                      AND reservation_status IN ('confirmed', 'pending')
                ");
                $stmt->bind_param("ss", $res_space, $res_date);
                $stmt->execute();
                $existingResult = $stmt->get_result();
                while ($row = $existingResult->fetch_assoc()) {
                    $existingStart = (int)substr($row['reservation_time'], 0, 2) * 60 + (int)substr($row['reservation_time'], 3, 2);
                    $existingEnd   = (int)substr($row['expected_timeout'], 0, 2) * 60 + (int)substr($row['expected_timeout'], 3, 2);
                    if ($startMinutes < $existingEnd && $endMinutes > $existingStart) {
                        $error    = "⚠️ This time slot has already been booked.";
                        $is_taken = true;
                        break;
                    }
                }
                $stmt->close();
            }
        }
    }

    if ($error === '') {
        // --- Insert reservation (prepared statement) ---
        if ($isRoom) {
            $stmt = $conn->prepare("
                INSERT INTO reservation (space_id, reservation_date, reservation_time, expected_timeout, reservation_status)
                VALUES (?, ?, ?, ?, 'Pending')
            ");
            $stmt->bind_param("ssss", $res_space, $res_date, $res_time, $res_timeout);
        } else {
            $stmt = $conn->prepare("
                INSERT INTO reservation (space_id, reservation_date, reservation_time, expected_timeout, reservation_status)
                VALUES (?, ?, '07:00:00', '22:00:00', 'Pending')
            ");
            $stmt->bind_param("ss", $res_space, $res_date);
        }

        try {
            $stmt->execute();
            $reservation_id = $conn->insert_id;
            $stmt->close();

            // --- Insert customer (prepared statement) ---
            $stmt = $conn->prepare("
                INSERT INTO customer (reservation_id, first_name, middle_name, last_name, suffix, gender, email_address, phone_number)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("isssssss", $reservation_id, $first_name, $middle_name, $last_name, $suffix, $sex, $email, $phone);

            try {
                $stmt->execute();
                $stmt->close();

                // --- Insert payment (prepared statement) ---
                $ref_val = $reference_code !== '' ? $reference_code : null;
                $stmt = $conn->prepare("
                    INSERT INTO payment (reservation_id, reference_code, amount_paid, payment_mode, payment_date_time)
                    VALUES (?, ?, ?, ?, NOW())
                ");
                $stmt->bind_param("isds", $reservation_id, $ref_val, $amount_paid, $payment_mode);
                $stmt->execute();
                $stmt->close();

                // --- Optional split payment ---
                if ($payment_mode === 'Cash' && $split_payment === 'yes') {
                    $ref_val_2 = $reference_code_2 !== '' ? $reference_code_2 : null;
                    $stmt = $conn->prepare("
                        INSERT INTO payment (reservation_id, reference_code, amount_paid, payment_mode, payment_date_time)
                        VALUES (?, ?, ?, ?, NOW())
                    ");
                    $stmt->bind_param("isds", $reservation_id, $ref_val_2, $amount_paid_2, $payment_mode_2);
                    $stmt->execute();
                    $stmt->close();
                }

                $success = "Reservation submitted successfully for " . htmlspecialchars($res_date) . "!";

            } catch (mysqli_sql_exception $e) {
                $conn->query("DELETE FROM reservation WHERE reservation_id = $reservation_id");
                $error = "Failed to save customer details. Please try again.";
            }

        } catch (mysqli_sql_exception $e) {
            if ($conn->errno === 1062) {
                $error    = "⚠️ Aray! naunahan ka.";
                $is_taken = true;
            } else {
                $error = "Failed to save reservation. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reservation Form</title>
<link rel="stylesheet" href="bookingstyle.css">
</head>

<body>
<div id="bg-slider"></div>

<?php if ($success): ?>
<div class="res-parent">
    <div class="result-card">
        <h2>🎉 Booking Confirmed!</h2>
        <p><?php echo htmlspecialchars($success); ?></p>
        <div style="margin-top:16px;padding:12px 16px;background:#f0f9f0;border:1px solid #b2dfb2;border-radius:8px;text-align:center;">
            <p style="font-size:0.85rem;color:#555;margin-bottom:4px;">Your Reservation ID</p>
            <p style="font-size:1.6rem;font-weight:700;letter-spacing:2px;color:#2e7d32;">#<?php echo $reservation_id; ?></p>
            <p style="font-size:0.78rem;color:#777;margin-top:4px;">Save this number to track your booking.</p>
        </div>
        <p style="margin-top:20px;"><a href="../book.php">← Back to spaces</a></p>
    </div>
</div>

<?php elseif ($is_taken): ?>
<div class="res-parent">
    <div class="result-card">
        <h2>⚠️ Slot Taken</h2>
        <p><?php echo htmlspecialchars($error); ?></p>
        <p style="margin-top:20px;"><a href="../book.php">← Choose another date or space</a></p>
    </div>
</div>

<?php else: ?>

<?php if ($error): ?>
<div class="res-parent" style="padding-top:16px;">
    <div class="result-card" style="border-left:4px solid #e74c3c;">
        <p style="color:#c0392b;font-weight:600;">⚠️ <?php echo htmlspecialchars($error); ?></p>
    </div>
</div>
<?php endif; ?>

<form method="POST" id="reservationForm">
    <input type="hidden" name="reservation_date" value="<?php echo htmlspecialchars($date); ?>">
    <input type="hidden" name="space_id"          value="<?php echo htmlspecialchars($space_id); ?>">
    <?php if ($isRoom): ?>
        <input type="hidden" name="reservation_time" id="reservation_time" value="<?php echo htmlspecialchars($_POST['reservation_time'] ?? ''); ?>">
        <input type="hidden" name="expected_timeout"  id="expected_timeout"  value="<?php echo htmlspecialchars($_POST['expected_timeout']  ?? ''); ?>">
    <?php endif; ?>

    <!-- ===================== STEP 1 ===================== -->
    <div class="step <?php echo (!$error || ($error && $isRoom && (!isset($_POST['reservation_time']) || $_POST['reservation_time'] === ''))) ? 'active' : ''; ?>" id="step1">

        <div class="card">
            <div class="panel-left">
                <h2 class="panel-title">Reservation Information</h2>

                <?php if ($isRoom): ?>
                <div>
                    <p style="font-size:0.8rem;color:#555;margin-bottom:4px;">Select your time slot (min. 4 hrs)</p>
                    <div class="legend">
                        <span style="background:#d4f7d4;color:green;">Available</span>
                        <span style="background:#fff3cd;color:goldenrod;">Pending</span>
                        <span style="background:#f8d7da;color:red;">Occupied</span>
                    </div>
                    <div id="hourGrid">
                        <?php
                            for ($i = 0; $i < $TOTAL_SLOTS; $i++) {
                                $totalMinutes = 420 + ($i * 30);
                                $h = intdiv($totalMinutes, 60);
                                $m = $totalMinutes % 60;
                                $displayHour = $h > 12 ? $h - 12 : $h;
                                if ($displayHour === 0) $displayHour = 12;
                                $ampm = $h >= 12 ? 'PM' : 'AM';
                                $label = $displayHour . ':' . str_pad($m, 2, '0', STR_PAD_LEFT) . ' ' . $ampm;
                                $status = $slotStatus[$i];
                                echo '<div class="hour ' . $status . '" data-slot="' . $i . '">' . $label . '</div>';
                            }
                        ?>
                    </div>
                    <button type="button" id="clearSelection">Clear Selection</button>
                    <p class="error-msg" id="rangeErrorMsg"></p>
                    <p id="selectedRange" style="font-size:0.82rem;margin-top:6px;color:#444;"></p>
                    <p id="totalPriceDisplay" style="font-weight:700;margin-top:6px;"><strong>Total:</strong> ₱0.00</p>
                </div>

                <?php else: ?>
                <div class="price-info">
                    <strong>Date:</strong> <?php echo htmlspecialchars($date); ?><br>
                    <strong>Time:</strong> 7:00 AM – 10:00 PM<br>
                    <strong>Price:</strong> ₱<?php echo number_format($space_price, 2); ?> / whole day
                </div>
                <?php endif; ?>

                <?php if ($isRoom): ?>
                <div class="price-info">
                    <strong>Date:</strong> <?php echo htmlspecialchars($date); ?><br>
                    <strong>Hours:</strong> 7:00 AM – 11:00 PM (min. 4 hrs)<br>
                    <strong>Rate:</strong> ₱<?php echo number_format($space_price, 2); ?> / hour
                </div>
                <?php endif; ?>
            </div>

            <div class="panel-right">
                <h2 class="panel-title">Contact Details</h2>

                <label class="field-label">First name <span class="req">*</span></label>
                <input class="field-input" maxlength="50" type="text" name="first_name"
                       placeholder="Juan" required
                       value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">

                <label class="field-label">Middle name</label>
                <input class="field-input" maxlength="50" type="text" name="middle_name"
                       placeholder="Santos (optional)"
                       value="<?php echo htmlspecialchars($_POST['middle_name'] ?? ''); ?>">

                <div>
                    <label class="field-label">Last name <span class="req">*</span></label>
                    <input class="field-input" maxlength="50" type="text" name="last_name"
                           placeholder="Dela Cruz" required
                           value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                </div>
                <div>
                    <label class="field-label">Suffix</label>
                    <select class="field-input" name="suffix">
                        <option value="">— None —</option>
                        <?php foreach (['Jr.','Sr.','II','III','IV'] as $s): ?>
                        <option value="<?php echo $s; ?>" <?php echo (($_POST['suffix'] ?? '') === $s) ? 'selected' : ''; ?>><?php echo $s; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <label class="field-label">Sex <span class="req">*</span></label>
                <div class="pill-group">
                    <input type="radio" name="sex" id="sexMale" value="Male" required
                           <?php echo (($_POST['sex'] ?? '') === 'Male') ? 'checked' : ''; ?>>
                    <label class="pill-btn" for="sexMale">Male</label>
                    <input type="radio" name="sex" id="sexFemale" value="Female"
                           <?php echo (($_POST['sex'] ?? '') === 'Female') ? 'checked' : ''; ?>>
                    <label class="pill-btn" for="sexFemale">Female</label>
                </div>

                <label class="field-label">Email Address <span class="req">*</span></label>
                <input class="field-input" maxlength="50" type="email" name="email"
                       placeholder="juan.delacruz@example.com" required
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">

                <label class="field-label">Contact Number <span class="req">*</span></label>
                <div class="phone-row">
                    <span class="phone-prefix">+63</span>
                    <input class="field-input" type="tel" name="phone"
                           placeholder="9123456789"
                           pattern="9[0-9]{9}" title="Enter a valid PH mobile number (9XXXXXXXXX)" required
                           value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                </div>
            </div>
        </div>

        <div class="action-bar">
            <button type="button" class="btn-cancel" onclick="window.location.href='../book.php'">← Cancel</button>
            <button type="button" class="btn-primary" id="toStep2">Next →</button>
        </div>
    </div>

    <!-- ===================== STEP 2 ===================== -->
    <div class="step <?php echo ($error && !$is_taken) ? 'active' : ''; ?>" id="step2">

        <div class="card-single">
            <h2 class="panel-title">Payment</h2>

            <?php if ($isRoom): ?>
            <p id="totalPriceDisplay2" style="text-align:center;font-size:1.05rem;margin-bottom:18px;"><strong>Total:</strong> ₱0.00</p>
            <?php else: ?>
            <p id="totalPriceDisplay2" style="text-align:center;font-size:1.05rem;margin-bottom:18px;"><strong>Total:</strong> ₱<?php echo number_format($space_price, 2); ?></p>
            <?php endif; ?>

            <label class="field-label">Payment Mode</label>
            <div class="pay-pill-group">
                <input type="radio" name="payment_mode" id="pm_ewallet" value="E-wallet"
                       <?php echo (($_POST['payment_mode'] ?? '') === 'E-wallet') ? 'checked' : ''; ?>>
                <label class="pay-pill-btn" for="pm_ewallet">E-Wallet</label>

                <input type="radio" name="payment_mode" id="pm_card" value="Card"
                       <?php echo (($_POST['payment_mode'] ?? '') === 'Card') ? 'checked' : ''; ?>>
                <label class="pay-pill-btn" for="pm_card">Credit / Debit Card</label>

                <input type="radio" name="payment_mode" id="pm_cash" value="Cash"
                       <?php echo (($_POST['payment_mode'] ?? '') === 'Cash') ? 'checked' : ''; ?>>
                <label class="pay-pill-btn" for="pm_cash">Cash</label>
            </div>

            <div id="referenceCodeDiv" class="hidden" style="margin-top:16px;">
                <div id="qrDiv" class="qrparent hidden">
                    <img class="qr" src="../../assets/qrcode.jpg">
                </div>
                <div id="bankDiv" class="qrparent hidden">
                    <img class="banktrans" src="../../assets/banktrans.jpg">
                </div>
                <label class="field-label">Reference Code <span class="req">*</span></label>
                <input class="field-input" maxlength="20" type="text" name="reference_code" id="reference_code"
                       value="<?php echo htmlspecialchars($_POST['reference_code'] ?? ''); ?>">
            </div>

            <div style="margin-top:16px;">
                <label class="field-label">Amount Paid <span class="req">*</span></label>
                <input class="field-input" type="number" name="amount_paid" id="amount_paid"
                       step="0.01" placeholder="0.00"
                       value="<?php echo htmlspecialchars($_POST['amount_paid'] ?? ''); ?>">
            </div>

            <div id="splitQuestionDiv" class="hidden" style="margin-top:20px;">
                <label class="field-label">Split Payment?</label>
                <div class="pay-pill-group">
                    <input type="radio" name="split_payment" id="split_yes" value="yes"
                           <?php echo (($_POST['split_payment'] ?? '') === 'yes') ? 'checked' : ''; ?>>
                    <label class="pay-pill-btn" for="split_yes">Yes</label>
                    <input type="radio" name="split_payment" id="split_no" value="no"
                           <?php echo (($_POST['split_payment'] ?? 'no') === 'no') ? 'checked' : ''; ?>>
                    <label class="pay-pill-btn" for="split_no">No</label>
                </div>
            </div>

            <div id="splitDetailsDiv" class="hidden" style="margin-top:16px;">
                <label class="field-label">Second Payment Mode</label>
                <div class="pay-pill-group">
                    <input type="radio" name="payment_mode_2" id="pm2_ewallet" value="E-wallet"
                           <?php echo (($_POST['payment_mode_2'] ?? '') === 'E-wallet') ? 'checked' : ''; ?>>
                    <label class="pay-pill-btn" for="pm2_ewallet">E-Wallet</label>
                    <input type="radio" name="payment_mode_2" id="pm2_card" value="Card"
                           <?php echo (($_POST['payment_mode_2'] ?? '') === 'Card') ? 'checked' : ''; ?>>
                    <label class="pay-pill-btn" for="pm2_card">Card</label>
                </div>

                <div id="referenceCodeDiv2" class="hidden" style="margin-top:12px;">
                    <div id="qrDiv2" class="qrparent hidden">
                        <img class="qr" src="../../assets/qrcode.jpg">
                    </div>
                    <div id="bankDiv2" class="qrparent hidden">
                        <img class="banktrans" src="../../assets/banktrans.jpg">
                    </div>
                    <label class="field-label">Reference Code <span class="req">*</span></label>
                    <input class="field-input" type="text" name="reference_code_2" id="reference_code_2"
                           value="<?php echo htmlspecialchars($_POST['reference_code_2'] ?? ''); ?>">
                </div>

                <div style="margin-top:12px;">
                    <label class="field-label">Amount Paid <span class="req">*</span></label>
                    <input class="field-input" type="number" name="amount_paid_2" id="amount_paid_2"
                           step="0.01" placeholder="0.00"
                           value="<?php echo htmlspecialchars($_POST['amount_paid_2'] ?? ''); ?>">
                </div>
            </div>

            <p class="error-msg" id="paymentErrorMsg" style="margin-top:10px;"></p>
        </div>

        <div class="action-bar">
            <button type="button" class="btn-back" id="toStep1">← Previous</button>
            <button type="submit" class="btn-primary">Submit Reservation</button>
        </div>
    </div>

</form>
<?php endif; ?>

<script>
const step1      = document.getElementById('step1');
const step2      = document.getElementById('step2');
const toStep2Btn = document.getElementById('toStep2');
const toStep1Btn = document.getElementById('toStep1');

toStep2Btn.addEventListener('click', () => {
    const requiredFields = step1.querySelectorAll('[required]');
    for (const field of requiredFields) {
        if (!field.checkValidity()) {
            field.reportValidity();
            return;
        }
    }

    <?php if ($isRoom): ?>
    const rtInput  = document.getElementById('reservation_time');
    const rangeErr = document.getElementById('rangeErrorMsg');
    if (!rtInput.value || !document.getElementById('expected_timeout').value) {
        rangeErr.textContent = 'Please select a valid time range before continuing.';
        rangeErr.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
    }
    <?php endif; ?>

    step1.classList.remove('active');
    step2.classList.add('active');
    window.scrollTo({ top: 0, behavior: 'smooth' });
});

toStep1Btn.addEventListener('click', () => {
    step2.classList.remove('active');
    step1.classList.add('active');
    window.scrollTo({ top: 0, behavior: 'smooth' });
});

// ---- Payment mode logic ----
const paymentModeRadios  = document.querySelectorAll('input[name="payment_mode"]');
const referenceCodeDiv   = document.getElementById('referenceCodeDiv');
const referenceCodeInput = document.getElementById('reference_code');
const qrDiv              = document.getElementById('qrDiv');
const bankDiv            = document.getElementById('bankDiv');
const splitQuestionDiv   = document.getElementById('splitQuestionDiv');
const splitDetailsDiv    = document.getElementById('splitDetailsDiv');
const splitRadios        = document.querySelectorAll('input[name="split_payment"]');
const paymentMode2Radios = document.querySelectorAll('input[name="payment_mode_2"]');
const referenceCodeDiv2  = document.getElementById('referenceCodeDiv2');
const referenceCodeInput2= document.getElementById('reference_code_2');
const qrDiv2             = document.getElementById('qrDiv2');
const bankDiv2           = document.getElementById('bankDiv2');
const paymentErrorMsg    = document.getElementById('paymentErrorMsg');

function applyPaymentMode(value) {
    if (value === 'E-wallet') {
        referenceCodeDiv.classList.remove('hidden');
        referenceCodeInput.required = true;
        qrDiv.classList.remove('hidden');
        bankDiv.classList.add('hidden');
        splitQuestionDiv.classList.add('hidden');
        splitDetailsDiv.classList.add('hidden');
        resetSplitSection();
    } else if (value === 'Card') {
        referenceCodeDiv.classList.remove('hidden');
        referenceCodeInput.required = true;
        qrDiv.classList.add('hidden');
        bankDiv.classList.remove('hidden');
        splitQuestionDiv.classList.add('hidden');
        splitDetailsDiv.classList.add('hidden');
        resetSplitSection();
    } else if (value === 'Cash') {
        referenceCodeDiv.classList.add('hidden');
        referenceCodeInput.required = false;
        referenceCodeInput.value = '';
        qrDiv.classList.add('hidden');
        bankDiv.classList.add('hidden');
        splitQuestionDiv.classList.remove('hidden');
    }
}

paymentModeRadios.forEach(radio => {
    radio.addEventListener('change', () => {
        const val = document.querySelector('input[name="payment_mode"]:checked').value;
        applyPaymentMode(val);
    });
});

// Re-apply state on page load (for POST re-renders)
const checkedPM = document.querySelector('input[name="payment_mode"]:checked');
if (checkedPM) applyPaymentMode(checkedPM.value);

splitRadios.forEach(radio => {
    radio.addEventListener('change', () => {
        const value = document.querySelector('input[name="split_payment"]:checked').value;
        if (value === 'yes') {
            splitDetailsDiv.classList.remove('hidden');
        } else {
            splitDetailsDiv.classList.add('hidden');
            resetSplitSection();
        }
    });
});

// Re-apply split state on page load
const checkedSplit = document.querySelector('input[name="split_payment"]:checked');
if (checkedSplit && checkedSplit.value === 'yes') {
    splitDetailsDiv.classList.remove('hidden');
}

function applyPaymentMode2(value) {
    referenceCodeDiv2.classList.remove('hidden');
    referenceCodeInput2.required = true;
    if (value === 'E-wallet') {
        qrDiv2.classList.remove('hidden');
        bankDiv2.classList.add('hidden');
    } else if (value === 'Card') {
        qrDiv2.classList.add('hidden');
        bankDiv2.classList.remove('hidden');
    }
}

paymentMode2Radios.forEach(radio => {
    radio.addEventListener('change', () => {
        const val = document.querySelector('input[name="payment_mode_2"]:checked').value;
        applyPaymentMode2(val);
    });
});

// Re-apply second payment mode on page load
const checkedPM2 = document.querySelector('input[name="payment_mode_2"]:checked');
if (checkedPM2) applyPaymentMode2(checkedPM2.value);

function resetSplitSection() {
    paymentMode2Radios.forEach(r => r.checked = false);
    referenceCodeDiv2.classList.add('hidden');
    referenceCodeInput2.required = false;
    referenceCodeInput2.value = '';
    qrDiv2.classList.add('hidden');
    bankDiv2.classList.add('hidden');
    const a2 = document.getElementById('amount_paid_2');
    if (a2) a2.value = '';
}

document.getElementById('reservationForm').addEventListener('submit', function(e) {
    const pmChecked = document.querySelector('input[name="payment_mode"]:checked');
    if (!pmChecked) {
        e.preventDefault();
        paymentErrorMsg.textContent = 'Please select a payment mode.';
        return;
    }
    const amountPaid = document.getElementById('amount_paid').value;
    if (!amountPaid || parseFloat(amountPaid) <= 0) {
        e.preventDefault();
        paymentErrorMsg.textContent = 'Please enter a valid amount paid.';
        return;
    }
    paymentErrorMsg.textContent = '';
});

<?php if ($isRoom): ?>
const slotCells            = document.querySelectorAll('.hour');
const rangeErrorMsg        = document.getElementById('rangeErrorMsg');
const selectedRangeDisp    = document.getElementById('selectedRange');
const reservationTimeInput = document.getElementById('reservation_time');
const expectedTimeoutInput = document.getElementById('expected_timeout');
const clearBtn             = document.getElementById('clearSelection');
const totalPriceDisplay    = document.getElementById('totalPriceDisplay');
const totalPriceDisplay2   = document.getElementById('totalPriceDisplay2');
const amountPaidInput      = document.getElementById('amount_paid');

const PRICE_PER_HOUR     = <?php echo json_encode($space_price); ?>;
const SLOT_MINUTES       = 30;
const BASE_MINUTES       = 420;
const TOTAL_SLOTS        = 32;
const MIN_DURATION_SLOTS = 8;

let startSlot = null;
let endSlot   = null;

function formatTime(slot) {
    const t = BASE_MINUTES + (slot * SLOT_MINUTES);
    return String(Math.floor(t/60)).padStart(2,'0') + ':' + String(t%60).padStart(2,'0') + ':00';
}
function formatLabel(slot, isEnd = false) {
    const t = BASE_MINUTES + (slot * SLOT_MINUTES) + (isEnd ? SLOT_MINUTES : 0);
    let h = Math.floor(t/60), m = t%60;
    const ampm = h >= 12 ? 'PM' : 'AM';
    let dh = h % 12; if (dh===0) dh=12;
    return dh + ':' + String(m).padStart(2,'0') + ' ' + ampm;
}
function formatPrice(v) { return '₱' + v.toFixed(2); }

function updateTotalPrice(start, end) {
    const hours = (end - start) * (SLOT_MINUTES / 60);
    const total = hours * PRICE_PER_HOUR;
    totalPriceDisplay.innerHTML  = '<strong>Total:</strong> ' + formatPrice(total);
    totalPriceDisplay2.innerHTML = '<strong>Total:</strong> ' + formatPrice(total);
    amountPaidInput.value = total.toFixed(2);
}
function resetTotalPrice() {
    totalPriceDisplay.innerHTML  = '<strong>Total:</strong> ₱0.00';
    totalPriceDisplay2.innerHTML = '<strong>Total:</strong> ₱0.00';
    amountPaidInput.value = '';
}
function clearSelection() { slotCells.forEach(c => c.classList.remove('selected')); }
function highlightRange(s, e) {
    clearSelection();
    slotCells.forEach(c => { if (parseInt(c.dataset.slot) >= s && parseInt(c.dataset.slot) < e) c.classList.add('selected'); });
}
function hasConflict(s, e) {
    for (let i = s; i < e; i++) {
        const c = document.querySelector(`.hour[data-slot="${i}"]`);
        if (!c || c.classList.contains('occupied') || c.classList.contains('pending')) return true;
    }
    return false;
}

slotCells.forEach(cell => {
    cell.addEventListener('click', () => {
        if (cell.classList.contains('occupied') || cell.classList.contains('pending')) return;
        const clicked = parseInt(cell.dataset.slot);
        rangeErrorMsg.textContent = '';

        if (startSlot === null || endSlot !== null) {
            startSlot = clicked; endSlot = null;
            clearSelection(); cell.classList.add('selected');
            selectedRangeDisp.textContent = `Start: ${formatLabel(startSlot)} — now pick an end time`;
            reservationTimeInput.value = ''; expectedTimeoutInput.value = '';
            resetTotalPrice();
        } else {
            let proposedEnd = clicked + 1;
            if (proposedEnd <= startSlot)                    { rangeErrorMsg.textContent = 'End time must be after start time.'; return; }
            if (proposedEnd - startSlot < MIN_DURATION_SLOTS){ rangeErrorMsg.textContent = 'Booking must be at least 4 hours long.'; return; }
            if ((proposedEnd - startSlot) % 2 !== 0)         { rangeErrorMsg.textContent = 'Booking duration must be a whole number of hours (e.g. 4 or 5 hrs, not 4.5).'; return; }
            if (proposedEnd > TOTAL_SLOTS)                   { rangeErrorMsg.textContent = 'End time cannot exceed 11:00 PM.'; return; }
            if (hasConflict(startSlot, proposedEnd))         { rangeErrorMsg.textContent = 'Selected range overlaps with an occupied or pending slot.'; return; }

            endSlot = proposedEnd;
            highlightRange(startSlot, endSlot);
            selectedRangeDisp.textContent = `Selected: ${formatLabel(startSlot)} – ${formatLabel(endSlot - 1, true)}`;
            reservationTimeInput.value = formatTime(startSlot);
            expectedTimeoutInput.value = formatTime(endSlot);
            updateTotalPrice(startSlot, endSlot);
        }
    });
});

clearBtn.addEventListener('click', () => {
    startSlot = null; endSlot = null;
    clearSelection();
    selectedRangeDisp.textContent = ''; rangeErrorMsg.textContent = '';
    reservationTimeInput.value = ''; expectedTimeoutInput.value = '';
    resetTotalPrice();
});

<?php else: ?>
document.getElementById('amount_paid').value = (<?php echo json_encode($space_price); ?>).toFixed(2);
<?php endif; ?>
</script>
<script src="iskrip.js"></script>

</body>
</html>