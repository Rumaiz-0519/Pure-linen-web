
document.addEventListener('DOMContentLoaded', function() {
    const optionBtns = document.querySelectorAll('.subscription-option-btn');
    
    optionBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            // Remove selected class from all buttons
            optionBtns.forEach(b => b.classList.remove('selected'));
            // Add selected class to clicked button
            this.classList.add('selected');
        });
    });
});


