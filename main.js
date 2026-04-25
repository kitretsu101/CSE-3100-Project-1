/**
 * main.js - Main JavaScript file for Bit2byte Coding Club Website
 * Contains all vanilla JavaScript features using ES6+ syntax
 */

// =====================================================
// TYPEWRITER EFFECT
// =====================================================

class Typewriter {
    constructor(element, text, speed = 100) {
        this.element = element;
        this.text = text;
        this.speed = speed;
        this.index = 0;
        this.isDeleting = false;
        this.element.classList.add('typewriter');
    }

    type() {
        const currentText = this.text.substring(0, this.index);
        this.element.textContent = currentText;

        if (!this.isDeleting) {
            this.index++;
            if (this.index > this.text.length) {
                this.isDeleting = true;
                setTimeout(() => this.type(), 1500); // Pause before deleting
                return;
            }
        } else {
            this.index--;
            if (this.index < 0) {
                this.isDeleting = false;
                setTimeout(() => this.type(), 500); // Pause before typing again
                return;
            }
        }

        setTimeout(() => this.type(), this.isDeleting ? this.speed / 2 : this.speed);
    }

    start() {
        this.type();
    }
}

// =====================================================
// STICKY NAVBAR
// =====================================================

class StickyNavbar {
    constructor(navbarSelector, threshold = 80) {
        this.navbar = document.querySelector(navbarSelector);
        this.threshold = threshold;
        this.isSticky = false;
        this.init();
    }

    init() {
        window.addEventListener('scroll', () => this.handleScroll());
    }

    handleScroll() {
        const scrollY = window.scrollY;

        if (scrollY > this.threshold && !this.isSticky) {
            this.navbar.classList.add('sticky');
            this.isSticky = true;
        } else if (scrollY <= this.threshold && this.isSticky) {
            this.navbar.classList.remove('sticky');
            this.isSticky = false;
        }
    }
}

// =====================================================
// TOAST NOTIFICATION
// =====================================================

class Toast {
    constructor() {
        this.container = this.createContainer();
        document.body.appendChild(this.container);
    }

    createContainer() {
        const container = document.createElement('div');
        container.id = 'toast-container';
        container.style.position = 'fixed';
        container.style.bottom = '20px';
        container.style.right = '20px';
        container.style.zIndex = '1000';
        return container;
    }

    show(message, type = 'success', duration = 3000) {
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.textContent = message;

        this.container.appendChild(toast);

        // Trigger animation
        setTimeout(() => toast.classList.add('show'), 100);

        // Auto remove
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 300);
        }, duration);
    }
}

// =====================================================
// STATS COUNTER
// =====================================================

class StatsCounter {
    constructor() {
        this.observer = null;
        this.init();
    }

    init() {
        const statsSection = document.querySelector('#stats');
        if (!statsSection) return;

        this.observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    this.animateCounters();
                    this.observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });

        this.observer.observe(statsSection);
    }

    animateCounters() {
        const counters = document.querySelectorAll('.stat-number');

        counters.forEach(counter => {
            const target = parseInt(counter.getAttribute('data-target'));
            const duration = 2000; // 2 seconds
            const step = target / (duration / 16); // 60fps
            let current = 0;

            counter.classList.add('counting');

            const timer = setInterval(() => {
                current += step;
                if (current >= target) {
                    current = target;
                    clearInterval(timer);
                    counter.classList.remove('counting');
                }
                counter.textContent = Math.floor(current);
            }, 16);
        });
    }
}

// =====================================================
// SKELETON LOADING
// =====================================================

class SkeletonLoader {
    constructor() {
        this.teamGrid = document.querySelector('.team-grid');
        this.init();
    }

    init() {
        if (!this.teamGrid) return;

        this.showSkeletons();
        setTimeout(() => this.hideSkeletons(), 1500);
    }

    showSkeletons() {
        // Hide real cards
        const realCards = this.teamGrid.querySelectorAll('.team-card');
        realCards.forEach(card => card.style.display = 'none');

        // Create skeleton cards
        for (let i = 0; i < 6; i++) {
            const skeletonCard = this.createSkeletonCard();
            this.teamGrid.appendChild(skeletonCard);
        }
    }

    createSkeletonCard() {
        const card = document.createElement('div');
        card.className = 'team-card skeleton-card';

        card.innerHTML = `
            <div class="team-image skeleton"></div>
            <div class="team-info">
                <div class="skeleton skeleton-text long"></div>
                <div class="skeleton skeleton-text short"></div>
            </div>
        `;

        return card;
    }

    hideSkeletons() {
        // Remove skeleton cards
        const skeletonCards = this.teamGrid.querySelectorAll('.skeleton-card');
        skeletonCards.forEach(card => card.remove());

        // Show real cards with fade-in effect
        const realCards = this.teamGrid.querySelectorAll('.team-card');
        realCards.forEach((card, index) => {
            card.style.display = 'block';
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';

            setTimeout(() => {
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });
    }
}

// =====================================================
// CARD TILT EFFECT
// =====================================================

class CardTilt {
    constructor(cardSelector) {
        this.cards = document.querySelectorAll(cardSelector);
        this.init();
    }

    init() {
        this.cards.forEach(card => {
            card.classList.add('card-tilt');
            card.addEventListener('mousemove', (e) => this.handleMouseMove(e, card));
            card.addEventListener('mouseleave', (e) => this.handleMouseLeave(e, card));
        });
    }

    handleMouseMove(e, card) {
        const rect = card.getBoundingClientRect();
        const centerX = rect.left + rect.width / 2;
        const centerY = rect.top + rect.height / 2;
        const mouseX = e.clientX - centerX;
        const mouseY = e.clientY - centerY;

        const rotateX = (mouseY / (rect.height / 2)) * -10;
        const rotateY = (mouseX / (rect.width / 2)) * 10;

        card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg)`;
    }

    handleMouseLeave(e, card) {
        card.style.transform = 'perspective(1000px) rotateX(0deg) rotateY(0deg)';
    }
}

// =====================================================
// LOCALSTORAGE MANAGEMENT
// =====================================================

class LocalStorageManager {
    constructor() {
        this.formKey = 'bit2byte-form-data';
        this.init();
    }

    init() {
        this.loadFormData();
        this.setupFormAutoSave();
    }

    // Form Data Management
    saveFormData() {
        const contactFormData = {
            name: document.getElementById('name')?.value || '',
            email: document.getElementById('email')?.value || '',
            message: document.getElementById('message')?.value || ''
        };

        const membershipFormData = {
            firstName: document.getElementById('firstName')?.value || '',
            lastName: document.getElementById('lastName')?.value || '',
            email: document.getElementById('email')?.value || '',
            phone: document.getElementById('phone')?.value || '',
            year: document.getElementById('year')?.value || '',
            department: document.getElementById('department')?.value || '',
            message: document.getElementById('message')?.value || ''
        };

        const formData = {
            contact: contactFormData,
            membership: membershipFormData
        };

        localStorage.setItem(this.formKey, JSON.stringify(formData));
    }

    loadFormData() {
        const savedData = localStorage.getItem(this.formKey);
        if (savedData) {
            try {
                const formData = JSON.parse(savedData);

                // Load contact form data
                if (formData.contact) {
                    if (document.getElementById('name')) document.getElementById('name').value = formData.contact.name;
                    if (document.getElementById('email')) document.getElementById('email').value = formData.contact.email;
                    if (document.getElementById('message')) document.getElementById('message').value = formData.contact.message;
                }

                // Load membership form data
                if (formData.membership) {
                    if (document.getElementById('firstName')) document.getElementById('firstName').value = formData.membership.firstName;
                    if (document.getElementById('lastName')) document.getElementById('lastName').value = formData.membership.lastName;
                    if (document.getElementById('phone')) document.getElementById('phone').value = formData.membership.phone;
                    if (document.getElementById('year')) document.getElementById('year').value = formData.membership.year;
                    if (document.getElementById('department')) document.getElementById('department').value = formData.membership.department;
                }
            } catch (e) {
                console.warn('Failed to load saved form data');
            }
        }
    }

    setupFormAutoSave() {
        // Contact form
        const contactForm = document.querySelector('.contact-form');
        if (contactForm) {
            const contactInputs = contactForm.querySelectorAll('input, textarea');
            contactInputs.forEach(input => {
                input.addEventListener('input', () => {
                    clearTimeout(this.saveTimeout);
                    this.saveTimeout = setTimeout(() => this.saveFormData(), 500);
                });
            });
        }

        // Membership form
        const membershipForm = document.querySelector('#membershipForm');
        if (membershipForm) {
            const membershipInputs = membershipForm.querySelectorAll('input, textarea, select');
            membershipInputs.forEach(input => {
                input.addEventListener('input', () => {
                    clearTimeout(this.saveTimeout);
                    this.saveTimeout = setTimeout(() => this.saveFormData(), 500);
                });
            });
        }
    }
}

// =====================================================
// INTERSECTION OBSERVER ANIMATIONS
// =====================================================

class ScrollAnimations {
    constructor() {
        this.observer = null;
        this.init();
    }

    init() {
        this.observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate');
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        });

        // Add animation classes to sections
        this.addAnimationClasses();
    }

    addAnimationClasses() {
        // About section
        const aboutTexts = document.querySelectorAll('.about-text');
        aboutTexts.forEach(text => {
            text.classList.add('fade-in');
            this.observer.observe(text);
        });

        // Event cards
        const eventCards = document.querySelectorAll('.event-card');
        eventCards.forEach((card, index) => {
            card.classList.add('slide-up');
            card.style.transitionDelay = `${index * 0.1}s`;
            this.observer.observe(card);
        });

        // Team cards
        const teamCards = document.querySelectorAll('.team-card');
        teamCards.forEach((card, index) => {
            card.classList.add('slide-up');
            card.style.transitionDelay = `${index * 0.1}s`;
            this.observer.observe(card);
        });

        // Stats items
        const statItems = document.querySelectorAll('.stat-item');
        statItems.forEach((item, index) => {
            item.classList.add('fade-in');
            item.style.transitionDelay = `${index * 0.1}s`;
            this.observer.observe(item);
        });

        // Contact form
        const contactForm = document.querySelector('.contact-form-wrapper');
        if (contactForm) {
            contactForm.classList.add('slide-left');
            this.observer.observe(contactForm);
        }

        // Social links
        const socialWrapper = document.querySelector('.social-wrapper');
        if (socialWrapper) {
            socialWrapper.classList.add('slide-right');
            this.observer.observe(socialWrapper);
        }
    }
}

// =====================================================
// FORM HANDLING
// =====================================================

class ContactForm {
    constructor() {
        this.form = document.querySelector('.contact-form');
        this.toast = new Toast();
        this.init();
    }

    init() {
        if (!this.form) return;

        this.form.addEventListener('submit', (e) => this.handleSubmit(e));
    }

    handleSubmit(e) {
        e.preventDefault();

        // Simulate form submission
        const formData = new FormData(this.form);
        const data = Object.fromEntries(formData);

        // Show success toast
        this.toast.show('Thank you! Your message has been sent successfully.', 'success');

        // Reset form
        this.form.reset();

        // Clear saved contact form data
        const savedData = localStorage.getItem('bit2byte-form-data');
        if (savedData) {
            try {
                const parsedData = JSON.parse(savedData);
                parsedData.contact = { name: '', email: '', message: '' };
                localStorage.setItem('bit2byte-form-data', JSON.stringify(parsedData));
            } catch (e) {
                localStorage.removeItem('bit2byte-form-data');
            }
        }
    }
}

class MembershipForm {
    constructor() {
        this.form = document.querySelector('#membershipForm');
        this.init();
    }

    init() {
        if (!this.form) return;

        this.form.addEventListener('submit', (e) => this.handleSubmit(e));
    }

    handleSubmit(e) {
        e.preventDefault();

        // Get form data
        const firstName = document.getElementById('firstName').value;
        const lastName = document.getElementById('lastName').value;
        const email = document.getElementById('email').value;

        // Show success message
        alert(`Welcome, ${firstName} ${lastName}!\n\nYour application has been submitted successfully.\nWe'll contact you at ${email} soon.`);

        // Reset form
        this.form.reset();

        // Clear saved membership form data
        const savedData = localStorage.getItem('bit2byte-form-data');
        if (savedData) {
            try {
                const parsedData = JSON.parse(savedData);
                parsedData.membership = {
                    firstName: '', lastName: '', email: '', phone: '',
                    year: '', department: '', message: ''
                };
                localStorage.setItem('bit2byte-form-data', JSON.stringify(parsedData));
            } catch (e) {
                localStorage.removeItem('bit2byte-form-data');
            }
        }
    }
}

// =====================================================
// INITIALIZATION
// =====================================================

document.addEventListener('DOMContentLoaded', () => {
    // Typewriter effect
    const heroSubtitle = document.querySelector('.hero-subtitle');
    if (heroSubtitle) {
        const typewriter = new Typewriter(heroSubtitle, 'Where Code Meets Creativity');
        typewriter.start();
    }

    // Sticky navbar
    new StickyNavbar('.navbar');

    // Stats counter
    new StatsCounter();

    // Skeleton loading
    new SkeletonLoader();

    // Card tilt effects
    new CardTilt('.event-card');
    new CardTilt('.team-card');

    // LocalStorage management
    new LocalStorageManager();

    // Scroll animations
    new ScrollAnimations();

    // Contact form handling
    new ContactForm();

    // Membership form handling
    new MembershipForm();

    // CTA button handler
    const ctaButton = document.querySelector('.cta-button');
    if (ctaButton) {
        ctaButton.addEventListener('click', () => {
            window.location.href = 'member-form.html';
        });
    }
});