document.addEventListener('DOMContentLoaded', () => {
    const emailFormContainer = document.getElementById('emailFormContainer');
    const emailInput = document.getElementById('emailInput');
    const errorMessage = document.getElementById('errorMessage');
    const btnCheckEmail = document.getElementById('btn');
    const captchaModal = document.getElementById('captchaModal');
    const closeModalBtn = document.getElementById('closeModal');
    const dragSlider = document.getElementById('dragSlider');
    const dragBox = document.getElementById('dragBox');
    const dragText = document.getElementById('dragText');
    const captchaSuccessMessage = document.getElementById('captchaSuccess');
    const captchaFailureMessage = document.getElementById('captchaFailure');
    const captchaBox = document.getElementById('captchaBox');
    const refreshCaptchaBtn = document.getElementById('refreshCaptcha');
    const puzzlePiece = document.getElementById('puzzlePiece');
    const puzzleCutout = document.getElementById('puzzleCutout');

    const registeredUsers = [
        'test@example.com',
        'user123@gmail.com',
        'admin@webmarket.com',
        '0912345678',
    ];

    let failureCount = 0;
    const maxAttempts = 2; // Maximum attempts before returning to the previous page

    // Listen for form submission (using a click listener on the submit button)
    btnCheckEmail.addEventListener('click', (e) => {
        e.preventDefault(); // Prevent the default form submission
        
        const userInput = emailInput.value.trim();
        if (registeredUsers.includes(userInput)) {
            errorMessage.style.display = 'none';
            emailInput.style.border = '1px solid #ddd';
            captchaModal.style.display = 'block';
            initializeCaptcha();
        } else {
            errorMessage.style.display = 'block';
            emailInput.style.border = '2px solid #ff0000';
        }
    });

    closeModalBtn.addEventListener('click', () => {
        captchaModal.style.display = 'none';
        failureCount = 0; // Reset counter
    });

    window.addEventListener('click', (e) => {
        if (e.target == captchaModal) {
            captchaModal.style.display = 'none';
            failureCount = 0; // Reset counter
        }
    });

    refreshCaptchaBtn.addEventListener('click', () => {
        initializeCaptcha();
    });

    let isDragging = false;
    let initialX = 0;
    let targetX = 0;
    const puzzleWidth = 50;
    const puzzleHeight = 50;
    
    // Placeholder images for the CAPTCHA
    const imageSources = [
        'image/captche1.jpg', // Sửa đường dẫn ở đây
           'image/captcha2.jpg', // Sửa đường dẫn ở đây
        'image/captcha3.jpg',
        'image/captcha4.jpg', // Sửa đường dẫn ở đây
        'image/captcha7.jpg',
        'image/captcha6.jpg',
        'image/captcha5.jpg',
    ];

    function initializeCaptcha() {
        isDragging = false;
        dragSlider.style.left = '0px';
        dragSlider.style.backgroundColor = '#f00';
        dragSlider.style.cursor = 'grab';
        dragText.style.opacity = 1;
        dragText.style.display = 'block';
        captchaSuccessMessage.style.display = 'none';
        captchaFailureMessage.style.display = 'none';
        
        const randomImageSrc = imageSources[Math.floor(Math.random() * imageSources.length)];
        
        captchaBox.style.backgroundImage = `url(${randomImageSrc})`;
        puzzlePiece.style.backgroundImage = `url(${randomImageSrc})`;
        
        const maxTargetX = captchaBox.offsetWidth - puzzleWidth;
        targetX = Math.floor(Math.random() * maxTargetX);
        
        puzzleCutout.style.left = `${targetX}px`;
        puzzlePiece.style.left = `0px`;
        puzzlePiece.style.backgroundPosition = `-${targetX}px 0px`;
        

        dragSlider.innerHTML = '<span>&#x27A4;</span>';
    }

    dragSlider.addEventListener('mousedown', (e) => {
        e.preventDefault();
        isDragging = true;
        initialX = e.clientX - dragSlider.offsetLeft;
        dragSlider.style.transition = 'none';
        captchaFailureMessage.style.display = 'none';
    });

    document.addEventListener('mousemove', (e) => {
        if (!isDragging) return;
        const newX = e.clientX - initialX;
        const offsetX = Math.min(Math.max(0, newX), dragBox.offsetWidth - dragSlider.offsetWidth);
        dragSlider.style.left = `${offsetX}px`;
        puzzlePiece.style.left = `${offsetX}px`;
        dragText.style.opacity = 1 - (offsetX / (dragBox.offsetWidth - dragSlider.offsetWidth));
    });

    document.addEventListener('mouseup', () => {
        if (!isDragging) return;
        isDragging = false;
        dragSlider.style.transition = 'left 0.3s ease-in-out';
        
        const currentX = dragSlider.offsetLeft;
        const tolerance = 5;
        if (Math.abs(currentX - targetX) < tolerance) {
            // Success
            dragSlider.style.backgroundColor = '#4CAF50';
            dragSlider.innerHTML = '<span>&#x2713;</span>';
            dragSlider.style.cursor = 'default';
            dragText.style.display = 'none';
            captchaSuccessMessage.style.display = 'block';
            failureCount = 0; // Reset counter
            
            setTimeout(() => {
                captchaModal.style.display = 'none';
            }, 1000);
        } else {
            // Failure
            dragSlider.style.left = '0px';
            puzzlePiece.style.left = '0px';
            dragText.style.opacity = 1;
            
            failureCount++;
            captchaFailureMessage.style.display = 'block';
            if (failureCount >= maxAttempts) {
                setTimeout(() => {
                    captchaModal.style.display = 'none';
                    failureCount = 0; // Reset counter
                }, 1500);
            } else {
                setTimeout(() => {
                    initializeCaptcha();
                }, 1500);
            }
        }
    });

    // Handle touch events for mobile
    dragSlider.addEventListener('touchstart', (e) => {
        isDragging = true;
        initialX = e.touches[0].clientX - dragSlider.offsetLeft;
        dragSlider.style.transition = 'none';
        captchaFailureMessage.style.display = 'none';
    });

    document.addEventListener('touchmove', (e) => {
        if (!isDragging) return;
        const newX = e.touches[0].clientX - initialX;
        const offsetX = Math.min(Math.max(0, newX), dragBox.offsetWidth - dragSlider.offsetWidth);
        dragSlider.style.left = `${offsetX}px`;
        puzzlePiece.style.left = `${offsetX}px`;
        dragText.style.opacity = 1 - (offsetX / (dragBox.offsetWidth - dragSlider.offsetWidth));
    });

    document.addEventListener('touchend', () => {
        if (!isDragging) return;
        isDragging = false;
        dragSlider.style.transition = 'left 0.3s ease-in-out';
        const currentX = dragSlider.offsetLeft;
        const tolerance = 5;
        if (Math.abs(currentX - targetX) < tolerance) {
            dragSlider.style.backgroundColor = '#4CAF50';
            dragSlider.innerHTML = '<span>&#x2713;</span>';
            dragSlider.style.cursor = 'default';
            dragText.style.display = 'none';
            captchaSuccessMessage.style.display = 'block';
            failureCount = 0; // Reset counter
            setTimeout(() => {
                captchaModal.style.display = 'none';
            }, 1000);
        } else {
            dragSlider.style.left = '0px';
            puzzlePiece.style.left = '0px';
            dragText.style.opacity = 1;
            
            failureCount++;
            captchaFailureMessage.style.display = 'block';
            if (failureCount >= maxAttempts) {
                setTimeout(() => {
                    captchaModal.style.display = 'none';
                    failureCount = 0; // Reset counter
                }, 1500);
            } else {
                setTimeout(() => {
                    initializeCaptcha();
                }, 1500);
            }
        }
    });

    // Initial captcha load
    initializeCaptcha();
});
