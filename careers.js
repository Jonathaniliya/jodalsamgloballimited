document.addEventListener('DOMContentLoaded', function() {
  
  // Multi-step form state
  let currentStep = 1;
  const totalSteps = 4;
  
  // Get all step elements
  const stepItems = document.querySelectorAll('.step-item');
  const stepContents = document.querySelectorAll('.step-content');
  const progressLine = document.querySelector('.progress-line');
  const btnPrev = document.getElementById('btnPrev');
  const btnNext = document.getElementById('btnNext');
  const btnSubmit = document.getElementById('btnSubmit');
  
  // Form elements
  const careersForm = document.getElementById('careersForm');
  const departmentSelect = document.getElementById('department');
  const positionSelect = document.getElementById('position');
  const cvInput = document.getElementById('cvUpload');
  const fileName = document.getElementById('fileName');
  const phoneInput = document.getElementById('careerPhone');
  const emailInput = document.getElementById('careerEmail');
  
  // Position options by department
  const positionsByDepartment = {
    'Construction': ['Project Manager', 'Site Engineer', 'Supervisor', 'Site Accountant', 'Store Keeper'],
    'Oil & Gas': ['Pump Attendant'],
    'Head Office': ['Accountant']
  };
  
  // Initialize intl-tel-input for phone number
  if (phoneInput) {
    const iti = window.intlTelInput(phoneInput, {
      initialCountry: 'auto',
      geoIpLookup: function(callback) {
        fetch('https://ipapi.co/json/')
          .then(function(res) { return res.json(); })
          .then(function(data) { callback(data.country_code); })
          .catch(function() { callback('ng'); });
      },
      preferredCountries: ['ng', 'gb', 'us', 'gh', 'za'],
      separateDialCode: true,
      utilsScript: 'https://cdn.jsdelivr.net/npm/intl-tel-input@21.0.8/build/js/utils.js'
    });
    
    // Store iti instance globally for form submission
    window.careerPhoneIti = iti;
  }
  
  // Email validation
  const emailPattern = /^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/;
  
  if (emailInput) {
    emailInput.addEventListener('input', function() {
      if (emailInput.value && !emailPattern.test(emailInput.value)) {
        emailInput.classList.add('invalid');
      } else {
        emailInput.classList.remove('invalid');
      }
    });
  }
  
  // Department change handler - update position options
  if (departmentSelect && positionSelect) {
    departmentSelect.addEventListener('change', function() {
      const selectedDept = departmentSelect.value;
      
      // Clear current options
      positionSelect.innerHTML = '<option value="">Select Position</option>';
      
      // Populate with new options
      if (selectedDept && positionsByDepartment[selectedDept]) {
        positionsByDepartment[selectedDept].forEach(function(pos) {
          const option = document.createElement('option');
          option.value = pos;
          option.textContent = pos;
          positionSelect.appendChild(option);
        });
        positionSelect.disabled = false;
      } else {
        positionSelect.disabled = true;
      }
      
      // Reset position value
      positionSelect.value = '';
    });
  }
  
  // File upload handler
  if (cvInput && fileName) {
    cvInput.addEventListener('change', function() {
      if (cvInput.files.length > 0) {
        const file = cvInput.files[0];
        const fileSize = file.size / 1024 / 1024; // Size in MB
        const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        
        if (!allowedTypes.includes(file.type)) {
          alert('Please upload a PDF or Word document (DOC/DOCX)');
          cvInput.value = '';
          fileName.classList.remove('visible');
          return;
        }
        
        if (fileSize > 5) {
          alert('File size must be less than 5MB');
          cvInput.value = '';
          fileName.classList.remove('visible');
          return;
        }
        
        fileName.textContent = '📄 ' + file.name;
        fileName.classList.add('visible');
      } else {
        fileName.classList.remove('visible');
      }
    });
  }
  
  // Show specific step
  function showStep(step) {
    // Hide all steps
    stepContents.forEach(function(content) {
      content.classList.remove('active');
    });
    
    // Show current step
    const currentContent = document.getElementById('step' + step);
    if (currentContent) {
      currentContent.classList.add('active');
    }
    
    // Update step indicators
    stepItems.forEach(function(item, index) {
      const stepNum = index + 1;
      if (stepNum < step) {
        item.classList.add('completed');
        item.classList.remove('active');
      } else if (stepNum === step) {
        item.classList.add('active');
        item.classList.remove('completed');
      } else {
        item.classList.remove('active', 'completed');
      }
    });
    
    // Update progress line
    const progressPercent = ((step - 1) / (totalSteps - 1)) * 100;
    if (progressLine) {
      progressLine.style.width = progressPercent + '%';
    }
    
    // Update buttons
    if (btnPrev) {
      btnPrev.disabled = (step === 1);
    }
    
    if (btnNext) {
      btnNext.style.display = (step === totalSteps) ? 'none' : 'block';
    }
    
    if (btnSubmit) {
      btnSubmit.style.display = (step === totalSteps) ? 'block' : 'none';
    }
    
    // If on review step, populate review
    if (step === 4) {
      populateReview();
    }
  }
  
  // Validate current step
  function validateStep(step) {
    let isValid = true;
    const currentContent = document.getElementById('step' + step);
    
    if (!currentContent) return false;
    
    const requiredInputs = currentContent.querySelectorAll('[required]');
    
    requiredInputs.forEach(function(input) {
      if (input.type === 'email') {
        if (!input.value || !emailPattern.test(input.value)) {
          input.classList.add('invalid');
          isValid = false;
        } else {
          input.classList.remove('invalid');
        }
      } else if (!input.value.trim()) {
        input.classList.add('invalid');
        isValid = false;
        
        // Add red border temporarily
        input.style.borderColor = '#e74c3c';
        setTimeout(function() {
          input.style.borderColor = '';
        }, 2000);
      } else {
        input.classList.remove('invalid');
      }
    });
    
    return isValid;
  }
  
  // Populate review summary
  function populateReview() {
    // Personal Information
    document.getElementById('reviewFullName').textContent = document.getElementById('fullName').value || '-';
    document.getElementById('reviewEmail').textContent = document.getElementById('careerEmail').value || '-';
    
    // Get full phone number with country code
    let phoneValue = '-';
    if (window.careerPhoneIti && phoneInput.value) {
      phoneValue = window.careerPhoneIti.getNumber() || phoneInput.value;
    }
    document.getElementById('reviewPhone').textContent = phoneValue;
    
    document.getElementById('reviewLocation').textContent = document.getElementById('location').value || '-';
    
    // Professional Information
    document.getElementById('reviewDepartment').textContent = document.getElementById('department').value || '-';
    document.getElementById('reviewPosition').textContent = document.getElementById('position').value || '-';
    document.getElementById('reviewExperience').textContent = document.getElementById('experience').value || '-';
    document.getElementById('reviewQualification').textContent = document.getElementById('qualification').value || '-';
    
    // Documents
    const cvFile = document.getElementById('cvUpload').files[0];
    document.getElementById('reviewCV').textContent = cvFile ? cvFile.name : '-';
    document.getElementById('reviewLinkedIn').textContent = document.getElementById('linkedIn').value || '-';
    
    const coverLetter = document.getElementById('coverLetter').value;
    document.getElementById('reviewCoverLetter').textContent = coverLetter || '-';
  }
  
  // Next button handler
  if (btnNext) {
    btnNext.addEventListener('click', function() {
      if (validateStep(currentStep)) {
        currentStep++;
        if (currentStep > totalSteps) currentStep = totalSteps;
        showStep(currentStep);
        window.scrollTo({ top: 0, behavior: 'smooth' });
      } else {
        alert('Please fill in all required fields before proceeding.');
      }
    });
  }
  
  // Previous button handler
  if (btnPrev) {
    btnPrev.addEventListener('click', function() {
      currentStep--;
      if (currentStep < 1) currentStep = 1;
      showStep(currentStep);
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }
  
  // Form submission handler
  if (careersForm) {
    careersForm.addEventListener('submit', function(e) {
      e.preventDefault();
      
      if (!validateStep(4)) {
        alert('Please ensure all required information is provided.');
        return;
      }
      
      // Disable submit button
      btnSubmit.textContent = 'Submitting...';
      btnSubmit.disabled = true;
      
      // Prepare form data
      const formData = new FormData(careersForm);
      
      // Add full phone number with country code
      if (window.careerPhoneIti) {
        const fullPhone = window.careerPhoneIti.getNumber();
        formData.set('phone', fullPhone);
      }
      
      // Submit via AJAX
      fetch('send-career-mail.php', {
        method: 'POST',
        body: formData
      })
      .then(function(response) {
        return response.text();
      })
      .then(function(text) {
        let data;
        try {
          data = JSON.parse(text);
        } catch(e) {
          data = null;
        }
        
        if (data && data.ok) {
          // Success
          showFormMessage('success', 'Application submitted successfully! We will review your application and contact you soon.');
          careersForm.reset();
          fileName.classList.remove('visible');
          
          // Reset to step 1 after 3 seconds
          setTimeout(function() {
            currentStep = 1;
            showStep(currentStep);
            hideFormMessage();
            btnSubmit.textContent = 'Submit Application';
            btnSubmit.disabled = false;
          }, 3000);
        } else {
          // Error
          const errorMsg = (data && data.error) ? data.error : 'An error occurred. Please try again.';
          showFormMessage('error', errorMsg);
          btnSubmit.textContent = 'Submit Application';
          btnSubmit.disabled = false;
        }
      })
      .catch(function(err) {
        console.error('Submit error:', err);
        showFormMessage('error', 'Network error. Please check your connection and try again.');
        btnSubmit.textContent = 'Submit Application';
        btnSubmit.disabled = false;
      });
    });
  }
  
  // Show form message
  function showFormMessage(type, message) {
    const messageEl = document.getElementById('formMessage');
    if (messageEl) {
      messageEl.className = 'form-message ' + type + ' visible';
      messageEl.textContent = message;
      messageEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
  }
  
  // Hide form message
  function hideFormMessage() {
    const messageEl = document.getElementById('formMessage');
    if (messageEl) {
      messageEl.classList.remove('visible');
    }
  }
  
  // Initialize - show first step
  showStep(currentStep);
  
});
