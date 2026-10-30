/* =========================================================================
   PPH Product Page  —  v5.0
   - Review textarea placeholder            (from 3.8)
   - Format card switching (digital <-> print)
   - Bridges format cards to WooCommerce variation inputs
   - Tabs
   - Sample-pages thumbnail rail + lightbox
   ========================================================================= */
(function () {
	'use strict';

	function ready(fn) {
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', fn);
		} else {
			fn();
		}
	}

	/* ---------------------------------------------------------------- 3.8 */
	function setPlaceholder() {
		var c = document.getElementById('comment');
		if (c && !c.getAttribute('placeholder')) {
			c.setAttribute('placeholder', 'Type your review here\u2026');
		}
	}

	/* ------------------------------------------------- variation bridging */

	/**
	 * Tries to select `value` inside WooCommerce's variation UI.
	 * Handles both the plain <select> and the swatch <li> plugins.
	 */
	function selectVariation(value) {
		if (!value) { return false; }
		var wrap = document.querySelector('.pph-pp form.cart .variations');
		if (!wrap) { return false; }

		var want = value.trim().toLowerCase();
		var done = false;

		/* 1. Native selects */
		Array.prototype.forEach.call(wrap.querySelectorAll('select'), function (sel) {
			if (done) { return; }
			Array.prototype.forEach.call(sel.options, function (opt) {
				if (done) { return; }
				var ov = (opt.value || '').trim().toLowerCase();
				var ot = (opt.textContent || '').trim().toLowerCase();
				if (ov === want || ot === want) {
					sel.value = opt.value;
					sel.dispatchEvent(new Event('change', { bubbles: true }));
					done = true;
				}
			});
		});
		if (done) { return true; }

		/* 2. Swatch / button style variation plugins */
		var items = wrap.querySelectorAll('li.variable-item, .variable-item');
		Array.prototype.forEach.call(items, function (li) {
			if (done) { return; }
			var v = (li.getAttribute('data-value') || li.textContent || '').trim().toLowerCase();
			if (v === want) {
				li.click();
				done = true;
			}
		});
		return done;
	}

	/* ------------------------------------------------------- format cards */

	function initFormats() {
		var cards = document.querySelectorAll('.pph-fmt:not(.pph-free-sample-card)');
		if (!cards.length) { return; }

		function activate(card) {
			var key  = card.getAttribute('data-key');
			var type = card.getAttribute('data-type');
			var attr = card.getAttribute('data-attr') || '';

			Array.prototype.forEach.call(cards, function (c) {
				var on = (c === card);
				c.classList.toggle('is-on', on);
				c.setAttribute('aria-checked', on ? 'true' : 'false');
			});

			var sampleCard = document.querySelector('.pph-free-sample-card');
			if (sampleCard) {
				sampleCard.classList.remove('is-on');
				sampleCard.setAttribute('aria-checked', 'false');
			}

			/* Show the matching action panel, hide the rest */
			Array.prototype.forEach.call(document.querySelectorAll('.pph-act'), function (p) {
				p.hidden = (p.getAttribute('data-for') !== key);
			});

			/* 5.9.0: the Details tab follows the chosen format, so the paperback
			   shows its own ISBN and the hardcover shows a different one. */
			Array.prototype.forEach.call(document.querySelectorAll('.pph-dl-fmt'), function (b) {
				b.hidden = (b.getAttribute('data-for') !== key);
			});

			/* Digital -> also move WooCommerce's own variation picker */
			if (type === 'digital') {
				selectVariation(attr || card.getAttribute('data-label'));
			}
		}

		Array.prototype.forEach.call(cards, function (card) {
			card.addEventListener('click', function () { activate(card); });
			card.addEventListener('keydown', function (e) {
				if (e.key === 'Enter' || e.key === ' ') {
					e.preventDefault();
					activate(card);
				}
			});
		});

		/* Free Sample is visually part of the format row but is not a paid format. */
		var sampleCard = document.querySelector('.pph-free-sample-card');
		if (sampleCard) {
			function activateSample() {
				Array.prototype.forEach.call(cards, function (c) {
					c.classList.remove('is-on');
					c.setAttribute('aria-checked', 'false');
				});
				sampleCard.classList.add('is-on');
				sampleCard.setAttribute('aria-checked', 'true');
				Array.prototype.forEach.call(document.querySelectorAll('.pph-act'), function (p) {
					p.hidden = (p.getAttribute('data-for') !== 'free-sample');
				});
				Array.prototype.forEach.call(document.querySelectorAll('.pph-dl-fmt'), function (b) { b.hidden = true; });
			}
			sampleCard.addEventListener('click', activateSample);
			sampleCard.addEventListener('keydown', function (e) {
				if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); activateSample(); }
			});
		}

		/* Open on the first digital format if there is one, else the first card */
		var first = document.querySelector('.pph-fmt[data-type="digital"]:not(.pph-free-sample-card)') || cards[0];
		if (first) { activate(first); }
	}

	/* ---------------------------------------------------------------- tabs */

	function initTabs() {
		var tabs = document.querySelectorAll('.pph-tab');
		if (!tabs.length) { return; }

		function show(name) {
			Array.prototype.forEach.call(tabs, function (t) {
				var on = t.getAttribute('data-tab') === name;
				t.classList.toggle('is-on', on);
				t.setAttribute('aria-selected', on ? 'true' : 'false');
			});
			Array.prototype.forEach.call(document.querySelectorAll('.pph-panel'), function (p) {
				p.hidden = (p.getAttribute('data-panel') !== name);
			});
		}

		Array.prototype.forEach.call(tabs, function (t) {
			t.addEventListener('click', function () { show(t.getAttribute('data-tab')); });
		});

		show(tabs[0].getAttribute('data-tab'));
	}

	/* -------------------------------------------------------- cover / rail */

	function initGallery() {
		var main = document.querySelector('.pph-pp-cover-img');
		var rail = document.querySelectorAll('.pph-thumb');
		if (!main || !rail.length) { return; }

		Array.prototype.forEach.call(rail, function (t) {
			t.addEventListener('click', function () {
				var full = t.getAttribute('data-full');
				if (!full) { return; }
				main.setAttribute('src', full);
				main.removeAttribute('srcset');
				Array.prototype.forEach.call(rail, function (o) {
					o.classList.toggle('is-on', o === t);
				});
			});
		});
	}

	/* ------------------------------------------------------------ lightbox */

	function initLightbox() {

		var triggers = document.querySelectorAll('[data-pph-zoom]');
		if (!triggers.length) { return; }

		/* Group the triggers so the arrows walk through the images that belong
		   together. The side rail and the cover are one group, the Sample Pages
		   tab is another. A trigger with no group stands on its own. */
		var groups = {};
		var loose  = 0;

		/* 5.11.0: how each picture should be framed in the large view.
		   front = front cover, back = back cover, spread = two open pages. */
		var kinds = {};

		Array.prototype.forEach.call(triggers, function (t) {
			var name = t.getAttribute('data-pph-group');
			if (!name) {
				loose += 1;
				name = 'single-' + loose;
				t.setAttribute('data-pph-group', name);
			}
			if (!groups[name]) { groups[name] = []; }

			var src = t.getAttribute('data-pph-zoom');
			var at  = groups[name].indexOf(src);

			/* The same picture can have two triggers, for example the cover
			   thumbnail and the View larger link. They share one slide. */
			if (at === -1) {
				groups[name].push(src);
				at = groups[name].length - 1;
			}
			t.setAttribute('data-pph-index', String(at));

			if (!kinds[src]) {
				kinds[src] = t.getAttribute('data-pph-kind') || 'spread';
			}
		});

		/* ---------------------------------------------------- the viewer */

		var box = document.createElement('div');
		box.className = 'pph-lb';
		box.hidden = true;
		box.setAttribute('role', 'dialog');
		box.setAttribute('aria-modal', 'true');
		box.setAttribute('aria-label', 'Image viewer');
		box.innerHTML =
			  '<div class="pph-lb-top">'
			+ '<button type="button" class="pph-lb-x" aria-label="Close image viewer">'
			+ '<span aria-hidden="true">\u00d7</span><span class="pph-lb-x-t">Close</span>'
			+ '</button></div>'
			+ '<button type="button" class="pph-lb-nav pph-lb-prev" aria-label="Previous image">'
			+ '<span aria-hidden="true">\u2039</span></button>'
			+ '<button type="button" class="pph-lb-nav pph-lb-next" aria-label="Next image">'
			+ '<span aria-hidden="true">\u203a</span></button>'
			+ '<figure class="pph-lb-stage"><img alt="" /></figure>'
			+ '<div class="pph-lb-bar">'
			+ '<div class="pph-lb-count"></div>'
			+ '<div class="pph-lb-dots"></div>'
			+ '</div>';
		document.body.appendChild(box);

		var img    = box.querySelector('img');
		var stage  = box.querySelector('.pph-lb-stage');
		var prevEl = box.querySelector('.pph-lb-prev');
		var nextEl = box.querySelector('.pph-lb-next');
		var countEl= box.querySelector('.pph-lb-count');
		var dotsEl = box.querySelector('.pph-lb-dots');

		var list   = [];
		var at     = 0;
		var opener = null;

		function paint() {
			if (!list.length) { return; }

			if (at < 0)            { at = list.length - 1; }
			if (at > list.length - 1) { at = 0; }

			img.classList.remove('is-in');
			img.setAttribute('src', list[at]);

			/* 5.11.0: frame the picture like the real object. A front cover gets
			   a cover mock, a back cover gets the mirrored one, and inside pages
			   are shown as an open book with a centre fold. */
			var kind = kinds[list[at]] || 'spread';
			stage.className = 'pph-lb-stage is-' + kind;
			/* Let the browser paint the new file before the fade starts. */
			window.setTimeout(function () { img.classList.add('is-in'); }, 20);

			var many = list.length > 1;
			prevEl.hidden = !many;
			nextEl.hidden = !many;
			countEl.textContent = many ? (at + 1) + ' / ' + list.length : '';

			dotsEl.innerHTML = '';
			if (many) {
				for (var i = 0; i < list.length; i++) {
					var d = document.createElement('button');
					d.type = 'button';
					d.className = 'pph-lb-dot' + (i === at ? ' is-on' : '');
					d.setAttribute('aria-label', 'Image ' + (i + 1));
					d.setAttribute('data-go', String(i));
					dotsEl.appendChild(d);
				}
			}
		}

		function open(name, index, from) {
			list = groups[name] || [];
			if (!list.length) { return; }

			at     = index || 0;
			opener = from || null;

			box.hidden = false;
			document.body.classList.add('pph-lb-open');
			paint();

			/* The close button takes the focus, so the keyboard works at once. */
			var x = box.querySelector('.pph-lb-x');
			if (x && x.focus) { x.focus(); }
		}

		function close() {
			box.hidden = true;
			document.body.classList.remove('pph-lb-open');
			img.classList.remove('is-in');
			if (opener && opener.focus) { opener.focus(); }
			opener = null;
		}

		function step(by) {
			if (list.length < 2) { return; }
			at += by;
			paint();
		}

		/* ------------------------------------------------------- wiring */

		Array.prototype.forEach.call(triggers, function (t) {

			function go(e) {
				e.preventDefault();
				open(
					t.getAttribute('data-pph-group'),
					parseInt(t.getAttribute('data-pph-index'), 10) || 0,
					t
				);
			}

			t.addEventListener('click', go);

			/* The cover is a div, so it needs the keyboard wired by hand. */
			if (t.getAttribute('role') === 'button') {
				t.addEventListener('keydown', function (e) {
					if (e.key === 'Enter' || e.key === ' ') { go(e); }
				});
			}
		});

		prevEl.addEventListener('click', function (e) { e.stopPropagation(); step(-1); });
		nextEl.addEventListener('click', function (e) { e.stopPropagation(); step(1); });

		dotsEl.addEventListener('click', function (e) {
			var go = e.target && e.target.getAttribute('data-go');
			if (go === null || go === undefined) { return; }
			e.stopPropagation();
			at = parseInt(go, 10);
			paint();
		});

		/* 5.11.8: the cross is drawn inside a span, so a tap often landed on
		   that span and the old test, which only looked at the button class,
		   missed it. The button is now wired on its own, and the fallback walks
		   up from whatever was tapped. */
		var closeBtn = box.querySelector('.pph-lb-x');

		function hitClose(el) {
			while (el && el !== box) {
				if (el.classList && el.classList.contains('pph-lb-x')) { return true; }
				el = el.parentNode;
			}
			return false;
		}

		if (closeBtn) {
			closeBtn.addEventListener('click', function (e) {
				e.preventDefault();
				e.stopPropagation();
				close();
			});

			/* A touch closes at once, before the browser turns it into a click. */
			closeBtn.addEventListener('touchend', function (e) {
				e.preventDefault();
				e.stopPropagation();
				close();
			});
		}

		box.addEventListener('click', function (e) {
			if (hitClose(e.target)) {
				e.preventDefault();
				close();
				return;
			}

			if (e.target === box
				|| e.target.classList.contains('pph-lb-stage')
				|| e.target.classList.contains('pph-lb-top')) { close(); }
		});

		document.addEventListener('keydown', function (e) {
			if (box.hidden) { return; }
			if (e.key === 'Escape')     { close(); }
			if (e.key === 'ArrowLeft')  { step(-1); }
			if (e.key === 'ArrowRight') { step(1); }
		});

		/* --------------------------------------------- swipe on a phone */

		var x0 = 0, y0 = 0, moved = false;

		box.addEventListener('touchstart', function (e) {
			if (!e.touches || !e.touches.length) { return; }
			x0 = e.touches[0].clientX;
			y0 = e.touches[0].clientY;
			moved = false;
		}, { passive: true });

		box.addEventListener('touchmove', function () {
			moved = true;
		}, { passive: true });

		box.addEventListener('touchend', function (e) {
			if (!moved || !e.changedTouches || !e.changedTouches.length) { return; }

			var dx = e.changedTouches[0].clientX - x0;
			var dy = e.changedTouches[0].clientY - y0;

			/* A mostly sideways drag turns the page. A long drag down closes. */
			if (Math.abs(dx) > 45 && Math.abs(dx) > Math.abs(dy)) {
				step(dx < 0 ? 1 : -1);
			} else if (dy > 90 && Math.abs(dy) > Math.abs(dx)) {
				close();
			}
		}, { passive: true });
	}

	/* ----------------------------------------------------------------- go */
	ready(function () {
		setPlaceholder();
		initFormats();
		initTabs();
		initGallery();
		initLightbox();
	});
})();


/* ---------------------------------------------------------------------
   5.2  Retailer strip highlight

   Deliberately written as its own block so the format code above stays
   untouched. It watches the format cards for a class change and fades
   the retailers that do not belong to the current selection.
   --------------------------------------------------------------------- */
(function () {
	'use strict';

	function sync() {
		var strip = document.querySelector('.pph-rt-strip');
		if (!strip) { return; }

		var samplePicked = document.querySelector('.pph-free-sample-card.is-on');
		if (samplePicked) {
			/* Free Sample is not a purchase format: hide the retailer strip completely. */
			strip.style.display = 'none';
			return;
		}

		var picked = document.querySelector('.pph-fmt.is-on:not(.pph-free-sample-card)');
		var key = picked ? picked.getAttribute('data-key') : '';
		var btns = strip.querySelectorAll('.pph-rt');
		var shown = 0;
		var i;

		/* 5.11.3: the strip belongs to the selected format only. A retailer
		   that does not carry that edition is taken out of the row instead of
		   being faded, so no link is offered that does not apply. */
		for (i = 0; i < btns.length; i++) {
			var fmts = (btns[i].getAttribute('data-fmts') || '').split(' ');
			var hit = key ? fmts.indexOf(key) !== -1 : true;

			btns[i].classList.remove('is-dim');

			if (hit) {
				btns[i].style.display = '';
				shown++;
			} else {
				btns[i].style.display = 'none';
			}
		}

		/* An edition sold on this store only, such as the eBook, has no
		   retailer at all. The whole strip, with its heading and its note, is
		   taken off the page for that selection. */
		strip.style.display = shown ? '' : 'none';
	}

	function boot() {
		var wrap = document.querySelector('.pph-fmts');
		if (!wrap) { return; }

		sync();

		if (window.MutationObserver) {
			new window.MutationObserver(sync).observe(wrap, {
				subtree: true,
				attributes: true,
				attributeFilter: ['class']
			});
		} else {
			wrap.addEventListener('click', function () {
				window.setTimeout(sync, 0);
			});
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();


/* =========================================================================
   PPH 5.5  ->  Files filter + sort, FAQ accordion, sticky mobile buy bar

   This is a separate IIFE. It shares nothing with the older code, so if one
   part ever breaks the rest of the page keeps working.
   ========================================================================= */
(function () {
  'use strict';

  /* ---------------- Files Included: filter chips + sort ---------------- */

  function initResources() {
    var wrap = document.querySelector('.pph-res-wrap');
    if (!wrap) { return; }

    var chips = wrap.querySelectorAll('.pph-chip');
    var grid  = wrap.querySelector('.pph-res-grid');
    var sort  = wrap.querySelector('.pph-res-sort select');
    var empty = wrap.querySelector('.pph-res-empty');
    if (!grid) { return; }

    var cards = Array.prototype.slice.call(grid.querySelectorAll('.pph-res-card'));

    function applyFilter(group) {
      var shown = 0;

      cards.forEach(function (c) {
        var ok = (group === 'all') || (c.getAttribute('data-group') === group);
        if (ok) { c.removeAttribute('hidden'); shown++; }
        else { c.setAttribute('hidden', 'hidden'); }
      });

      if (empty) {
        if (shown === 0) { empty.removeAttribute('hidden'); }
        else { empty.setAttribute('hidden', 'hidden'); }
      }
    }

    Array.prototype.forEach.call(chips, function (chip) {
      chip.addEventListener('click', function () {
        Array.prototype.forEach.call(chips, function (c) { c.classList.remove('is-on'); });
        chip.classList.add('is-on');
        applyFilter(chip.getAttribute('data-group') || 'all');
      });
    });

    if (sort) {
      sort.addEventListener('change', function () {
        var mode = sort.value;

        var sorted = cards.slice().sort(function (a, b) {
          if (mode === 'name') {
            return (a.getAttribute('data-name') || '')
              .localeCompare(b.getAttribute('data-name') || '');
          }
          if (mode === 'type') {
            return (a.getAttribute('data-label') || '')
              .localeCompare(b.getAttribute('data-label') || '');
          }
          /* default = the order set in the admin */
          return (parseInt(a.getAttribute('data-order'), 10) || 0)
               - (parseInt(b.getAttribute('data-order'), 10) || 0);
        });

        sorted.forEach(function (c) { grid.appendChild(c); });
      });
    }
  }

  /* ------------------------- FAQ accordion ------------------------- */

  function initFaq() {
    var items = document.querySelectorAll('.pph-faq-item');
    if (!items.length) { return; }

    Array.prototype.forEach.call(items, function (item, i) {
      var btn = item.querySelector('.pph-faq-q');
      var ans = item.querySelector('.pph-faq-a');
      if (!btn || !ans) { return; }

      function setOpen(open) {
        if (open) {
          item.classList.add('is-open');
          ans.removeAttribute('hidden');
          btn.setAttribute('aria-expanded', 'true');
        } else {
          item.classList.remove('is-open');
          ans.setAttribute('hidden', 'hidden');
          btn.setAttribute('aria-expanded', 'false');
        }
        var sign = btn.querySelector('.pph-faq-sign');
        if (sign) { sign.innerHTML = open ? '&minus;' : '+'; }
      }

      /* The first question stays open, so the reader can see there is content. */
      setOpen(i === 0);

      btn.addEventListener('click', function () {
        setOpen(!item.classList.contains('is-open'));
      });
    });
  }

  /* --------------------- sticky mobile buy bar --------------------- */

  function initBuyBar() {
    var bar = document.querySelector('.pph-buybar');
    var hero = document.querySelector('.pph-pp-hero');
    if (!bar || !hero) { return; }

    function check() {
      var past = hero.getBoundingClientRect().bottom < 0;
      if (past) { bar.classList.add('is-in'); }
      else { bar.classList.remove('is-in'); }
    }

    window.addEventListener('scroll', check, { passive: true });
    window.addEventListener('resize', check);
    check();
  }

  function boot() {
    initResources();
    initFaq();
    initBuyBar();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
