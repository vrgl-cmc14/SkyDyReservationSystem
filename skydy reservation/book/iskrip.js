const bgImages = [
    'url("../images/wp.jpg")',
    'url("../images/wp1.jpg")', 
    'url("../images/wp2.jpg")',
    'url("../images/wp3.jpg")', 
    'url("../images/wp4.jpg")', 
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