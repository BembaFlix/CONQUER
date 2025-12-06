// main.js - Complete with all fixes
// Theme Toggle
const themeSwitch = document.getElementById('theme-switch');
const htmlElement = document.documentElement;

// Check for saved theme preference
const savedTheme = localStorage.getItem('theme') || 'light';
if (savedTheme === 'dark') {
    themeSwitch.checked = true;
    htmlElement.setAttribute('data-theme', 'dark');
}

themeSwitch.addEventListener('change', function() {
    if (this.checked) {
        htmlElement.setAttribute('data-theme', 'dark');
        localStorage.setItem('theme', 'dark');
    } else {
        htmlElement.setAttribute('data-theme', 'light');
        localStorage.setItem('theme', 'light');
    }
});

// Mobile Navigation Toggle
const navToggle = document.querySelector('.nav-toggle');
const navMenu = document.querySelector('.nav-menu');

if (navToggle && navMenu) {
    navToggle.addEventListener('click', () => {
        navMenu.classList.toggle('active');
        navToggle.classList.toggle('active');
    });
}

// Close mobile menu when clicking on a link
document.querySelectorAll('.nav-link').forEach(link => {
    link.addEventListener('click', () => {
        if (navMenu) navMenu.classList.remove('active');
        if (navToggle) navToggle.classList.remove('active');
    });
});

// Smooth scrolling for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        e.preventDefault();
        const targetId = this.getAttribute('href');
        if (targetId === '#') return;
        
        const targetElement = document.querySelector(targetId);
        if (targetElement) {
            window.scrollTo({
                top: targetElement.offsetTop - 80,
                behavior: 'smooth'
            });
        }
    });
});

// Animate counter numbers
function animateCounter(element, target) {
    let current = 0;
    const increment = target / 100;
    const timer = setInterval(() => {
        current += increment;
        if (current >= target) {
            element.textContent = target;
            clearInterval(timer);
        } else {
            element.textContent = Math.floor(current);
        }
    }, 20);
}

// Initialize counters when in viewport
const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const counters = entry.target.querySelectorAll('[data-count]');
            counters.forEach(counter => {
                const target = parseInt(counter.getAttribute('data-count'));
                animateCounter(counter, target);
            });
        }
    });
}, { threshold: 0.5 });

// Observe hero stats
const heroStats = document.querySelector('.hero-stats');
if (heroStats) observer.observe(heroStats);

// Back to Top Button
const backToTop = document.querySelector('.back-to-top');

if (backToTop) {
    window.addEventListener('scroll', () => {
        if (window.pageYOffset > 300) {
            backToTop.classList.add('visible');
        } else {
            backToTop.classList.remove('visible');
        }
    });

    backToTop.addEventListener('click', () => {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
}

// Modal Functions
let currentStep = 1;
let selectedPlan = '';

function openMembershipModal() {
    const modal = document.getElementById('membershipModal');
    if (modal) {
        modal.style.display = 'flex';
        resetForm();
    }
}

function closeModal() {
    const modal = document.getElementById('membershipModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

function selectPlan(plan) {
    selectedPlan = plan;
    openMembershipModal();
}

function selectPlanOption(plan) {
    selectedPlan = plan;
    document.querySelectorAll('.plan-option').forEach(option => {
        option.classList.remove('selected');
    });
    event.currentTarget.classList.add('selected');
}

function nextStep() {
    if (currentStep < 3) {
        document.getElementById(`step${currentStep}`).classList.remove('active');
        currentStep++;
        document.getElementById(`step${currentStep}`).classList.add('active');
        updateProgressBar();
    }
}

function prevStep() {
    if (currentStep > 1) {
        document.getElementById(`step${currentStep}`).classList.remove('active');
        currentStep--;
        document.getElementById(`step${currentStep}`).classList.add('active');
        updateProgressBar();
    }
}

function updateProgressBar() {
    document.querySelectorAll('.progress-step').forEach(step => {
        const stepNum = parseInt(step.getAttribute('data-step'));
        if (stepNum <= currentStep) {
            step.classList.add('active');
        } else {
            step.classList.remove('active');
        }
    });
}

function resetForm() {
    currentStep = 1;
    selectedPlan = '';
    document.querySelectorAll('.form-step').forEach(step => {
        step.classList.remove('active');
    });
    const step1 = document.getElementById('step1');
    if (step1) step1.classList.add('active');
    document.querySelectorAll('.plan-option').forEach(option => {
        option.classList.remove('selected');
    });
    document.querySelectorAll('.progress-step').forEach(step => {
        step.classList.remove('active');
    });
    const firstProgressStep = document.querySelector('.progress-step[data-step="1"]');
    if (firstProgressStep) firstProgressStep.classList.add('active');
    const registrationForm = document.getElementById('registrationForm');
    if (registrationForm) registrationForm.reset();
}

// Form Submission
const registrationForm = document.getElementById('registrationForm');
if (registrationForm) {
    registrationForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Get form data
        const formData = new FormData(this);
        const data = {
            plan: selectedPlan,
            name: formData.get('name'),
            email: formData.get('email'),
            phone: formData.get('phone')
        };
        
        // Simulate API call
        setTimeout(() => {
            alert(`Registration successful!\nWelcome to Conquer Gym!\nPlan: ${selectedPlan}\nName: ${data.name}`);
            closeModal();
            resetForm();
        }, 1000);
    });
}

// Close modal when clicking outside
const membershipModal = document.getElementById('membershipModal');
if (membershipModal) {
    membershipModal.addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });
}

// Initialize AOS
if (typeof AOS !== 'undefined') {
    AOS.init({
        duration: 1000,
        once: true,
        offset: 100
    });
}

// Hide loading screen
window.addEventListener('load', function() {
    const loadingScreen = document.querySelector('.loading-screen');
    if (loadingScreen) {
        setTimeout(() => {
            loadingScreen.style.opacity = '0';
            setTimeout(() => {
                loadingScreen.style.display = 'none';
            }, 500);
        }, 1000);
    }
});

// Navigation scroll effect - FIXED for dark mode
window.addEventListener('scroll', () => {
    const navbar = document.querySelector('.navbar');
    if (!navbar) return;
    
    const isDarkMode = htmlElement.getAttribute('data-theme') === 'dark';
    
    if (window.scrollY > 100) {
        if (isDarkMode) {
            navbar.style.background = 'rgba(30, 39, 46, 0.98)';
        } else {
            navbar.style.background = 'rgba(255, 255, 255, 0.98)';
        }
        navbar.style.boxShadow = 'var(--shadow-md)';
    } else {
        if (isDarkMode) {
            navbar.style.background = 'rgba(30, 39, 46, 0.95)';
        } else {
            navbar.style.background = 'rgba(255, 255, 255, 0.95)';
        }
        navbar.style.boxShadow = 'var(--shadow-sm)';
    }
});

// Dark mode adjustments for navbar
if (themeSwitch) {
    themeSwitch.addEventListener('change', function() {
        const navbar = document.querySelector('.navbar');
        if (!navbar) return;
        
        if (this.checked) {
            navbar.style.background = 'rgba(30, 39, 46, 0.98)';
        } else {
            navbar.style.background = 'rgba(255, 255, 255, 0.98)';
        }
    });
}

// Login button functionality
const loginBtn = document.querySelector('.btn-login:not([onclick])');
if (loginBtn) {
    loginBtn.addEventListener('click', function() {
        alert('Login functionality would open here!');
    });
}

// Join Now button functionality
const joinBtn = document.querySelector('.btn-join');
if (joinBtn) {
    joinBtn.addEventListener('click', function() {
        openMembershipModal();
    });
}