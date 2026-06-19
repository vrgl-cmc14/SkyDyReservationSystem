<!DOCTYPE html>
<html>
    <head>
        <style>
            .hidden { display: none; }

        </style>
    </head>
    <body>
        <div>
            <form id="paymentForm">
                <label>PAYMENT MODE</label>
                <div class="paymentMode">
                    <input type="radio" value="E-wallet" name="payment_mode" id="E-wallet" required>
                    <label for="E-wallet"> E-wallet </label>

                    <input type="radio" value="Card" name="payment_mode" id="Card" required>
                    <label for="Card"> Card </label>

                    <input type="radio" value="Cash" name="payment_mode" id="Cash" required>
                    <label for="Cash"> Cash </label>
                </div>


                <div id="referenceCodeDiv" class="section hidden">
                    <label for="reference_code">Reference Code: </label>
                    <input type="text" name="reference_code" id="reference_code">
                </div>
                <div>
                    <label for="amount_paid">Amount Paid: </label>
                    <input type="number" name="amount_paid" id="amount_paid">
                </div>

                <div id="splitQuestionDiv" class="section hidden">
                    <label>Split Payment?</label>
                    <input type="radio" name="split_payment" id="split_yes" value="yes">
                    <label for="split_yes"> Yes </label>

                    <input type="radio" name="split_payment" id="split_no" value="no">
                    <label for="split_no"> No </label>
                </div>

                <div id="splitDetailsDiv" class="section hidden">
                    <label>SECOND PAYMENT MODE</label>
                    <div class="paymentMode2">
                        <input type="radio" value="E-wallet" name="payment_mode_2" id="E-wallet2">
                        <label for="E-wallet2"> E-wallet </label>

                        <input type="radio" value="Card" name="payment_mode_2" id="Card2">
                        <label for="Card2"> Card </label>
                    </div>

                    <div id="referenceCodeDiv2" class="section hidden">
                        <label for="reference_code_2">Reference Code: </label>
                        <input type="text" name="reference_code_2" id="reference_code_2">
                    </div>

                    <div>
                        <label for="amount_paid_2">Amount Paid: </label>
                        <input type="number" name="amount_paid_2" id="amount_paid_2">
                    </div>
                </div>


                <input type="submit" value="submit">
            </form>
        </div>

        <script>
            const paymentModeRadios = document.querySelectorAll('input[name="payment_mode"]');
            const referenceCodeDiv = document.getElementById('referenceCodeDiv');
            const referenceCodeInput = document.getElementById('reference_code');
            const splitQuestionDiv = document.getElementById('splitQuestionDiv');
            const splitDetailsDiv = document.getElementById('splitDetailsDiv');

            const splitRadios = document.querySelectorAll('input[name="split_payment"]');
            const paymentMode2Radios = document.querySelectorAll('input[name="payment_mode_2"]');
            const referenceCodeDiv2 = document.getElementById('referenceCodeDiv2');
            const referenceCodeInput2 = document.getElementById('reference_code_2');

            paymentModeRadios.forEach(radio => {
                radio.addEventListener('change', () => {
                    const value = document.querySelector('input[name="payment_mode"]:checked').value;

                    if (value === 'Card' || value === 'E-wallet') {
                        referenceCodeDiv.classList.remove('hidden');
                        referenceCodeInput.required = true;

                        splitQuestionDiv.classList.add('hidden');
                        splitDetailsDiv.classList.add('hidden');
                        resetSplitSection();
                    } else if (value === 'Cash') {
                        referenceCodeDiv.classList.add('hidden');
                        referenceCodeInput.required = false;
                        referenceCodeInput.value = '';

                        splitQuestionDiv.classList.remove('hidden');
                    }
                });
            });

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

            paymentMode2Radios.forEach(radio => {
                radio.addEventListener('change', () => {
                    referenceCodeDiv2.classList.remove('hidden');
                    referenceCodeInput2.required = true;
                });
            });

            function resetSplitSection() {
                paymentMode2Radios.forEach(r => r.checked = false);
                referenceCodeDiv2.classList.add('hidden');
                referenceCodeInput2.required = false;
                referenceCodeInput2.value = '';
                document.getElementById('amount_paid_2').value = '';
            }
        </script>
    </body>
</html>