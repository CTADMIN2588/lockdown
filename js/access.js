// Vicidial Secure Access: small page behaviours. No dependencies; ES5 so old browsers run it.
(function () {
  'use strict';

  // Show / hide the password.
  var reveal = document.getElementById('reveal');
  var password = document.getElementById('password');
  if (reveal && password) {
    reveal.addEventListener('click', function () {
      var show = password.type === 'password';
      password.type = show ? 'text' : 'password';
      reveal.textContent = show ? 'Hide' : 'Show';
      reveal.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    });
  }

  // Stop a double submit while the firewall is being updated.
  var form = document.getElementById('login');
  if (form) {
    form.addEventListener('submit', function () {
      var btn = form.querySelector('.btn');
      if (btn) {
        setTimeout(function () { btn.disabled = true; }, 0);
      }
    });
  }

  // Access granted: count down, then open Vicidial. (A meta refresh does the same without JS.)
  var redirect = document.getElementById('redirect');
  if (redirect) {
    var left = parseInt(redirect.getAttribute('data-seconds'), 10) || 3;
    var url = redirect.getAttribute('data-url');
    var timer = setInterval(function () {
      left -= 1;
      if (left <= 0) {
        clearInterval(timer);
        redirect.textContent = '0';
        window.location.replace(url);
        return;
      }
      redirect.textContent = String(left);
    }, 1000);
  }

  // Locked out: tick the wait time down, then reload so the form works again.
  var lock = document.getElementById('lock');
  if (lock) {
    var wait = parseInt(lock.getAttribute('data-seconds'), 10) || 0;
    var tick = setInterval(function () {
      wait -= 1;
      if (wait <= 0) {
        clearInterval(tick);
        window.location.reload();
        return;
      }
      var s = wait % 60;
      lock.textContent = Math.floor(wait / 60) + ':' + (s < 10 ? '0' : '') + s;
    }, 1000);
  }
})();
