
const texts = document.querySelectorAll('.text-overlay h2');
let currentIndex = 0;

function cycleTexts() {
    texts[currentIndex].classList.remove('active');
    currentIndex = (currentIndex + 1) % texts.length; // Reinicia al primer texto después del último
    texts[currentIndex].classList.add('active');
}

setInterval(cycleTexts, 3000); // Cambia cada 3 segundos
