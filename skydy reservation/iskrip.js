/* ==========================================================================
   1. SCREEN LOADER LOGIC
   ========================================================================== */
const hideLoadingScreen = () => {
    const loadingScreen = document.getElementById('loading-screen');
    
    if (loadingScreen && !loadingScreen.classList.contains('fade-out-loader')) {
        loadingScreen.classList.add('fade-out-loader');
        
        setTimeout(() => {
            loadingScreen.style.display = 'none';
        }, 600);
    }
};

window.addEventListener('load', hideLoadingScreen);
setTimeout(hideLoadingScreen, 3500); // Fail-safe fallback timeout

/* ==========================================================================
   2. SIDEBAR NAVIGATION CONTROLLER
   ========================================================================== */


/* ==========================================================================
   3. BACKGROUND IMAGE TRANSITION SLIDER
   ========================================================================== */
const bgImages = [
    'url("images/wp.jpg")',
    'url("images/wp1.jpg")', 
    'url("images/wp2.jpg")',
    'url("images/wp3.jpg")', 
    'url("images/wp4.jpg")', 
];
    
const slider = document.getElementById('bg-slider');
if (slider) {
    let currentImageIndex = 0;
    slider.style.backgroundImage = bgImages[0];

    setInterval(() => {
        slider.style.opacity = 0;

        setTimeout(() => {
            currentImageIndex = (currentImageIndex + 1) % bgImages.length; 
            slider.style.backgroundImage = bgImages[currentImageIndex];
            slider.style.opacity = 1; 
        }, 1000); 
    }, 6000);
}

//=========MOBILE
const menuIcon   = document.querySelector('.menu-icon');
const sidebar    = document.querySelector('.sidebar');
const container  = document.querySelector('.container');


sidebar.classList.remove('mobile-open');
container.classList.remove('mobile-open');

menuIcon.addEventListener('click', () => {
    if (window.innerWidth <= 768) {
        sidebar.classList.toggle('mobile-open');
        container.classList.toggle('mobile-open'); 
    } else {
        sidebar.classList.toggle('collapsed');
    }
});

window.addEventListener('resize', () => {
    if (window.innerWidth > 768) {
        sidebar.classList.remove('mobile-open');
        container.classList.remove('mobile-open');
    }
});
