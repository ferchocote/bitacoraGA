// subscription-popup.js


(function() {
    // storage/timer no longer required; popup always shows

    function injectStyles() {
        if (document.getElementById('subscription-popup-styles')) return;
        const css = `
        #subscription-popup-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 100000;
        }
        #subscription-popup {
            background: #fff;
            border-radius: 8px;
            max-width: 420px;
            width: 90%;
            padding: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            font-family: Arial, sans-serif;
            color: #222;
            text-align:center;
        }
        #subscription-popup h3 { margin: 0 0 8px 0; font-size: 18px }
        #subscription-popup p { margin: 0 0 16px 0; font-size: 14px }
        #subscription-popup .actions { text-align: center }
        #subscription-popup button { margin: 8px auto; padding: 8px 12px; border-radius: 4px; border: none; cursor: pointer; display: inline-block }
        #subscription-popup .btn-primary { background: #0078d4; color: white }
        #subscription-popup .btn-secondary { background: #f3f3f3; color: #222 }
        /* pastel icon color */
        #subscription-popup svg path,
        #subscription-popup svg circle {
            stroke: #eb8484 !important;
        }
        `;
        const style = document.createElement('style');
        style.id = 'subscription-popup-styles';
        style.innerText = css;
        document.head.appendChild(style);
    }

    function createPopup() {
        const backdrop = document.createElement('div');
        backdrop.id = 'subscription-popup-backdrop';

        const dialog = document.createElement('div');
        dialog.id = 'subscription-popup';

        dialog.innerHTML = `
            <div style="margin-bottom: 30px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="100px" height="100px" viewBox="0 0 24 24" fill="none">
                <path d="M12 8V12" stroke="#000000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M12 16.0195V16" stroke="#000000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <circle cx="12" cy="12" r="10" stroke="#000000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <h1>Tu suscripción ha expirado</h1>
            <p>Algunas funcionalidades fueron deshabilitadas y otras podrían dejar de estar disponibles pronto. Para seguir usando la aplicación sin interrupciones, por favor contacta a un administrador.</p>
            <div class="actions">
                <button class="btn-secondary" style="background-color: #e74c3c; color:#fff" id="subscription-pay">Recordarme más tarde</button>
            </div>
        `;

        backdrop.appendChild(dialog);

        const laterBtn = dialog.querySelector('#subscription-later');
        if (laterBtn) {
            laterBtn.addEventListener('click', function() {
                closePopup();
            });
        }

        const payBtn = dialog.querySelector('#subscription-pay');
        if (payBtn) {
            // disable button for a short countdown so user has time to read/close
            let countdown = 5;
            const origText = payBtn.textContent;
            payBtn.disabled = true;
            payBtn.textContent = `${origText} (${countdown})`;
            const timer = setInterval(() => {
                countdown -= 1;
                if (countdown > 0) {
                    payBtn.textContent = `${origText} (${countdown})`;
                } else {
                    clearInterval(timer);
                    payBtn.disabled = false;
                    payBtn.textContent = origText;
                }
            }, 1000);

            payBtn.addEventListener('click', function() {
                // Dispatch an event so the app can react (e.g., redirect to payment page)
                const ev = new CustomEvent('subscriptionPopup:pay');
                window.dispatchEvent(ev);
                closePopup();
            });
        }

        return backdrop;
    }

    // popup should always be displayed unless explicitly disabled
    function shouldShow() {
        return !(window.SubscriptionPopup && window.SubscriptionPopup.disable);
    }

    // no-op; no storage required any more
    function markShown() {}

    let popupElement = null;
    // timestamp of last attempt to show popup; used to debounce multiple calls
    let _lastShowTs = 0;

    function showPopup() {
        const now = Date.now();
        if (now - _lastShowTs < 1000) {
            // ignore extra calls within 1s
            return;
        }
        _lastShowTs = now;

        if (popupElement) return; // already visible
        injectStyles();
        popupElement = createPopup();
        document.body.appendChild(popupElement);
    }

    function closePopup() {
        if (!popupElement) return;
        popupElement.remove();
        popupElement = null;
    }

    // helper used both when DOMContentLoaded hasn't fired yet or when it has
    function init() {
        try {
            if (shouldShow()) {
                showPopup();
            }
        } catch (e) {
            console.error('subscription-popup error', e);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        // script was injected after the event; run immediately
        init();
    }

    // Expose for debugging or manual triggering (e.g. open console and call
    // SubscriptionPopup.show() or clear the key to force it again).
    window.SubscriptionPopup = {
        show: showPopup,
        close: closePopup,
        // allow disabling via global flag
        disable: false
    };

})();
