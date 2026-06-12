/**
 * SaaS Pricing & Membership Logic
 * Coordinates cycles, accordion collapses, secure gateway demo popups, and mock payment calls.
 */

document.addEventListener('DOMContentLoaded', () => {
    
    // ─── 1. Billing cycle toggle ─────────────────────────────────────
    const billingCheckbox = document.getElementById('billingCheckbox');
    const switchWrapper = document.getElementById('switchWrapper');
    const labelMonthly = document.getElementById('labelMonthly');
    const labelYearly = document.getElementById('labelYearly');
    const priceAmounts = document.querySelectorAll('.price-amount');
    const pricePeriods = document.querySelectorAll('.price-period');

    if (billingCheckbox && switchWrapper) {
        // Toggle on wrapper click
        switchWrapper.addEventListener('click', () => {
            billingCheckbox.checked = !billingCheckbox.checked;
            handleBillingToggle();
        });

        // Toggle on checkbox change
        billingCheckbox.addEventListener('change', handleBillingToggle);
    }

    function handleBillingToggle() {
        const isYearly = billingCheckbox.checked;
        
        if (isYearly) {
            labelYearly.classList.add('active');
            labelMonthly.classList.remove('active');
            updatePrices('yearly');
        } else {
            labelMonthly.classList.add('active');
            labelYearly.classList.remove('active');
            updatePrices('monthly');
        }
    }

    function updatePrices(cycle) {
        priceAmounts.forEach((el, index) => {
            const monthlyVal = el.getAttribute('data-monthly');
            const yearlyVal = el.getAttribute('data-yearly');
            
            // Fading animation transition
            el.style.opacity = '0';
            el.style.transform = 'translateY(-4px)';
            
            setTimeout(() => {
                const targetVal = (cycle === 'yearly') ? yearlyVal : monthlyVal;
                
                if (targetVal === 'Enterprise') {
                    el.textContent = 'Enterprise';
                } else if (targetVal === '0') {
                    el.textContent = '0';
                } else {
                    el.textContent = targetVal;
                }
                
                // Update periods
                const periodEl = pricePeriods[index];
                if (periodEl) {
                    if (targetVal === 'Enterprise') {
                        periodEl.textContent = '';
                    } else {
                        periodEl.textContent = (cycle === 'yearly') ? '/ year' : '/ month';
                    }
                }

                el.style.opacity = '1';
                el.style.transform = 'translateY(0)';
            }, 150);
        });
    }

    // ─── 2. FAQ Accordion ─────────────────────────────────────────────
    const faqQuestions = document.querySelectorAll('.faq-question');
    
    faqQuestions.forEach(question => {
        question.addEventListener('click', () => {
            const faqItem = question.parentElement;
            const isActive = faqItem.classList.contains('active');
            
            // Close other open accordions
            document.querySelectorAll('.faq-item').forEach(item => {
                item.classList.remove('active');
            });
            
            // Toggle current accordion
            if (!isActive) {
                faqItem.classList.add('active');
            }
        });
    });

    // ─── 3. Checkout Modal Trigger ────────────────────────────────────
    const checkoutModal = document.getElementById('checkoutModal');
    const closeModalBtn = document.getElementById('closeModalBtn');
    const subscribeBtns = document.querySelectorAll('.subscribe-btn');
    
    // Modal Summary elements
    const summaryPlanName = document.getElementById('summaryPlanName');
    const summaryBillingCycle = document.getElementById('summaryBillingCycle');
    const summaryPrice = document.getElementById('summaryPrice');
    
    const checkoutPlanId = document.getElementById('checkoutPlanId');
    const checkoutBillingCycle = document.getElementById('checkoutBillingCycle');

    subscribeBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            // Check login state
            if (!IS_LOGGED_IN) {
                // Redirect to login page
                window.location.href = 'user-login.php?redirect=pricing.php';
                return;
            }

            const card = btn.closest('.pricing-card');
            const planName = card.getAttribute('data-plan');
            const isYearly = billingCheckbox ? billingCheckbox.checked : false;
            const cycleText = isYearly ? 'yearly' : 'monthly';
            
            // Get proper ID
            const planId = isYearly ? (btn.getAttribute('data-plan-yearly-id') || btn.getAttribute('data-plan-id')) : btn.getAttribute('data-plan-id');
            
            // Free plan doesn't need checkout
            if (planName === 'Free Member') {
                registerFreePlan(planId);
                return;
            }

            // Get Price
            const priceEl = card.querySelector('.price-amount');
            const priceVal = isYearly ? priceEl.getAttribute('data-yearly') : priceEl.getAttribute('data-monthly');

            // Populate summary details
            summaryPlanName.textContent = planName;
            summaryBillingCycle.textContent = isYearly ? 'Yearly Billing' : 'Monthly Billing';
            summaryPrice.textContent = '৳' + parseFloat(priceVal).toLocaleString();
            
            checkoutPlanId.value = planId;
            checkoutBillingCycle.value = cycleText;

            // Display checkout modal
            checkoutModal.classList.add('active');
        });
    });

    // Modal Close click listeners
    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', () => {
            checkoutModal.classList.remove('active');
        });
    }

    window.addEventListener('click', (e) => {
        if (e.target === checkoutModal) {
            checkoutModal.classList.remove('active');
        }
    });

    // ─── 4. Gateway Select Options ────────────────────────────────────
    const gatewayOptions = document.querySelectorAll('.gateway-option');
    const phoneGatewayInput = document.getElementById('phoneGatewayInput');
    const cardGatewayInput = document.getElementById('cardGatewayInput');
    const gatewayPhoneInput = document.getElementById('gatewayPhone');

    gatewayOptions.forEach(option => {
        option.addEventListener('click', () => {
            gatewayOptions.forEach(opt => opt.classList.remove('active'));
            option.classList.add('active');
            
            const radio = option.querySelector('input[type="radio"]');
            if (radio) {
                radio.checked = true;
                
                // Toggle card vs phone layouts
                if (radio.value === 'card') {
                    cardGatewayInput.style.display = 'block';
                    phoneGatewayInput.style.display = 'none';
                    gatewayPhoneInput.removeAttribute('required');
                } else {
                    cardGatewayInput.style.display = 'none';
                    phoneGatewayInput.style.display = 'block';
                    gatewayPhoneInput.setAttribute('required', '');
                }
            }
        });
    });

    // ─── 5. Mock Checkout Submission ──────────────────────────────────
    const checkoutForm = document.getElementById('checkoutForm');
    const paymentLoader = document.getElementById('paymentLoader');

    if (checkoutForm) {
        checkoutForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            // Set button loading state
            if (paymentLoader) paymentLoader.style.display = 'inline-block';
            const submitBtn = checkoutForm.querySelector('button[type="submit"]');
            if (submitBtn) submitBtn.disabled = true;

            const formData = new FormData(checkoutForm);
            
            try {
                const response = await fetch('subscribe-process.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.status === 'success') {
                    // Successful simulated payment
                    checkoutModal.classList.remove('active');
                    showToast('success', data.message || 'Payment success!');
                    
                    // Redirect to user dashboard after success
                    setTimeout(() => {
                        window.location.href = 'user/dashboard.php';
                    }, 1500);
                } else {
                    showToast('error', data.message || 'Mock payment authorized failed.');
                }
            } catch (err) {
                console.error(err);
                showToast('error', 'Network error authorizing payment.');
            } finally {
                if (paymentLoader) paymentLoader.style.display = 'none';
                if (submitBtn) submitBtn.disabled = false;
            }
        });
    }

    // ─── 6. Register Free Plan Direct ─────────────────────────────────
    async function registerFreePlan(planId) {
        const formData = new FormData();
        formData.append('plan_id', planId);
        formData.append('billing_cycle', 'monthly');
        formData.append('payment_method', 'free');
        
        try {
            const response = await fetch('subscribe-process.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            
            if (data.status === 'success') {
                showToast('success', 'Plan updated successfully!');
                setTimeout(() => {
                    window.location.href = 'user/dashboard.php';
                }, 1500);
            } else {
                showToast('error', data.message);
            }
        } catch (err) {
            console.error(err);
            showToast('error', 'Network error registering plan.');
        }
    }

    // ─── 7. Sponsor Relations Popup ───────────────────────────────────
    const contactSponsorBtn = document.querySelector('.contact-sponsor-btn');
    const sponsorModal = document.getElementById('sponsorModal');
    const closeSponsorModalBtn = document.getElementById('closeSponsorModalBtn');

    if (contactSponsorBtn && sponsorModal) {
        contactSponsorBtn.addEventListener('click', () => {
            sponsorModal.classList.add('active');
        });
    }

    if (closeSponsorModalBtn) {
        closeSponsorModalBtn.addEventListener('click', () => {
            sponsorModal.classList.remove('active');
        });
    }

    // ─── 8. Toast Helper ─────────────────────────────────────────────
    function showToast(type, message) {
        const oldToast = document.querySelector('.toast-msg');
        if (oldToast) oldToast.remove();

        const toast = document.createElement('div');
        toast.className = `toast-msg ${type}`;
        toast.textContent = message;
        document.body.appendChild(toast);
        toast.style.display = 'block';
        
        setTimeout(() => {
            toast.style.animation = 'slideInRight 0.3s ease reverse';
            setTimeout(() => {
                toast.remove();
            }, 300);
        }, 4000);
    }
});
