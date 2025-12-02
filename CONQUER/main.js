// DOM Ready
$(document).ready(function() {
    // Load membership plans from backend
    loadMembershipPlans();
    
    // Modal triggers
    $('#signupBtn').click(function(e) {
        e.preventDefault();
        $('#signupModal').modal('show');
    });
    
    $('#loginBtn').click(function(e) {
        e.preventDefault();
        $('#loginModal').modal('show');
    });
    
    // Signup form submission
    $('#signupForm').submit(function(e) {
        e.preventDefault();
        registerMember();
    });
});

// Load membership plans from backend
function loadMembershipPlans() {
    $.ajax({
        url: 'http://localhost:3000/api/memberships',
        method: 'GET',
        success: function(plans) {
            displayMembershipPlans(plans);
        },
        error: function(error) {
            console.error('Error loading plans:', error);
            // Fallback data if backend is down
            displayMembershipPlans([
                { name: 'Basic', price: 29, features: ['Gym Access', 'Locker', 'Free Wi-Fi'] },
                { name: 'Pro', price: 49, features: ['All Basic', 'Group Classes', 'Personal Trainer 1x'], popular: true },
                { name: 'Elite', price: 79, features: ['All Pro', 'Unlimited Classes', '24/7 Access', 'Nutrition Plan'] }
            ]);
        }
    });
}

// Display membership plans
function displayMembershipPlans(plans) {
    const container = $('#membershipPlans');
    container.empty();
    
    plans.forEach((plan, index) => {
        const cardClass = plan.popular ? 'membership-card popular' : 'membership-card';
        
        const featuresHTML = plan.features.map(feature => 
            `<li class="list-group-item"><i class="fas fa-check text-success"></i> ${feature}</li>`
        ).join('');
        
        const cardHTML = `
            <div class="col-md-4">
                <div class="card ${cardClass} h-100">
                    <div class="card-header text-center">
                        <h4>${plan.name}</h4>
                        <div class="price">$${plan.price}<small>/month</small></div>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            ${featuresHTML}
                        </ul>
                    </div>
                    <div class="card-footer text-center">
                        <button class="btn btn-primary btn-lg select-plan" data-plan="${plan.name}">
                            Select Plan
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        container.append(cardHTML);
    });
    
    // Add click event to plan selection buttons
    $('.select-plan').click(function() {
        const plan = $(this).data('plan');
        $('#signupModal').modal('show');
        $('#membershipType').val(plan.toLowerCase());
    });
}

// Register new member
function registerMember() {
    const memberData = {
        name: $('#fullName').val(),
        email: $('#email').val(),
        password: $('#password').val(),
        membershipType: $('#membershipType').val()
    };
    
    $.ajax({
        url: 'http://localhost:3000/api/auth/register',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(memberData),
        success: function(response) {
            $('#signupMessage').html(`
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> 
                    Registration successful! Welcome to Conquer Gym.
                </div>
            `);
            
            // Clear form and close modal after 2 seconds
            setTimeout(() => {
                $('#signupForm')[0].reset();
                $('#signupModal').modal('hide');
                $('#signupMessage').empty();
            }, 2000);
        },
        error: function(error) {
            $('#signupMessage').html(`
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> 
                    ${error.responseJSON?.message || 'Registration failed. Please try again.'}
                </div>
            `);
        }
    });
}