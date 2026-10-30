/* PPH 5.16.0 - Free Sample: optional email pop-up, then download.
   Step 1: ask for an email (optional). Step 2: Download button.
   Skip, X, Cancel, Esc and a click outside all lead to step 2 without saving. */
(function () {
	'use strict';

	var btn = document.querySelector('[data-pph-sample="1"]');
	if (!btn) { return; }

	var downloadUrl = btn.getAttribute('href');
	var productId   = btn.getAttribute('data-product-id') || '';
	var title       = btn.getAttribute('data-product-title') || 'Free Sample';
	var modal, lastFocus, step = 1;

	function esc(s) {
		return String(s).replace(/[&<>"']/g, function (c) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
		});
	}

	function build() {
		modal = document.createElement('div');
		modal.className = 'pph-lead';
		modal.setAttribute('role', 'dialog');
		modal.setAttribute('aria-modal', 'true');
		modal.setAttribute('aria-labelledby', 'pph-lead-h');
		modal.hidden = true;
		modal.innerHTML =
			'<div class="pph-lead-box">' +
			'<button type="button" class="pph-lead-x" data-act="skip" aria-label="Close">&times;</button>' +

			/* ---- step 1: optional email ---- */
			'<div class="pph-lead-s1">' +
			'<h3 id="pph-lead-h">Get your free sample</h3>' +
			'<p class="pph-lead-sub">' + esc(title) + '</p>' +
			'<p>Share your email and we will let you know about new books and updates. ' +
			'This is completely optional.</p>' +
			'<form class="pph-lead-form" novalidate>' +
			'<input type="email" name="email" placeholder="you@example.com" autocomplete="email" aria-label="Email address">' +
			'<input type="text" name="pph_website" tabindex="-1" autocomplete="off" aria-hidden="true" class="pph-lead-hp">' +
			'<div class="pph-lead-err" role="alert" hidden></div>' +
			'<button type="submit" class="pph-lead-go">Save email &amp; continue</button>' +
			'</form>' +
			'<div class="pph-lead-alt">' +
			'<button type="button" class="pph-lead-link" data-act="skip">Skip, no thanks</button>' +
			'<button type="button" class="pph-lead-link" data-act="skip">Cancel</button>' +
			'</div>' +
			'</div>' +

			/* ---- step 2: download ---- */
			'<div class="pph-lead-s2" hidden>' +
			'<h3>Your sample is ready</h3>' +
			'<p class="pph-lead-sub">' + esc(title) + '</p>' +
			'<p class="pph-lead-thanks" hidden>Thank you! Your email has been saved.</p>' +
			'<a class="pph-lead-dl" href="' + esc(downloadUrl) + '" download>&#8595; Download Free Sample</a>' +
			'<button type="button" class="pph-lead-link" data-act="close">Close</button>' +
			'</div>' +
			'</div>';

		document.body.appendChild(modal);

		modal.addEventListener('click', function (e) {
			if (e.target === modal) { go2(false); return; }
			var act = e.target.getAttribute && e.target.getAttribute('data-act');
			if (act === 'skip')  { go2(false); }
			if (act === 'close') { close(); }
		});

		modal.querySelector('.pph-lead-form').addEventListener('submit', submit);

		/* The real download: close the pop-up shortly after the browser starts it. */
		modal.querySelector('.pph-lead-dl').addEventListener('click', function () {
			setTimeout(close, 700);
		});

		document.addEventListener('keydown', function (e) {
			if (modal.hidden) { return; }
			if (e.key === 'Escape') { step === 1 ? go2(false) : close(); }
		});
	}

	function show1() {
		step = 1;
		modal.querySelector('.pph-lead-s1').hidden = false;
		modal.querySelector('.pph-lead-s2').hidden = true;
		modal.hidden = false;
		document.documentElement.classList.add('pph-lead-open');
		var inp = modal.querySelector('input[type=email]');
		if (inp) { setTimeout(function () { inp.focus(); }, 30); }
	}

	function go2(saved) {
		step = 2;
		modal.querySelector('.pph-lead-s1').hidden = true;
		modal.querySelector('.pph-lead-s2').hidden = false;
		modal.querySelector('.pph-lead-thanks').hidden = !saved;
		modal.hidden = false;
		var dl = modal.querySelector('.pph-lead-dl');
		if (dl) { dl.focus(); }
	}

	function close() {
		modal.hidden = true;
		document.documentElement.classList.remove('pph-lead-open');
		if (lastFocus && lastFocus.focus) { lastFocus.focus(); }
	}

	function submit(e) {
		e.preventDefault();
		var form = e.target;
		var err  = form.querySelector('.pph-lead-err');
		var go   = form.querySelector('.pph-lead-go');
		var val  = (form.email.value || '').trim();

		err.hidden = true;

		/* An empty box is the same as Skip. */
		if (val === '') { go2(false); return; }

		if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(val)) {
			err.textContent = 'Please enter a valid email address, or choose Skip.';
			err.hidden = false;
			return;
		}

		go.disabled = true;
		go.textContent = 'Saving...';

		var body = new URLSearchParams();
		body.append('action', 'pph_sample_lead');
		body.append('email', val);
		body.append('product_id', productId);
		body.append('source_url', window.location.href);
		body.append('pph_website', form.pph_website.value || '');

		fetch((window.PPH_LEAD && PPH_LEAD.ajax) || '/wp-admin/admin-ajax.php', {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: body.toString()
		})
			.then(function (r) { return r.json(); })
			.then(function (j) {
				go.disabled = false;
				go.innerHTML = 'Save email &amp; continue';
				if (j && j.success) { go2(true); return; }
				err.textContent = (j && j.data && j.data.message) || 'Could not save. You can still choose Skip.';
				err.hidden = false;
			})
			.catch(function () {
				/* A network problem must never block the download. */
				go.disabled = false;
				go.innerHTML = 'Save email &amp; continue';
				err.textContent = 'Could not save right now. You can still choose Skip to download.';
				err.hidden = false;
			});
	}

	btn.addEventListener('click', function (e) {
		e.preventDefault();
		lastFocus = btn;
		if (!modal) { build(); }
		show1();
	});
})();
