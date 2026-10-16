// Navigation: View Final Result button -> page8.html (or success page)
const submitBtn = document.getElementById('submit-btn');
if (submitBtn) {
  submitBtn.addEventListener('click', function() {
    // You can add logic here to check if user passed or failed
    // For now, redirect to page8.html (Try Again page)
    window.location.href = 'page8.php';
  });
}
