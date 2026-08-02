import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.data('carousel', () => ({
  canScrollLeft: false,
  canScrollRight: true,
  init() {
    this.$nextTick(() => {
      this.updateArrows();
      this.$refs.track.addEventListener('scroll', () => this.updateArrows());
    });
  },
  updateArrows() {
    const el = this.$refs.track;
    this.canScrollLeft = el.scrollLeft > 4;
    this.canScrollRight = el.scrollLeft < el.scrollWidth - el.clientWidth - 4;
  },
  scroll(direction) {
    const card = this.$refs.track.querySelector('.carousel-card');
    if (!card) return;
    const gap = 24;
    const distance = (card.offsetWidth + gap) * (direction === 'next' ? 1 : -1);
    this.$refs.track.scrollBy({ left: distance, behavior: 'smooth' });
  },
}));

Alpine.start();
