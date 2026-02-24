
let currentIndex = 0;
const track = document.querySelector('.reviews-track');
const totalSlides = 15;
const slidesToShow = 3;

function moveSlide(direction) {
    const maxIndex = totalSlides - slidesToShow;
    currentIndex += direction;

    if (currentIndex < 0) {
        currentIndex = 0;
    }

    if (currentIndex > maxIndex) {
        currentIndex = maxIndex;
    }

    const slideWidth = document.querySelector('.review-card').offsetWidth;
    track.style.transform = `translateX(-${currentIndex * slideWidth}px)`;
}
