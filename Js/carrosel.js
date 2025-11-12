let index = 0;
const carousel = document.getElementById('carousel');
const cards = document.querySelectorAll('.card');
const totalCards = cards.length;
const visibleCards = 4; // mostra 4 ao mesmo tempo

function moveSlide(step) {
  const maxIndex = totalCards - visibleCards;
  index = Math.min(Math.max(index + step, 0), maxIndex);
  carousel.style.transform = `translateX(${-index * (100 / visibleCards)}%)`;
}
