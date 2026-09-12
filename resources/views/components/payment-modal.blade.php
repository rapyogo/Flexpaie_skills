@props([
    'amount' => 10.00,
    'currency' => 'USD',
    'reference' => null,
    'initUrl' => route('flexpay.initiate'),
    'statusUrl' => route('flexpay.status'),
    'onSuccessUrl' => null,
])

<div id="flexpay-momo-container" style="font-family: inherit;">
    <!-- Modal Backdrop -->
    <div id="flexpay-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.65);z-index:999999;align-items:center;justify-content:center;backdrop-filter:blur(3px);">
        <div style="background:#fff;border-radius:16px;max-width:440px;width:90%;box-shadow:0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04);overflow:hidden;position:relative;animation:fpFadeIn 0.25s ease-out;">
            
            <!-- Modal Header -->
            <div style="background:#0f172a;padding:20px;color:#fff;text-align:center;">
                <h3 style="margin:0;font-size:18px;font-weight:700;">Paiement Mobile Money</h3>
                <p style="margin:6px 0 0 0;font-size:14px;color:#94a3b8;">
                    Montant : <strong style="color:#38bdf8;font-size:16px;">{{ number_format($amount, 2) }} {{ $currency }}</strong>
                </p>
            </div>

            <!-- Step 1 : Phone Input -->
            <div id="fp-step-input" style="padding:24px;">
                <label for="fp-phone-input" style="display:block;font-size:13px;font-weight:600;color:#334155;margin-bottom:8px;">
                    Numéro Mobile Money (Airtel, Vodacom, Orange, Africell) :
                </label>
                <div style="position:relative;">
                    <input type="tel" id="fp-phone-input" placeholder="ex: 0812345678 ou +243973604485" 
                           style="width:100%;box-sizing:border-box;padding:12px 14px;border:2px solid #cbd5e1;border-radius:10px;font-size:16px;outline:none;transition:border-color 0.2s;"
                           oninput="fpDetectOperator(this.value)">
                </div>

                <!-- Live Operator Indicator -->
                <div id="fp-operator-indicator" style="margin-top:10px;min-height:24px;display:flex;align-items:center;"></div>

                <div id="fp-input-error" style="color:#ef4444;font-size:13px;margin-top:8px;display:none;"></div>

                <button id="fp-btn-pay" onclick="fpStartPayment()" 
                        style="width:100%;margin-top:18px;padding:14px;background:#0284c7;color:#fff;border:none;border-radius:10px;font-size:16px;font-weight:700;cursor:pointer;transition:background 0.2s;">
                    Payer {{ number_format($amount, 2) }} {{ $currency }}
                </button>

                <button onclick="fpCloseModal()" 
                        style="width:100%;margin-top:8px;padding:10px;background:transparent;color:#64748b;border:none;font-size:14px;cursor:pointer;">
                    Annuler
                </button>
            </div>

            <!-- Step 2 : Waiting / Polling -->
            <div id="fp-step-waiting" style="padding:28px 24px;text-align:center;display:none;">
                <div style="margin:0 auto 16px auto;width:56px;height:56px;border:4px solid #e2e8f0;border-top-color:#0284c7;border-radius:50%;animation:fpSpin 1s linear infinite;"></div>
                
                <h4 style="margin:0 0 8px 0;font-size:17px;color:#0f172a;font-weight:700;">Consultez votre téléphone !</h4>
                <p id="fp-waiting-msg" style="margin:0 0 16px 0;font-size:14px;color:#475569;line-height:1.4;">
                    Une demande d'autorisation avec saisie de votre code PIN a été envoyée sur votre téléphone.
                </p>

                <!-- Operator Notice -->
                <div id="fp-waiting-op" style="margin-bottom:16px;"></div>

                <!-- Timeout Bar -->
                <div style="width:100%;background:#e2e8f0;height:6px;border-radius:3px;overflow:hidden;margin-bottom:12px;">
                    <div id="fp-progress-bar" style="width:100%;height:100%;background:#0284c7;transition:width 1s linear;"></div>
                </div>
                <div style="font-size:12px;color:#94a3b8;" id="fp-timer-label">Délai restant : ~180s</div>

                <!-- Re-check Button without re-initiating -->
                <button id="fp-btn-recheck" onclick="fpManualCheck()" 
                        style="margin-top:20px;padding:10px 18px;background:#f1f5f9;color:#334155;border:1px solid #cbd5e1;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;">
                    🔄 Vérifier à nouveau (sans renvoyer de notification)
                </button>
            </div>

            <!-- Step 3 : Success -->
            <div id="fp-step-success" style="padding:28px 24px;text-align:center;display:none;">
                <div style="width:56px;height:56px;background:#dcfce7;color:#16a34a;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:28px;margin-bottom:14px;">
                    ✓
                </div>
                <h4 style="margin:0 0 8px 0;font-size:18px;color:#15803d;font-weight:700;">Paiement confirmé !</h4>
                <p style="margin:0 0 20px 0;font-size:14px;color:#475569;">
                    Votre transaction a été validée avec succès par l'opérateur.
                </p>
                <button onclick="fpOnSuccess()" 
                        style="width:100%;padding:14px;background:#16a34a;color:#fff;border:none;border-radius:10px;font-size:15px;font-weight:700;cursor:pointer;">
                    Continuer
                </button>
            </div>

            <!-- Step 4 : Failed -->
            <div id="fp-step-failed" style="padding:28px 24px;text-align:center;display:none;">
                <div style="width:56px;height:56px;background:#fee2e2;color:#dc2626;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:28px;margin-bottom:14px;">
                    ✕
                </div>
                <h4 style="margin:0 0 8px 0;font-size:18px;color:#b91c1c;font-weight:700;">Échec de la transaction</h4>
                <p id="fp-fail-reason" style="margin:0 0 20px 0;font-size:14px;color:#475569;">
                    Le paiement a été rejeté ou a expiré.
                </p>
                <button onclick="fpResetModal()" 
                        style="width:100%;padding:12px;background:#0284c7;color:#fff;border:none;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;">
                    Réessayer
                </button>
            </div>

        </div>
    </div>
</div>

<style>
@keyframes fpSpin { to { transform: rotate(360deg); } }
@keyframes fpFadeIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
</style>

<script>
let fpCurrentOrderNumber = null;
let fpCurrentReference = "{{ $reference }}";
let fpPollTimer = null;
let fpCountdownTimer = null;
let fpSecondsLeft = 180;
const FP_INIT_URL = "{{ $initUrl }}";
const FP_STATUS_URL = "{{ $statusUrl }}";
const FP_SUCCESS_URL = "{{ $onSuccessUrl }}";
const FP_AMOUNT = {{ (float) $amount }};
const FP_CURRENCY = "{{ $currency }}";

function fpOpenModal() {
    document.getElementById('flexpay-modal').style.display = 'flex';
    fpResetModal();
}

function fpCloseModal() {
    fpStopTimers();
    document.getElementById('flexpay-modal').style.display = 'none';
}

function fpResetModal() {
    fpStopTimers();
    document.getElementById('fp-step-input').style.display = 'block';
    document.getElementById('fp-step-waiting').style.display = 'none';
    document.getElementById('fp-step-success').style.display = 'none';
    document.getElementById('fp-step-failed').style.display = 'none';
    document.getElementById('fp-input-error').style.display = 'none';
}

function fpDetectOperator(phone) {
    const clean = phone.replace(/\D/g, '');
    let prefix = '';
    if (clean.startsWith('243') && clean.length >= 5) {
        prefix = clean.substring(3, 5);
    } else if (clean.startsWith('0') && clean.length >= 3) {
        prefix = clean.substring(1, 3);
    }

    const indicator = document.getElementById('fp-operator-indicator');
    const airtel = ['97', '98', '99'];
    const vodacom = ['81', '82', '83'];
    const orange = ['80', '84', '85', '89'];
    const africell = ['90', '91'];

    if (airtel.includes(prefix)) {
        indicator.innerHTML = '<span style="color:#b91c1c;font-weight:700;font-size:12px;">🔴 Airtel Money détecté</span>';
    } else if (vodacom.includes(prefix)) {
        indicator.innerHTML = '<span style="color:#b91c1c;font-weight:700;font-size:12px;">🔴 M-Pesa (Vodacom) détecté</span>';
    } else if (orange.includes(prefix)) {
        indicator.innerHTML = '<span style="color:#c2410c;font-weight:700;font-size:12px;">🟠 Orange Money détecté</span>';
    } else if (africell.includes(prefix)) {
        indicator.innerHTML = '<span style="color:#6b21a8;font-weight:700;font-size:12px;">🟣 AfriMoney (Africell) détecté</span>';
    } else {
        indicator.innerHTML = '';
    }
}

async function fpStartPayment() {
    const phoneInput = document.getElementById('fp-phone-input');
    const phone = phoneInput.value.trim();
    const errorDiv = document.getElementById('fp-input-error');
    errorDiv.style.display = 'none';

    if (!phone) {
        errorDiv.innerText = 'Veuillez saisir votre numéro de téléphone.';
        errorDiv.style.display = 'block';
        return;
    }

    const btn = document.getElementById('fp-btn-pay');
    btn.disabled = true;
    btn.innerText = 'Envoi en cours...';

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const res = await fetch(FP_INIT_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                phone: phone,
                amount: FP_AMOUNT,
                currency: FP_CURRENCY,
                reference: fpCurrentReference || ('FP-' + Date.now())
            })
        });

        const data = await res.json();

        if (res.ok && data.status) {
            fpCurrentOrderNumber = data.orderNumber;
            fpCurrentReference = data.reference;

            // Passer à l'écran d'attente
            document.getElementById('fp-step-input').style.display = 'none';
            document.getElementById('fp-step-waiting').style.display = 'block';

            fpStartPolling();
        } else {
            errorDiv.innerText = data.message || 'Impossible d\'initier le paiement.';
            errorDiv.style.display = 'block';
        }
    } catch (e) {
        errorDiv.innerText = 'Erreur réseau : ' + e.message;
        errorDiv.style.display = 'block';
    } finally {
        btn.disabled = false;
        btn.innerText = 'Payer';
    }
}

function fpStartPolling() {
    fpSecondsLeft = 180;
    const bar = document.getElementById('fp-progress-bar');
    const timerLabel = document.getElementById('fp-timer-label');

    fpCountdownTimer = setInterval(() => {
        fpSecondsLeft--;
        const pct = Math.max(0, (fpSecondsLeft / 180) * 100);
        bar.style.width = pct + '%';
        timerLabel.innerText = 'Délai restant : ~' + fpSecondsLeft + 's';

        if (fpSecondsLeft <= 0) {
            fpStopTimers();
            fpShowFailed('Délai dépassé (3 minutes). Si vous avez tapé votre code PIN, cliquez sur "Vérifier à nouveau".');
        }
    }, 1000);

    // Sondage toutes les 3 secondes
    fpPollTimer = setInterval(fpCheckStatus, 3000);
}

function fpStopTimers() {
    if (fpPollTimer) clearInterval(fpPollTimer);
    if (fpCountdownTimer) clearInterval(fpCountdownTimer);
}

async function fpCheckStatus() {
    if (!fpCurrentOrderNumber && !fpCurrentReference) return;

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const res = await fetch(FP_STATUS_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                orderNumber: fpCurrentOrderNumber,
                reference: fpCurrentReference
            })
        });

        const data = await res.json();

        if (data.status === 'completed') {
            fpStopTimers();
            document.getElementById('fp-step-waiting').style.display = 'none';
            document.getElementById('fp-step-success').style.display = 'block';
        } else if (data.status === 'failed') {
            fpStopTimers();
            fpShowFailed(data.message || 'La transaction a échoué.');
        }
        // Si data.status === 'pending', continuer le polling normalement
    } catch (e) {
        console.warn('Erreur temporaire de polling FlexPay :', e);
    }
}

function fpManualCheck() {
    const btn = document.getElementById('fp-btn-recheck');
    btn.innerText = 'Vérification en cours...';
    btn.disabled = true;

    fpCheckStatus().finally(() => {
        setTimeout(() => {
            btn.innerText = '🔄 Vérifier à nouveau (sans renvoyer de notification)';
            btn.disabled = false;
        }, 1500);
    });
}

function fpShowFailed(reason) {
    document.getElementById('fp-step-waiting').style.display = 'none';
    document.getElementById('fp-step-failed').style.display = 'block';
    document.getElementById('fp-fail-reason').innerText = reason;
}

function fpOnSuccess() {
    if (FP_SUCCESS_URL) {
        window.location.href = FP_SUCCESS_URL;
    } else {
        fpCloseModal();
        location.reload();
    }
}
</script>
