(function () {

	function init(root) {
		const track = root.querySelector('[data-wpbm-carousel-track]');
		const prev = root.querySelector('[data-wpbm-carousel-prev]');
		const next = root.querySelector('[data-wpbm-carousel-next]');

		if (!track || !prev || !next) return;

		const cards = Array.from(track.children);
		if (cards.length <= 1) return;

		// 🔥 CLONADO PARA LOOP INFINITO
		const cloneNodes = () => {
			const first = cards.slice(0, 2).map(n => n.cloneNode(true));
			const last = cards.slice(-2).map(n => n.cloneNode(true));

			first.forEach(n => track.appendChild(n));
			last.reverse().forEach(n => track.insertBefore(n, track.firstChild));
		};

		cloneNodes();

		let index = 2; // arrancamos después de clones
		let cardWidth = 0;

		const updateMetrics = () => {
			const card = track.querySelector('.wpbm-carousel__card');
			if (!card) return;

			const gap = parseFloat(getComputedStyle(track).gap || 0);
			cardWidth = card.offsetWidth + gap;
		};

		const jump = (i, smooth = true) => {
			track.style.scrollBehavior = smooth ? 'smooth' : 'auto';
			track.scrollLeft = i * cardWidth;
			index = i;
		};

		const move = (dir) => {
			index += dir;
			jump(index);

			// 🔥 LOOP LOGIC
			setTimeout(() => {
				const total = cards.length;

				// si pasamos el final real
				if (index >= total + 2) {
					index = 2;
					jump(index, false);
				}

				// si pasamos el inicio real
				if (index <= 1) {
					index = total + 1;
					jump(index, false);
				}
			}, 300);
		};

		prev.addEventListener('click', () => move(-1));
		next.addEventListener('click', () => move(1));

		// init
		updateMetrics();

		window.addEventListener('resize', updateMetrics);

		// start position (skip clones)
		setTimeout(() => jump(2, false), 50);
	}

	function boot() {
		document.querySelectorAll('[data-wpbm-carousel]').forEach(init);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}

})();