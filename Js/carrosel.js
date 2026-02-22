let index = 0;
const carousel = document.getElementById('carousel');
const cards = document.querySelectorAll('.card');
const totalCards = cards.length;

function getVisibleCards() {
  if (window.innerWidth <= 680) return 1;
  if (window.innerWidth <= 1024) return 2;
  return 4;
}

function applySlidePosition() {
  const visibleCards = getVisibleCards();
  const maxIndex = Math.max(0, totalCards - visibleCards);
  index = Math.min(index, maxIndex);
  carousel.style.transform = `translateX(${-index * (100 / visibleCards)}%)`;
}

function moveSlide(step) {
  const visibleCards = getVisibleCards();
  const maxIndex = totalCards - visibleCards;
  index = Math.min(Math.max(index + step, 0), maxIndex);
  carousel.style.transform = `translateX(${-index * (100 / visibleCards)}%)`;
}

window.addEventListener('resize', applySlidePosition);
window.addEventListener('load', applySlidePosition);
