document.addEventListener('DOMContentLoaded', function() {

  var navbar = document.getElementById('navbar');
  var hamburger = document.getElementById('hamburger');
  var navLinks = document.getElementById('navLinks');

  window.addEventListener('scroll', function() {
    if (window.scrollY > 50) {
      navbar.classList.add('scrolled');
    } else {
      navbar.classList.remove('scrolled');
    }
  });

  hamburger.addEventListener('click', function() {
    hamburger.classList.toggle('active');
    navLinks.classList.toggle('active');
  });

  var navAnchors = document.querySelectorAll('.nav-links a');
  navAnchors.forEach(function(anchor) {
    anchor.addEventListener('click', function() {
      hamburger.classList.remove('active');
      navLinks.classList.remove('active');
    });
  });

  var revealElements = document.querySelectorAll('.reveal, .reveal-left, .reveal-right');

  var observer = new IntersectionObserver(function(entries) {
    entries.forEach(function(entry) {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');
        observer.unobserve(entry.target);
      }
    });
  }, {
    threshold: 0.15,
    rootMargin: '0px 0px -50px 0px'
  });

  revealElements.forEach(function(el) {
    observer.observe(el);
  });

  function animateCounters() {
    var counters = document.querySelectorAll('.stat-number');
    counters.forEach(function(counter) {
      var target = parseInt(counter.getAttribute('data-target'));
      var duration = 2000;
      var start = 0;
      var startTime = null;

      function updateCounter(timestamp) {
        if (!startTime) startTime = timestamp;
        var progress = Math.min((timestamp - startTime) / duration, 1);
        var eased = 1 - Math.pow(1 - progress, 3);
        counter.textContent = Math.floor(eased * target);
        if (progress < 1) {
          requestAnimationFrame(updateCounter);
        } else {
          counter.textContent = target;
        }
      }

      requestAnimationFrame(updateCounter);
    });
  }

  var statsSection = document.querySelector('.stats-bar');
  var statsObserver = new IntersectionObserver(function(entries) {
    entries.forEach(function(entry) {
      if (entry.isIntersecting) {
        animateCounters();
        statsObserver.unobserve(entry.target);
      }
    });
  }, { threshold: 0.3 });

  statsObserver.observe(statsSection);

  var phoneInput = document.getElementById('phoneInput');
  var iti = window.intlTelInput(phoneInput, {
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

  var emailInput = document.getElementById('emailInput');
  var emailError = document.querySelector('.email-error');
  var emailPattern = /^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/;

  emailInput.addEventListener('input', function() {
    if (emailInput.value && !emailPattern.test(emailInput.value)) {
      emailInput.classList.add('invalid');
      emailError.classList.add('visible');
    } else {
      emailInput.classList.remove('invalid');
      emailError.classList.remove('visible');
    }
  });

  emailInput.addEventListener('blur', function() {
    if (emailInput.value && !emailPattern.test(emailInput.value)) {
      emailInput.classList.add('invalid');
      emailError.classList.add('visible');
    }
  });

  var contactForm = document.getElementById('contactForm');
  contactForm.addEventListener('submit', function(e) {
    e.preventDefault();

    if (!emailPattern.test(emailInput.value)) {
      emailInput.classList.add('invalid');
      emailError.classList.add('visible');
      emailInput.focus();
      return;
    }

    var submitBtn = contactForm.querySelector('button[type="submit"]');
    submitBtn.textContent = 'Sending...';
    submitBtn.disabled = true;

    var formData = new FormData(contactForm);
    var fullPhone = iti.getNumber();
    formData.set('phone', fullPhone);

    fetch('send-mail.php', {
      method: 'POST',
      body: formData
    })
    .then(function(response) { return response.text(); })
    .then(function(text) {
      var data;
      try {
        data = JSON.parse(text);
      } catch(e) {
        data = null;
      }

      if (data && data.ok) {
        submitBtn.textContent = 'Message Sent!';
        submitBtn.style.background = '#27ae60';
        contactForm.reset();
        emailInput.classList.remove('invalid');
        emailError.classList.remove('visible');
        setTimeout(function() {
          submitBtn.textContent = 'Send Message';
          submitBtn.style.background = '';
          submitBtn.disabled = false;
        }, 3000);
      } else {
        var errorMsg = (data && data.error) ? data.error : text;
        console.log('Mail error:', errorMsg);
        submitBtn.textContent = 'Failed - Try Again';
        submitBtn.title = errorMsg;
        submitBtn.style.background = '#e74c3c';
        submitBtn.disabled = false;
        setTimeout(function() {
          submitBtn.textContent = 'Send Message';
          submitBtn.style.background = '';
          submitBtn.title = '';
        }, 5000);
      }
    })
    .catch(function(err) {
      console.log('Fetch error:', err);
      submitBtn.textContent = 'Failed - Try Again';
      submitBtn.style.background = '#e74c3c';
      submitBtn.disabled = false;
      setTimeout(function() {
        submitBtn.textContent = 'Send Message';
        submitBtn.style.background = '';
      }, 3000);
    });
  });

  document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
    anchor.addEventListener('click', function(e) {
      e.preventDefault();
      var targetId = this.getAttribute('href');
      var targetEl = document.querySelector(targetId);
      if (targetEl) {
        targetEl.scrollIntoView({ behavior: 'smooth' });
      }
    });
  });

});