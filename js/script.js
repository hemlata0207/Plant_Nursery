
document.addEventListener('DOMContentLoaded', function() {
    const wrapper = document.querySelector('.swiper-wrapper');
    const slides = document.querySelectorAll('.swiper-slide');
    const pagination = document.querySelectorAll('.swiper-pagination-bullet');
    const nextBtn = document.querySelector('.swiper-button-next');
    const prevBtn = document.querySelector('.swiper-button-prev');
    
    let currentIndex = 0;
    const slideWidth = 100;
    
    function updateSlider() {
        wrapper.style.transform = `translateX(-${currentIndex * slideWidth}%)`;
        
        pagination.forEach((bullet, index) => {
            if (index === currentIndex) {
                bullet.classList.add('active');
            } else {
                bullet.classList.remove('active');
            }
        });
    }
    
    nextBtn.addEventListener('click', function() {
        if (currentIndex < slides.length - 1) {
            currentIndex++;
            updateSlider();
        }
    });
    
    prevBtn.addEventListener('click', function() {
        if (currentIndex > 0) {
            currentIndex--;
            updateSlider();
        }
    });
    
    pagination.forEach((bullet, index) => {
        bullet.addEventListener('click', function() {
            currentIndex = index;
            updateSlider();
        });
    });
    
    // Auto-play functionality
    let interval = setInterval(() => {
        if (currentIndex < slides.length - 1) {
            currentIndex++;
        } else {
            currentIndex = 0;
        }
        updateSlider();
    }, 5000000000); // Change slide every 5 seconds
    
    // Pause auto-play when hovering over the slider
    const swiperContainer = document.querySelector('.swiper-container');
    swiperContainer.addEventListener('mouseenter', () => {
        clearInterval(interval);
    });
    
    swiperContainer.addEventListener('mouseleave', () => {
        interval = setInterval(() => {
            if (currentIndex < slides.length - 1) {
                currentIndex++;
            } else {
                currentIndex = 0;
            }
            updateSlider();
        }, 5000);
    });
});
